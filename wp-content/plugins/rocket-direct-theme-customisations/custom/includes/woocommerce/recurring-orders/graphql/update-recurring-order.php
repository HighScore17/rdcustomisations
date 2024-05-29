<?php

use GraphQL\Error\UserError;
use WPGraphQL\WooCommerce\Model\Order;


class WC_Horizon_GraphQL_Update_Recurring_Order {
  public static $instance = null;

  public static function init() {
    if( !(self::$instance instanceof WC_Horizon_GraphQL_Update_Recurring_Order) ) {
      self::$instance = new WC_Horizon_GraphQL_Update_Recurring_Order();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_mutation' ] );
  }

  public function register_mutation() {
    register_graphql_mutation("updateRecurringOrder", array(
      "inputFields" => $this->get_input_fields(),
      "outputFields" => WC_Horizon_GraphQL_Recurring_Order_Object::get_recurring_order_fields(),
      "mutateAndGetPayload" => [ $this, 'mutate_and_get_payload' ]
    ));
  }

  function get_input_fields() {
    return array(
      "id" => array(
        "type" => "ID"
      ),
      "frequency" => array(
        "type" => "String"
      ),
      "payment" => array(
        "type" => "HorizonPaymentTypeInput"
      ),
      "status" => array(
        "type" => "String"
      ),
      "shipping" => array(
        "type" => "CustomerAddressInput"
      ),
    );
  }


  public function mutate_and_get_payload( $input ) {
    global $recurring_order_frequencies;

    if( __empty_some_key( $input, [ "id", "frequency", "status" ] ) ) {
      throw new UserError("Missing required fields");
    }

    if( __empty_some_key( $input["payment"], ["id", "type"] ) ) {
      throw new UserError( "Payment information missing" );
    }

    $recurring_order = wc_horizon_get_recurring_order( $input["id"] );

    if( !$recurring_order ) {
      throw new UserError("The recurring order don't exists");
    }

    $old_recurring_order = clone $recurring_order;

    $user_id = get_current_user_id();

    if( intval( $recurring_order->get_owner() ) !== $user_id ) {
      throw new UserError("Access denied.");
    }

    $payment = $input["payment"];
    
    if( $payment["type"] !== 'credit-card' || !function_exists('wc_horizon_get_credit_card') ) {
      throw new UserError("Payment method not allowed");
    }

    $cc = wc_horizon_get_credit_card( $payment["id"], $user_id );

    if( !$cc ) {
      throw new UserError( "You don't have permissions to access to this payment method" );
    }
    
    $frequency = __array_find( $recurring_order_frequencies, function( $frequency ) use ($input) {
      return $frequency["id"] === $input["frequency"];
    } );

    $prev_status = $recurring_order->get_status();

    $recurring_order->set_address("shipping", $input["shipping"]);
    $recurring_order->set_payment( $payment["type"], $payment["id"] );
    $recurring_order->set_frequency( $frequency ?? $recurring_order_frequencies[0] );
    $recurring_order->set_status( $input["status"]  );
    $recurring_order->save();

    if( $prev_status !== $recurring_order->get_status() ) {
      do_action( 'wc_horizon_recurring_order_status_changed', $recurring_order, $recurring_order->get_status() );
    }

    do_action('wc_horizon_recurring_order_updated', $old_recurring_order, $recurring_order);

    return wc_horizon_recurring_order_post_to_array( $recurring_order );
  }
}

WC_Horizon_GraphQL_Update_Recurring_Order::init();