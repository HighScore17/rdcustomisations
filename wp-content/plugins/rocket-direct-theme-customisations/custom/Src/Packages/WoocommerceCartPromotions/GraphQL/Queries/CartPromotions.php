<?php

use WPGraphQL\WooCommerce\Model\Product;

class WoocommerceCartPromotionsGraphql {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof WoocommerceCartPromotionsGraphql ) {
      self::$instance = new WoocommerceCartPromotionsGraphql();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    add_action( 'graphql_register_types', [ $this, 'registerMutation' ] );
  }

  function registerMutation() {
    register_graphql_field( 'cart', 'promotions', array(
      'type' => 'cartPromotions',
      'resolve' => [ $this, 'resolve' ],
    ) );

    register_graphql_object_type("cartPromotion", array(
      "fields" => array(
        "id" => array(
          "type" => "String",
        ),
        "product" => array(
          "type" => "Product",
        )
      )
    ));
    register_graphql_object_type("cartPromotions", array(
      "fields" => array(
        "nodes" => array(
          "type" => array(
            "list_of" => "cartPromotion"
          )
        )
      )
    ));
  }

  function resolve() {
    $promotions = WoocommerceCartPromotionsCalculator::getAvailablePromotions();
    return array(
      "nodes" => array_map( function( $promotion ) {
        return array(
          "id" => $promotion["id"],
          "product" => new Product( $promotion["productId"] )
        );
      }, $promotions )
    ) ;
  }
}

WoocommerceCartPromotionsGraphql::init();