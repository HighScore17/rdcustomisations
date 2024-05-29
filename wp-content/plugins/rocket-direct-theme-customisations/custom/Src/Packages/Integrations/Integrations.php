<?php

class WP_Horizon_Integrations_Page {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof WP_Horizon_Integrations_Page ) {
      self::$instance = new WP_Horizon_Integrations_Page();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    add_action( 'admin_menu', [$this, 'registerMenuPage'] );
  }

  function registerMenuPage() {
    add_menu_page( 
      'Rocket Integrations',
      'Shop Integrations',
      'manage_options',
      'rocket-integrations',
      [$this, 'render'],
      'dashicons-plugins-checked',
      50
    );
  }

  function render() {
    require_once __DIR__ . "/Admin/View.php";
  }

  static function manageAPIKey( $id , $apiKey = null, $decrypted = false ) {
    $value = get_option( $id, "" );
    $valueDecrypted = $value ? System_Data_Encryptation::decrypt_encoded( $value ) : '';

    if( $apiKey === null ) {
      return $decrypted ? $valueDecrypted : $value;
    }
    
    $apiKey = trim( $apiKey );
    if( !$apiKey ) return;

    $incomingIsEncrypted = System_Data_Encryptation::decrypt_encoded( $apiKey );
    $encrypted = !$incomingIsEncrypted ? System_Data_Encryptation::encrypt_and_encode( $apiKey ) : $apiKey;
    update_option( $id, $encrypted );
  }
}

WP_Horizon_Integrations_Page::init();