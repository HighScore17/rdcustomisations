<?php 
use GraphQL\Error\UserError;

class WC_Multiple_Address_Update_GraphQL {

  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Multiple_Address_Update_GraphQL ) {
      self::$instance = new WC_Multiple_Address_Update_GraphQL();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_mutation' ] );
  }

  public function register_mutation() {
    register_graphql_mutation( 'updateAddress', [
      'inputFields' => WC_Multiple_Address_Object_GraphQL::get_address_fields_filtered(["user_id", "isPrimary"]),
      'outputFields' => [
          "updated" => ["type" => "Int"]
      ],
      'mutateAndGetPayload' => [ $this, 'mutate_and_get_payload' ]
    ]);
  }

  public function mutate_and_get_payload( $input ) {
    $user_id = get_current_user_id();

    if( !$user_id ) {
      throw new UserError("You must be logged in.");
    }

    $result = WC_Customer_Multiple_Address::update_address( $input, $user_id );

    return $result;
  }
}

WC_Multiple_Address_Update_GraphQL::init();