<?php

use GraphQL\Error\UserError;

class WC_Horizon_GraphQL_Get_Recurring_Orders {
  public static $instance = null;

  public static function init() {
    if( !(self::$instance instanceof WC_Horizon_GraphQL_Get_Recurring_Orders) ) {
      self::$instance = new WC_Horizon_GraphQL_Get_Recurring_Orders();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_query' ] );
  }

  public function register_query() {
    register_graphql_field("RootQuery", "recurringOrders", array(
      "args" => array(),
      "type" => "horizonRecurringOrderObjectList",
      "resolve" => [ $this, 'resolve' ]
    ));
  }

  public function resolve() {
    $recurring_orders = wc_horizon_get_recurring_orders();

    if( !$recurring_orders || !is_array( $recurring_orders ) ) {
      throw new UserError("User not logged in");
    }

    return array(
      "nodes" => array_map( 'wc_horizon_recurring_order_post_to_array', $recurring_orders )
    );
  }
}

WC_Horizon_GraphQL_Get_Recurring_Orders::init();