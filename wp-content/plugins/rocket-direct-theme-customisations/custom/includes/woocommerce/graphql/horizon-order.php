<?php

class Woocommerce_Horizon_Order_Fields_Graphql {
  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_Horizon_Order_Fields_Graphql ) {
      self::$instance = new Woocommerce_Horizon_Order_Fields_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
  }

  public function register_graphql_types() {
    register_graphql_object_type("lineItemLivePriceObject", array(
      "fields" => array(
        "amount" => array(
          "type" => "Float"
        ),
        "isSpecial" => array(
          "type" => "Boolean"
        )
      )
    ));
    register_graphql_field("LineItem", "livePrice", array(
      'type' => 'lineItemLivePriceObject',
      "resolve" => [ $this, 'resolve' ]
    ));
  }

  public function resolve( $source ) {
    $price = __get_lineitem_current_price( $source );
    $price_b2c = __get_lineitem_current_price( $source, "b2c" );
    return array(
      "amount" => $price,
      "isSpecial" => $price < $price_b2c
    );
  }
}

Woocommerce_Horizon_Order_Fields_Graphql::init();