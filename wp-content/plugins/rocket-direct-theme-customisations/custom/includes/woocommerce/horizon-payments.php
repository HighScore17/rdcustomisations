<?php

class Horizon_Woocommerce_Payment_Status {
  public static $instance = null;

  public static function init() {
    if( !(self::$instance instanceof Horizon_Woocommerce_Payment_Status) ) {
      self::$instance = new Horizon_Woocommerce_Payment_Status();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action('woocommerce_usio_payment_declined', [$this, 'on_failed_usio_payment'], 10, 4);
  }

  function on_failed_usio_payment( $order_id, $error, $token, $payment_id ) {
    $order = wc_get_order( $order_id );

    if( !$order ){
      return;
    }

    wc_horizon_set_order_brand( $order );

    slack_post_message(":money_with_wings: The payment could not be processed for the order #{$order->get_order_number()} of {$order->get_billing_email()} \"{$error->get_error_message()}\"", __slack_channel("tickets"));
  }
}
    Horizon_Woocommerce_Payment_Status::init();