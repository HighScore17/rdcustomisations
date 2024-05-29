<?php 
class Extes_Express_Shipping extends Shipping_Provider {
  private $is_dev;
  private $credentials;

  function __construct( $is_dev, $credentials ) {
    $this->is_dev = $is_dev;
    $this->credentials = $credentials;
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
        'commodity' => $this->format_packages( $packages )
      ),
    );
    if( count( $accessorials ) ) {
      $payload['accessorials'] = array(
        'accessorialCode' => $accessorials
      );
    }
    return $payload;
  }

  function format_packages( $packages ) {
    $packages_formated = array();
    foreach( $packages as $class => $weight ) {
      array_push( $packages_formated, array(
        'class' => $class,
        'weight' => $weight
      ) );
    }
    return $packages_formated;
  }

  function make_request( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
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
    return is_wp_error( $response ) ? $response : $this->format_response( $response );
  }

  function check_response( $response ) {
    if( !isset( $response->requestID ) || !isset( $response->quoteInfo ) ) {
      return new WP_Error('failed', 'Failed to get response');
    } 
    return $response;
  }

  function format_response( $response ) {
    if( is_wp_error( $response ) ) {
      return $response;
    }
    if( !is_array( $response->quoteInfo->quote ) ) {
      $response->quoteInfo->quote = array( $response->quoteInfo->quote );
    }
    $result = array();
    foreach( $response->quoteInfo->quote as $quote ) {
      array_push( $result, array(
        'total' => $quote->pricing->totalPrice,
        'service' => array(
          'code' => $quote->serviceLevel->id,
          'description' => $quote->serviceLevel->text
        ),
        'estimated_delivery' => $quote->delivery->date,
        'resume' => array_map( [ $this, 'get_accesorials_array' ], $quote->accessorialInfo->accessorial )
      ) );
    }
    return $result;
  }

  function get_accesorials_array( $accessorial ) {
    return array(
      'label' => $accessorial->description,
      'charge' => $accessorial->charge
    );
  }

  function validate_input( $service_code, $ship_from, $ship_to, $accessorials ) {
    if(
      $this->valid_ship( $ship_from ) &&
      $this->valid_ship( $ship_to )
    ) {
      return TRUE;
    }
    return FALSE;
  }

  function validate_packages( $packages )
  {
    if( !is_array( $packages ) ) {
      return FALSE;
    }
    foreach( $packages as $package ) {
      if ( !$this->valid_package( $package ) ) {
        return FALSE;
      }
    }
    return TRUE;
  }

  function get_accessorials() {
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

  /**
   * Start custom functions
   */
  function valid_ship( $ship )
  {
    if( !is_array( $ship ) ) {
      return FALSE;
    }
    if( 
      isset( 
        $ship["Name"], 
        $ship["Address"],
        $ship["Address"]["AddressLine"], 
        $ship["Address"]["City"],
        $ship["Address"]["StateProvinceCode"],
        $ship["Address"]["PostalCode"], 
      )
    ) {
      return TRUE;
    }
    return FALSE;
  }

  function valid_package( $package )
  {
    return TRUE;
  }

  function format_package( $package ) {
    return array(
      'PackagingType' => array(
        'Code' => $package->code,
      ),
      'Dimensions' => array(
        'UnitOfMeasurement' => array(
          'Code' => $package->dimensions_unit,
        ),
        "Length" => $package->length,
        "Width" => $package->width,
        "Height" => $package->height
      ),
      'PackageWeight' => array(
        'UnitOfMeasurement' => array(
          'Code' => $package->weight_unit
        ),
        'Weight' => $package->weight
      )
    );
  }
}