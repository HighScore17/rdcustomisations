<?php

class Woocommerce_ShipStation_Order_Async {
  static $instace = null;
  protected $action = 'woocommerce_order_status_processing';
  protected $argument_count = 1;

  static function init() {
    if( !self::$instace instanceof Woocommerce_ShipStation_Order_Async ) {
      self::$instace = new Woocommerce_ShipStation_Order_Async();
      self::$instace->addHooks();
    }
  }

  function addHooks() {
    add_action( 'woocommerce_async_order_status_processing', [ $this, 'onOrderProcessing' ] );
  }

  function onOrderProcessing() {
    $order_id = $_POST["order_id"];
    $order = __get_wc_order( $order_id );

    if( !$order ) {
      return slack_post_message( 
        "Order #$order_id cannot be sync to ShipStation. Reason: Order doesn't exists in Woocommerce", 
        __slack_channel("tickets") 
      );
    }

    $this->syncOrder( $order );
    $this->createLabels( $order );
  }

  function syncOrder( WC_Order $order ) {
    $order_id = $order->get_order_number();

    if( $order->get_meta("shipstation_parent_order_id") ) {
      return;
    }

    if( Woocommerce_ShipStation_Admin_Values::canSyncOrder() !== 'yes' ) {
      return;
    }

    $ssOrder = Woocommerce_ShipStation_Orders_Integration::sync_order( $order, Woocommerce_ShipStation_Orders_Integration::AWAITING_SHIPMENT_STATUS );

    if( is_wp_error( $ssOrder ) ) {
      return slack_post_message( 
        "Order #$order_id cannot be created in ShipStation. Reason: " . $ssOrder->get_error_message(),
        __slack_channel("tickets")
      );
    }

    return true;
  }

  function createLabels( WC_Order $order ) {
    $order_id = $order->get_order_number();
    
    if( Woocommerce_ShipStation_Admin_Values::canCreateLabels() !== 'yes' ) {
      return;
    }

    $logger = wc_get_logger();
    $logger->info( Woocommerce_ShipStation_Admin_Values::canCreateLabels(), ["source" => "aAA"] );

    $ssLabels = Woocommerce_Shipstation_Label::createLabelFromOrder( $order );

    $logger = wc_get_logger();
    $logger->info( wc_print_r( $ssLabels, true ), ["source" => "aAA"] );

    if( is_wp_error( $ssLabels ) ) {
      return slack_post_message( 
        "Labels for order #$order_id cannot be created in ShipStation. Reason: " . $ssLabels->get_error_message(),
        __slack_channel("tickets")
      );
    }

    $order->set_status('wc-in-transit');
    $order->save();
    return true;
  }
}

Woocommerce_ShipStation_Order_Async::init();