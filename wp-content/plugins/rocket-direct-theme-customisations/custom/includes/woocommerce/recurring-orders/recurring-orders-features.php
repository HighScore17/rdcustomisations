<?php

class WC_Horizon_Recurring_Orders_Features {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof WC_Horizon_Recurring_Orders_Features ) {
      self::$instance = new WC_Horizon_Recurring_Orders_Features();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action('wc_horizon_recurring_order_notification_for_day_2', array( $this, 'before_process_recurring_order' ) );
  }

  function before_process_recurring_order( $recurring_order ) {
    $this->precalculate_shipping_cost( $recurring_order );
  }

  function precalculate_shipping_cost( WC_Horizon_Recurrring_Order $recurring_order ) {
    $shipping_rate = wc_horizon_recurring_order_get_shipping_cost( $recurring_order );
    
    if( is_wp_error( $shipping_rate ) ) {
      return;
    }

    $recurring_order->set_precalculated_shipping_cost( $shipping_rate );
    $recurring_order->save();
  }
}

WC_Horizon_Recurring_Orders_Features::init();