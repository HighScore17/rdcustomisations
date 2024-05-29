<?php

class Shipstation_Package_Builder {
  /**
   * Minify multiples packages into boxes
   */
  static function minify( $packages ) {
    $packages = self::get_packages_by_category( $packages );
    $limits = self::get_limits_by_category(  );
    $boxes = array();
    foreach( $packages as $category => $packages ) {
      $box = null;
      foreach( $packages as $_package ) {
        list( $id, $package ) = $_package;
        if( $package->get_quantity() > 0 ) {
          list( $_box, $_boxes ) = self::addPackagesToBox( $package, $limits[$category], $box );
          $box = $_box;
          $boxes = array_merge( $boxes, $_boxes );
        }
        if( $package->get_boxes() > 0 ) {
          $created_boxes = self::create_box_from_boxes( $id, $package->get_boxes(), $package );
          if( !$created_boxes ) {
            return new WP_Error('invalid-shipment', 'Some items are not available for shipping.');
          }
          $boxes = array_merge( $boxes, $created_boxes );
        }
      }

      if( $box && count( $box->getItems() ) ) {
        $box->close();
        $boxes[] = $box;
      }
    }
    
    return $boxes;
  }

  static function create_box_from_boxes( $id, $_boxes = 1, $package ) {
    $rules = self::get_shipping_rules();
    $boxes = array();

    if( !isset( $rules[$id]["boxes"] ) || !is_array( $rules[$id]["boxes"] ) ) {
      return null;
    }

    $rules = $rules[$id]["boxes"];
    $remaining_boxes = $_boxes;
    ksort($rules);

    while($remaining_boxes > 0) {
      $current_rule = null;
      $current_rule_qty = 0;

      if( isset($rules[ strval($remaining_boxes)]) ) {
        $boxes[] = array_merge(
          $rules[ strval($remaining_boxes)],
          array("total" => $remaining_boxes * $package->get_price_per_box())
        );
        $remaining_boxes = 0;
      } else {
        foreach( $rules as $qty => $rule ) {
          if( $qty <= $remaining_boxes || !$current_rule) {
            $current_rule = $rule;
            $current_rule_qty = $qty;
          } else {
            break;
          }
        }
        $boxes[] = array_merge(
          $current_rule,
          array("total" => $current_rule_qty * $package->get_price_per_box())
        );
        $remaining_boxes -= ($current_rule_qty ? $current_rule_qty : 1);
      }
    }
    return self::format_boxes( $boxes, $package );
  }

  static function format_boxes( $boxes, Shipping_Common_Package $package ) {
    return array_map( function( $box ) use ($package) {
      return Box_Package::create_from_package(
        new Shipping_Common_Package(
          $package->get_id(),
          $box["weight"],
          $box["length"],
          $box["width"],
          $box["height"],
          $package->get_class(),
          $package->get_cases_per_pallet(),
          $package->get_max_cases(),
          $package->get_max_pallets(),
          $box["total"]
        ));
    }, $boxes );
  }

  /**
   * Add package items to a box
   */
  static function addPackagesToBox( $package, $limits, $box ) {
    $boxes = array();

    if( !$box ) {
      $box = new Box_Package( $limits["dimensions"], $limits["weight"], $limits["direction"] );
    }
    
    $quantity = $package->get_quantity();
    $package->set_quantity(1, -1);

    for( $i = 0; $i < $quantity; $i++ ) {
      $added = $box->addItem( $package );
      if( !$added ) {
        $box->close();
        $boxes[] = $box;
        $box = new Box_Package( $limits["dimensions"], $limits["weight"], $limits["direction"] );
        $added = $box->addItem( $package );
      }
      if( !$added ) {
        $boxes[] = Box_Package::create_from_package( $package );
      }
    }

    return array( $box, array_filter($boxes, function($box) {
      return count($box->getItems()) > 0;
    }));
  }

  static function get_packages_by_category( $packages ) {
    $categories = [];
    foreach ($packages as $key => $package) {
      list( $cat, $id ) = explode( ":", $key );
      if( ! is_array( $categories[ $cat ] ) ) {
        $categories[ $cat ] = array();
      }
      $categories[ $cat ][] = array( $id, $package );
    }
    return $categories;
  }

  static function get_limits_by_category() {
    return array(
      "glove" => array(
        "dimensions" => __vector_3D( 18, 22, 22 ),
        "weight" => 36,
        "direction" => "x",
      ),
      "glove-kingfa" => array(
        "dimensions" => __vector_3D( 22, 18, 22 ),
        "weight" => 36,
        "direction" => "y",
      ),
      "mask" => array(
        "dimensions" => __vector_3D( 21, 15, 16 ),
        "weight" => 36,
        "direction" => "x",
      )
    );
  }

  static function get_shipping_rules() {
    return array(
      "3ply-lvl3-510k" => array(
        "boxes" => array(
          "10" => self::format_shipping_rule( 12, 12, 12, 5 ),
          "20" => self::format_shipping_rule( 12, 12, 12, 10 ),
          "30" => self::format_shipping_rule( 21, 15, 12, 15 ),
          "40" => self::format_shipping_rule( 21, 15, 14, 20 ),
          "50" => self::format_shipping_rule( 21, 15, 16, 26 ),
        )
      )
    );
  }

  static function format_shipping_rule( $length, $width, $height, $weight ) {
    return array_merge(
      __vector_3D( $length, $width, $height ),
      array(
        "weight" => $weight
      )
    );
  }
}