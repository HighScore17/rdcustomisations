<?php

class Horizon_ActiveCampaign_To_Apollo_Webhooks {
  static $instance = null;

  const STAGE_FIELD_ID = "52";

  static function init() {
    if(!self::$instance instanceof Horizon_ActiveCampaign_To_Apollo_Webhooks) {
      self::$instance = new Horizon_ActiveCampaign_To_Apollo_Webhooks();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action('horizon_ac_contact_updated', [$this, 'on_contact_updated']);
    add_action('horizon_ac_contact_created', [$this, 'on_contact_created']);
  }

  function on_contact_created( $contact ) {
    $ac_stage = $this->get_ac_stages( $contact );
    $logger = wc_get_logger();

    if( !$ac_stage ) {
      return new WP_Error("ac-stage", "AC Stage can't be found");
    }

    $apollo_contacts = $this->get_apollo_contacts( $contact["email"] );
    
    if( !is_array( $apollo_contacts ) ) {
      return new WP_Error('contact-not-found', 'Contacts can\'t be retrieved.');
    }

    if( count($apollo_contacts) ) {
      return new WP_Error('contact-found', 'Contacts alredy exists on Apollo.');
    }

    $this->apollo_create_contact( array(
      "email" => $contact["email"],
      "first_name" => $contact["firstName"],
      "last_name" => $contact["lastName"],
      "organization_name" => $contact["orgname"],
      "contact_stage_id" => $this->get_apollo_stage_id( $ac_stage ),
    ) );
    $logger->info( wc_print_r( array(
      "email" => $contact["email"],
      "first_name" => $contact["firstName"],
      "last_name" => $contact["lastName"],
      "organization_name" => $contact["orgname"],
      "contact_stage_id" => $this->get_apollo_stage_id( $ac_stage ),
    ), true ),  array("source" => "ac-to-apollo-create-contact") );
  }

  function on_contact_updated( $contact ) {
    $ac_stage = $this->get_ac_stages( $contact );

    if( !$ac_stage ) {
      return new WP_Error("ac-stage", "AC Stage can't be found");
    }
    $apollo_contacts = $this->get_apollo_contacts( $contact["email"] );
    
    if( !$apollo_contacts || !count($apollo_contacts) ) {
      return new WP_Error('contact-not-found', 'Contacts can\'t be found on Apollo.');
    }

    $lifecycles = get_option( 'ac-to-apollo-lifecyclies', array() );

    foreach( $lifecycles as $lifecycle ) {
      $apollo_lc = "ac-to-apollo-apollo-$lifecycle-option-dropdown-value";
      $ac_lc = "ac-to-apollo-ac-$lifecycle-option-dropdown-value";

      $apollo_lf = json_decode( str_replace('\"', '"',  get_option($apollo_lc) ), true );
      $ac_lf = json_decode( str_replace('\"', '"',  get_option($ac_lc) ), true );


      if( $ac_lf["value"] === $ac_stage["value"] && $apollo_lf["value"] ) {
        $ids = array_map(function( $contact ) {
          return $contact["id"];
        }, $apollo_contacts);
        $this->apollo_update_lifecycle( $ids, $apollo_lf["value"]  );
        break;
      }
    }
  }

  function get_ac_stages( $contact ) {
    $fields = Active_Campaign_Contacts::get_fields( $contact["links"]["fieldValues"] );

    if( !is_array( $fields ) ) {
      return;
    }

    $ac_field = get_option("ac-to-apollo-ac-field-dropdown-value");
    $ac_field_obj = json_decode( $ac_field, true );

    if( !$ac_field_obj["value"] ) {
      return;
    }

    return __array_find( $fields, function( $field ) use ( $ac_field_obj ) {
      return $field["field"] === $ac_field_obj["value"];
    } );
  }

  function get_apollo_contacts( $email ) {
    $apollo = new Apollo_SDK();
    $apollo_contacts = $apollo->make_request("contacts/search", "POST", array(
      "q_keywords" => $email
    ));

    if( empty($apollo_contacts) ) {
      return;
    }

    $apollo_contacts = json_decode( $apollo_contacts, true );

    if( !is_array( $apollo_contacts["contacts"] ) ) {
      return;
    }

    return array_filter( $apollo_contacts["contacts"], function($c) use ($email) {
      return $c["email"] === $email;
    });
  }

  function get_apollo_fields_options() {
    $apollo_field = get_option("ac-to-apollo-apollo-field-dropdown-value");
    $apollo_field_obj = $apollo_field ?  json_decode( str_replace( '\"', '"', $apollo_field ), true ) : null;
    return array_values( $apollo_field_obj && key_exists( "data", $apollo_field_obj ) ? array_map( function($option) {
      return array(
        "label" => $option["name"],
        "value" => $option["id"]
      );
    }, $apollo_field_obj["data"] ) : []);
  }

  function get_apollo_stage_id( $ac_stage ) {
    $lifecycles = get_option( 'ac-to-apollo-lifecyclies', array() );

    foreach( $lifecycles as $lifecycle ) {
      $apollo_lc = "ac-to-apollo-apollo-$lifecycle-option-dropdown-value";
      $ac_lc = "ac-to-apollo-ac-$lifecycle-option-dropdown-value";

      $apollo_lf = json_decode( str_replace('\"', '"',  get_option($apollo_lc) ), true );
      $ac_lf = json_decode( str_replace('\"', '"',  get_option($ac_lc) ), true );


      if( $ac_lf["value"] === $ac_stage["value"] && $apollo_lf["value"] ) {
        return $apollo_lf["value"];
      }
    }
  }

  function apollo_update_lifecycle( $ids, $stage ) {
    $apollo = new Apollo_SDK();
    $apollo->make_request("contacts/update_stages/", "POST", array(
      "contact_ids" => array_values($ids),
      "contact_stage_id" => $stage
    ), true);
  }

  function apollo_create_contact( $params ) {
    $apollo = new Apollo_SDK();
    $apollo->make_request("contacts", "POST", $params);
  }
}

Horizon_ActiveCampaign_To_Apollo_Webhooks::init();