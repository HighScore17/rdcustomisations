<?php

class Horizon_Apollo_To_AC_Sync {
  static $instance = null;

  static function init() {
    if(!self::$instance instanceof Horizon_Apollo_To_AC_Sync) {
      self::$instance = new Horizon_Apollo_To_AC_Sync();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action('horizon_apollo_to_ac_sync_by_stage', [$this, 'sync_apollo_contacts']);
  }

  function sync_apollo_contacts() {
    $apollo = new Apollo_SDK();
    $ac_defult_stage = json_decode( str_replace( '\"', '"', get_option("ac-to-apollo-ac-default-stage-at-create") ), true );
    $apollo_defult_stage = json_decode( str_replace( '\"', '"', get_option("ac-to-apollo-apollo-default-stage-at-create") ), true );
    $apollo_stage_to_sync = json_decode( str_replace( '\"', '"', get_option("ac-to-apollo-apollo-sync-by-stage") ), true );
    $ac_field = json_decode(  str_replace( '\"', '"', get_option("ac-to-apollo-ac-field-dropdown-value") ), true );

    if( !is_array( $ac_defult_stage ) || !is_array( $apollo_defult_stage ) || !is_array( $apollo_stage_to_sync ) ) {
      return;
    }
    
    $result = $apollo->make_raw_request("contacts/search", "POST", array(
      "contact_stage_ids" => [$apollo_stage_to_sync["value"]],
      "per_page" => 200
    ));

    $status = wp_remote_retrieve_response_code( $result );
    $body = wp_remote_retrieve_body( $result );

    if( $status != 200 || empty( $body ) ) {
      return;
    }

    $body = json_decode( $body, true );

    if( !is_array( $body ) || !is_array( $body["contacts"] ) ) {
      return;
    }

    foreach( $body["contacts"] as $contact ) {
      if(!Active_Campaign_Contacts::get_by_email($contact["email"])) {
        $ac_contact = Active_Campaign_Contacts::create(array(
          "contact" => array(
            "email" => $contact["email"],
            "firstName" => $contact["first_name"],
            "lastName" => $contact["last_name"],
            "phone" => $contact["sanitized_phone"],
            "fieldValues" => array(
              array(
                "field" => $ac_field["value"],
                "value" => $ac_defult_stage["value"]
              )
            )
          )
        ));
        if( !is_array( $ac_contact ) ) {
          continue;
        }
      }
      
      if( $contact["contact_stage_id"] != $apollo_defult_stage["value"] ) {
        Apollo_Contacts::update_stage( array( $contact["id"] ), $apollo_defult_stage["value"] );
      }
      usleep( 200000 );
    }
  }
}

Horizon_Apollo_To_AC_Sync::init();