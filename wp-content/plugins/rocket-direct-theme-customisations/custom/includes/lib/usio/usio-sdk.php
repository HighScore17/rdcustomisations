<?php

class USIO_SDK {
  private $merchant_key = "";

  function __construct( $merchant_key ) {
    $this->merchant_key = $merchant_key;
  }

  /**
   * Return the base url of the USIO endpoint in base of the version
   */
  function get_base_url( $version ) {
    if( $version === "v2" ) {
      return  "https://payments.usiopay.com/2.0/payments.svc/JSON/";
    }
    return "https://checkout.securepds.com/checkout/checkout.svc/json/";
  }

  /**
   * Tokenize a credit card and return the confirmation ID
   */
  function tokenize_cc( $token, $billing_address ) {
    $result = $this->make_payment( $token, 0, $billing_address );

    if( is_wp_error( $result ) ) {
      return $result;
    }

    if( $result["Status"] !== "success" )
      return new WP_Error( "usio_tokenize_cc", $result["Message"] );
    
    return $result["Confirmation"];
  }

  /**
   * Make a simple Payment
   */
  function make_payment( $token, $amount, $billing_address ) {
    $args = array_merge(
      $billing_address,
      array (
        "MerchantKey" => $this->merchant_key,
        "Token" => $token,
        "Amount" => $amount,
        "AdditionalSearch" => '',
				"AccountCode1" => '',
				"AccountCode2" => '',
				"AccountCode3" => '',
				"VerStr" => ""
      )
    );
    return $this->make_request($this->get_base_url( "v1" ) . "SinglePayment", $args );
  }

  /**
   * Make a payment from a previous authorized credit card
   */
  function make_recurrent_payment( $amount, $token ) {
    $args = array(
      "MerchantID" => "",
      "Login" => "",
      "Password" => "",
      "Token" => $token,
      "Amount" => $amount,
    );

  }

  function make_request( $url, $body ) {
    $response = wp_remote_post( $url, array(
      "method" => "POST",
      "body" => json_encode($body),
      "headers" => array(
        "Content-Type" => "application/json"
      )
    ));

    if( is_wp_error($response) ) {
      return $response;
    }

    $body = json_decode( $response["body"], true );

    if( json_last_error() == JSON_ERROR_NONE ) {
      return $body;
    }

    return new WP_Error( "json_error", "JSON error: " . json_last_error_msg() );
  }
}

add_action('rest_api_init', function() {
  register_rest_route('horizon/v1', 'cc/save', array(
    'methods' => ['POST', 'GET'],
    'callback' => function(WP_REST_Request $request) {
      $token = $request->get_param('token');
      $usio = new USIO_SDK( get_option("woocommerce_usio_testmode") === "yes" ? "AEAE82F9-5A34-47C3-A61E-1E8EE37BE3AD" : get_option("woocommerce_usio_api_key") );
      $result = $usio->make_payment( $token, 0, $request->get_param('billing_address') );
      if( is_wp_error( $result ) ) {
        return [ "error" => $result->get_error_message() ];
      }
      return $result;
    },
    'permission_callback' => '__return_true'
  ));
});

