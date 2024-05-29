<?php

use GraphQL\Error\UserError;

class Shipping_Calculator {

  const UPS_SHIPPING = 'basic';
  const LTL_SHIPPING = 'ltl';
  const FLT_SHIPPING = 'flt';
  private $ups;
  private $estes_express;

  function __construct( $is_dev ) {
    $options = get_option( 'shipping_calculator' );
    // Init UPS
    $this->ups = new Shipping_UPS( $is_dev, array(
      "AccessLicenseNumber" => '',// $options['ups_api_key'],
      "Username" => '',//$options['ups_username'],
      "Password" => '',//$options['ups_password'] 
    ));
    // Init Estes Express
    $this->estes_express = new Extes_Express_Shipping( $is_dev, array(
      "user" => $options['estes_express_username'],
      "password" => $options['estes_express_password'],
      "account" => $options['estes_express_account']
    ));
  }

  function valid_config() {
    $options = get_option( 'shipping_calculator' );
    if( 
      //isset( $options['ups_api_key'] ) &&
      //isset( $options['ups_username'] ) &&
      //isset( $options['ups_password'] ) &&
      isset( $options['estes_express_username'] ) &&
      isset( $options['estes_express_password'] ) &&
      isset( $options['estes_express_account'] ) &&
      ( isset($options['shipping_max_ups_weight'] ) && intval( $options['shipping_max_ups_weight'] ) > 0 ) &&
      ( isset($options['shipping_max_ltl_weight'] ) && intval( $options['shipping_max_ltl_weight'] ) > 0 )
     ) {
       return TRUE;
     } 
    return FALSE;
  }

  /**
   * Get quote based on cart packages
   */
  function get_quotes( $cart ) {
    if( !$this->valid_config() ) {
      $this->throw_a_error("Invalid Shop configuration.");
    }
    return $this->resolve_quotes( $cart );
  }

  function resolve_quotes( $cart ) {
    $quote_data = $this->get_quote_data( $cart );
    $addresses = $this->get_addresses();
    $accessorials = $this->get_accessorials();
    $provider_accessorials = $this->get_provider_accessorials( $quote_data['type'] );
    $quote_provider = $this->get_quote_request_provider( $quote_data['type'] );
    $rates = array();

    if( $quote_provider ) {
      $packages = $this->create_packages($quote_data['type'], $quote_data['items']);//$quote_provider['packages_creator']( $quote_data['items'] );
      $rates = $quote_provider->make_rate_quote( NULL, $addresses['from'], $addresses['to'], $packages, $accessorials  );
    } else {
      print_r($quote_provider);
      $rates = $this->get_flt_response();
    }
    return $this->format_rates_names( $rates, $provider_accessorials, $accessorials );
  }

  /**
   * This function will add estimated delivery date and accessorials to the rate name to get that data in the woocommerce native shipping rates
   */
  function format_rates_names( $rates, $provider_accessorials, $selected_accessorials ) {
    $formated_rates = array();
    foreach( $rates as $rate ) {
      $rate["service"]["description"] .= " ~ " . $rate["estimated_delivery"] . " ~ ";
      foreach( $provider_accessorials as $accessorial ) {
        if( in_array( $accessorial["id"], $selected_accessorials ) ) {
          $rate["service"]["description"] .= $accessorial["label"] . ", ";
        }
      }
      array_push( $formated_rates, $rate );
    }
    return $formated_rates;
  }

  /**
   * Default FLT rate
   */
  function get_flt_response() {
    return array(
      array(
        'total' => '0.0',
        'service' => array(
          'code' => 'flt',
          'description' => 'FLT Cost not included',
        ),
        'estimated_delivery' =>  Date('Y-m-d', strtotime('+35 days'))
      )
    );
  }

  /**
   * Get rate provider based on packages content
   */
  function get_quote_request_provider( $type ) {
    $shipping_provider = NULL;
    switch( $type ) {
      case 'basic':
        $shipping_provider = $this->ups;
        break;
      case 'ltl':
        $shipping_provider = $this->estes_express;
        break;
      default:
        break;
    }
    return $shipping_provider;
  }

