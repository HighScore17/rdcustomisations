<?php

class Woocommerce_ShipStation_SDK {

  static function request( $endpoint, $method, $body = null ) {
    $url = "https://ssapi.shipstation.com/{$endpoint}";
    $args = array(
      "method" => $method,
      "headers" => array(
        "Authorization" => self::getAuthorization(),
        "Content-Type" => "application/json",
      ),
      "timeout" => 30,
    );

    if ($body) {
      $args["body"] = json_encode($body);
    }
    
    return wp_remote_request($url, $args);
  }

  static function getAuthorization() {
    return "Basic " . base64_encode( Woocommerce_ShipStation_Admin_Values::apiKey(null, true) . ":" . Woocommerce_ShipStation_Admin_Values::apiSecret(null, true) );
  }

  static function getCarrierCode() {
    return "fedex";
  }
}