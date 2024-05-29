<?php

use GraphQL\Error\UserError;

class WC_Multiple_Address_Create_GraphQL {

  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Multiple_Address_Create_GraphQL ) {
      self::$instance = new WC_Multiple_Address_Create_GraphQL();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_mutation' ] );
  }

  public function register_mutation() {
    register_graphql_mutation( 'addAddress', [
      'inputFields' => WC_Multiple_Address_Object_GraphQL::get_address_fields_filtered(["id", "isPrimary"]),
      'outputFields' => [
          "id" => ["type" => "String"]
      ],
      'mutateAndGetPayload' => [ $this, 'mutate_and_get_payload' ]
    ] );
  }

  public function mutate_and_get_payload( $input ) {
    $user_id = get_current_user_id();

    if( !$user_id ) {
      return array( "id" => 0 );
    }

    $result = WC_Customer_Multiple_Address::create_address( $input, $user_id );

    if ( is_wp_error( $result ) ) {
      throw new UserError( $result->get_error_message() );
    }

    return $result;
  }
}

WC_Multiple_Address_Create_GraphQL::init();