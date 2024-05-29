<?php 
use GraphQL\Error\UserError;

class WC_Multiple_Address_Get_Primary_GraphQL {

  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Multiple_Address_Get_Primary_GraphQL ) {
      self::$instance = new WC_Multiple_Address_Get_Primary_GraphQL();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_query' ] );
  }

  public function register_query() {
    register_graphql_field( 'RootQuery', 'primaryAddress', [
      'type' => 'Address',
      'description' => __('Get the default primary address'),
      'args' => [
          'user_id' => [ 'type' => 'Int' ],
          'type' => [ 'type' => 'String' ],
      ],
      'resolve' => [$this, 'resolve' ]
    ]);
  }

  public function resolve(  $source, $args, $context, $info ) {
    $user_id = get_current_user_id();

    if( !$user_id ) {
      throw new UserError("You must be logged in.");
    }

    return WC_Customer_Multiple_Address::get_primary_address( $args["type"], $user_id );
  }
}

WC_Multiple_Address_Get_Primary_GraphQL::init();