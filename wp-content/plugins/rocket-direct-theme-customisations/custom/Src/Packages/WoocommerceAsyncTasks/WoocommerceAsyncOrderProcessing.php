<?php

class Woocommerce_Async_Order_Processing extends WP_Async_Task {
  static $instace = null;
  protected $action = 'woocommerce_order_status_processing';
  protected $argument_count = 1;

  static function init() {
    if( !self::$instace instanceof Woocommerce_Async_Order_Processing ) {
      self::$instace = new Woocommerce_Async_Order_Processing();
    }
  }

  protected function prepare_data( $data ) {
    return apply_filters( 'woocommerce_async_order_status_processing_args', [ "order_id" => $data[0] ] );
  }

  protected function run_action() {
    do_action('woocommerce_async_order_status_processing');
  }
}

Woocommerce_Async_Order_Processing::init();