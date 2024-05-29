<?php

class WC_Horizon_Recurring_Orders_Cron_Job {

  const MAX_PAYMENT_ATTEMPS = 30;
  public static $payment_attepms = 0;

  public static function make_orders() {
    self::setup_enviroment();
    add_action('wc_horizon_recurring_order_payment_failed', 'WC_Horizon_Recurring_Orders_Cron_Job::add_failed_payment_attemp');
    foreach_paginated_posts( function( $post_id )  {
      $recurring_order = wc_horizon_get_recurring_order( $post_id );
    
      if( !is_a( $recurring_order, 'WC_Horizon_Recurrring_Order' ) ) {
        return;
      }

      if( !$recurring_order->is_active() ) {
        return;
      }

      if( !self::is_today_the_order_date( $recurring_order ) ) {
        return;
      }

      $order = self::process_recurring_order( $recurring_order );

      if( !is_a( $order, 'WC_Order' ) ) {
        return;
      }

      $order->payment_complete();
      $order->save();
      self::update_recurring_order_status( $recurring_order, $order );
      
    }, 'recurring_orders', 5 );
    remove_action('wc_horizon_recurring_order_payment_failed', 'WC_Horizon_Recurring_Orders_Cron_Job::add_failed_payment_attemp');
    self::$payment_attepms = 0;
    return array("data" => null);
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
   * Check if the recurring order has to be placed today and if today isn't the day the a notification hook will be fired
   * @param WC_Horizon_Recurrring_Order The recurring order
   * @return bool if today is the day
   */
  protected static function is_today_the_order_date( $recurring_order ) {
    $upcoming_date = $recurring_order->get_upcoming_delivery();
    
    if( !$upcoming_date  ) {
      return false;
    }

    $now = new DateTime(  );
    $upcoming = new DateTime( $upcoming_date );
    $days = intval( $now->diff($upcoming)->format("%r%a") ) ;

    if( $days > 0 ) {
      do_action( 'wc_horizon_recurring_order_notification_for_day_' . $days, $recurring_order );
      return false;
    }

    return true;
  }

  /**
   * Update the recurring orders fileds after process the order
   * @param WC_Horizon_Recurring_Order $recurring_order The recurring order
   * @param WC_Order $order the order created
   */
  protected static function update_recurring_order_status( WC_Horizon_Recurrring_Order $recurring_order, $order ) {
    $recurring_order->set_last_order( $order );
    $recurring_order->save();
  }

  /**
   * Process the current recurring order to create it and make a payment
   * @param WC_Horizon_Recurrring_Order $recurring_order the recurring order
   * @return WC_Order|Void The current order if it could be created
   */
  protected static function process_recurring_order( $recurring_order ) {
    $allowed_payments = [ "credit-card" ];
    $payment = $recurring_order->get_payment();

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

    $order = self::create_order( $recurring_order, $credit_card );

    if( is_wp_error( $order ) ) {
      return do_action( "wc_horizon_recurring_order_failed", $recurring_order, $order->get_error_message() );
    }

    do_action('wc_horizon_recurring_order_placed', $order);

    if( self::process_payment( $order, $recurring_order, $credit_card ) === true ) {
      do_action( "wc_horizon_recurring_order_processed_successfully", $recurring_order, $order );
    }

    return $order;    
  }

  /**
   * Create a order object and save in from a recurring order 
   * @param WC_Horizon_Recurrring_Order $recurring_order The recurring order
   * @param WC_Horizon_Credit_Card $credit_card the payment linked to the recurring order
   * @return WC_Order|WP_Error Order created or a error
   */
  protected static function create_order( WC_Horizon_Recurrring_Order $recurring_order, $credit_card ) {
    $order = wc_create_order( array(
      'customer_id' => $recurring_order->get_owner(),
      'created_via' => 'subscription',
    ) );

    if( is_wp_error( $order ) ) {
      return $order;
    }
    
    self::set_shipping_address( $order, $recurring_order->get_address("shipping") );
    self::set_billing_address( $order, $credit_card->get_billing() );
    self::set_lineitems( $recurring_order, $order );

    $shipping = self::set_shipping_method( $recurring_order, $order );

    if( is_wp_error( $shipping ) ) {
      return $shipping;
    }

    $order->update_meta_data( "subscription_id", $recurring_order->get_id() );
    $order->calculate_totals();
    $order->save();
    return $order;
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
   * Add lineitems to the order
   * @param WC_Horizon_Recurrring_Order $recurring_order The recurring order
   * @param WC_Order $order The order
   */
  protected static function set_lineitems( $recurring_order, &$order ) {
    foreach( $recurring_order->get_lineitems() as $item ) {
      $product = wc_get_product( $item["variation_id"] ?? $item["product_id"] );
      $order_item_id = $order->add_product( $product, $item["quantity"], array(
        "subtotal" => $item["subtotal"],
        "total" => $item["total"]
      ) );
      foreach( $item["metaData"] as $meta ) {
        if( !is_a( $meta, 'WC_Meta_Data' ) ) {
          continue;
        }
        $data = $meta->get_data();
        if(!wc_get_order_item_meta( $order_item_id, $data["key"])) {
          wc_add_order_item_meta( $order_item_id, $data["key"], $data["value"] );
        }
      }
    }
  }

  /**
   * Add the shipping address
   * @param WC_Order $order The order
   * @param array<string> The address array
   */
  protected static function set_shipping_address(  WC_Order &$order, $address ) {
    $order->set_shipping_address_1( $address["address_1"] );
    $order->set_shipping_address_2( $address["address_2"] );
    $order->set_shipping_city( $address["city"] );
    $order->set_shipping_company( $address["company"] );
    $order->set_shipping_country( $address["country"] );
    $order->set_shipping_first_name( $address["first_name"] );
    $order->set_shipping_last_name( $address["last_name"] );
    $order->set_shipping_phone( $address["phone"] );
    $order->set_shipping_postcode( $address["postcode"] );
    $order->set_shipping_state( $address["state"] );
  }

  /**
   * Add the billing address
   * @param WC_Order $order The order
   * @param array<string> The address array
   */
  protected static function set_billing_address( WC_Order &$order, $address ) {
    $order->set_billing_address_1( $address["address1"] );
    $order->set_billing_address_2( $address["address2"] );
    $order->set_billing_city( $address["city"] );
    $order->set_billing_company( $address["company"] );
    $order->set_billing_country( $address["country"] );
    $order->set_billing_first_name( $address["firstname"] );
    $order->set_billing_last_name( $address["lastname"] );
    $order->set_billing_phone( $address["phone"] );
    $order->set_billing_postcode( $address["postcode"] );
    $order->set_billing_state( $address["state"] );
    $order->set_billing_email( $address["email"] );
  }

  protected static function set_shipping_method( WC_Horizon_Recurrring_Order $recurring_order, WC_Order &$order ) {

    $shipping_rate = $recurring_order->get_precalculated_shipping_cost();

    if( !$shipping_rate || !is_array( $shipping_rate ) || __empty_some_key( $shipping_rate, ["total", "service"] ) ) {
      $shipping_rate = wc_horizon_recurring_order_get_shipping_cost( $recurring_order );
    }

    $recurring_order->set_precalculated_shipping_cost(null);
    $recurring_order->save();
    

    if( is_wp_error( $shipping_rate ) ) {
      return $shipping_rate;
    }

    $service = $shipping_rate["service"];
    $shipping_item = new WC_Order_Item_Shipping();
    $shipping_item->set_method_title( $service["description"] );
    $shipping_item->set_method_id( $service["code"] );
    $shipping_item->set_total( $shipping_rate["total"] );
    $order->add_item( $shipping_item );

    return true;
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

add_action( 'woocommerce_horizon_run_subscriptions', 'WC_Horizon_Recurring_Orders_Cron_Job::make_orders' );