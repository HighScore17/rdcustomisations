<?php

class Woocommerce_Horizon_Cart_Messages_Graphql {

  public static $instance;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_Horizon_Cart_Messages_Graphql ) {
      self::$instance = new Woocommerce_Horizon_Cart_Messages_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_cart_messages' ] );
  }

  function register_cart_messages() {
    $this->register_graphql_object_types();
    $this->register_cart_messages_query();
  }

  function register_graphql_object_types() {
    register_graphql_object_type("horizonCartMessage", array(
      'fields' => array(
        'type' => array( 'type' => 'String' ),
        'payload' => array( 'type' => 'String' ),
        'payloadType' => array( 'type' => 'String' )
      )
    ));

    register_graphql_object_type( 'horizonCartMessageList', array(
      'fields' => array(
        'nodes' => array(
          'type' => array(
            'list_of' => 'horizonCartMessage'
          )
        )
      )
    ) );
  }

  function register_cart_messages_query() {
    register_graphql_field('cart', 'messages', array(
      'type' => 'horizonCartMessageList',
      'resolve' => function() {
        $nodes = apply_filters( 'horizon_woocommerce_cart_messages', [] );
        return array(
          'nodes' => $nodes
        );
      }
    ));
  }

}
Woocommerce_Horizon_Cart_Messages_Graphql::init();