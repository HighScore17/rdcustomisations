<?php
class Woocommerce_Shipstation_Label_Admin {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof Woocommerce_Shipstation_Label_Admin ) {
      self::$instance = new Woocommerce_Shipstation_Label_Admin();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    //XD
    add_action( 'wp_ajax_admin_shipstation_create_label', [ $this, 'handleCreateLabel'] );
  }

  function handleCreateLabel() {
    $labels = Woocommerce_Shipstation_Label::createLabelFromOrder( $_POST['order_id'] );

    if( !$labels ) {
      wp_send_json_error( "Labels cannot be created" );
    }

    wp_send_json_success( array( "success" => true ) );
    wp_die();
  }

  
}

Woocommerce_Shipstation_Label_Admin::init();