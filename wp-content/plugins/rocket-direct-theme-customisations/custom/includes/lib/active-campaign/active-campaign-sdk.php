<?php
class Active_Campaign_SDK {
  CONST API_URL = "https://rocketdistributors.api-us1.com/api/3/";

  public function request( $endpoint, $body ) {
    return wp_remote_post( 
      self::API_URL . $endpoint,
      array(
        'headers' => array (
          'Api-Token' => get_option('horizon-api-key-active-campaign'),
          'Content-Type' => 'application/json'
        ),
        'body' => json_encode($body)
      )
    );
    
  }

  public function call($endpoint, $body, $method = "POST") {
    if( substr($endpoint, 0, strlen(self::API_URL)) === self::API_URL ) {
      $endpoint = substr($endpoint, strlen(self::API_URL));
    }

    $args = array(
      'method' => $method,
      'headers' => array (
        'Api-Token' => get_option('horizon-api-key-active-campaign'),
        'Content-Type' => 'application/json',
      )
    );
    if( $body ) {
      $args['body'] = json_encode($body);
    }
    return wp_remote_request(self::API_URL . $endpoint, $args);
  }

  static function sync_user( $email, $firstname, $lastname, $list_id ) {
    $ac = new Active_Campaign_SDK();
    $body = array(
      'contact' => array (
        'email' => $email,
        'firstName' => $firstname,
        'lastName' => $lastname
      )
    );
    $ac_response = $ac->request( "contact/sync", $body);
    if( !is_wp_error( $ac_response ) && $ac_response['response']['code'] === 200 ) {
      // Suscribe contact to lst
      $contact = json_decode( $ac_response['body'], TRUE );
      $contact_id = $contact['contact']['id'];
      Active_Campaign_SDK::subscribe_to_list( $list_id, $contact_id );
      return TRUE;
    }
    return FALSE;
  }

  static function subscribe_to_list( $list_id, $contact_id, $status = 1 ) {
    $ac = new Active_Campaign_SDK();
    return $ac->request( "contactLists", array(
      'contactList' => array(
        'list' => $list_id,
        'contact' => $contact_id,
        'status' => $status
      )
    ));
  }
}