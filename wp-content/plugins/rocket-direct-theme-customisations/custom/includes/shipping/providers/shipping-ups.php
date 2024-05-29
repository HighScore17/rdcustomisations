<?php
class Shipping_UPS extends Shipping_Provider {
  private $is_dev;
  private $credentials;

  function __construct( $is_dev, $credentials ) {
    $this->is_dev = $is_dev;
    $this->credentials = $credentials;
  }

  public function get_allowed_services() {
    return array(
      '01' => 'Next Day Air',
      '02' => '2nd Day Air',
      '03' => 'Ground',
      '12' => '3 Day Select',
      '13' => 'Next Day Air Saver',
      '14' => 'UPS Next Day Air Early',
      '59' => '2nd Day Air A.M.',
    );
  }

  function get_estimated_delivery_time( $service ) {
    $estimated = '';
    $delay_shipping = 1;
    switch( $service ) {
      case "01":
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 1) . ' weekdays'));
        break;
      case "02":
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 2) . ' weekdays'));
        break;
      case "03":
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 5) . ' weekdays'));
        break;
      case "12":
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 3) . ' weekdays'));
        break;
      case "13":
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 1) . ' weekdays'));
        break;
      case "14":
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 1) . ' weekdays'));
        break;
      case "59":
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 2) . ' weekdays'));
        break;
      default:
        $estimated = Date('Y-m-d', strtotime('+' . ($delay_shipping + 30).  ' weekdays'));
    }
    return $estimated;
  }

  function get_url() {
    if( $this->is_dev ) {
      return "https://wwwcie.ups.com/ship/v1801/rating/Rate";
    } else {
      return "https://onlinetools.ups.com/ship/v1801/rating/Rate";
    }
  }

  function get_headers() {
    return array(
      "AccessLicenseNumber" => $this->credentials["AccessLicenseNumber"],
      "Username" => $this->credentials["Username"],
      "Password" => $this->credentials["Password"],
      "Expect" => "",
      "Content-Type" => "application/json"
    );
  }

  function get_rate_quote_body( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
    return array(
      'RateRequest' => array(
        'Shipment' => array(
          'Shipper' => array(
            'Name' => $ship_from['Name'],
            'Address' => array(
              'AddressLine' => $ship_from['Address']['AddressLine'],
              'City' => $ship_from['Address']['City'],
              'StateProvinceCode' => $ship_from['Address']['StateProvinceCode'],
              'PostalCode' => $ship_from['Address']['PostalCode'],
              'CountryCode' => 'US'
            )
          ),
          'ShipFrom' => array(
            'Name' => $ship_from['Name'],
            'Address' => array(
              'AddressLine' => $ship_from['Address']['AddressLine'],
              'City' => $ship_from['Address']['City'],
              'StateProvinceCode' => $ship_from['Address']['StateProvinceCode'],
              'PostalCode' => $ship_from['Address']['PostalCode'],
              'CountryCode' => 'US'
            )
          ),
          'ShipTo' => array(
            'Name' => $ship_to["Name"],
            'Address' => array(
              'AddressLine' => $ship_to['Address']["AddressLine"],
              'City' => $ship_to['Address']["City"],
              'StateProvinceCode' => $ship_to['Address']["StateProvinceCode"],
              'PostalCode' => $ship_to['Address']["PostalCode"],
              'CountryCode' => 'US'
            )
          ),
          'Service' => array(
            'Code' => strval($service_code),
            'Description' => $this->get_allowed_services()[$service_code]
          ),
          'Package' => array_map( [ $this, 'format_package' ], $packages )
        )
      )
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

  function check_response( $responses ) {
    $new_responses = array();
    foreach ( $responses as $response ) {
      $data = is_wp_error( $response ) ? array() : json_decode( $response['body'], TRUE );
      if( is_wp_error( $data ) ) {
        array_push( $new_responses, $response );
      }
      else if( isset( $data["response"]["errors"][0] ) ) {
        $error = $data["response"]["errors"][0];
        array_push( $new_responses, new WP_Error( $error["code"], $error["message"] ) );
      }
      else if( 
        !isset( $data["RateResponse"]["Response"]["ResponseStatus"]["Code"] ) ||
        $data["RateResponse"]["Response"]["ResponseStatus"]["Code"] !== "1"
      ) {
        array_push( $new_responses, new WP_Error( "error", "Failed to parse response" ) );
      }else{
        array_push( $new_responses, $data );
      }
    }
    return $new_responses;
  }

  function format_response( $responses ) {
    $results = array();
    foreach( $responses as $response ) {
      if( is_wp_error( $response ) ) {
        array_push( $results, array(
          'error' => $response->get_error_message(),
        ) );
      } else {
        $rated = $response["RateResponse"]["RatedShipment"];
        array_push( $results, array(
          "total" => floatval($rated["TotalCharges"]["MonetaryValue"]),
          "service" => array(
            "code" => $rated["Service"]["Code"],
            "description" => $this->get_allowed_services()[$rated["Service"]["Code"]]
          ),
          "estimated_delivery" => $this->get_estimated_delivery_time($rated["Service"]["Code"]),
          "resume" => array(
            array(
              "label" => "Base Service",
              "charge" => floatval($rated["BaseServiceCharge"]["MonetaryValue"])
            ),
            array(
              "label" => "Transportation",
              "charge" => floatval($rated["TransportationCharges"]["MonetaryValue"])
            ),
            array(
              "label" => "Service Options",
              "charge" => floatval($rated["ServiceOptionsCharges"]["MonetaryValue"])
            ),
          ) )
          );
      }
    }
    return $results;
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

  function get_valid_dimensions_units()
  {
    return array(
      'IN',
      'CM',
    );
  }
  function get_valid_weight_units()
  {
    return array(
      'LBS',
      'KGS',
    );
  }

  function valid_package( $package )
  {
    if(
      $package instanceof Shipping_Package &&
      ( isset( $package->dimensions_unit ) && in_array( $package->dimensions_unit, $this->get_valid_dimensions_units() ) ) &&
      ( isset( $package->weight_unit ) && in_array( $package->weight_unit, $this->get_valid_weight_units() ) ) &&
      ( isset( $package->length ) && intval( $package->length )  > 0 && strlen($package->length) <= 6 ) &&
      ( isset( $package->width ) && intval( $package->width )  > 0 && strlen($package->width) <= 6 ) &&
      ( isset( $package->height ) && intval( $package->height )  > 0 && strlen($package->height) <= 6 ) &&
      ( isset( $package->weight ) && intval( $package->weight )  > 0 && strlen($package->weight) <= 6 )
    ) {
      return TRUE;
    }
    return FALSE;
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