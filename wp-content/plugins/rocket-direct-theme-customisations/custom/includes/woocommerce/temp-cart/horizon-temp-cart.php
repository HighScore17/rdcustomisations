<?php

class Horizon_Temp_Cart {
  public static $instance = null;

  public static function init() {
    if( self::$instance === null ) {
      self::$instance = new self();
      self::$instance->add_hooks();
    }
  }

  public function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_temporaty_cart' ] );
    add_action( 'graphql_input_fields', [ $this, 'add_temporary_cart_to_add_to_cart_mutation' ], 10, 2 );
  }

  public function add_temporary_cart_to_add_to_cart_mutation( $input_fields, $type_name ) {
    if( $type_name === "AddToCartInput" ) {
      $input_fields["temporaryCart"] = [
        'type' => 'Boolean',
        'description' => 'If the cart is temporary',
        'resolve' => function( $value, $args, $context, $info ) {
          die();
        }
      ];
    }
    return $input_fields;
  }

    
  function register_graphql_temporaty_cart() {

  }
}

Horizon_Temp_Cart::init();