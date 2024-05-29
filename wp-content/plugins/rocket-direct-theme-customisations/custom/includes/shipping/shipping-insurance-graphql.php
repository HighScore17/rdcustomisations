<?php

class Shipping_Insurance_GraphQL {

  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof Shipping_Insurance_GraphQL ) {
      self::$instance = new Shipping_Insurance_GraphQL();
      self::$instance->enable();
    }
  }

  function enable()
  {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_shipping_api' ] );
  }

  function register_graphql_shipping_api()
  {
    $this->register_graphql_insurance();
  }

  function register_graphql_insurance() {
    register_graphql_object_type('horizonShippingInsuranceOutput', array(
      'fields' => array(
        'insurance' => array(
          'type' => 'Boolean'
        )
      )
    ));
    register_graphql_field('RootQuery', 'horizonShippingInsurance', array(
      'type' => 'horizonShippingInsuranceOutput',
      'resolve' => [$this, 'resolve_shipping_insurance']
    ));
  }

  function resolve_shipping_insurance() {
    print_r(WC()->shipping());
    wp_die();
    return array(
      "insurance" => true
    );
  }
}

Shipping_Insurance_GraphQL::init();