<?php

class USPSAddressValidatorQueriesGraphql {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof USPSAddressValidatorQueriesGraphql ) {
      self::$instance = new USPSAddressValidatorQueriesGraphql();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    add_action( 'graphql_register_types', [ $this, 'registerQueries' ] );
  }

  function registerQueries() {
    $this->registerObjectTypes();
    $this->registerFields();
  }

  function registerFields() {
    register_graphql_field( 'RootQuery', 'validateAddress', array(
      'args' => $this->getAddressArray(),
      'type' => 'addressValidatated',
      'resolve' => [ $this, 'resolve' ],
    ) );
  }

  function registerObjectTypes() {
    register_graphql_object_type( 'addressValidatedMessage', array(
      'fields' => [
        'type' => [ 'type' => 'String' ],
        'payload' => [ 'type' => 'String' ]
      ]
    ));

    register_graphql_object_type( 'addressValidatedFormatted', [
      'fields' => $this->getAddressArray()
    ] );

    register_graphql_object_type("addressValidatated", [
      'fields' => [
        'message' => [ 'type' => 'addressValidatedMessage' ],
        'address' => [ 'type' => 'addressValidatedFormatted' ]
      ]
    ]);
  }

  function getAddressArray() {
    return [
      'address1' => [ 'type' => 'String' ],
      'address2' => [ 'type' => 'String' ],
      'State' => [ 'type' => 'String' ],
      'City' => [ 'type' => 'String' ],
      'Zip' => [ 'type' => 'String' ],
    ];
  }

  function resolve( $source, $args ) {
    $validation = USPSAddressValidatorSDK::validate( $args );

    if( is_wp_error( $validation ) ) {
      $validation = array(
        'message' => array(
          'type' => 'error',
          'payload' => trim( $validation->get_error_message() )
        )
      );
    }

    return $validation;
  }
}

USPSAddressValidatorQueriesGraphql::init();