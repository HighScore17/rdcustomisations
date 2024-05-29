<?php

class WC_Horizon_GraphQL_Get_Recurring_Order_Options {
  public static $instance = null;

  public static function init() {
    if( !(self::$instance instanceof WC_Horizon_GraphQL_Get_Recurring_Order_Options) ) {
      self::$instance = new WC_Horizon_GraphQL_Get_Recurring_Order_Options();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_query' ] );
  }

  public function register_query() {
    register_graphql_field("RootQuery", "recurringOrderOptions", array(
      "args" => array(),
      "type" => "horizonRecurringOrderOptionsObject",
      "resolve" => [ $this, 'resolve' ]
    ));
  }

  public function resolve(  ) {
    global $recurring_order_frequencies;
    global $recurring_order_discount;
    return array(
      "frequencies" => array(
        "nodes" => $recurring_order_frequencies
      ),
      "discount" => $recurring_order_discount
    );
  }
}

WC_Horizon_GraphQL_Get_Recurring_Order_Options::init();