<?php

class WC_Horizon_Request_Free_Box_Graphql {
  public static $instance = null;

  static function init() {
    if ( !self::$instance instanceof WC_Horizon_Request_Free_Box_Graphql ) {
      self::$instance = new WC_Horizon_Request_Free_Box_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql' ] );
  }

  function register_graphql() {
    $this->register_object_type();
    $this->register_mutations();
  }

  function register_mutations() {
    register_graphql_mutation( 'requestFreeBox', array(
      'inputFields' => array(
        'shipping' => array(
          'type' => 'CustomerAddressInput'
        ),
        'products' => array(
          'type' => array(
            'list_of' => 'freeBoxProduct'
          )
        )
      ),
      'outputFields' => array(
        'success' => array(
          'type' => 'Boolean'
        )
      ),
      'mutateAndGetPayload' => array( $this, 'mutate' )
    ) );
  }

  function register_object_type() {
    register_graphql_input_type( 'freeBoxProduct', array(
      "fields" => array(
        'product_id' => array(
          'type' => 'Int'
        ),
        'size' => array(
          'type' => 'String'
        )
      )
    ) );
  }

  function mutate( $input ) {
    WC_Horizon_Free_Box_Order::process( $input["products"], $input["shipping"] );
    return array(
      "success" => true,
    );
  }
}

WC_Horizon_Request_Free_Box_Graphql::init();