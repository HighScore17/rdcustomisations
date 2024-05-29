<?php 

class WC_Multiple_Address_Object_GraphQL {

  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Multiple_Address_Object_GraphQL ) {
      self::$instance = new WC_Multiple_Address_Object_GraphQL();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_objects' ] );
  }

  public function register_objects() {
    register_graphql_object_type('Address', [
        'description' => 'Multiples users address',
        'fields' => self::get_address_fields()
    ]);

    register_graphql_object_type('Addresses', [
        'fields' => [
            'nodes' => [
                'type' => [
                    'list_of' => 'Address'
                ]
            ],
        ]
    ]);
  }

  static function get_address_fields() {
    return [
      'id' => ['type' => 'Int'],
      'alias' => ['type' => 'String'],
      'firstName' => ['type' => 'String'],
      'lastName' => ['type' => 'String'],
      'company' => ['type' => 'String'],
      'country' => ['type' => 'String'],
      'address1' => ['type' => 'String'],
      'address2' => ['type' => 'String'],
      'city' => ['type' => 'String'],
      'state' => ['type' => 'String'],
      'postcode' => ['type' => 'String'],
      'phone' => ['type' => 'String'],
      'email' => ['type' => 'String'],
      'type' => ['type' => 'String'],
      'user_id' => ['type' => 'Int'],
      'isPrimary' => ['type' => 'Int'],
      'address_type' => ['type' => 'String'],
    ];
  }

  static function get_address_fields_filtered($exclude = [])
    {
      $addresses_data = self::get_address_fields();
      $fileds = [];

      foreach($addresses_data as $key => $value) {
        if(!in_array($key, $exclude)) {
          $fileds[$key] = $value;
        }
      }

      return $fileds;
    }
}

WC_Multiple_Address_Object_GraphQL::init();