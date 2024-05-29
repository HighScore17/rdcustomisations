<?php

  class UPS_API_Quote{
    public static $instance;
    
    public static function start()
    {
      if( !self::$instance instanceof UPS_API_Quote ) {
        self::$instance = new UPS_API_Quote();
        self::$instance->init();
      }
    }

    function init()
    {
      add_action( 'graphql_register_types', [ $this, 'register_graphql_api' ] );
    }

    function register_graphql_api()
    {
      register_graphql_object_type('UPSCharge', [
        'fields' => array(
            'CurrencyCode' => array(
              'type' => 'String'
            ),
            'MonetaryValue' => array(
              'type' => 'String'
            ),
        )
      ]);
      register_graphql_object_type('UPSRateQuote', [
        'fields' => array(
            'serviceCode' => array(
              'type' => 'String'
            ),
            'transportationCharges' => array(
              'type' => 'UPSCharge'
            ),
            'baseServiceCharge' => array(
              'type' => 'UPSCharge'
            ),
            'serviceOptionsCharges' => array(
              'type' => 'UPSCharge'
            ),
            'totalCharges' => array(
              'type' => 'UPSCharge'
            ),
        )
      ]);
      register_graphql_object_type('UPS', [
        'fields' => array(
            'quote' => array(
              'type' => 'UPSRateQuote'
            )
        )
      ]);
      register_graphql_input_type('UPSAddress', array(
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
      register_graphql_input_type('UPSShipment', array(
        'fields' => array(
          'name' => array(
            'type' => 'String',
          ),
          'address' => array(
            'type' => 'UPSAddress'
          )
        )
      ) );

      register_graphql_field('RootQuery', 'ups', array(
        'type' => 'UPS',
        'args' => array(
          'shipTo' => array(
            'type' => 'UPSShipment',
            'required' => true
          )
        ),
        'resolve' => function( $source, $args, $context, $info )
        {
          $upsRate = $this->get_rate_quote( $args );
          if( isset( $upsRate["response"], $upsRate["response"]["errors"] ) ) {
            throw new UserError( __( $upsRate["response"]["errors"]["message"], "woocommerce" ) );
          }
          $rated_shipment =$upsRate["RateResponse"]["RatedShipment"];
          return array(
            'quote' => array(
              'serviceCode' => $rated_shipment["Service"]["Code"],
              'totalCharges' => array(
                'currencyCode' => $rated_shipment["TotalCharges"]["CurrencyCode"],
                'monetaryValue' => $rated_shipment["TotalCharges"]["MonetaryValue"]
              )
            )
          );
        }
      ));
    }

    function get_rate_quote( $args )
    {
      $shipTo = $args["shipTo"];
      $body = array(
        'RateRequest' => array(
          'Shipment' => array(
            'Shipper' => array(
              'Name' => "Rocket Direct",
              'Address' => array(
                'AddressLine' => '138 Main Street',
                'City' => 'Kenbridge',
                'StateProvinceCode' => 'VA',
                'PostalCode' => '23944',
                'CountryCode' => 'US'
              )
            ),
            'ShipFrom' => array(
              'Name' => "Rocket Direct",
              'Address' => array(
                'AddressLine' => '138 Main Street',
                'City' => 'Kenbridge',
                'StateProvinceCode' => 'VA',
                'PostalCode' => '23944',
                'CountryCode' => 'US'
              )
            ),
            'ShipTo' => array(
              'Name' => $shipTo["name"],
              'Address' => array(
                'AddressLine' => $shipTo["address"]["addressLine"],
                'City' => $shipTo["address"]["city"],
                'StateProvinceCode' => $shipTo["address"]["stateProvinceCode"],
                'PostalCode' => $shipTo["address"]["postalCode"],
                'CountryCode' => 'US'
              )
            ),
            'Service' => array(
              'Code' => '03',
              'Description' => 'Ground'
            ),
            'Package' => array(
              array(
                'PackagingType' => array(
                  'Code' => '02',
                ),
                'Dimensions' => array(
                  'UnitOfMeasurement' => array(
                    'Code' => 'IN'
                  ),
                  "Length" => "33",
                  "Width" => "32",
                  "Height" => "4"
                ),
                'PackageWeight' => array(
                  'UnitOfMeasurement' => array(
                    'Code' => 'LBS'
                  ),
                  'Weight' => '35'
                )
              )
            )
          )
        )
      );
      $http_args = array(
        'headers' => array(
          'AccessLicenseNumber' => '8D98512EC3425815',
          'Username' => 'Rocketdist',
          'Password' => '4303russel!'
        ),
        'body' => json_encode($body)
      );
      $response = wp_remote_post("https://wwwcie.ups.com/ship/v1801/rating/Rate", $http_args);
      if( !is_wp_error( $response ) )
      {
        return json_decode( $response['body'], TRUE );
      }
      return null;
    }
  }
  UPS_API_Quote::start();

?>