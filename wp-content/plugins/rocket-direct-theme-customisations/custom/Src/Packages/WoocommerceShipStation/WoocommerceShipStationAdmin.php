<?php

class Woocommerce_ShipStation_Admin_Integration {
  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_ShipStation_Admin_Integration ) {
      self::$instance = new Woocommerce_ShipStation_Admin_Integration();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    add_action( 'add_meta_boxes', [$this, 'registerShipStationMetabox']);
    add_action( 'wp_ajax_admin_sync_ss_order', [ $this, 'syncShipStationOrder' ]);
  }

  public function registerShipStationMetabox() {
    add_meta_box('wc_order_shipstation_sync', 'ShipStation', function() {
      require_once __DIR__ . "/Views/SyncOrder.php";
    }, 'shop_order', 'side');
  }

  public function syncShipStationOrder() {
    if( !__is_admin() ) {
      wp_send_json_error(new WP_Error('users_batch', 'Permission denied'));
      wp_die();
    }

    if( !isset( $_POST["order_id"] ) ) {
      wp_send_json_error( new WP_Error('sync-ss-order', 'Order ID not provided') );
      wp_die();
    }

    $order = wc_get_order( intval( $_POST["order_id"] ) );

    if( !$order ) {
      wp_send_json_error( new WP_Error('sync-ss-order', 'Order doesn\'t exists') );
      wp_die();
    }

    $ssOrder = Woocommerce_ShipStation_Orders_Integration::sync_order( $order, Woocommerce_ShipStation_Orders_Integration::AWAITING_SHIPMENT_STATUS );

    if( is_wp_error( $ssOrder ) ) {
      wp_send_json_error( $ssOrder->get_error_message());
      wp_die();
    }

    wp_send_json_success( array( "success" => true ) );
    wp_die();
  }
}

Woocommerce_ShipStation_Admin_Integration::init();