  function get_quote_data( $cart ) {
    $items = $this->get_cart_data( $cart );
    return array(
      'items' => $items,
      'type' => $this->get_shipment_type( $items )
    );
  }

  public function get_cart_data( $cart ) {
    $items = array();
    foreach( $cart["contents"] as $content_key => $content ) {
      $this->get_product_data( $items, $content );
    }
    return $items;
  }

  public function get_cart_data_legacy( $cart ) {
    $items = array();
    foreach( $cart as $variation ) {
      $this->get_product_data( $items, $variation );
    }
    return $items;
  }
  
  public function get_shipment_type( $items ) {
    if( $this->is_multiple_products( $items ) ) {
      $options = get_option( 'shipping_calculator' );
      $max_ups = intval($options['shipping_max_ups_weight']);
      $max_ltl = intval($options['shipping_max_ltl_weight']);
      $total_weight = 0;
      foreach( $items as $item ) {
        $total_weight += $item['weight'];
      }
      if( $total_weight <= $max_ups ) {
        return 'basic';
      } else if( $total_weight <= $max_ltl ) {
        return 'ltl';
      } else {
        return 'flt';
      }
    } else {
      $cases = 0; $max_cases = 1;
      $pallets = 0; $max_pallets = 1;
      foreach( $items as $product ) {
        $cases += count( $product['cases'] );
        $pallets += $product['pallets'];
        $max_cases = $product['max_cases'];
        $max_pallets = $product['max_pallets'];
      }
      if( $cases <= $max_cases ) {
        return 'basic';
      } else if ( $pallets <= $max_pallets ) {
        return 'ltl';
      } else {
        return 'flt';
      }
    }
  }

  function is_multiple_products( $items ) {
    if( !is_array( $items ) ) {
      $this->throw_a_error("Invalid cart");
    }
    $current_id = -1;
    $is_single = TRUE;
    $i = 0; 
    $max = count($items);
    
    while( $is_single && $i < $max ) {
      $item = $items[$i];
      if( $item['parent_id'] !== $current_id ) {
        if( $current_id === -1 ) {
          $current_id = $item['parent_id'];
        }else {
          $is_single = FALSE;
        }
      }
      $i++;
    }
    return !$is_single;
  }

  function get_product_data( &$items,  $item ) {
    $product = wc_get_product( $item["variation_id"] );
    $parent = wc_get_product( $product->get_parent_id() );
    $quantity = intval( $item["quantity"] );
    $items_per_case = intval( $parent->get_meta("shipment_case_items") );
    $cases_per_pallets = intval($parent->get_meta('shipment_case_per_pallets'));
    $product_has = intval( $product->get_meta("contains_item") ) * $quantity;
    $cases_number = $product_has / $items_per_case;
    $product_cases = array();
    $total_weight = 0;

    if( !$this->valid_product( $product, $parent ) ) {
      $this->throw_a_error("Some products are invalid.");
    }
    for( $i = 0; $i < $cases_number; $i++ ) {
      array_push( $product_cases, array(
        'id' => $item['id'],
        'cases_per_pallet' => $parent->get_meta('shipment_case_per_pallets'),
        'length' => $parent->get_meta('shipment_dimensions_length'),
        'width' => $parent->get_meta('shipment_dimensions_width'),
        'height' => $parent->get_meta('shipment_dimensions_height'),
        'weight' => $parent->get_meta('shipment_dimensions_weight'),
        'class' => $parent->get_meta('shipment_case_class'),
      ));
      $total_weight += intval( $parent->get_meta('shipment_dimensions_weight') );
      if($i > 10000) {
        $this->throw_a_error("Some products are invalid.");
      }
    }
    array_push( $items, array(
      'id' => $item["id"],
      'parent_id' => $product->get_parent_id(),
      'pallets' => count( $product_cases ) / $cases_per_pallets,
      'weight' => $total_weight,
      'class' => $parent->get_meta('shipment_case_class'),
      'cases' => $product_cases,
      'max_cases' => $parent->get_meta('shipment_max_ups'),
      'max_pallets' => $parent->get_meta('shipment_max_pallets'),
    ) );
  }

