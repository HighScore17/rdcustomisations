<?php

use GraphQL\Error\UserError;

class Shipping_Calculator_V2 {

  const SHIPMENT_TYPE_BASIC = 'basic';
  const SHIPMENT_TYPE_LTL = 'ltl';
  const SHIPMENT_TYPE_FLT = 'flt';

  private $options;

  function __construct() {
    $this->options = get_option("shipping_calculator");
  }

  public function get_rates( $items = null) {
    //$packages = Shipping_Packages_Generator::generate( $cart ? $cart : WC()->cart->get_cart() );
    if( !$items ) 
      $items = $this->cart_to_packages( WC()->cart->get_cart() );
      
    $packages = Woocommerce_Package_Builder::buildFromCart( $items );
    
    if( is_wp_error( $packages ) )
      $this->error( $packages->get_error_message() );

    if( !$packages ) {
      $this->error("Some items are not available for shipping.");
    }

    $carrier = $this->get_carrier($packages);
    $address = Shipping_Calculator_Addresses::get();
    $accessorials = isset($_POST['accessorials']) ? $_POST['accessorials'] : array();
    $rates = $carrier->do_rate_quote("", $address["from"], $address["to"], $packages, $accessorials);
    if(is_wp_error($rates)) {
      $this->error($rates->get_error_message());
    }
    return $rates;
  }

  public function cart_to_packages( $items = [] ) {
    return array_reduce($items, function($cart, $item ) {
      $cart->add_item( 
        $item['product_id'],
        $item['variation_id'],
        $item['quantity'],
        $item['line_total']
      );
      return $cart;
    }, new Woocommerce_Packages_Cart());
  }

  public function get_shipment_data( $packages ) {
    $type = $this->get_shipment_type($packages);
    $accessorials = $this->get_accessorials( $type );
    return array(
      "type" => $type,
      "accessorials" => $accessorials,
    );
  }

  private function get_accessorials($type) {
    if($type == self::SHIPMENT_TYPE_BASIC) {
      return Shipstation_Carrier::get_accessorials();
    } else if($type == self::SHIPMENT_TYPE_LTL) {
      return Estes_Express_Carrier::get_accessorials();
    } else if($type == self::SHIPMENT_TYPE_FLT) {
      return [];
    }
  }

  public function get_shipment_type( $packages ) {
    return self::SHIPMENT_TYPE_BASIC;
    //$reduced_packages = Shipping_Packages_Generator::reduce_packages_by_id( $packages );
    if( count( $packages ) > 1 ) {
      $max_parcel_weight = intval($this->options['shipping_max_ups_weight']);
      $max_ltl_pallets = intval($this->options['shipping_max_ltl_pallets']);
      $total_weight = array_reduce($packages, function($acc, $current){
        return $acc + $current->get_total_weight();
      }, 0);
      // Parcel
      if( $total_weight <= $max_parcel_weight ) {
        return self::SHIPMENT_TYPE_BASIC;
      } 
      // LTL or FLT
      $pallets_calculator = Pallets_Calculator::calculate($packages);
      //print_r($pallets_calculator->get_pallets());
      if( count( $pallets_calculator->get_pallets() ) <= $max_ltl_pallets ) {
        return self::SHIPMENT_TYPE_LTL;
      } else {
        return self::SHIPMENT_TYPE_FLT;
      }
    } else {
      $package = reset($packages);
      $max_cases = $package->get_max_cases();
      $max_pallets = $package->get_max_pallets();
      $total_cases = $package->get_quantity();
      $total_pallets = ceil($total_cases / $package->get_cases_per_pallet());
      if( $total_cases <= $max_cases ) {
        return self::SHIPMENT_TYPE_BASIC;
      } else if ( $total_pallets <= $max_pallets ) {
        return self::SHIPMENT_TYPE_LTL;
      } else {
        return self::SHIPMENT_TYPE_FLT;
      }
    }
  }

  private function get_carrier( $packages ) {
    return $this->get_basic_carrier();
    $carrier_type = $this->get_shipment_type( $packages );
    if( $carrier_type === self::SHIPMENT_TYPE_BASIC ) {
      return $this->get_basic_carrier();
    } else if( $carrier_type === self::SHIPMENT_TYPE_LTL ) {
      return $this->get_ltl_carrier();
    } else {
      return $this->get_flt_carrier();
    }
  }

  private function get_basic_carrier() {
    return new Shipstation_Carrier(array(
      'username' => $this->options["shipstation_api_key"],
      'password' => $this->options["shipstation_api_secret"],
    ));
  }

  private function get_ltl_carrier() {
    return new Estes_Express_Carrier(array(
      'user' => $this->options["estes_express_username"],
      'password' => $this->options["estes_express_password"],
      'account' => $this->options["estes_express_account"],
    ));
  }

  private function get_flt_carrier() {
    return new FLT_Carrier(array());
  }

  private function packages_has_multiple_products($packages) {
    return count( $packages ) > 1;
  }

  function error( $message ) {
    throw new UserError( $message ? $message : "An error occurred :y" );
  }
}

class Shipping_Common_Package {
  private $quantity;
  private $weight;
  private $length;
  private $width;
  private $height;
  private $class;
  private $cases_per_pallet;
  private $total_weight;
  private $max_cases;
  private $max_pallets;
  private $id;
  private $price;
  private $boxes;
  private $price_per_box;
  

  function __construct($id, $weight, $length, $width, $height, $class, $cases_per_pallet, $max_cases, $max_pallets, $price, $quantity = 1, $boxes = 0, $price_per_box = 0 ) {
    $this->id = $id;
    $this->weight = $weight;
    $this->length = $length;
    $this->width = $width;
    $this->height = $height;
    $this->class = $class;
    $this->cases_per_pallet = $cases_per_pallet;
    $this->max_cases = $max_cases;
    $this->max_pallets = $max_pallets;
    $this->price = $price;
    $this->quantity = $quantity;
    $this->total_weight = $this->weight * $this->quantity;
    $this->boxes = $boxes;
    $this->price_per_box = $price_per_box;
  }

  public function add_quantity($quantity, $boxes = 0) {
    $this->quantity += $quantity;
    $this->boxes += $boxes;
    $this->total_weight += $this->weight * $quantity;

  }

  public function set_quantity($quantity, $boxes = 0) {
    $this->quantity = $quantity;
    $this->total_weight = $this->weight * $quantity;
    if( $boxes >= 0 ) {
      $this->boxes = $boxes;
    }
  }

  public function get_id() {
    return $this->id;
  }
  
  public function get_weight() {
    return floatval( $this->weight );
  }

  public function get_length() {
    return floatval( $this->length );
  }

  public function get_width() {
    return floatval( $this->width );
  }

  public function get_height() {
    return floatval( $this->height );
  }

  public function get_class() {
    return $this->class;
  }

  public function get_cases_per_pallet() {
    return $this->cases_per_pallet;
  }

  public function get_max_cases() {
    return $this->max_cases;
  }

  public function get_max_pallets() {
    return $this->max_pallets;
  }

  public function get_quantity() {
    return intval( $this->quantity );
  }

  public function get_total_weight() {
    return floatval( $this->total_weight );
  }

  public function get_price() {
    return $this->price;
  }

  public function get_price_per_box() {
    return $this->price_per_box;
  }

  public function get_boxes() {
    return $this->boxes;
  }
}
