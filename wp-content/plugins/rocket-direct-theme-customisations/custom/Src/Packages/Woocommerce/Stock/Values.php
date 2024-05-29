<?php

class Woocommerce_Stock_Packagen_Admin_Values {
  static $prefix = "__wc_stock_integration_";

  static function canSetOutStock( $can = null ) {
    if( $can === null ) {
      return get_option( self::$prefix . "can_set_out_stock", "no" );
    }
    update_option( self::$prefix . "can_set_out_stock", $can ? "yes" : "no" );
  }

  static function minium_cases( $minium = null ) {
    if( $minium === null ) {
      return intval( get_option( self::$prefix . "minium_cases", 1 ) );
    }
    update_option( self::$prefix . "minium_cases", intval( $minium ) );
  }

  static function minium_masks_cases( $minium = null ) {
    if( $minium === null ) {
      return intval( get_option( self::$prefix . "minium_masks_cases", 1 ) );
    }
    update_option( self::$prefix . "minium_masks_cases", intval( $minium ) );
  }
}