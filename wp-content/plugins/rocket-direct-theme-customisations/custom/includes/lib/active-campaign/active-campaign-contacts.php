<?php

class Active_Campaign_Contacts {

  /** 
    * Retrieve a contact from Active Campaign by email
    * @param string email - email address of contact
    */
  static function get_by_email( $email) {
    $ac = new Active_Campaign_SDK();
    $response = $ac->call("contacts?email=" . $email, null, "GET");
    if( is_wp_error($response) || $response['response']['code'] !== 200 || empty($response['body']) ) {
      return null;
    }
    $result =  json_decode(wp_remote_retrieve_body($response), true);

    if( !is_array($result["contacts"]) || count($result["contacts"]) === 0 ) {
      return null;
    }

    return $result["contacts"][0];
  }

  /** 
    * Retrieve the custom fields for a contact
    * @param string link - Link returned from Active Campaign Single Contact
    */
  static function get_fields( $link ) {

    $ac = new Active_Campaign_SDK();
    $response = $ac->call($link, null, "GET");

    if(is_wp_error($response) || $response['response']['code'] !== 200 || empty($response['body'])) {
      return null;
    }

    $result =  json_decode(wp_remote_retrieve_body($response), true);

    if( !is_array($result["fieldValues"]) ) {
      return null;
    }

    return $result["fieldValues"];
  }

  static function create( $fields = array() ) {
    $ac = new Active_Campaign_SDK();
    $response = $ac->call("contacts", $fields, "POST");
    $status = wp_remote_retrieve_response_code( $response );
    $body = wp_remote_retrieve_body( $response );

    if( ($status != 200 && $status != 201) || empty( $body ) ) {
      return;
    }

    $body = json_decode( $body, true );

    if( !is_array( $body ) || !is_array( $body["contact"] ) ) {
      return;
    }
    return $body;
  }
}