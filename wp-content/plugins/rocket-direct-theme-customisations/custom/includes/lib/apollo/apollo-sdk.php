<?php

class Apollo_SDK {
  function get_baseurl() {
    return "https://api.apollo.io/v1/";
  }

  function get_url( $endpoint, $get ) {
    return $this->get_baseurl() . $endpoint . ($get ? "?api_key=" . get_option('horizon-api-key-apollo') : "");
  }

  function make_request( $endpoint, $method = "GET", $body = null, $p = false ) {
    return wp_remote_retrieve_body( $this->make_raw_request( $endpoint, $method, $body, $p ) );
  }

  function make_raw_request( $endpoint, $method = "GET", $body = null, $p = false ) {
    $params = array(
      "method" => $method,
      "headers" => array(
        "Content-Type" => "application/json"
    ));
    if( $body ) {
      $params["body"] = json_encode(
        array_merge($body, array( "api_key" => get_option('horizon-api-key-apollo')))
      );
    }

    return wp_remote_post( $this->get_url( $endpoint, $method === "GET" ), $params);
  }
}