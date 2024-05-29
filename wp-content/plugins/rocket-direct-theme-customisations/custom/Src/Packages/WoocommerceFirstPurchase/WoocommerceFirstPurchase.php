<?php

class Woocommerce_First_Purchase_Events extends WP_Async_Task {
  static $instace = null;
  protected $action = 'woocommerce_new_order';
  protected $argument_count = 2;

  static function init() {
    if( !self::$instace instanceof Woocommerce_First_Purchase_Events ) {
      self::$instace = new Woocommerce_First_Purchase_Events();
    }
  }

  protected function prepare_data( $data ) {
    $order_id = $data[0];
    return array(
      "order_id" => $order_id
    );
  }

  protected function run_action() {
    $order_id = $_POST["order_id"];
    $order = wc_get_order( $order_id );
    $this->log("Running async");

    if( !$order ) {
      $this->log("Not order");
      return;
    }

    if( !__customer_has_orders_paid( $order->get_billing_email() ) ) {
      $order->update_meta_data( 'is_first_bought', 'yes' );
      $order->save();
      do_action( 'wp_async_customer_first_bought', $order_id );
    } else {
      $this->log(":,vvv");
    }
  }

  function log( $msg ) {
    wc_get_logger()->info( $msg, array("source" => "aaa-async-task") );
  }
}

Woocommerce_First_Purchase_Events::init();