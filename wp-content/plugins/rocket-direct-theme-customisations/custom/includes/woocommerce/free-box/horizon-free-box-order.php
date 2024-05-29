<?php

class WC_Horizon_Free_Box_Order {
  static function process( $products, $shipping ) {
    $order = self::create_order();
    $shipping = self::get_address( $shipping );

    if( !__is_valid_wc_order( $order ) ) {
      return;
    }

    $products_ids = self::get_products_variations_ids( $products );

    foreach( $products_ids as $product_id ) {
      $order->add_product(  wc_get_product( $product_id ), 1, array("subtotal" => 0, "total" => 0) );
    }

    $order->set_address( $shipping, 'shipping' );
    $order->set_address( $shipping, 'billing' );
    $order->save();
  }

  static function get_products_variations_ids( $products ) {
    $ids = [];
    foreach( $products as $product ) {
      $product_id = $product["product_id"];
      $size = $product["size"];

      $product = wc_get_product( $product_id );
      
      if( !$product ) {
        return;
      }

      $variations = $product->get_children();

      $variation_id = __array_find( $variations, function( $variation_id ) use ($size) {
        $variation = wc_get_product( $variation_id );

        if( !$variation ) {
          return;
        }
        $pa_presentation = $variation->get_attribute("pa_presentation");
        $pa_size = $variation->get_attribute("pa_size");

        return $pa_presentation === "box" && ( !$size || strtolower( $pa_size ) === strtolower($size));
      } );

      if( !$variation_id ) {
        return;
      }

      $ids[] = $variation_id;
    }
    return $ids;
  }

  static function get_address( $address ) {
    $address['first_name'] = $address['firstName'];
    $address['last_name'] = $address['lastName'];
    $address['address_1'] = $address['address1'];
    $address['address_2'] = $address['address2'];
    return $address;
  }

  static function create_order() {
    return wc_create_order(array(
      'status' => 'wc-processing',
      'created_via' => 'ac_free_sample'
    ));
  }
}