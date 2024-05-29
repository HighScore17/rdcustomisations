<?php
require_once __DIR__ . "/Values.php";

class Woocommerce_Stock_Package_Admin_Page {
  static $instance = null;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_Stock_Package_Admin_Page ) {
      self::$instance = new Woocommerce_Stock_Package_Admin_Page();
      self::$instance->addHooks();
    }
  }

  
  function addHooks() {
    add_filter( 'rocket_integration_tabs', [ $this, 'registerPage' ] );
    add_action( 'rocket_integration_controller_for_wc_stock_integration', [ $this, 'controller' ] );
  }

  function registerPage( $pages ) {
    $pages['woo-stock'] = array(
      "title" => "Woocommerce Stock",
      "render" => [$this, 'render']
    );
    return $pages;
  }

  function render() {
    require_once __DIR__ . "/View.php";
  }

  function controller() {
    $canSetOutStock = isset( $_POST["wc_stock_enable"] ) ? ($_POST["wc_stock_enable"] === "on" ? "yes" : "no") : "no";

    if( isset($_POST["wc_stock_min_cases"]) ) {
      Woocommerce_Stock_Packagen_Admin_Values::minium_cases( $_POST["wc_stock_min_cases"] );
    }
    if( isset($_POST["wc_stock_min_cases_masks"]) ) {
      Woocommerce_Stock_Packagen_Admin_Values::minium_masks_cases( $_POST["wc_stock_min_cases_masks"] );
    }

    Woocommerce_Stock_Packagen_Admin_Values::canSetOutStock( $canSetOutStock === "yes");
    

  }
}

Woocommerce_Stock_Package_Admin_Page::init();