<?php

class Horizon_Free_Box {

  const FREE_CASE_5 = 'FREE_CASE_5';
  const FREE_CASE_10 = 'FREE_CASE_10';
  const VARIATION_ID = 2025;

  public static $instance = null;
  static function init() {
    if( !self::$instance instanceof Horizon_Free_Box ) {
      self::$instance = new Horizon_Free_Box();
      self::$instance->add_hooks();
    }
  }

  public function add_hooks() {
    add_action( 'woocommerce_checkout_create_order', [ $this, 'add_free_case' ], 10, 2 );
  }

  public function add_free_case( WC_Order &$order, $data ) {
    if( count($order->get_coupons()) > 0 ) {
      return;
    }

    $free_case_5 = 0;
    $free_case_10 = 0;
    foreach( $order->get_items() as $item_id =>  $item ) {
      if($item->get_variation_id() !== self::VARIATION_ID) {
        continue;
      }
      $custom_promotion = $item->get_meta('Custom Promotion');
      if( $custom_promotion === self::FREE_CASE_5 ) {
        $free_case_5 += $item->get_quantity();
      }
      else if( $custom_promotion === self::FREE_CASE_10 ) {
        $free_case_10 += $item->get_quantity();
      }
    }

    if($free_case_5 >= 5 || $free_case_10 >= 10) {
      $this->add_free_case_item($order);
    } 
  }

  private function add_free_case_item( WC_Order &$order) {
    $product = wc_get_product(self::VARIATION_ID);
    $item = new WC_Order_Item_Product();
    $item->set_props(
      array(
        'quantity'     => 1,
        'variation'    => self::VARIATION_ID,
        'subtotal'     => 0,
        'total'        => 0,
        'subtotal_tax' => 0,
        'total_tax'    => 0,
        'taxes'        => 0,
      )
    );

    if ( $product ) {
      $item->set_props(
        array(
          'name'         => $product->get_name(),
          'tax_class'    => $product->get_tax_class(),
          'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
          'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
        )
      );
    }
    $item->add_meta_data("Size", isset( $_POST["freeCaseSize"] ) ? $_POST["freeCaseSize"] : "S");
    $item->set_backorder_meta();
    $order->add_item($item);
  }

}

Horizon_Free_Box::init();