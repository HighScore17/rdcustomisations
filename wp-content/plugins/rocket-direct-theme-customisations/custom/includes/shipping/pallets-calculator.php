<?php

class Pallets_Calculator {
  private $height = 80;
  private $width = 40;
  private $length = 48;

  private $available_height = 80;
  private $available_width = 40;
  private $available_length = 48;

  private $pallets = array();
  private $packages = array();
  private $not_full_pallet = null;

  private function calculate_volument() {
    return $this->height * $this->width * $this->length;
  }

  static function calculate($packages) {
    $calculator = new Pallets_Calculator();
    foreach($packages as $package) {
      $calculator->add_to_pallet($package);
    }
    $one = $calculator->add_leftover_packages( $calculator->width, $calculator->length, $calculator->height );
    $two = $calculator->add_leftover_packages( $calculator->length, $calculator->width, $calculator->height );
    $calculator->pallets = array_merge( $calculator->pallets, count($one) < count($two) ? $one : $two );
    return $calculator;
  }

  public function get_data() {
    return array(
      "pallets" => $this->pallets,
      "packages" => $this->packages,
    );
  }

  public function get_pallets() {
    return $this->pallets;
  }

  private function add_to_pallet( Shipping_Common_Package $package ) {
    $pallets = $package->get_quantity() / $package->get_cases_per_pallet();
    $full_pallets_quantity = floor($pallets);
    $cases_quantity = $package->get_quantity() - ( $package->get_cases_per_pallet() * $full_pallets_quantity );

    for($i = 0; $i < $full_pallets_quantity; $i++) {
      $this->pallets[] = new Pallet_Item($this->height, $this->width, $this->length, $package->get_weight() * $package->get_cases_per_pallet());
    }

    if($cases_quantity > 0) {
      $leftover_package = clone $package;
      $leftover_package->set_quantity($cases_quantity);
      $this->packages[] = $leftover_package;
    }
  }

  private function add_leftover_packages( $width, $length, $height ) {
    $pallets = [];
    $pallet = new Pallet_Item($height, $width, $length, 0);
    foreach( $this->packages as $package ) {
      for($i = 0; $i < $package->get_quantity(); $i++) {
        if( !$pallet->add_case( $package->get_length(), $package->get_width(), $package->get_height() ) ) {
          $pallets[] = $pallet;
          $pallet = new Pallet_Item($height, $width, $length, 0);
          $pallet->add_case( $package->get_length(), $package->get_width(), $package->get_height() );
        }
      }
      
    }
    $pallets[] = $pallet;
    return $pallets;
  }
}

class Pallet_Item {
  private $height;
  private $width;
  private $length;
  private $weight;
  private $available_height;
  private $available_width;
  private $available_length;
  private $cases = [];

  private $column_length = 0;
  private $column_height = 0;

  function __construct( $height, $width, $length, $weight ) {
    $this->height = $height;
    $this->width = $width;
    $this->length = $length;
    $this->available_height = $height;
    $this->available_width = $width;
    $this->available_length = $length;
    $this->weight = $weight;
  }

  function add_case( $length, $width, $height, $weight = 0 ) {
      if( $this->available_width >= $width ) {
        $this->add_case_in_a_new_column( $length, $width, $height);
      } else if( $this->available_length >= $length ) {
        $this->add_case_in_a_new_row( $length, $width, $height);
      } else if( $this->available_height >= $height ) {
        $this->add_case_in_a_new_layer( $length, $width, $height);
      } else {
        return false;
      }
      return true;
  }

  private function add_case_in_a_new_column( $length, $width, $height ) {
    if($length > $this->column_length) {
      $this->available_length -= ($length - $this->column_length);
      $this->column_length = $length;
    }
    if($height > $this->column_height) {
      $this->available_height -= ($height - $this->column_height);
      $this->column_height = $height;
    }
    $this->add_package( $length, $width, $height, $this->width - $this->available_width, $this->length - ($this->available_length + $this->column_length), $this->height - ($this->available_height + $this->column_height) );
    $this->available_width -= $width;
  }

  private function add_case_in_a_new_row( $length, $width, $height ) {
    if($height > $this->column_height) {
      $this->column_height = $height;
    }
    $this->column_length = $length;
    $this->available_length -= $this->column_length;
    $this->available_width = $this->width - $width;
    $this->add_package( $length, $width, $height, 0, $this->length - ($this->available_length + $this->column_length),$this->height - ($this->available_height + $this->column_height));
  }

  private function add_case_in_a_new_layer( $length, $width, $height ) {
    $this->available_height -= $height;
    $this->available_width = $this->width - $width;
    $this->available_length = $this->length - $length;
    $this->column_length = $length;
    $this->column_height = $height;
    $this->add_package( $length, $width, $height, 0, 0, $this->height - $this->available_height - $height );
  }

  function add_package( $length, $width, $height, $x, $y, $z ) {
    $this->cases[] = array (
      "width" => $width,
      "height" => $height,
      "length" => $length,
      "x" => $x,
      "y" => $y,
      "z" => $z,
    );
  }

  function reduce_case_size( $width, $length ) {
    $this->available_width -= $width;
    $this->available_length -= $length;
  }

  function add_new_row( $height ) {
    $this->available_height -= $height;
    $this->available_width = $this->width;
    $this->available_length = $this->length;
  }

}

class Pallet_Row {
  public $width;
  public $available_length;

  public function __construct( $width, $length ) {
    $this->width = $width;
    $this->available_length = $length;
  }

  function can_add_item(  ) {

  }
}