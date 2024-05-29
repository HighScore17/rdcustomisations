<?php
require_once __DIR__ . "/shipstation-packages/shipstation-package-builder.php";
require_once __DIR__ . "/shipstation-packages/shipstation-box.php";

class Shipstation_Carrier extends Shipping_Carrier {
  function get_url() {
    return "https://ssapi.shipstation.com/shipments/getrates";
  }

  function get_estimated_delivery_time($service) {
    $available_shipping_methods = array(
      
      "fedex_2day_am" => 2,
      "fedex_2day" => 2,
      "fedex_ground" => 5,
      "fedex_express_saver" => 3,
      "fedex_home_delivery" => 5,
      "fedex_first_overnight" => 1,
      "fedex_priority_overnight" => 1,
      "fedex_standard_overnight" => 1,
    );

    if( key_exists($service, $available_shipping_methods) ) {
      return Date('Y-m-d', strtotime('+' . ($available_shipping_methods[$service] + 1) . ' weekdays'));
    }

    return Date('Y-m-d', strtotime('+' . (14 + 1) . ' weekdays'));
  }

  function get_rate_quote_body($service_code, $ship_from, $ship_to, $package, $accessorials) {
    $body =  array(
      "carrierCode" => "fedex",
      "fromPostalCode" => $ship_from['Address']['PostalCode'],
      "toCountry" => "US",
      "toPostalCode" => $ship_to['Address']['PostalCode'],
      "weight" => array(
        "value" => $package->get_weight(),
        "units" => "pounds"
      ),
      "dimensions" => array_merge(
        $package->get_dimensions(),
        array(
          "units" => "inches"
        )
      )
    );
    if( !is_null($ship_to['Address']['ResidentialAddress']) ) {
      $body["residential"] = $ship_to['Address']['ResidentialAddress'];
    }
    if( !is_null($ship_to['Address']['City']) ) {
      $body["toCity"] = $ship_to['Address']['City'];
    }
    if( !is_null($ship_to['Address']['StateProvinceCode']) ) {
      $body["toState"] = $ship_to['Address']['StateProvinceCode'];
    }
    return $body;
  }

  /**
   * Make post request
   */
  function do_api_request( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
    $packages = Woocommerce_Shipstation_Packages::build( $packages );
    $responses = array();
    $headers = $this->get_headers();

    if( is_wp_error( $packages ) ) {
      return $packages;
    }
    
    foreach( $packages as  $package ) {
      $payload = json_encode( $this->get_rate_quote_body( $service_code, $ship_from, $ship_to, $package, $accessorials ) );
      $response = wp_remote_post( 
        $this->get_url(), 
        array(
          'headers' => $headers,
          'body' => $payload,
          'timeout' => 10,
        )
      );
      $responses[] = $response;
    }

    return array(
      "rates" => $this->format_rates($responses, $packages),
      "accessorials" => [],
      "residential_address" => $ship_to['Address']['ResidentialAddress'],
    ) ;
  }

  function format_rates( $rates, $packages ) {
    $parsed_rates =  $this->parse_rates( $rates );
    $valid_rates = $this->validate_rates_homogeneity( $parsed_rates );
    $joined_rates = $this->filter_rates( $this->join_rates( $valid_rates ) );
    return $this->format_services($joined_rates, $packages);
  }

  /**
   * Filter rates to show only the valids
   */
  function filter_rates( $rates ) {
    $overnight_current_date = new DateTime("now", new DateTimeZone('America/Toronto') );
    $overnight_min_date = clone $overnight_current_date;
    $overnight_min_date = $overnight_min_date->setTime( 10, 0, 0, 0 );

    if( $overnight_current_date > $overnight_min_date ) {
      unset( $rates["fedex_first_overnight"], $rates["fedex_priority_overnight"], $rates["fedex_standard_overnight"] );
    }
    return $rates;
  }

