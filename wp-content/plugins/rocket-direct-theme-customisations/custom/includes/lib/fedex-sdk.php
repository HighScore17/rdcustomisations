<?php

class FEDEX_SDK {

  private $sandbox_mode;
  private $options;

  function __construct( $sandbox_mode = false ) {
    $this->sandbox_mode = $sandbox_mode;
    $this->options = get_option("shipping_calculator");
  }

  /**
   * @param $url URL to send the request to
   * @param $data data of the request: body, headers, etc.
   * @param $authorization if include bearer token 
   * @return array|WP_Error
   */
  public function request($endpoint, $data = array(), $authorization = true) {
    

    $response = wp_remote_request( $this->get_base_url() . $endpoint, array(
      'method' => $data["method"] ? $data["method"] : 'POST',
      'headers' => array_merge(
        array(
          'Content-Type' => $data["Content-Type"] ? $data["Content-Type"] : 'application/json',
        ),
        $authorization ? array(
          'Authorization' => 'Bearer ' . $this->get_access_token(),
        ) : array()
      ) ,
      'body' => $data["body"] ? $data["body"] : array(),
    )); 
    if($data["retrieve_body"] === true) {
      return json_decode(wp_remote_retrieve_body($response), TRUE);
    }
    return $response;
  }

  /**
   * Get the url based in the enviroment
   * @return string
   */
  public function get_base_url() {
    return "https://apis.fedex.com/";
  }

  /**
   * Get the access token
   * @return string
   */
  private function get_access_token() {
    $endpoint = "oauth/token";
    $data = array(
      'grant_type' => 'client_credentials',
      'client_id' => $this->options["fedex_client_id"],
      'client_secret' => $this->options["fedex_client_secret"],
    );
    $body = $this->request($endpoint, array(
      'Content-Type' => 'application/x-www-form-urlencoded',
      'body' => $data,
      'retrieve_body' => true,
    ), false);
    return $body['access_token'];
  }
}