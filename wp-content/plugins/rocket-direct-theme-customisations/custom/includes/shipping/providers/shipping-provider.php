<?php

class Shipping_Provider {
  private $is_dev;
  private $credentials;
  private $config;
  
  function __construct( $is_dev, $credentials, $config = [ "singleRequest" => true ] ) {
    $this->is_dev = $is_dev;
    $this->credentials = $credentials;
    $this->config = $config;
  }

  /**
   * Get allowed services by the provider
   */
  public function get_allowed_services() {
    return array();
  }

  /**
   * Get the url to fetch based on the enviroment
   */
  function get_url() {
    return "";
  }

  /**
   * Get headers needed for the request (crendetials included)
   */
  function get_headers() {
    return array();
  }

  /**
   * Validate and make request
   */
  function make_rate_quote( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
    if( !$this->validate_input( $service_code, $ship_from, $ship_to, $packages, $accessorials ) ) {
      return new WP_Error( "invalid", __( 'Some parameters are invalid.', 'horizon-customisations' ) );
    }
    if( !$this->validate_packages( $packages ) ) {
      return new WP_Error( "invalid", __( 'Packages data is invalid.', 'horizon-customisations' ) );
    }
    return $this->make_request( $service_code, $ship_from, $ship_to, $packages, $accessorials );
  }

  /**
   * Make post request
   */
  function make_request( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
    $allowed_services = $this->get_allowed_services();
    $responses = array();
    foreach( $allowed_services as $service => $description ) {
      $headers = $this->get_headers();
      $payload = json_encode( $this->get_rate_quote_body( $service, $ship_from, $ship_to, $packages, $accessorials ) );
      $response = wp_remote_post( 
        $this->get_url(), 
        array(
          'headers' => $headers,
          'body' => $payload
        )
      );
      array_push( $responses, $response );
    }
    $responses = $this->check_response( $responses );
    return $this->format_response( $responses );
  }

  
  /**
   * Get Rate Quote body to send
   */
  function get_rate_quote_body( $service_code, $ship_from, $ship_to, $packages, $accessorials ) {
    return array();
  }

  /**
   * Validate input values (shipping from, to & service)
   */
  function validate_input( $service_code, $ship_from, $ship_to, $accessorials ) {
    return TRUE;
  }

  /**
   * Validate if packages values are valid.
   */
  function validate_packages( $packages ) {
    return TRUE;
  }

  function check_response( $response ) {
    return is_wp_error( $response ) ? $response : json_encode( $response['body'] );
  }

  /**
   * @return array
   * (
   *  "total" => float,
   *  "service" => array(
   *    "code" => string,
   *    "description" => string,
   *  ),
   *  "resume" => array(
   *    ...
   *    array(
   *      "charge" => float,
   *      "label" => string
   *    )
   *    ...
   *  )
   * )
   */
  function format_response( $response ) {
    return $response;
  }

  function get_accessorials() {
    return array();
  }
}

class Shipping_Package {
  public $code;
  public $dimensions_unit = "IN";
  public $weight_unit = "LBS";
  public $length;
  public $width;
  public $height;
  public $weight;
  public $class;
  public $cases_per_pallet;
}