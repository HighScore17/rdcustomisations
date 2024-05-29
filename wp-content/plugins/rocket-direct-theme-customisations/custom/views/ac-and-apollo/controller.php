<?php

$apollo_lifecycle_values = array();
$ac_lifecycle_values = array();

if( isset( $_POST['ac-to-apollo-lifecyclies'] ) ) {
  update_option( 'ac-to-apollo-lifecyclies', explode(",",$_POST['ac-to-apollo-lifecyclies'] ));
} 

$lifecycles = get_option( 'ac-to-apollo-lifecyclies', array() );



if( isset( $_POST["ac-to-apollo-apollo-field-dropdown-value"] ) && !empty( $_POST["ac-to-apollo-apollo-field-dropdown-value"] ) ) {
  update_option( "ac-to-apollo-apollo-field-dropdown-value", $_POST["ac-to-apollo-apollo-field-dropdown-value"] );
}

if( isset( $_POST["ac-to-apollo-apollo-sync-by-stage-dropdown-value"] ) && !empty( $_POST["ac-to-apollo-apollo-sync-by-stage-dropdown-value"] ) ) {
  update_option( "ac-to-apollo-apollo-sync-by-stage", $_POST["ac-to-apollo-apollo-sync-by-stage-dropdown-value"] );
}

if( isset( $_POST["ac-to-apollo-ac-default-stage-at-create-dropdown-value"] ) ) {
  update_option( "ac-to-apollo-ac-default-stage-at-create", $_POST["ac-to-apollo-ac-default-stage-at-create-dropdown-value"] );
} 

if( isset( $_POST["ac-to-apollo-apollo-default-stage-at-create-dropdown-value"] ) ) {
  update_option( "ac-to-apollo-apollo-default-stage-at-create", $_POST["ac-to-apollo-apollo-default-stage-at-create-dropdown-value"] );
} 

if( isset( $_POST["ac-to-apollo-ac-field-dropdown-value"] ) && !empty( $_POST["ac-to-apollo-ac-field-dropdown-value"] ) ) {
  $ac_current_field = json_decode( str_replace( '\"', '"', get_option("ac-to-apollo-ac-field-dropdown-value") ), true );
  $ac_new_field = json_decode( str_replace( '\"', '"', $_POST["ac-to-apollo-ac-field-dropdown-value"] ), true );
  if( $ac_current_field && $ac_new_field["value"] !== $ac_current_field["value"] ) {
    $ac = new Active_Campaign_SDK();
    $ac_response = wp_remote_retrieve_body( $ac->call($ac_new_field["data"], null, "GET") );
    $ac_body = json_decode( $ac_response, true );
    if( is_array( $ac_body ) && is_array( $ac_body["fieldOptions"] ) ) {
      $ac_new_field["options"] = array_map( function( $field ) {
        return array(
          "label" => $field["label"],
          "value" => $field["value"],
          "id" => $field["id"],
        );
      }, $ac_body["fieldOptions"] );
    }
  }
  update_option( "ac-to-apollo-ac-field-dropdown-value",json_encode( $ac_new_field ) );
}

foreach( $lifecycles as $lifecycle ) {
  $apollo_lc = "ac-to-apollo-apollo-" . $lifecycle . "-option-dropdown-value";
  $ac_lc = "ac-to-apollo-ac-" . $lifecycle . "-option-dropdown-value";
  if( isset( $_POST[$apollo_lc] ) && !empty( $_POST[$apollo_lc] ) ) {
    update_option( $apollo_lc, $_POST[$apollo_lc] );
  }

  if( isset( $_POST[$ac_lc] ) && !empty( $_POST[$ac_lc] ) ) {
    update_option( $ac_lc, $_POST[$ac_lc] );
  }

  $apollo_lifecycle_values[$lifecycle] = get_option($apollo_lc);
  $ac_lifecycle_values[$lifecycle] = get_option($ac_lc);
}

$ac_api_key = get_option("ac-to-apollo-horizon-ac-api-key");
$apollo_api_key = get_option("ac-to-apollo-horizon-apollo-api-key");
$apollo_field = get_option("ac-to-apollo-apollo-field-dropdown-value");
$ac_field = get_option("ac-to-apollo-ac-field-dropdown-value");
$apollo_field_obj = $apollo_field ?  json_decode( str_replace( '\"', '"', $apollo_field ), true ) : null;
$apollo_lifecycle_options = array_values( $apollo_field_obj && key_exists( "data", $apollo_field_obj ) ? array_map( function($option) {
  return array(
    "label" => $option["name"],
    "value" => $option["id"]
  );
}, $apollo_field_obj["data"] ) : []);
$ac_field_obj = json_decode( $ac_field, true );
$ac_lifecycle_options = $ac_field_obj ? $ac_field_obj["options"] : [];
$ac_defult_stage = get_option("ac-to-apollo-ac-default-stage-at-create");
$ac_defult_stage_obj = json_decode( str_replace( '\"', '"', $ac_defult_stage ), true );
$apollo_defult_stage = get_option("ac-to-apollo-apollo-default-stage-at-create");
$apollo_defult_stage_obj = json_decode( str_replace( '\"', '"', $apollo_defult_stage ), true );
$apollo_stage_to_sync = get_option("ac-to-apollo-apollo-sync-by-stage");