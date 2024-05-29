<?php

class WC_Horizon_Email_Notifications_For_Recurring_Orders {

  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Horizon_Email_Notifications_For_Recurring_Orders ) {
      self::$instance = new WC_Horizon_Email_Notifications_For_Recurring_Orders();
      self::$instance->add_hooks();
    }
  }

  public function add_hooks() {
    add_action( 'wc_horizon_recurring_order_updated', [$this, 'send_notification_to_recurring_order_updated',], 10, 2 );
    add_action( 'wc_horizon_recurring_order_created', [$this, 'send_notification_to_recurring_order_created'] );
    add_action( 'wc_horizon_recurring_order_status_changed', [$this, 'send_notification_to_recurring_order_canceled'], 10, 2 );
    add_action( 'wc_horizon_recurring_order_after_create_order', [$this, 'send_notification_to_recurring_order_upcoming_delivery'], 10, 2);
    add_action( 'wc_horizon_recurring_order_processed_successfully', [$this, 'send_notification_to_recurring_order_processed'], 10, 2 );
  }
    /**
     * Send order created email notification
     */
    public function send_notification_to_recurring_order_created( WC_Horizon_Recurrring_Order $recurring_order ) {
        $context = $this->get_context($recurring_order );
        postmark_send_email( 'subscription_confirmation', $context, wc_horizon_get_email_sender("subscription"), $this->get_user_email( $recurring_order ) );
        if( !__is_development_enviroment() ) {
          postmark_send_email( 'subscription_confirmation', $context, wc_horizon_get_email_sender("subscription"), "sales@rocket.direct,abigail@horizon.com.pr,grecia@horizon.com.pr" );
        }
        
    }
    /**
     * Send order updated email notification
     */
    public function send_notification_to_recurring_order_updated( WC_Horizon_Recurrring_Order $old, WC_Horizon_Recurrring_Order $recurring_order ) {
        $context = $this->get_context($recurring_order );
        $context["changes"] = "";

        if( !$recurring_order->is_active() ) {
          return;
        }

        $changes = wc_horizon_recurring_order_detect_changes( $old, $recurring_order );

        if( in_array( "frequency", $changes ) ) {
          $context["changes"] .= "-Delivery Frequency <br/>";
        }

        if( in_array( "shipping_address", $changes ) ) {
          $context["changes"] .= "-Shipping Address <br/>";
        }

        if( in_array( "payment", $changes ) ) {
          $context["changes"] .= "-Payment Method <br/>";
        }

        postmark_send_email('subscription_updated', $context, wc_horizon_get_email_sender("subscription"), $this->get_user_email($recurring_order));
        //copy to
        if( !__is_development_enviroment() ) {
          postmark_send_email('subscription_updated', $context, wc_horizon_get_email_sender("subscription"), "sales@rocket.direct,abigail@horizon.com.pr,grecia@horizon.com.pr,javier@horizon.com.pr");
        }
    }
    /**
     * Send order canceled email notification
     */
    public function send_notification_to_recurring_order_canceled( WC_Horizon_Recurrring_Order $recurring_order, $statuc ) {
      $context = $this->get_context($recurring_order );
      if ( !$recurring_order->is_active() ) {
        postmark_send_email('subscription_canceled', $context, wc_horizon_get_email_sender("subscription"), $this->get_user_email($recurring_order));
        if( !__is_development_enviroment() ) {
          postmark_send_email('subscription_canceled', $context, wc_horizon_get_email_sender("subscription"), "sales@rocket.direct,abigail@horizon.com.pr,grecia@horizon.com.pr,javier@horizon.com.pr");
        }
      }
    }

    /**
     * Send order for days email notification
     */
    public function send_notification_to_recurring_order_upcoming_delivery( WC_Horizon_Recurrring_Order $recurring_order, WC_Order $order ) {
      $context = $this->get_context( $recurring_order, $order );
      postmark_send_email('subscription_upcoming_delivery', $context, wc_horizon_get_email_sender("subscription"), $this->get_user_email($recurring_order));
      //copy to
      if( !__is_development_enviroment() ) {
        postmark_send_email('subscription_upcoming_delivery', $context, wc_horizon_get_email_sender("subscription"), "sales@rocket.direct,abigail@horizon.com.pr,grecia@horizon.com.pr,javier@horizon.com.pr");
      }
    }

    /**
     * Send order for days email notification
     */
    public function send_notification_to_recurring_order_processed( WC_Horizon_Recurrring_Order $recurring_order, WC_Order $order ) {
      $context = $this->get_context( $recurring_order, $order );
      postmark_send_email('subscription_order', $context, wc_horizon_get_email_sender("subscription"), $this->get_user_email($recurring_order));
      //copy to
      if( !__is_development_enviroment() ) {
        postmark_send_email('subscription_order', $context, wc_horizon_get_email_sender("subscription"), "sales@rocket.direct,abigail@horizon.com.pr,grecia@horizon.com.pr,javier@horizon.com.pr");
      }
    }

  /**
   * Get the subscription owner email
   */
  private function get_user_email( WC_Horizon_Recurrring_Order $recurring_order ) {
    $user = get_userdata( intval($recurring_order->get_owner()) );
    return $user ? $user->user_email : "";
  }

  /**
   * Get the subscription owner email
   */
  static function get_subs_user_email( WC_Horizon_Recurrring_Order $recurring_order ) {
    $user = get_userdata( intval($recurring_order->get_owner()) );
    return $user ? $user->user_email : "";
  }

  /**
   * Get the full context for the email
   */
  private function get_context(  WC_Horizon_Recurrring_Order $recurring_order, WC_Order $order = null ) {
    $address = $this->get_addresses_context( $recurring_order );
    $products = $this->get_lineitems_context( $recurring_order, $order );
    $subtotal =  __safe_number_format( $recurring_order->get_subtotal(), 2 );
    $shipping = $order ? $order->get_shipping_total() :  __safe_number_format( $recurring_order->get_estimated_shipping_cost(), 2 );
    $total = __safe_number_format( $subtotal + $shipping, 2);
    return array_merge(
      $address,
      array(
        "subscription_id" => $recurring_order->get_id(),
        "items" => $products,
        "payment_title" => $this->get_payment_type( $recurring_order ),
        "delivery_frequency" => $recurring_order->get_frequency()["label"],
        "subscription_total" => "$" . $total,
        "subtotal" => "$" . $subtotal,
        "shipping_cost" => "$" . $shipping,
        "total" => "$" . $total,
      )
    );
  }

  /**
   * Get the context form addresses
   */
  private function get_addresses_context( WC_Horizon_Recurrring_Order $recurring_order ) {
    $billing = $this->get_billing_address( $recurring_order );
    $shipping = $recurring_order->get_address("shipping");
    return array(
      "first_name" => $billing["firstname"],
      "billing_full_name" => $billing["firstname"] . " " . $billing["lastname"],
      "billing_company" => $billing["company"],
      "billing_address" => $billing["address1"],
      "billing_city" => $billing["city"],
      "billing_state" => $billing["state"],
      "billing_zip_code" => $billing["postcode"],
      "billing_country" => $billing["country"],
      "shipping_full_name" => $shipping["first_name"] . " " . $billing["last_name"],
      "shipping_company" => $shipping["company"],
      "shipping_address" => $shipping["address_1"],
      "shipping_city" => $shipping["city"],
      "shipping_state" => $shipping["state"],
      "shipping_zip_code" => $shipping["postcode"],
      "shipping_country" => $shipping["country"],
    );
  }

  /**
   * Get the context for products
   */
  private function get_lineitems_context( WC_Horizon_Recurrring_Order $recurring_order, WC_Order $order = null ) {
    $upcoming_delivery = $order ? new DateTime() : new DateTime( $recurring_order->get_upcoming_delivery() );

    if( $order ) {
      $upcoming_delivery = $order->is_paid() ? $upcoming_delivery->modify("+1 day") : $upcoming_delivery->modify("+2 day");
    }

    $upcoming_delivery = $upcoming_delivery->format("M jS, Y");
    return $order ? $this->get_lineitems_context_by_order( $recurring_order, $order, $upcoming_delivery ) : $this->get_lineitems_context_by_recurring_order( $recurring_order, $upcoming_delivery );
  }

  private function get_lineitems_context_by_order( WC_Horizon_Recurrring_Order $recurring_order, WC_Order $order, $upcoming_delivery ) {
    return array_values( array_map( function( WC_Order_Item $item ) use ( $recurring_order, $order, $upcoming_delivery ) {
      $metadata = $this->get_lineitem_meta_as_associative( $item );
      return array(
        'order_number' => $order->get_order_number(),
        "subscription_id" => $recurring_order->get_id(),
        "product_name" => $item->get_name(),
        "upcoming_delivery" => $upcoming_delivery,
        "order_quantity" => $item->get_quantity(),
        "presentation" => ucfirst( $metadata["pa_presentation"] ?? "" ),
        "product_size" => strtoupper( $metadata["pa_size"] ?? "" ),
        "cost_per_product" => "$" . __safe_number_format( $item->get_total() * $item->get_quantity(), 2) ,
        "total" => "$" . __safe_number_format( $item->get_total(), 2 ) 
      );
    }, $order->get_items()));
  }

  private function get_lineitems_context_by_recurring_order( WC_Horizon_Recurrring_Order $recurring_order, $upcoming_delivery ) {
    return array_map( function( $product ) use ($recurring_order, $upcoming_delivery) {
      return array(
        "subscription_id" => $recurring_order->get_id(),
        "product_name" => $product["name"],
        "upcoming_delivery" => $upcoming_delivery,
        "order_quantity" => $product["quantity"],
        "presentation" => ucfirst( $this->get_lineitem_meta($product["metaData"], "pa_presentation")["value"] ?? "" ),
        "product_size" => strtoupper( $this->get_lineitem_meta($product["metaData"], "pa_size")["value"] ?? "" ),
        "cost_per_product" => "$" . number_format(floatval( $product["total"] ) / intval( $product["quantity"] ), 2),
        "total" => "$" . number_format( $product["total"], 2 ) 
      );
    }, $recurring_order->get_lineitems() );
  }

  /**
   * Get metadata as associative array from a order
   */
  private function get_lineitem_meta_as_associative( $item ) {
    $metadata = $item->get_meta_data();
    $items = [];

    foreach( $metadata as $meta ) {
      $items[$meta->key] = $meta->value;
    }

    return $items;
  }

  /**
   * Get product meta by key
   */
  private function get_lineitem_meta( $meta, $key ) {
    $data = __array_find( $meta, function( $item ) use ($key) {
      $data = $item->get_data();
      return $data["key"] === $key;
    } );
    return $data ? $data->get_data() : [];
  }

  /**
   * Get the billing address from a payment form
   */
  private function get_billing_address( WC_Horizon_Recurrring_Order $recurring_order ) {
    $payment = $recurring_order->get_payment();
    $cc = wc_horizon_get_credit_card( $payment["id"], intval( $recurring_order->get_owner() ) );

    if( !is_a( $cc, 'WC_Horizon_Credit_Card' ) ) {
      return array();
    }
    
    return $cc->get_billing();
  }

  /**
   * Get the payment type title
   */
  private function get_payment_type( WC_Horizon_Recurrring_Order $recurring_order ) {
    $payment = $recurring_order->get_payment();
    $cc = wc_horizon_get_credit_card( $payment["id"], intval( $recurring_order->get_owner() ) );

    if( !is_a( $cc, 'WC_Horizon_Credit_Card' ) ) {
      return "";
    }
    
    return $cc->get_card_type() . " " . $cc->get_placeholder();
  }
}

WC_Horizon_Email_Notifications_For_Recurring_Orders::init();