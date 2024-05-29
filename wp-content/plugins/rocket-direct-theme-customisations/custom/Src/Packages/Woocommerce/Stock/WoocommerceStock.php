<?php
require_once __DIR__ . "/Admin.php";
class Woocommerce_Stock_Package {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof Woocommerce_Stock_Package ) {
      self::$instance = new Woocommerce_Stock_Package();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    add_action( 'woocommerce_before_product_object_save', [ $this, 'handleStockUpdate' ], 999999, 2 );
  }

  function handleStockUpdate( WC_Product &$product ) {

    if( Woocommerce_Stock_Packagen_Admin_Values::canSetOutStock() !== "yes" )
      return;

    $stockAmount = $product->get_stock_quantity();
    $presentation = strtolower( $product->get_attribute("pa_presentation") ?? '');
    $maxCases = Woocommerce_Stock_Packagen_Admin_Values::minium_cases();
    $parent = wc_get_product( $product->get_parent_id() );
    $slug = $parent ? $parent->get_slug() : $product->get_slug();
    $name = $parent ? $parent->get_name() : $product->get_name();

    if( $slug === '3-ply-level-3-surgical-masks-510k' )
      $maxCases = Woocommerce_Stock_Packagen_Admin_Values::minium_masks_cases();

    if( $presentation !== "case" && $presentation !== "box" )
      return;

    if( $presentation === "box" ) {
      $contain = $product->get_meta( 'contains_item' );
      $caseVariation = wc_get_product( find_product_presentation_variation( $parent, $product ) );

      if( !$caseVariation )
        return;

      $containCase = $caseVariation->get_meta( 'contains_item' );
      $maxCases = $containCase * $maxCases / $contain;
    }

    if( 
      $stockAmount <= $maxCases && $stockAmount !== 0 ) {
        $prev = $_POST["WOOCOMMERCE_BRAND_NAME"];
        $_POST["WOOCOMMERCE_BRAND_NAME"] = "amerisano";
        $message = ":package: " . $name . " was updated to out of stock\n";
        $message .= "*Presentation:* " . strtoupper( $product->get_attribute("pa_presentation") ) . "\n";
        $message .= "*Size:* " . strtoupper( $product->get_attribute("pa_size") ) . "\n";
        $message .= "*Current Stock On Cin7*: " . $stockAmount; 
        $product->set_stock_quantity(0);
        slack_post_message( $message, __slack_channel("tickets") );
        $_POST["WOOCOMMERCE_BRAND_NAME"] = $prev;
    }
  }
}

Woocommerce_Stock_Package::init();