  /**
   * Parse the response from the API
   * @param  array $rates_responses Response returned by wp_remote_post
   * @return array  Array of rates in the format of $rate_key => $rate_value
   */
  function parse_rates( $rates_responses ) {
    $rates = array();
    foreach( $rates_responses as $rate_response ) {
      if(is_wp_error($rate_response) ||  !is_array($rate_response) || empty($rate_response["body"]) ) {
        return null;
      }

      $rate = json_decode( $rate_response["body"], true );
      foreach($rate as $service) {
        if( !$this->validate_service_properties($service, [ "serviceName", "serviceCode", "shipmentCost", "otherCost" ]) ) {
          return null;
        }
      }
      $rates[] = $this->reduce_rates($rate);
    }
    return $rates;
  }

  function reduce_rates( $rates ) {
    $reduced_rates = array();
    foreach( $rates as  $rate ) {
      $reduced_rates[$rate["serviceCode"]] = $rate;
    }
    return $reduced_rates;
  }

  function validate_service_properties( $service, $properties) {
    foreach( $properties as $property ) {
      if( !key_exists( $property, $service ) ) {
        return false;
      }
    }
    return true;
  }

  function validate_rates_homogeneity( $rates ) {
    // Count rates services
    $expected_length = count( $rates );
    $service_counts = array();
    $i = 0;
    foreach( $rates as $rate ) {
      foreach( $rate as $service_key => $service ) {
        if( !key_exists( $service_key, $service_counts ) ) {
          $service_counts[$service_key] = 0;
        }
        $service_counts[$service_key]++;
      }
    }

    // Remove services that are not homogeneous
    foreach( $service_counts as $service_key => $service_count ) {
      if( $service_count != $expected_length ) {
        foreach( $rates as $rate ) {
          unset($rates[$i][$service_key]);
          $i++;
        }
      }
    }
    return $rates;
  }

  function join_rates( $rates ) {
    $joined_rates = array();
    foreach( $rates as $rate ) {
      foreach( $rate as $service_key => $service ) {
        if( !key_exists($service_key, $joined_rates) ) {
          $joined_rates[$service_key] = array(
            "cost" => 0,
            "code" => $service["serviceCode"],
            "name" => $service["serviceName"] ,
          );
        }
        $joined_rates[$service_key]["cost"] += ($service["shipmentCost"] + $service["otherCost"]);
      }
    }
    return $joined_rates;
  }

  function format_services( $services, $packages ) {
    $common_services = array();
    foreach( $services as $service_key => $service ) {
      $common_services[] = array(
        "total" => $service["cost"],
        "service" => array(
          "code" => $service["code"],
          "description" => $service["name"]
        ),
        "estimated_delivery" => $this->get_estimated_delivery_time($service["code"]),
        "resume" => array(),
        'insurance' => $this->get_insurance_cost($packages),
      ); 
    }
    return $common_services;
  }

  private function get_insurance_cost_per_value() {
    return array(
      "cost" => 0.79,
      "per_value" => 100,
      "max_value" => 10000,
    );
  }
  
  private function get_insurance_cost( $packages ) {
    $insurance = $this->get_insurance_cost_per_value();
    $free_case_insurance = true;
    $insurance_cost = 0;
    $message = "";
    foreach( $packages as $package ) {
      $package_price = $package->get_price();
      $cost_per_case = ceil( $package_price / $insurance["per_value"] );
      if( $package_price > $insurance["max_value"] ) {
        $cost_per_case = ceil( $insurance["max_value"] / $insurance["per_value"] );
        $message = "The insurance only cover up to $". number_format($insurance["max_value"], 2 ) ." per package" ;
      }
      $insurance_cost += $cost_per_case * $insurance["cost"];
      if($free_case_insurance && $package_price <= $insurance["per_value"]) {
        $insurance_cost -= $insurance["cost"];
        $free_case_insurance = false;
      }
    }
    return array(
      "cost" => round($insurance_cost, 2),
      "message" => $message
    );
  }
}