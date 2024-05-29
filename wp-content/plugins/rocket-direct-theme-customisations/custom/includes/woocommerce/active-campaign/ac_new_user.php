<?php

class Woocommerce_Horizon_New_User_Created {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof Woocommerce_Horizon_New_User_Created  ) {
      self::$instance = new Woocommerce_Horizon_New_User_Created();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action('user_register', [$this, 'on_user_created'], 999, 2);
  }

  function on_user_created( $user_id, $userdata ) {
    try {
      $this->set_ac_user_as_website_customer( $user_id, $userdata );
    } catch (Exception $e){}
  }

  function log( $msg ) {
    $logger = wc_get_logger();
    $logger->info( $msg, array("source" => "ac-sync-contact") );
  }

  function set_ac_user_as_website_customer( $user_id, $userdata ) {
			$user_data = get_userdata( $user_id );

      $this->log("User Data Reveived" . wc_print_r( $user_data, true ));

      if ( 
        !isset( $user_data->user_email ) ||
        !get_user_meta( $user_data->ID, 'activecampaign_for_woocommerce_contact_synced', true )
      ) {
        return;
      }

      $ac = new Active_Campaign_SDK();
      $body = wp_remote_retrieve_body( $ac->call( "contacts?email=" . $user_data->user_email, null, "GET" ) );

      if( !$body ) {
        return;
      }

      $contacts = json_decode( $body, true );

      $this->log("Contacts" . wc_print_r($contacts, true));

      if( !is_array( $contacts) || !is_array( $contacts["contacts"] ) || !count($contacts["contacts"])  ) {
        return;
      }

      $contact = $contacts["contacts"][0];
      $ac_web_account_field = get_option( "ac-integration-web-account-field", array() );
      $ac_web_account_field_value = get_option( "ac-integration-web-account-field-value", array() );

      if( !$ac_web_account_field || !$ac_web_account_field_value ) {
        return;
      }

      $ac_web_account_field = json_decode( str_replace( '\"', '"', $ac_web_account_field ), true  ); 
      $ac_web_account_field_value = json_decode( str_replace( '\"', '"', $ac_web_account_field_value ), true  ); 

      $this->log("Payload". wc_print_r(array(
        "field" => $ac_web_account_field["value"],
        "value" => $ac_web_account_field_value["value"]
      ), true));


      $body = $ac->call( "contacts/" . $contact["id"], array(
        "contact" => array(
          "fieldValues" => array(
            array(
              "field" => $ac_web_account_field["value"],
              "value" => $ac_web_account_field_value["value"]
            )
          )
        )
      ), "PUT" );

      $this->log("Response: " . wc_print_r( $body, true ));
  }
}

Woocommerce_Horizon_New_User_Created::init();