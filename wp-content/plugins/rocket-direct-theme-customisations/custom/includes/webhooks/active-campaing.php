<?php

class Horizon_ActiveCampaign_Webhooks {
  static $instance = null;

  static function init() {
    if(!self::$instance instanceof Horizon_ActiveCampaign_Webhooks) {
      self::$instance = new Horizon_ActiveCampaign_Webhooks();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action('rest_api_init', [$this, 'register_api_routes']);
  }

  function register_api_routes() {
    register_rest_route('horizon/v1', 'ac/contact-updated', array(
      'methods' => ['POST', 'GET'],
      'callback' => [$this, 'on_contact_update'],
      'permission_callback' => '__return_true'
    ));
    register_rest_route('horizon/v1', 'ac/contact-created', array(
      'methods' => ['POST', 'GET'],
      'callback' => [$this, 'on_contact_created'],
      'permission_callback' => '__return_true'
    ));
  }

  function on_contact_update(WP_REST_Request $request) {

    $params = $request->get_params();

    if( !is_array( $params["contact"] ) || empty( $params["contact"]["email"] ) ) {
      return;
    }

    $contact = Active_Campaign_Contacts::get_by_email( $params["contact"]["email"] );

    if( $contact ) {
      do_action( 'horizon_ac_contact_updated', $contact );
    }
  }

  function on_contact_created( WP_REST_Request $request ) {
    $params = $request->get_params();

    if( !is_array( $params["contact"] ) || empty( $params["contact"]["email"] ) ) {
      return;
    }

    $contact = Active_Campaign_Contacts::get_by_email( $params["contact"]["email"] );

    if( $contact ) {
      do_action( 'horizon_ac_contact_created', $contact );
    }
  }
}

Horizon_ActiveCampaign_Webhooks::init();