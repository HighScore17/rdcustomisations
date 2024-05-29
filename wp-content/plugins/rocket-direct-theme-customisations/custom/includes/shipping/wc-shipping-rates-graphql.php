<?php

use GraphQL\Error\UserError;

class Woocommerce_Horizon_Shipping_Rates_Graphql {
  static $instance = null;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_Horizon_Shipping_Rates_Graphql ) {
      self::$instance = new Woocommerce_Horizon_Shipping_Rates_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
  }

  function register_graphql_types() {
    $this->register_object_types();
    $this->register_rates_calculator();
  }

  function register_object_types() {
    register_graphql_input_type( 'shippingRatesProducts', array(
      'fields' => array(
        'productId' => array( 'type' => 'Int' ),
        'variationId' => array( 'type' => 'Int' ),
        'quantity' => array( 'type' => 'Int' )
      )
    ));

    register_graphql_object_type( 'shippingRateProducts', array(
      'fields' => array(
        'rates' => array( 'type' => array( 'list_of' => 'horizonShippingRate' ) )
      )
    ));
  }

  function register_rates_calculator() {
    register_graphql_field( 'RootQuery', 'shippingRatesByProducts', array(
      'args' => array(
        'products' => array(
          'type' => array(
            'list_of' => 'shippingRatesProducts'
          )
        ),
        'shipping' => array('type' => 'CustomerAddressInput')
      ),
      'type' => 'shippingRateProducts',
      'resolve' => array( $this, 'resolve_rates_calculator' )
    ));
  }

  function resolve_rates_calculator( $_, $input ) {
    if( !is_array( $input["products"] ) ) {
      return new UserError("Products not found.");
    }

    $cart = new WC_Cart();
    foreach( $input["products"] as $product ) {
      if(
        is_array( $product ) &&
        isset( $product["productId"] ) &&
        isset( $product["variationId"] ) &&
        isset( $product["quantity"] ) 
       ) {
        $cart->add_to_cart( $product["productId"], $product["quantity"], $product["variationId"] );
       }
    }
    $calculator = new Shipping_Calculator_V2();

    $_POST["shipTo"] = array(
      "Address" => array(
        "PostalCode" => $input["shipping"]["postcode"],
        "City" => $input["shipping"]["city"],
        "StateProvinceCode" => $input["shipping"]["state"]
      )
    );

    $cart_content = $cart->get_cart();

    if( !count( $cart_content ) ) {
      throw new UserError("Cart empty");
    }

    $items = $calculator->cart_to_packages( $cart_content );
    $rates = $calculator->get_rates( $items );

    if( !is_array( $rates ) || !is_array( $rates["rates"] ) ) {
      throw new UserError("Rates not found. Please verify your shipping address.");
    }

    return array(
      "rates" => $rates["rates"]
    );
  }
}

Woocommerce_Horizon_Shipping_Rates_Graphql::init();