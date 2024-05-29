<?php

define("SHIPSTATION_API_KEY", "c1185179307b4d4ab339365b3fb6f8aa");
define("SHIPSTATION_API_SECRET", "3340860f538a425f97092407223e0bce");

class ShipStation_SDK {
  static function request( $endpoint, $method, $body = null ) {
    $logger = wc_get_logger();
    $url = substr($endpoint, 0, 5) === "https" ? $endpoint : "https://ssapi.shipstation.com/{$endpoint}";
    $args = array(
      "method" => $method,
      "headers" => array(
        "Authorization" => "Basic " . base64_encode(SHIPSTATION_API_KEY . ":" . SHIPSTATION_API_SECRET),
        "Content-Type" => "application/json",
      ),
      "timeout" => 60,
    );
    if ($body) {
      $args["body"] = json_encode($body);
    }
    $logger->info( wc_print_r($args, true), [ "source" => "shipstation_sdk" ] );
    $response = wp_remote_request($url, $args);
    $logger->info( wc_print_r($response, true), [ "source" => "shipstation_sdk" ] );
    return json_decode(
      wp_remote_retrieve_body( $response), true
    );
  }
}