  function valid_product( $product, $parent ) {
    if( 
      ( ( $contains = $product->get_meta( 'contains_item' )) !== NULL && intval( $contains ) > 0  ) &&
      ( ( $length = $parent->get_meta( 'shipment_dimensions_length' )) !== NULL && intval( $length ) > 0  ) &&
      ( ( $width = $parent->get_meta( 'shipment_dimensions_width' )) !== NULL && intval( $width ) > 0  ) &&
      ( ( $height = $parent->get_meta( 'shipment_dimensions_height' )) !== NULL && intval( $height ) > 0  ) &&
      ( ( $weight = $parent->get_meta( 'shipment_dimensions_weight' )) !== NULL && intval( $weight ) > 0  ) &&
      ( ( $class = $parent->get_meta( 'shipment_case_class' )) !== NULL ) &&
      ( ( $case_items = $parent->get_meta( 'shipment_case_items' )) !== NULL && intval( $case_items ) > 0  ) &&
      ( ( $case_per_pallet = $parent->get_meta( 'shipment_case_per_pallets' )) !== NULL && intval( $case_per_pallet ) > 0  ) &&
      ( ( $max_ups = $parent->get_meta( 'shipment_max_ups' )) !== NULL && intval( $max_ups ) > 0  ) &&
      ( ( $max_ups = $parent->get_meta( 'shipment_max_pallets' )) !== NULL && intval( $max_ups ) > 0  )
    ) {
      return TRUE;
    }
    return FALSE;
  }

  function throw_a_error( $message ) {
    throw new UserError( $message );
  }

  function create_packages( $type, $products ) {
    if( $type === self::UPS_SHIPPING ) {
      return $this->create_ups_packages( $products );
    } else if ( $type === self::LTL_SHIPPING ) {
      return $this->create_estes_express_packages( $products );
    } else {
      return array();
    }
  }

  function create_ups_packages( $products )
  {
    $packages = array();
    foreach( $products as $product ) {
      foreach( $product['cases'] as $case ) {
        $package = new Shipping_Package();
        $package->code = "02";
        $package->length = $case["length"];
        $package->width = $case["width"];
        $package->height = $case["height"];
        $package->weight = $case["weight"];
        $package->dimensions_unit = "IN";
        $package->weight_unit = "LBS";
        array_push( $packages, $package );
      }
    }
    return $packages;
  }

  function create_shipstation_package($products) {
    $packages = array();
    foreach( $products as $product ) {
      foreach( $product['cases'] as $case ) {
        $package = new Shipping_Package();
        $package->length = $case["length"];
        $package->width = $case["width"];
        $package->height = $case["height"];
        $package->weight = $case["weight"];
        $package->dimensions_unit = "inches";
        $package->weight_unit = "pounds";
        array_push( $packages, $package );
      }
    }
    return $packages;
  }

  function create_estes_express_packages( $products ) {
    $packages = array();
    foreach( $products as $product ) {
      if( !isset( $packages[ $product['class'] ] )) {
        $packages[ $product['class'] ] = 0;
      } 
      $packages[ $product['class'] ] += $product['weight'];
    }
    return $packages;
  }

  function get_addresses() {
    return array(
      'from' => $this->get_from_address(),
      'to' => $this->get_to_address()
    );
  }

  function get_from_address() {
    $options = get_option( 'shipping_calculator' );
    return array(
      'Name' => $options['shipper_name'],
      'Address' => array(
        'AddressLine' => $options['shipper_address'],
        'City' => $options['shipper_city'],
        'PostalCode' => $options['shipper_postcode'],
        'StateProvinceCode' => $options['shipper_state'],
      )
    );
  }

  function get_to_address() {
    return $_POST["shipTo"];
  }

  function get_accessorials() {
    if( isset($_POST["accessorials"]) && is_array($_POST["accessorials"]) ) {
      return $_POST["accessorials"];
    }
    return array();
  }

  function get_provider_accessorials( $type ) {
    if( $type === self::UPS_SHIPPING ) {
      return $this->ups->get_accessorials();
    } else if ( $type === self::LTL_SHIPPING ) {
      return $this->estes_express->get_accessorials();
    } else {
      return array();
    }
  }
}