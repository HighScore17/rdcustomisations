<?php
require_once __DIR__ . "/Values.php";

class Woocommerce_ShipStation_Admin_Page {
  static $instance = null;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_ShipStation_Admin_Page ) {
      self::$instance = new Woocommerce_ShipStation_Admin_Page();
      self::$instance->addHooks();
    }
  }

  
  function addHooks() {
    add_filter( 'rocket_integration_tabs', [ $this, 'registerPage' ] );
    add_action( 'rocket_integration_controller_for_shipstation_integration', [ $this, 'controller' ] );
  }

  function registerPage( $pages ) {
    $pages['shipstation'] = array(
      "title" => "Shipstation",
      "render" => [$this, 'render']
    );
    return $pages;
  }

  function render() {
    require_once __DIR__ . "/View.php";
  }

  function controller() {
    $canSyncOrder = isset( $_POST["ss_sync_order"] ) ? ($_POST["ss_sync_order"] === "on" ? "yes" : "no") : "no";
    $canCreateLabels = isset( $_POST["ss_create_labels"] ) ? ($_POST["ss_create_labels"] === "on" ? "yes" : "no") : "no";

    if( isset($_POST["ss_api_key"]) ) {
      Woocommerce_ShipStation_Admin_Values::apiKey( $_POST["ss_api_key"] );
    }

    if( isset($_POST["ss_api_secret"]) ) {
      Woocommerce_ShipStation_Admin_Values::apiSecret( $_POST["ss_api_secret"] );
    }

    Woocommerce_ShipStation_Admin_Values::canSyncOrder( $canSyncOrder === "yes");
    Woocommerce_ShipStation_Admin_Values::canCreateLabels( $canCreateLabels === "yes");
    

  }
}

Woocommerce_ShipStation_Admin_Page::init();