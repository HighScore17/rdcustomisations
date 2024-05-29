<?php
if( isset( $_POST["ac-integration-web-account-field-dropdown-value"] ) && !empty( $_POST["ac-integration-web-account-field-dropdown-value"] ) ) {
  $ac_current_field = json_decode( str_replace( '\"', '"', get_option("ac-integration-web-account-field") ), true );
  $ac_new_field = json_decode( str_replace( '\"', '"', $_POST["ac-integration-web-account-field-dropdown-value"] ), true );
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
  update_option( "ac-integration-web-account-field",json_encode( $ac_new_field ) );
}

if( isset( $_POST["ac-integration-web-account-field-value-dropdown-value"] ) && !empty( $_POST["ac-integration-web-account-field-value-dropdown-value"] ) ) {
  update_option("ac-integration-web-account-field-value", $_POST["ac-integration-web-account-field-value-dropdown-value"] );
}

$ac_web_account_field = get_option( "ac-integration-web-account-field", array() );
$ac_web_account_field_obj = json_decode( get_option( "ac-integration-web-account-field", array() ), true );
$ac_web_account_field_value = get_option( "ac-integration-web-account-field-value", array() );

