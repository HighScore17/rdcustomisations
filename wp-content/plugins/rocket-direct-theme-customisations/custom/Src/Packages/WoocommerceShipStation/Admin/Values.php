<?php

class Woocommerce_ShipStation_Admin_Values {
  static $prefix = "__ss_integration_";

  static function apiKey( $apiKey = null, $decrypted = false ) {
    return WP_Horizon_Integrations_Page::manageAPIKey( self::$prefix . "api_key", $apiKey, $decrypted );
  }

  static function apiSecret( $secret = null, $decrypted = false ) {
    return WP_Horizon_Integrations_Page::manageAPIKey( self::$prefix . "api_secret", $secret, $decrypted );
  }

  static function canSyncOrder( $can = null ) {
    if( $can === null ) {
      return get_option( self::$prefix . "can_sync_order", "no" );
    }
    update_option( self::$prefix . "can_sync_order", $can ? "yes" : "no" );
  }

  static function canCreateLabels( $can = null ) {
    if( $can === null ) {
      return get_option( self::$prefix . "can_create_labels", "no" );
    }
    update_option( self::$prefix . "can_create_labels", $can ? "yes" : "no" );
  }
}