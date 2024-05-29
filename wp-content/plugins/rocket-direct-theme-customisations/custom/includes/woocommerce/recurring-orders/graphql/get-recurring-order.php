<?php

use GraphQL\Error\UserError;

class WC_Horizon_GraphQL_Get_Recurring_Order {
  public static $instance = null;

  public static function init() {
    if( !(self::$instance instanceof WC_Horizon_GraphQL_Get_Recurring_Order) ) {
      self::$instance = new WC_Horizon_GraphQL_Get_Recurring_Order();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_query' ] );
  }

  public function register_query() {
    register_graphql_field("RootQuery", "recurringOrder", array(
      "args" => array(
        "id" => array(
          "type" => "ID"
        )
      ),
      "type" => "horizonRecurringOrderObject",
      "resolve" => [ $this, 'resolve' ]
    ));
  }

  public function resolve( $source, $args ) {

    if( !$args["id"] ) {
      throw new UserError("Recurring order id wasn't provided");
    }

    $recurring_order = wc_horizon_get_recurring_order( intval( $args["id"] ) );

    if( !$recurring_order ) {
      throw new UserError("Recurring Order not found");
    }

    if( intval($recurring_order->get_owner()) !== get_current_user_id() ) {
      throw new UserError("You don't have permissions to access to this recurring order");
    }

    return wc_horizon_recurring_order_post_to_array( $recurring_order );
  }
}

WC_Horizon_GraphQL_Get_Recurring_Order::init();