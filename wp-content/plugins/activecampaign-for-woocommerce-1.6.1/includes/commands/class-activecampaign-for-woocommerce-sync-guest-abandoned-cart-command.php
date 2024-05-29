<?php

/**
 * The file that defines the Executable Command Interface.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.1.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command as Abandoned_Cart;
use Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities as Abandoned_Cart_Utilities;

/**
 * Handles sending the guest customer and pending order to AC.
 * When the email input field on the checkout page is changed,
 * an Ajax request will run the execute method.
 *
 * @since      1.1.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command implements Activecampaign_For_Woocommerce_Executable_Interface {

	/**
	 * The WC Cart
	 *
	 * @var WC_Cart
	 */
	public $cart;

	/**
	 * The WC Customer
	 *
	 * @var WC_Customer
	 */
	public $customer;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	public $customer_repository;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The guest customer email address
	 *
	 * @var string
	 */
	private $customer_email;

	/**
	 * The guest customer first name
	 *
	 * @var string
	 */
	private $customer_first_name;

	/**
	 * The guest customer last name
	 *
	 * @var string
	 */
	private $customer_last_name;

	/**
	 * The WooCommerce customer object
	 *
	 * @var WC_Customer
	 */
	private $customer_woo;

	/**
	 * Hash of the WooCommerce session ID plus the guest customer email.
	 * Used to identify an order as being created in a pending state.
	 *
	 * @var string
	 */
	private $external_checkout_id;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The WooCommerce session
	 *
	 * @var WC_Session|null
	 */
	private $wc_session;

	/**
	 * Activecampaign_For_Woocommerce_Sync_Guest_Abandoned_Cart_Command constructor.
	 *
	 * @param     WC_Cart|null                              $cart     The WC Cart.
	 * @param     WC_Customer|null                          $customer     The WC Customer.
	 * @param     WC_Session|null                           $wc_session     The WC Session.
	 * @param     Activecampaign_For_Woocommerce_Admin|null $admin     The admin object.
	 * @param     Ecom_Customer_Repository|null             $customer_repository     The Ecom Customer Repo.
	 * @param     Logger                                    $logger     The ActiveCampaign WooCommerce logger.
	 */
	public function __construct(
		WC_Cart $cart = null,
		WC_Customer $customer = null,
		WC_Session $wc_session = null,
		Admin $admin,
		Ecom_Customer_Repository $customer_repository,
		Logger $logger = null
	) {
		$this->cart                = $cart;
		$this->customer            = $customer;
		$this->wc_session          = $wc_session;
		$this->admin               = $admin;
		$this->customer_repository = $customer_repository;
		$this->logger              = $logger;
	}

	/**
	 * Initialize injections that are still null
	 */
	public function init() {
		$this->cart       = $this->cart ?: wc()->cart;
		$this->customer   = $this->customer ?: wc()->customer;
		$this->wc_session = $this->wc_session ?: wc()->session;
		$this->logger     = $this->logger ?: new Logger();
	}

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter

	/**
	 * Execute this command.
	 *
	 * @param     mixed ...$args     The array of parameters passed.
	 *
	 * @return boolean Whether or not the command was successful
	 */
	public function execute( ...$args ) {
		$this->init();

		if (
			! $this->validate_request() ||
			! $this->setup_woocommerce_customer() ||
			! $this->setup_woocommerce_cart()
		) {
			return false;
		}

		try {
			$abandoned_cart = new Abandoned_Cart();
			$abandoned_cart->init_data(
				[
					'customer_email'      => $this->customer_email,
					'customer_first_name' => $this->customer_first_name,
					'customer_last_name'  => $this->customer_last_name,
				]
			);
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Sync Guest Abandoned Cart: Some POST information was missing from the AJAX call.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		return true;
	}
	// phpcs:enable

	/**
	 * Validate that the request has all necessary data
	 *
	 * @return bool Whether or not this job was successful
	 */
	private function validate_request() {
		if ( is_user_logged_in() ) {
			$this->logger->debug( 'Abandon cart guest sync: User is logged in, cannot perform guest sync' );

			return false;
		}

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'sync_guest_abandoned_cart_nonce' ) ) {
			$this->logger->debug( 'Abandon cart guest sync: sync_guest_abandoned_cart_nonce failed' );

			return false;
		}

		$this->customer_email = sanitize_email( $_REQUEST['email'] );

		if ( ! $this->customer_email ) {
			$this->logger->debug( 'Abandon cart guest sync: invalid customer email' );

			return false;
		}

		if ( isset( $_REQUEST['first_name'] ) ) {
			$this->customer_first_name = $_REQUEST['first_name'];
		} else {
			$this->customer_first_name = '';
		}

		if ( isset( $_REQUEST['last_name'] ) ) {
			$this->customer_last_name = $_REQUEST['last_name'];
		} else {
			$this->customer_last_name = '';
		}

		return true;
	}

	/**
	 * Set up the WooCommerce customer object
	 * with the customer's email address
	 *
	 * @return bool Whether or not this job was successful
	 */
	private function setup_woocommerce_customer() {
		// Obtain WooCommerce customer model
		$this->customer_woo = $this->customer;

		if ( ! ( $this->customer_woo instanceof WC_Customer ) ) {
			$this->logger->debug( 'Abandon cart guest sync: customer_woo not an instance of WC_Customer' );

			return false;
		}

		$this->customer_woo->set_email( $this->customer_email );

		return true;
	}

	/**
	 * Set up the WooCommerce cart object with the checkout ID
	 *
	 * @return bool Whether or not this job was successful
	 */
	private function setup_woocommerce_cart() {
		if ( ! ( $this->cart instanceof WC_Cart ) ) {
			$this->logger->debug( 'Abandon cart guest sync: cart not an instance of WC_Cart' );

			return false;
		}

		$ab                         = new Abandoned_Cart_Utilities();
		$this->external_checkout_id = $ab->generate_externalcheckoutid( $this->wc_session->get_customer_id(), $this->customer_email );

		return true;
	}

	/**
	 * Set the logger (for testing)
	 *
	 * @param     Activecampaign_For_Woocommerce_Logger $logger     The logger.
	 */
	public function setLogger( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Set the session (for testing)
	 *
	 * @param     WC_Session|null $wc_session     The session.
	 */
	public function setWcSession( $wc_session ) {
		$this->wc_session = $wc_session;
	}
}
