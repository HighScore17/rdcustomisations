<?php

class WC_Horizon_Recurring_Orders_Creation_Cron_Job {

  public static function create_orders() {
    self::setup_enviroment();
    foreach_paginated_posts( function( $post_id )  {
      $recurring_order = wc_horizon_get_recurring_order( $post_id );
    
      if( !is_a( $recurring_order, 'WC_Horizon_Recurrring_Order' ) ) {
        return;
      }

      if( !$recurring_order->is_active() ) {
        return;
      }

      if( !self::is_today_the_order_creation_date( $recurring_order ) ) {
        return;
      }

      do_action( 'wc_horizon_recurring_order_before_create_order', $recurring_order );

      $order = self::process_recurring_order( $recurring_order );

      if( !__is_valid_wc_order( $order ) ) {
        return;
      }

      $order->save();
      self::update_recurring_order_status( $recurring_order, $order );

      do_action( 'wc_horizon_recurring_order_after_create_order', $recurring_order, $order );
      
    }, 'recurring_orders' );
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
   * Check if a order need to be created
   * @param WC_Horizon_Recurrring_Order The recurring order
   * @return bool if today is the day
   */
  protected static function is_today_the_order_creation_date( $recurring_order ) {
    $upcoming_date = $recurring_order->get_upcoming_delivery();
    
    if( !$upcoming_date  ) {
      return false;
    }

    $now = new DateTime(  );
    $upcoming = new DateTime( $upcoming_date );
    $days = intval( $now->diff($upcoming)->format("%r%a") ) + 1 ;

    do_action( 'wc_horizon_recurring_order_remaining_days_' . $days, $recurring_order );

    return $days === 2;
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
   * Process the current recurring order to create it 
   * @param WC_Horizon_Recurrring_Order $recurring_order the recurring order
   * @return WC_Order|Void The current order if it could be created
   */
  protected static function process_recurring_order( $recurring_order ) {
    $payment = $recurring_order->get_payment();
    $credit_card = wc_horizon_get_credit_card( $payment["id"], $recurring_order->get_owner() );

    if( !$credit_card ) {
      return do_action( "wc_horizon_recurring_order_failed", $recurring_order, "The owner of the payment method don't match with the owner of the subscription or the payment method was deleted by the owner" );
    }

    $order = self::create_order( $recurring_order, $credit_card );

    if( is_wp_error( $order ) ) {
      return do_action( "wc_horizon_recurring_order_failed", $recurring_order, $order->get_error_message() );
    }

    do_action('wc_horizon_recurring_order_created_order', $recurring_order, $order);

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
      'created_via' => 'horizon_subscription',
      'status' => 'wc-subs-48hrs'
    ) );

    if( is_wp_error( $order ) ) {
      return $order;
    }
    
    self::set_shipping_address( $order, $recurring_order->get_address("shipping") );
    self::set_billing_address( $order, $credit_card->get_billing() );
    self::set_lineitems( $recurring_order, $order );

    $shipping = self::set_shipping_method( $recurring_order, $order );

    if( is_wp_error( $shipping ) ) {
      $order->set_status("cancelled", 'Shipping cost can\'t be calculated.');
      $order->save();
      return $shipping;
    }

    $order->update_meta_data( "subscription_id", $recurring_order->get_id() );
    $order->calculate_totals();
    $order->save();
    return $order;
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

  protected static function set_shipping_method( WC_Horizon_Recurrring_Order $recurring_order, WC_Order &$order, $attemp = 1 ) {
    $shipping_rate = wc_horizon_recurring_order_get_shipping_cost( $recurring_order );

    if( is_wp_error( $shipping_rate ) ) {
      if( $attemp < 2 ) {
        return self::set_shipping_method( $recurring_order, $order, $attemp + 1);
      } else {
        return new WP_Error('subscription-shipping', 'The shipping rate can\'t be calculated');
      }
    }

    $service = $shipping_rate["service"];
    $shipping_item = new WC_Order_Item_Shipping();
    $shipping_item->set_method_title( $service["description"] );
    $shipping_item->set_method_id( $service["code"] );
    $shipping_item->set_total( $shipping_rate["total"] );
    $order->add_item( $shipping_item );

    return true;
  }
}

add_action( 'woocommerce_horizon_run_subscriptions', 'WC_Horizon_Recurring_Orders_Creation_Cron_Job::create_orders' );