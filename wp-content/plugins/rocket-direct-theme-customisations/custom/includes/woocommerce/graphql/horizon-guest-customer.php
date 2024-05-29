<?php

class Horizon_Graphql_Guest_Customer {

  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof Horizon_Graphql_Guest_Customer ) {
      self::$instance = new Horizon_Graphql_Guest_Customer();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_mutation' ] );
  }

  function register_object_types() {
    register_graphql_object_type('horizonGuestCustomerOutput', array(
      'fields' => array(
        'email' => array(
          'type' => 'String',
        ),
      )
    ));
  }

  function register_mutation() {
    register_graphql_mutation('setGuestCustomer', array(
      'inputFields' => array(
        'email' => array(
          'type' => 'String',
          'description' => 'The email address of the guest customer.',
        ),
      ),
      'outputFields' => array(
        'email' => array(
          'type' => 'String',
        ),
      ),
      'mutateAndGetPayload' => [$this, 'mutateAndGetPayload']
    ));
  }

  function mutateAndGetPayload($input) {
    WC()->frontend_includes();
    WC()->session->set('__guest_customer_email', $input["email"]);
    $_POST['__guest_customer_email'] = $input["email"];
    return array(
      "email" => $input["email"],
    );
  }
}

Horizon_Graphql_Guest_Customer::init();