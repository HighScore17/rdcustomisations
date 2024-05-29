<?php

class WC_Horizon_Credit_Card extends WC_Horizon_Post {
  
  protected $object_type = "credit-card-payment";
  protected $public_meta = array(
    'token',
    'ownerid',
    'userid',
    'username',
    'cardtype',
    'placeholder',
    'is_primary',
    'is_encrypted',
    'billing_first_name',
    'billing_last_name',
    'billing_company',
    'billing_address_1',
    'billing_address_2',
    'billing_city',
    'billing_state',
    'billing_postcode',
    'billing_country',
    'billing_phone',
    'billing_email',
  );

  function __construct($id = 0) {
    parent::__construct($id);
  }

  function get_userid() {
    return $this->get_prop("userid");
  }

  function get_name() {
    return $this->get_prop("username");
  }

  function get_token() {
    return $this->get_prop("token");
  }

  function get_card_type() {
    return $this->get_prop("cardtype");
  }

  function get_placeholder() {
    return $this->get_prop("placeholder");
  }

  function get_owner() {
    return $this->get_prop("ownerid");
  }

  function is_encrypted() {
    return $this->get_prop("is_encrypted") === "yes";
  }

  function is_primary() {
    return $this->get_prop("is_primary") === "yes";
  }

  function get_billing() {
    return array(
      "firstname" => $this->get_prop("billing_first_name"),
      "lastname" => $this->get_prop("billing_last_name"),
      "address1" => $this->get_prop("billing_address_1"),
      "address2" => $this->get_prop("billing_address_2"),
      "city" => $this->get_prop("billing_city"),
      "state" => $this->get_prop("billing_state"),
      "postcode" => $this->get_prop("billing_postcode"),
      "country" => $this->get_prop("billing_country"),
      "phone" => $this->get_prop("billing_phone"),
      "company" => $this->get_prop("billing_company"),
      "email" => $this->get_prop("billing_email")
    );
  }

  function set_token( $token ) {
    $this->set_prop("token", $token );
  }

  function set_name($name) {
    $this->set_prop("username", $name);
  }

  function set_card_type($type) {
    $this->set_prop("cardtype", $type);
  }

  function set_placeholder( $placeholder ) {
    $this->set_prop("placeholder", $placeholder);
  }

  function set_userid( $userid ) {
    $this->set_prop("userid", $userid);
  }

  function set_owner( $ownerid ) {
    $this->set_prop("ownerid", $ownerid);
  }

  function set_is_primary( $is_primary ) {
    $this->set_prop("is_primary", !!$is_primary ? "yes" : "no");
  }

  function set_is_encrypted( $is_encrypted ) {
    $this->set_prop("is_encrypted", !!$is_encrypted ? "yes" : "no");
  }

  function set_billing( $billing ) {
    $this->set_prop("billing_first_name", $billing["firstname"]);
    $this->set_prop("billing_last_name", $billing["lastname"]);
    $this->set_prop("billing_address_1", $billing["address1"]);
    $this->set_prop("billing_address_2", $billing["address2"]);
    $this->set_prop("billing_city", $billing["city"]);
    $this->set_prop("billing_state", $billing["state"]);
    $this->set_prop("billing_postcode", $billing["postcode"]);
    $this->set_prop("billing_country", $billing["country"]);
    $this->set_prop("billing_phone", $billing["phone"]);
    $this->set_prop("billing_email", $billing["email"]);
  }
}

add_action('init', function() {
  register_post_type("credit-card-payment", array(
    "label" => "Credit Card Payments",
    "public" => true,
    "has_archive" => false,
    "rewrite" => false,
    "show_in_rest" => true,
  ));
});


add_action('woocommerce_horizon_saved_credit-card-payment', function( $cc_id ) {
  /*$args = array(
    'meta_key' => 'custom-meta-key',
    'meta_query' => array(
        array(
            'key' => 'cp_annonceur',
            'value' => 'professionnel',
            'compare' => '=',
        )
    )
 );
 $query = new WP_Query($args);*/
});