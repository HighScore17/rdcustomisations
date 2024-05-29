<?php
class WC_Horizon_Order_Backordered {
  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Horizon_Order_Backordered ) {
      self::$instance = new WC_Horizon_Order_Backordered();
      self::$instance->add_hooks();
    }
  }

  public function add_hooks() {
    add_action( 'woocommerce_new_order', [ $this, 'set_order_as_backordered' ], 10, 2 );
  }
  
  public function set_order_as_backordered( $order_id, WC_Order &$order ) {
    $order->update_meta_data( 'has_backordered_items', __order_has_backordered_items( $order ) ? "yes" : "no" );
    $order->save();
  }
}

WC_Horizon_Order_Backordered::init();