<?php

class WC_Horizon_Recurring_Orders_Process_Orders_Cron_Job {

  const MAX_PAYMENT_ATTEMPS = 30;
  public static $payment_attepms = 0;

  public static function process_orders() {
    self::setup_enviroment();
    add_action('wc_horizon_recurring_order_payment_failed', 'WC_Horizon_Recurring_Orders_Process_Orders_Cron_Job::add_failed_payment_attemp');

    foreach_paginated_posts( function( $post_id ) {
      $order = wc_get_order( $post_id );

      if( !__is_valid_wc_order( $order ) ) {
        return;
      }

      $recurring_order = self::get_recurring_order( $order );
    
      if( !is_a( $recurring_order, 'WC_Horizon_Recurrring_Order' ) ) {
        return;
      }

      $created = $order->get_date_created();
      $now = new DateTime(  );

      if( !$created || intval( $created->diff($now)->format("%r%a") ) < 1 ) {
        return;
      }

      do_action( 'wc_horizon_recurring_order_before_process_order', $recurring_order, $order );

      $processed = self::process_order( $recurring_order, $order );

      if( !$processed ) {
        return;
      }

      $order->set_status( 'wc-processing' );
      $order->save();

      do_action( "wc_horizon_recurring_order_processed_successfully", $recurring_order, $order );
    }, 'shop_order', array( 'wc-subs-48hrs' ) );
    
    remove_action('wc_horizon_recurring_order_payment_failed', 'WC_Horizon_Recurring_Orders_Process_Orders_Cron_Job::add_failed_payment_attemp');
    
    self::$payment_attepms = 0;
    return array("data" => null);
  }


  /**
   * Return the recurring order associated to this order
   */
  static function get_recurring_order( WC_Order $order ) {
    return wc_horizon_get_recurring_order( $order->get_meta( 'subscription_id' ) );
  }

  /**
   * Setup the enviroment variables before process the orders
   */
  protected static function setup_enviroment() {
    // Prevent checkout rate limit ban
    if( !defined( 'WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS' ) ) {
      define( 'WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS', "yes" );
    }

    // Avoid problems with payment methods that use frontend functions like wc_add_notice
    WC()->initialize_session();
    WC()->initialize_cart();
    WC()->frontend_includes();
  }

  /**
   * Process the current recurring order to create it and make a payment
   * @param WC_Horizon_Recurrring_Order $recurring_order the recurring order
   * @return WC_Order|Void The current order if it could be created
   */
  protected static function process_order( $recurring_order, WC_Order $order ) {
    $allowed_payments = [ "credit-card" ];
    $payment = $recurring_order->get_payment();

    if( floatval( $order->get_total() ) == 0 ) {
      return do_action( "wc_horizon_recurring_order_failed", $recurring_order, "The total wasn't calculated correctlly" );
    }

    if( 
      !is_array( $payment ) || 
      !$payment["id"] || 
      !$payment["type"] || 
      !in_array( $payment["type"], $allowed_payments ) 
    ) {
      return do_action( "wc_horizon_recurring_order_failed", $recurring_order, "The payment method wasn't setup correctlly" );
    }

    $credit_card = wc_horizon_get_credit_card( $payment["id"], $recurring_order->get_owner() );

    if( !$credit_card ) {
      return do_action( "wc_horizon_recurring_order_failed", $recurring_order, "The owner of the payment method don't match with the owner of the subscription or the payment method was deleted by the owner" );
    }


    if( !self::process_payment( $order, $recurring_order, $credit_card ) === true ) {
      return;
    }

    return true;    
  }

  /**
   * Setup all payment variables needed to process a payment and run payment failed hooks
   * @param WC_Order $order The order to process payment
   * @param WC_Horizon_Recurrring_Order The recurring order
   * @param WC_Horizon_Credit_Card The credit card
   * @return bool|void true if success, void if failed 
   */
  protected static function process_payment( $order, $recurring_order, $credit_card ) {
    $_POST["payment_id"] = $credit_card->get_id();
    $_POST["payment_user_id"] = $credit_card->get_owner();
    $_POST["card_token"] = "";

    $payment_result = self::process_order_payment( $order->get_id(), "usio" );

    if( !$payment_result ) {
      return do_action( "wc_horizon_recurring_order_payment_failed", $recurring_order, $order, __( "Payment method ins't available", 'wc-subscriptions' ) );
    }

    if( !is_array( $payment_result ) ) {
      return do_action( "wc_horizon_recurring_order_payment_failed", $recurring_order, $order, __( "payment could not be completed", 'wc-subscriptions' ) );
    }

    if( $payment_result["result"] !== "success" ) {
      return do_action( "wc_horizon_recurring_order_payment_failed", $recurring_order, $order, __( wc_notices_to_string( wc_get_notices( "error" ) ), 'wc-subscriptions' ) );
    }

    return true;
  }

  /**
   * Process the order payment from a given payment method
   * @param int $order_id The order ID
   * @param string $payment_method The Payment method to use
   * @return mixed Any result that return the payment gateway selected
   */
  protected static function process_order_payment( $order_id, $payment_method ) {
		$available_gateways = WC()->payment_gateways->get_available_payment_gateways();

    if ( ! isset( $available_gateways[ $payment_method ] ) ) {
			return;
		}

		$process_payment_args = apply_filters(
			"{$payment_method}_process_payment_args",
			array( $order_id ),
			$payment_method
		);

		// Process Payment.
		return $available_gateways[ $payment_method ]->process_payment( ...$process_payment_args );
	}


  /**
   * Add a failed payment attemp and cancel the current event if there are many failed payment attemps
   */
  public static function add_failed_payment_attemp() {
    if( ++self::$payment_attepms >= self::MAX_PAYMENT_ATTEMPS ) {
      slack_post_message( ":alarm_clock: The subscriptions event has been cancelled automatically because of many failed payment attemps", __slack_channel("subscriptions") );
      wp_die();
    }
  }
}

add_action( 'woocommerce_horizon_run_subscriptions_payments', 'WC_Horizon_Recurring_Orders_Process_Orders_Cron_Job::process_orders' );
add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', function( $statuses, $order) {
  $statuses[] = 'subs-48hrs';
  return $statuses;
}, 10, 2);