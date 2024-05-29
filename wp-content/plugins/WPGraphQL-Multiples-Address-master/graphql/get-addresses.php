<?php 
use GraphQL\Error\UserError;

class WC_Multiple_Address_Get_Addresses_GraphQL {

  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Multiple_Address_Get_Addresses_GraphQL ) {
      self::$instance = new WC_Multiple_Address_Get_Addresses_GraphQL();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_query' ] );
  }

  public function register_query() {
    register_graphql_field( 'RootQuery', 'multipleAddress', [
      'type' => 'Addresses',
      'description' => 'Users Address',
      'args'        => [
          'id' => [
              'type' => [
                  'non_null' => 'Int',
              ],
          ],
          'type' => [
              'type' => [
                  'non_null' => 'String',
              ],
          ],
      ],
      'resolve' => [$this, 'resolve']
    ]);
  }

  public function resolve(  $source, $args, $context, $info ) {
    $user_id = get_current_user_id();

    if( !$user_id ) {
      return array( "nodes" => array() );
    }

    return WC_Customer_Multiple_Address::get_addresses( $args["type"], $user_id );
  }
}

WC_Multiple_Address_Get_Addresses_GraphQL::init();