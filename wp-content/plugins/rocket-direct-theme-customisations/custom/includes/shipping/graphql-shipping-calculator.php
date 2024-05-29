<?php
use GraphQL\Error\UserError;
class Graphql_Shipping_Calculator
{
  /**
   * For Check if quote in UPS or Estes
   */
  private $min_cases_for_truckload = 10;

  public static $instance;

  private $ups;
  private $estes_express;

  private $calc;
  

  public static function init()
  {
    if( !self::$instance instanceof Graphql_Shipping_Calculator ) {
      self::$instance = new Graphql_Shipping_Calculator();
      self::$instance->enable();
      self::$instance->calc = new Shipping_Calculator(TRUE);
    }
  }

  function get_cart() {
    global $woocommerce;
    $items = $woocommerce->cart->get_cart();
    $ids = array();
    foreach($items as $item => $values) {
        array_push( $ids, array(
          'id' => $values['data']->get_parent_id(),
          'variation_id' => $values['data']->get_id(),
          'quantity' => $values['quantity']
        )  );
    }
    return $ids;
  }

  function enable()
  {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_shipping_api' ] );
  }

  function register_graphql_shipping_api()
  {
    $this->register_graphql_shipping_objects();
    $this->register_graphql_shipping_input();
    $this->register_graphql_query_quote_type();
    $this->register_graphql_query_cart_recalculation();
  }

  function register_graphql_shipping_objects()
  {
    register_graphql_object_type('shippingRateQuoteAccessories', array(
      'fields' => array(
        'label' => array(
          'type' => 'String'
        ),
        'id' => array(
          'type' => 'String'
        )
      )
    ));

    register_graphql_object_type('shippingRateTypeData', array(
      'fields' => array(
        'accessorials' => array(
          'type' => array(
            'list_of' => 'shippingRateQuoteAccessories'
          )
        ),
        'type' => array(
          'type' => 'String'
        )
      )
    ));

    register_graphql_object_type('cartRecalculation', array(
      'fields' => array(
        'isCheckout' => array(
          'type' => 'Boolean'
        )
      )
    ));
  }

  function register_graphql_shipping_input()
  {
    register_graphql_input_type('shippingQuoteAddressData', array(
        'fields' => array(
          'addressLine' => array(
            'type' => 'String',
          ),
          'city' => array(
            'type' => 'String',
          ),
          'stateProvinceCode' => array(
            'type' => 'String',
          ),
          'postalCode' => array(
            'type' => 'String',
          )
        )
        
      ) );
      register_graphql_input_type('shippingQuoteAddress', array(
        'fields' => array(
          'name' => array(
            'type' => 'String',
          ),
          'address' => array(
            'type' => 'shippingQuoteAddressData'
          )
        )
      ) );
  }

  function register_graphql_query_quote_type()
  {
    register_graphql_field('RootQuery', 'shippingRateType', array(
      'type' => 'shippingRateTypeData',
      'resolve' => $this->shipping_quote_type_resolver()
    ));
  }

  function register_graphql_query_cart_recalculation() {
    register_graphql_field('RootQuery', 'cartRecalculateShipping', array(
      'type' => 'cartRecalculation',
      'args' => $this->get_cart_recalculation_args(),
      'resolve' => function( $source, $args, $context, $info ) {
        return array(
          'isCheckout' => $args["isCheckout"]
        );
      }
    ));
  }

  function get_cart_recalculation_args() {
    return array(
      'isCheckout' => array(
        'type' => 'Boolean'
      ),
      'isResidential' => array(
        'type' => 'Boolean'
      ),
      'accessorials' => array(
        'type' => array(
          'list_of' => 'String'
        )
      ),
      'date' => array(
        'type' => 'String'
      ),
      'shipTo' => array(
        'type' => 'shippingQuoteAddress'
      )
    );
  }

  function shipping_quote_type_resolver(  ) {
    return function( $source, $args, $context, $info ) {
      $items = $this->calc->get_cart_data_legacy( $this->get_cart() );
      $type = $this->calc->get_shipment_type( $items );
      $accessorials = $this->calc->get_provider_accessorials( $type );
      return array(
        'accessorials' => $accessorials,
        'type' => $type
      );
    };
  }
}
Graphql_Shipping_Calculator::init();