<?php
use GraphQL\Error\UserError;
class Graphql_Shipping_Calculator_V2
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
    if( !self::$instance instanceof Graphql_Shipping_Calculator_V2 ) {
      self::$instance = new Graphql_Shipping_Calculator_V2();
      self::$instance->enable();
    }
  }

  function enable()
  {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_shipping_api' ] );
  }

  function register_graphql_shipping_api()
  {
    $this->register_graphql_shipping_inputs();
    $this->register_graphql_shipping_type();
    $this->register_graphql_rates_query();
    $this->register_graphql_cart_allowed_shipping_methods();
  }

  function register_graphql_cart_allowed_shipping_methods() {
    register_graphql_field('cart', 'freeShippingAllowed', array(
      'type' => 'Boolean',
      'resolve' => function() {
        $packages = array();
        $available_packages = WC()->cart->needs_shipping()
          ? \WC()->shipping()->calculate_shipping( WC()->cart->get_shipping_packages() )
          : array();

        foreach ( $available_packages as $index => $package ) {
          $package['index'] = $index;
          $packages[] = $package;
        }

        if( !is_array($packages) || count($packages) === 0 ) {
          return false;
        }

        $allowedShipingMethods = apply_filters( 'woocommerce_package_rates', $packages[0]["rates"]);
        return !!__array_find($allowedShipingMethods, function($rate) {
          return $rate->get_method_id() === "free_shipping";
        });

      }
    ));
  }

  function register_graphql_shipping_inputs()
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
      ) 
    );
  }

  function register_graphql_shipping_type(){
    register_graphql_object_type('horizonShippingTypeAccessorials', array(
      'fields' => array(
        "id" => array(
          "type" => "String"
        ),
        "label" => array(
          "type" => "String"
        ),
      )
    ));
    register_graphql_object_type('horizonShippingTypeOutput', array(
      'fields' => array(
        "type" => array(
          "type" => "String"
        ),
        "accessorials" => array(
          "type" => array(
            "list_of" => "horizonShippingTypeAccessorials"
          )
        ),
      )
    ));

    register_graphql_field('RootQuery', 'horizonShippingType', array(
      'type' => 'horizonShippingTypeOutput',
      'resolve' => $this->shipping_type_resolver()
    ));

    register_graphql_field('RootQuery', 'horizonSingleShippingType', array(
      'args' => array(
        'variation_id' => array(
          'type' => 'Int'
        ),
        'quantity' => array(
          'type' => 'Int'
        )
      ),
      'type' => 'horizonShippingTypeOutput',
      'resolve' => $this->single_shipping_type_resolver()
    ));
  }

  function register_graphql_rates_query() {
    register_graphql_object_type('horizonShippingService', array(
      'fields' => array(
        "code" => array(
          "type" => "String"
        ),
        "description" => array(
          "type" => "String"
        ),
      )
    ));
    register_graphql_object_type('horizonShippingRate', array(
      'fields' => array(
        'total' => array(
          'type' => 'String'
        ),
        'service' => array(
          'type' => 'horizonShippingService'
        ),
        'estimated_delivery' => array(
          'type' => 'String'
        ),
      )
    ));
    
    register_graphql_object_type('horizonShippingRatesOutput', array(
      'fields' => array(
        'nodes' => array(
          'type' => array(
            'list_of' => 'horizonShippingRate'
          )
        )
      )
    ));

    register_graphql_field('RootQuery', 'horizonShippingRates', array(
      'type' => 'horizonShippingRatesOutput',
      'args' => array(
        'accessorials' => array(
          'type' => array(
            'list_of' => "String"
          )
        ),
        'isResidential' => array(
          'type' => 'Boolean'
        ),
        'shipTo' => array(
          'type' => 'shippingQuoteAddress'
        ),
      ),
      'resolve' => $this->register_graphql_shipping_rates_resolver()
    ));
    register_graphql_field('RootQuery', 'horizonSingleShippingRates', array(
      'type' => 'horizonShippingRatesOutput',
      'args' => array(
        'accessorials' => array(
          'type' => array(
            'list_of' => "String"
          )
        ),
        'zip' => array(
          'type' => 'String'
        ),
        'variation_id' => array(
          'type' => 'Int'
        ),
        'quantity' => array(
          'type' => 'Int'
        ),
      ),
      'resolve' => $this->register_graphql_single_shipping_rates_resolver()
    ));
  }

  function shipping_type_resolver() {
    return function() {
      $calculator = new Shipping_Calculator_V2();
      $items = $calculator->cart_to_packages( WC()->cart->get_cart() );
      $packages = Woocommerce_Package_Builder::buildFromCart( $items );
      return $calculator->get_shipment_data( $packages );
    };
  }

  function register_graphql_shipping_rates_resolver() {
    return function($parent, $args) {
      $to = $args['shipTo'];
      $_POST['accessorials'] = $args["accessorials"];
      $_POST['shipTo'] = array(
        'Name' => $to["name"],
        'Address' => array(
          'AddressLine' => $to["address"]["addressLine"],
          'City' => $to["address"]["city"],
          'PostalCode' => $to["address"]["postalCode"],
          'StateProvinceCode' => $to["address"]["stateProvinceCode"],
				  'ResidentialAddress' => $args['isResidential']
        )
      );
      $calculator = new Shipping_Calculator_V2();
      return array(
        "nodes" => $calculator->get_rates()["rates"]
      );
    };
  }

  function register_graphql_single_shipping_rates_resolver() {
    return function ($parent, $args) {
      $product = $args['variation_id'];
      $quantity = $args['quantity'];
      $cart = Graphql_Shipping_Calculator_V2::get_cart_from_product_id( $product, $quantity );
      $_POST['accessorials'] = $args["accessorials"];
      $_POST['shipTo'] = array(
        'Address' => array(
          'PostalCode' => $args['zip']
        ),
      );
      $calculator = new Shipping_Calculator_V2();
      $rates = $calculator->get_rates($cart)["rates"];
      return array(
        "nodes" => $rates
      );
    };

  }

  function single_shipping_type_resolver() {
    return function ($parent, $args) {
      if( !$args["variation_id"] || !$args["quantity"] ) {
        return null;
      }
      $product = wc_get_product( $args["variation_id"] );
      if(!$product) {
        return null;
      }
      $cart = Graphql_Shipping_Calculator_V2::get_cart_from_product_id($args["variation_id"], $args["quantity"]);
      $calculator = new Shipping_Calculator_V2();
      $packages = Woocommerce_Package_Builder::buildFromCart( $cart );
      return $calculator->get_shipment_data( $packages );
    };
  }

  static function get_cart_from_product_id( $variation, $quantity ) {
    $product = wc_get_product( $variation );
    $cart = new Woocommerce_Packages_Cart();
    $cart->add_item( $product->get_parent_id(), $variation, $quantity, Horizon_Product_Helper::get_b2c_price($variation, $quantity) );
    return $cart;
  }
}
Graphql_Shipping_Calculator_V2::init();