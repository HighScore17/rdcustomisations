<?php
use GraphQL\Error\UserError;

class WC_Horizon_Credit_Card_Get_Graphql {
  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof WC_Horizon_Credit_Card_Get_Graphql ) {
      self::$instance = new WC_Horizon_Credit_Card_Get_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
  }

  function register_graphql_types() {
    register_graphql_field("RootQuery", "creditCard", array(
      "args" => array(
        "id" => array(
          "type" => "ID"
        )
      ),
      "type" => "CreditCardObject",
      "resolve" => [ $this, 'resolve' ]
    ));
  }

  function resolve( $source, $args ) {
    $credit_card = wc_horizon_get_credit_card( $args['id'] );

    if( !$credit_card ) {
      throw new UserError( "Credit card not found" );
    }

    return wc_horizon_credit_card_to_array( $credit_card );
  }
}

WC_Horizon_Credit_Card_Get_Graphql::init();


