<?php

/**
 * Various customer utilities for the Activecampaign_For_Woocommerce plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/customers
 */

use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;

/**
 * The Customer Utilities Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/customers
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Customer_Utilities {

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Order_Utilities constructor.
	 *
	 * @param     Logger|null $logger     The Logger.
	 */
	public function __construct(
		Logger $logger = null
	) {
		$this->logger = $logger ?: new Logger();
	}

	/**
	 * Add the customer info to the order object.
	 *
	 * @param     WC_Order   $order The WC order.
	 * @param     Ecom_Order $ecom_order The AC order.
	 * @param     bool       $is_admin Is the process called by admin (Session & Customer not available).
	 *
	 * @return Ecom_Order|null
	 */
	public function add_customer_to_order( WC_Order $order, Ecom_Order $ecom_order, $is_admin = false ) {
		try {
			if ( $order->get_user_id() ) {
				// Set if the AC id is set
				$ecom_order->set_id( User_Meta_Service::get_current_cart_ac_id( $order->get_user_id() ) );
				if ( get_user_meta( $order->get_user_id(), 'activecampaign_for_woocommerce_ac_customer_id' ) ) {
					// if it's an AC customer already stored in hosted
					$ac_customerid = get_user_meta( $order->get_user_id(), 'activecampaign_for_woocommerce_ac_customer_id' );
					$ecom_order->set_customerid( $ac_customerid );
				}
			} elseif ( $order->get_customer_id() ) {
				$ecom_order->set_id( User_Meta_Service::get_current_cart_ac_id( $order->get_customer_id() ) );
				if ( get_user_meta( $order->get_customer_id(), 'activecampaign_for_woocommerce_ac_customer_id' ) ) {
					$ac_customerid = get_user_meta( $order->get_customer_id(), 'activecampaign_for_woocommerce_ac_customer_id' );
					$ecom_order->set_customerid( $ac_customerid );
				}
			}

			if ( ! $is_admin && wc()->customer && wc()->customer->get_email() ) {
				// Set the email address from customer
				$ecom_order->set_email( wc()->customer->get_email() );
			} elseif ( get_user_meta( $order->get_user_id(), 'email' ) ) {
				$email = get_user_meta( $order->get_user_id(), 'email' );
				// Set the email address from user
				$ecom_order->set_email( $email );
			} elseif ( $order->get_billing_email() ) {
				// Set the email address from order
				$ecom_order->set_email( $order->get_billing_email() );
			}

			return $ecom_order;
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_Order_Utilities: There was an error adding customer to the order.',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $t->getTrace(),
				]
			);

			return null;
		}
	}

	/**
	 * Returns a customer ID if we can find one.
	 *
	 * @param     WC_Order|null $order The order object.
	 *
	 * @return bool|string
	 */
	public function get_customer_id( WC_Order $order = null ) {
		if ( is_null( $order ) && ! empty( wc()->session->get_customer_id() ) ) {
			return wc()->session->get_customer_id();
		}

		if ( ! is_null( $order ) && ! empty( $order->get_customer_id() ) ) {
			return $order->get_customer_id();
		}

		if ( isset( wc()->customer ) && ! empty( wc()->customer->get_id() ) ) {
			return wc()->customer->get_id();
		}

		if ( isset( wc()->session ) && ! empty( wc()->session->get_customer_id() ) ) {
			return wc()->session->get_customer_id();
		}

		$this->logger->error(
			'Customer Utilities: Could not find a customer ID.',
			[
				'id'            => $order->get_id(),
				'order_number'  => $order->get_order_number(),
				'billing_email' => $order->get_billing_email(),
			]
		);

		return false;
	}

	/**
	 * Updates the last synced date on a record.
	 *
	 * @param int $customer_id The customer ID from the order.
	 *
	 * @throws Exception Does not stop.
	 */
	public function update_last_synced( $customer_id ) {
		$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		try {
			update_post_meta( $customer_id, 'activecampaign_for_woocommerce_last_synced', $date->format( 'Y-m-d H:i:s e' ) );
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not create datetime or save sync metadata to the customer',
				[
					'message'  => $t->getMessage(),
					'date'     => $date->format( 'Y-m-d H:i:s e' ),
					'order_id' => $customer_id,
					'trace'    => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Stores the ActiveCampaign ID for the customer record
	 *
	 * @param string $customer_id The WC customer ID.
	 * @param string $ac_id The Hosted record ID.
	 */
	public function store_ac_id( $customer_id, $ac_id ) {
		add_post_meta( $customer_id, 'activecampaign_for_woocommerce_hosted_id', $ac_id, true );
	}
}
