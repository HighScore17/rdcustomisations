<?php

/**
 * The file that defines the Executable Command Interface.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Customer as Ecom_Customer;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order_Factory as Ecom_Order_Factory;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command as Abandoned_Cart;
use AcVendor\GuzzleHttp\Exception\GuzzleException;
use AcVendor\Psr\Log\LoggerInterface;


/**
 * Send the cart and its products to ActiveCampaign for the given customer.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Update_Cart_Command implements Activecampaign_For_Woocommerce_Executable_Interface {

	/**
	 * The WC Cart
	 *
	 * @var WC_Cart
	 */
	private $cart;
	/**
	 * The WC Customer
	 *
	 * @var WC_Customer
	 */
	private $customer;
	/**
	 * The Ecom Order Factory
	 *
	 * @var Ecom_Order_Factory
	 */
	private $factory;
	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	private $order_repository;
	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	private $customer_repository;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The logger interface.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * The resulting existing or newly created AC ecom customer
	 *
	 * @var Ecom_Model
	 */
	private $customer_ac;

	/**
	 * The connection id stored in admin
	 *
	 * @var string
	 */
	private $connection_id;

	/**
	 * Activecampaign_For_Woocommerce_Update_Cart_Command constructor.
	 *
	 * @param     WC_Cart|null                              $cart     The WC Cart.
	 * @param     WC_Customer|null                          $customer     The WC Customer.
	 * @param     Activecampaign_For_Woocommerce_Admin|null $admin     The admin object.
	 * @param     Ecom_Order_Factory                        $factory     The Ecom Order Factory.
	 * @param     Ecom_Order_Repository                     $order_repository     The Ecom Order Repo.
	 * @param     Ecom_Customer_Repository|null             $customer_repository     The Ecom Customer Repo.
	 * @param     LoggerInterface                           $logger     The logger interface.
	 */
	public function __construct(
		WC_Cart $cart = null,
		WC_Customer $customer = null,
		Admin $admin,
		Ecom_Order_Factory $factory,
		Ecom_Order_Repository $order_repository,
		Ecom_Customer_Repository $customer_repository,
		LoggerInterface $logger = null
	) {
		$this->cart                = $cart;
		$this->customer            = $customer;
		$this->factory             = $factory;
		$this->order_repository    = $order_repository;
		$this->customer_repository = $customer_repository;
		$this->admin               = $admin;
		$this->logger              = $logger;
	}

	/**
	 * Initialize injections that are still null
	 */
	public function init() {
		// calling wc in the constructor causes an exception, since the object is not ready yet
		$this->cart     = $this->cart ?: wc()->cart;
		$this->customer = $this->customer ?: wc()->customer;
	}
	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * {@inheritDoc}
	 *
	 * @param     mixed ...$args     The array of parameters passed.
	 *
	 * @return bool
	 */
	public function execute( ...$args ) {
		$this->init();

		// If the customer is not logged in, there is nothing to do
		if ( ! ( $this->customer instanceof WC_Customer ) || $this->customer->get_email() === null ) {
			$this->logger->debug(
				'Update Cart Command: Customer not logged in. Do nothing.',
				[
					'cart' => $this->cart,
				]
			);

			return false;
		}

		// First, make sure we have the ID for the ActiveCampaign customer record
		try {
			if ( ! $this->verify_ac_customer_id( $this->customer->get_id() ) ) {
				$this->logger->warning(
					'Update Cart Command: Verify AC customer - Missing id for ActiveCampaign customer record.',
					[
						'customer_email' => $this->customer->get_email(),
						'customer_id'    => $this->customer->get_id(),
					]
				);
			}

			$this->create_customer();
		} catch ( Throwable $t ) {
			$this->logger->warning( 'Update Cart Command: There was an issue creating a customer or reading order.' );
		}

		// If we already have an AC ID, then this is an update. Otherwise, it's a create.
		try {
			$abandoned_cart = new Abandoned_Cart();
			$abandoned_cart->init();
		} catch ( Throwable $t ) {
			/**
			 * We have seen issues for a few users of this plugin where either the create or update call throws
			 * an exception, which ends up breaking their store. This try/catch is a stop-gap measure for now.
			 */

			$this->logger->error(
				'Update Cart: Could not process abandoned cart.',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			return false;
		}

		return true;
	}

	// phpcs:enable

	/**
	 * Try and find the AC customer ID in the local DB. If not found, create the customer
	 * in AC and save the ID the DB.
	 *
	 * @param     int $user_id     The WordPress User ID.
	 *
	 * @return bool
	 */
	private function verify_ac_customer_id( $user_id ) {
		// Nothing to do if we already have the AC customer ID.
		if ( ! empty( User_Meta_Service::get_current_user_ac_customer_id( $user_id ) ) ) {
			return true;
		}

		if ( ! $this->customer->get_email() ) {
			$this->logger->debug( 'Update Cart Command: Customer verification exception - No customer email found or no AC user ID set.', [ 'customer_email' => $this->customer->get_email() ] );

			return false;
		}

		try {
			/**
			 * The customer object from hosted
			 *
			 * @var Activecampaign_For_Woocommerce_Ecom_Customer $customer_from_hosted
			 */
			$ecom_customer = $this->customer_repository->find_by_email_and_connection_id(
				$this->customer->get_email(),
				$this->get_connection_id()
			);
		} catch ( GuzzleException $e ) {
			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );
			$this->logger->error( $message, [ 'stack trace' => $stack_trace ] );

			return false;
		} catch ( \Exception $e ) {
			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );
			$this->logger->error( $message, [ 'stack trace' => $stack_trace ] );

			return false;
		} catch ( Throwable $t ) {
			$message     = $t->getMessage();
			$stack_trace = $this->logger->clean_trace( $t->getTrace() );
			$this->logger->error( $message, [ 'stack trace' => $stack_trace ] );

			return false;
		}

		if ( $ecom_customer ) {
			User_Meta_Service::set_current_user_ac_customer_id( $user_id, $ecom_customer->get_id() );

			return true;
		}

		return false;
	}

	/**
	 * Creates a new ecomCustomer in Hosted
	 *
	 * @return bool whether or not a customer was created
	 */
	private function create_customer() {
		if ( isset( $this->customer ) && $this->customer->get_email() && $this->customer->get_id() && $this->get_connection_id() ) {
			$this->logger->debug( 'Update Cart: Trying to create a new customer.', [ 'customer' => $this->customer ] );
			$new_customer = new Ecom_Customer();
			$new_customer->set_email( $this->customer->get_email() );
			$new_customer->set_externalid( $this->customer->get_id() );
			$new_customer->set_connectionid( $this->get_connection_id() );
			$new_customer->set_first_name( $this->customer->get_first_name() );
			$new_customer->set_last_name( $this->customer->get_last_name() );

			try {
				$this->set_customer_ac( $this->customer_repository->create( $new_customer ) );
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Update Cart: Customer creation exception',
					[ 'message' => $t->getMessage() ]
				);

				return false;
			}

			User_Meta_Service::set_current_user_ac_customer_id( $this->customer->get_id(), $this->customer_ac->get_id() );

			return true;
		} else {
			$this->logger->debug( 'Update cart: Customer creation exception - No customer email found.' );

			return false;
		}
	}

	/**
	 * Returns the connection id
	 *
	 * @return string
	 */
	private function get_connection_id() {
		if ( ! $this->connection_id ) {
			$this->connection_id = $this->admin->get_storage()['connection_id'];
		}

		return $this->connection_id;
	}

	/**
	 * Returns the WC customer
	 *
	 * @return WC_Customer
	 */
	public function get_customer() {
		return $this->customer;
	}

	/**
	 * Sets the WC customer
	 *
	 * @param     WC_Customer $wc_customer     the WooCommerce customer.
	 */
	public function set_customer( $wc_customer ) {
		$this->customer = $wc_customer;
	}

	/**
	 * Returns the customer repository
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	public function get_customer_repository() {
		return $this->customer_repository;
	}

	/**
	 * Sets the customer repository
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer_Repository $repository     the Ecom_Customer_Repository.
	 */
	public function set_customer_repository( $repository ) {
		$this->customer_repository = $repository;
	}

	/**
	 * Returns the order repository
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	public function get_order_repository() {
		return $this->order_repository;
	}

	/**
	 * Sets the order repository
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Repository $repository     the Ecom_Order_Repository.
	 */
	public function set_order_repository( $repository ) {
		$this->order_repository = $repository;
	}

	/**
	 * Returns the Ecom_Customer
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Customer
	 */
	public function get_customer_ac() {
		return $this->customer_ac;
	}

	/**
	 * Sets the Ecom_Customer
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer $customer_ac     the Ecom_Customer.
	 */
	public function set_customer_ac( $customer_ac ) {
		$this->customer_ac = $customer_ac;
	}

	/**
	 * Returns the Ecom_Order_Factory
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Order_Factory
	 */
	public function get_order_factory() {
		return $this->factory;
	}

	/**
	 * Sets the Ecom_Order_Factory
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Factory $factory     the Ecom_Order_Factory.
	 */
	public function set_order_factory( $factory ) {
		$this->factory = $factory;
	}

	/**
	 * Returns the WC_Cart
	 *
	 * @return WC_Cart
	 */
	public function get_cart() {
		return $this->cart;
	}

	/**
	 * Sets the WC_Cart
	 *
	 * @param     WC_Cart $cart     the WooCommerce Cart.
	 */
	public function set_cart( $cart ) {
		$this->cart = $cart;
	}
}
