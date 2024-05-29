<?php

class ShipStation_Box_Package {
  private $master_dimensions = array();
  private $box_dimensions = array();
  private $column_dimensions = array();
  private $items = array();
  private $max_weight = 0;
  private $x = 0;
  private $y = 0;
  private $canAdd = true;
  private $direction = "x";


  function __construct( $dimensions, $max_weight, $direction = "x" ) {
    $this->master_dimensions = $dimensions;
    $this->max_weight = $max_weight;
    $this->direction = $direction;
    $this->box_dimensions = __vector_3D();
    $this->column_dimensions = __vector_3D();
  }

  /**
   * Add a package to the top of the box if the remaining height isn't exceded
   */
  private function addItemAtTop( $package ) {
    if( $this->direction === "x" ) {
      if(
        $package->get_length() > $this->master_dimensions["length"] ||
        ($this->box_dimensions["width"] + $package->get_width()) > $this->master_dimensions["width"] ||
        ($this->column_dimensions["height"] + $package->get_height()) > $this->master_dimensions["height"]
      ) {
        return false;
      }
    } else {
      if(
        $package->get_width() > $this->master_dimensions["width"] ||
        ($this->box_dimensions["length"] + $package->get_length()) > $this->master_dimensions["length"] ||
        ($this->column_dimensions["height"] + $package->get_height()) > $this->master_dimensions["height"]
      ) {
        return false;
      }
    }
    

    if( $this->column_dimensions["length"] < $package->get_length() ) {
      $this->column_dimensions["length"] = $package->get_length();
    }

    if( $this->column_dimensions["width"] < $package->get_width() ) {
      $this->column_dimensions["width"] = $package->get_width();
    }

    $this->column_dimensions["height"] += $package->get_height();
    $this->items[] = array(
      "x" => $this->x,
      "y" => $this->y,
      "package" => $package
    );
    $this->y++;
    return true;
  } 

  /**
   * Add a item to the box if there is enought space and the weight ins't exceded
   */
  public function addItem($package) {
    if( !$this->canAdd || ($package->get_total_weight() + $this->get_weight()) > $this->max_weight ) {
      return false;
    }

    if( !$this->addItemAtTop( $package ) ) {
      $this->close_column();
      return $this->addItemAtTop( $package );
    }

    return true;
  }

  /**
   * Create a single box from one package
   */
  static function create_from_package( Common_Shipping_Package $package ) {
    $box = new ShipStation_Box_Package( __vector_3D( $package->get_length(), $package->get_width(), $package->get_height() ), $package->get_total_weight());
    $box->addItem( $package );
    $box->close_column();
    return $box;
  }  

  /**
   * Close the current box Column
   */
  public function close_column() {
    if( $this->direction === "x" ) {
      $this->box_dimensions["width"] += $this->column_dimensions["width"];
      if( $this->box_dimensions["length"] < $this->column_dimensions["length"] ) {
        $this->box_dimensions["length"] = $this->column_dimensions["length"];
      }
    } else {
      $this->box_dimensions["length"] += $this->column_dimensions["length"];
      if( $this->box_dimensions["width"] < $this->column_dimensions["width"] ) {
        $this->box_dimensions["width"] = $this->column_dimensions["width"];
      }
    }
    if( $this->box_dimensions["height"] < $this->column_dimensions["height"] ) {
      $this->box_dimensions["height"] = $this->column_dimensions["height"];
    }
    $this->column_dimensions = __vector_3D();
    $this->x++;
    $this->y = 0;
    
  }

  /**
   * Close the box to prevent add items
   */
  public function close() {
    $this->close_column();
    $this->canAdd = false;
  }

  /**
   * Get items inside the box
   */
  public function getItems() {
    return $this->items;
  }

  /**
   * Get Box Dimensions
   */
  public function get_dimensions() {
    return $this->box_dimensions;
  }

  /**
   * Get Box Weight
   */
  function get_weight() {
    return array_reduce($this->items, function($acc, $item) {
      return $acc + $item["package"]->get_total_weight();
    }, 0);
  }

  function get_price() {
    return array_reduce($this->items, function($acc, $item) {
      return $acc + ($item["package"]->get_price() * $item["package"]->get_quantity());
    }, 0);
  }

}