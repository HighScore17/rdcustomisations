<?php

class Estes_Express_Carrier extends Shipping_Carrier {
  
  function __construct( $credentials, $config = [ "sandbox" => false ] ) {
    $this->credentials = $credentials;
    $this->config = $config;
  }

  function get_url() {
    return "https://www.estes-express.com/tools/rating/ratequote/v4.0/services/RateQuoteService?wsdl";
  }

  function get_headers() {
    return array(
      "user" => $this->credentials["user"],
      "password" => $this->credentials["password"],
    );
  }

  function do_api_request( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
      return array(
        "accessorials" => array(),
        "residential_address" => $ship_to['Address']['ResidentialAddress'],
        "rates" => array(
          array(
            'total' => '0.0',
            'service' => array(
              'code' => 'ltl',
              'description' => 'LTL Cost not included',
            ),
            'estimated_delivery' =>  Date('Y-m-d', strtotime('+35 days'))
          )
        )
      );
    $headers = $this->get_headers();
    $payload =$this->get_rate_quote_body( $service_code, $ship_from, $ship_to, $packages, $accessorials );
    $client = new SoapClient(
      $this->get_url(),
      array(
        "trace" => true,
        "exceptions" => true,
        "connection_timeout" => 5,
        "features" => SOAP_WAIT_ONE_WAY_CALLS,
        "cache" => WSDL_CACHE_NONE
      )
    );
    // Set headers
    $header = new SoapHeader( 'http://ws.estesexpress.com/ratequote' , 'auth', $headers);
    $client->__setSoapHeaders( $header );
    //Make request
    try{
      $response = $client->getQuote($payload);
    }
    catch( SoapFault $e) {
      return new WP_Error( $e->getCode(), $e->getMessage() );
    }
    unset( $client );
    $response = $this->check_response( $response );
    return is_wp_error( $response ) ? $response : array(
      'accessorials' => $this->get_accessorials_names($accessorials),
      'rates' => $this->format_response( $response, $this->get_packages_value($packages) ),
      "residential_address" => $ship_to['Address']['ResidentialAddress'],
    );
  }

  function get_rate_quote_body( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
    $payload = array(
      'requestID' => uniqid(),
      'account' => $this->credentials['account'],
      'originPoint' => array(
        'countryCode' => 'US',
        'postalCode' => $ship_from["Address"]["PostalCode"],
      ),
      'destinationPoint' => array(
        'countryCode' => 'US',
        'postalCode' => $ship_to["Address"]["PostalCode"],
      ),
      'payor' => 'S',
      'terms' => 'C',
      'stackable' => 'N',
      'baseCommodities' => array(
        'commodity' => $this->get_packages_payload( $packages )
      ),
    );
    if( count( $accessorials ) ) {
      $payload['accessorials'] = array(
        'accessorialCode' => $accessorials
      );
    }
    return $payload;
  }

  function get_packages_payload( $packages ) {
    $packages_by_class = $this->categorize_packages_by_class( $packages );
    $packages_formated = array();
    foreach( $packages_by_class as $class => $package_weight ) {
      array_push( $packages_formated, array(
        'class' => $class,
        'weight' => $package_weight
      ) );
    }
    return $packages_formated;
  }

  function categorize_packages_by_class( $packages ) {
    $packages_by_class = array();
    foreach( $packages as $package_key => $package ) {
      $class = $package->get_class();
      if( !isset( $packages_by_class[$class] ) ) {
        $packages_by_class[$class] = 0;
      }
      $packages_by_class[$class] += $package->get_total_weight();
    }
    return $packages_by_class;
  }

  function check_response( $response ) {
    if( !isset( $response->requestID ) || !isset( $response->quoteInfo ) ) {
      return new WP_Error('failed', 'Failed to get response');
    } 
    return $response;
  }

  function format_response( $response, $packages_value = 0.0 ) {
    if( is_wp_error( $response ) ) {
      return $response;
    }
    if( !is_array( $response->quoteInfo->quote ) ) {
      $response->quoteInfo->quote = array( $response->quoteInfo->quote );
    }
    $result = array();
    foreach( $response->quoteInfo->quote as $quote ) {
      $total_value = $quote->pricing->totalPrice + $packages_value;
      array_push( $result, array(
        'total' => $quote->pricing->totalPrice,
        'service' => array(
          'code' => $quote->serviceLevel->id,
          'description' => $quote->serviceLevel->text
        ),
        'estimated_delivery' => $quote->delivery->date,
        'insurance' => array(
          "cost" => round($this->get_insurance_cost( $total_value ), 2),
          "message" => $this->get_insurance_message( $total_value )
        ),
      ) );
    }
    return $result;
  }

  private function get_insurance_message( $total_price ) {
    $insurance = $this->get_insurance_cost_per_value();
    if($total_price > $insurance['max_value']) {
      return "The Insurance only covers up to $100,000 order value.";
    }
    return "";
  }

  private function get_packages_value( $packages ) {
    return array_reduce( $packages, function($acc, $package) {
      return $acc + $package->get_price() * $package->get_quantity();
    }, 0.0 );
  }


  private function get_accessorials_names( $accessorials ) {
    $accessorials_names = array();
    $accessorials_codes = $this->get_accessorials_codes();
    foreach( $accessorials as $accessorial ) {
      $accessorials_names[] = $accessorials_codes[$accessorial];
    }
    return $accessorials_names;
  }

  private function get_accessorials_codes( ) {
    $accessorials_codes = array();
    foreach( Estes_Express_Carrier::get_accessorials() as $accessorial ) {
      $accessorials_codes[$accessorial["id"]] = $accessorial["label"];
    }
    return $accessorials_codes;
  }

  static function get_accessorials() {
    return array();
    return array(
      array(
        'label' => 'Residential Delivery',
        'id' => 'HD'
      ),
      array(
        'label' => 'Inside Delivery',
        'id' => 'INS'
      ),
      array(
        'label' => 'Lift-Gate Service (Delivery)',
        'id' => 'LGATE'
      ),
      array(
        'label' => 'Notify Request',
        'id' => 'NCM'
      ),
      array(
        'label' => 'Appointment Request',
        'id' => 'APT'
      ),
      array(
        'label' => 'In-Bond Service',
        'id' => 'INB'
      ),
    );
  }

  function get_insurance_cost_per_value() {
    return array(
      "cost" => 0.65,
      "per_value" => 100,
      "max_value" => 100000,
      "minium_cost" => 55,
    );
  }

  private function get_insurance_cost( $total_value ) {
    $insurance = $this->get_insurance_cost_per_value();
    if($total_value <= 10000) {
      return 0.0;
    }
    if($total_value > $insurance['max_value']) {
      $total_value = $insurance['max_value'];
    }
    $cost = ($total_value / $insurance["per_value"]) * $insurance["cost"];
    return $cost >= $insurance["minium_cost"] ? $cost : $insurance["minium_cost"];
  }

  private function get_total_weight( $packages ) {
    return array_reduce( $packages, function( $acc, $package ) {
      return $acc + $package->get_total_weight();
    }, 0);
  }
}