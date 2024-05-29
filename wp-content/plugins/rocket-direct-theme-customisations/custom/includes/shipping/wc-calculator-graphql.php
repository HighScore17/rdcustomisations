<?php

class WC_Calculator_GraphQL {

  /**
   * Constructor
   */

  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof WC_Calculator_GraphQL ) {
      self::$instance = new WC_Calculator_GraphQL();
      self::$instance->enable();
    }
  }

  function enable()
  {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_shipping_api' ] );
  }

  function register_graphql_shipping_api()
  {
    $this->register_graphql_calculator_data();
  }

  function register_graphql_calculator_data() {
    register_graphql_object_type('shippingRateInsurance', array(
      'fields' => array(
        'id' => array(
          'type' => 'String'
        ),
        'cost' => array(
          'type' => 'Float'
        ),
        'message' => array(
          'type' => 'String'
        ),
      )
    ));
    register_graphql_object_type('shippingRateInsurances', array(
      'fields' => array(
        'nodes' => array(
          'type' => array(
            'list_of' => 'shippingRateInsurance'
          )
        )
      )
    ));
    register_graphql_object_type('horizonShippingCalculatorDataOutput', array(
      'fields' => array(
        'insurance' => array(
          'type' => 'shippingRateInsurances',
        ),
        'accessorials' => array(
          'type' => array(
            'list_of' => 'String'
          )
        ),
      )
    ));
    register_graphql_field('RootQuery', 'horizonShippingCalculatorData', array(
      'type' => 'horizonShippingCalculatorDataOutput',
      'resolve' => [$this, 'resolve_calculator_data']
    ));
  }

  function resolve_calculator_data() {
    $rates = WC()->session->get("cached_rates_quotes");
    $insurance = array();
    if( !is_array($rates) ) {
      return array();
    }
    foreach( $rates["rates"] as $rate ) {
      if( !empty($rate['insurance']) ) {
        $insurance[] = array(
          "id" => $rate["service"]["code"],
          "cost" => $rate['insurance']["cost"],
          "message" => $rate['insurance']["message"],
        ) ;
      }
    }
    return array(
      "accessorials" => $rates["accessorials"],
      "insurance" => array(
        "nodes" => $insurance
      )
    );
  }
}

WC_Calculator_GraphQL::init();