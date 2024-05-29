<?php

use GraphQL\Error\UserError;
use WPGraphQL\WooCommerce\Model\Order;


class WC_Horizon_GraphQL_Create_Recurrring_Order {
  public static $instance = null;

  public static function init() {
    if( !(self::$instance instanceof WC_Horizon_GraphQL_Create_Recurrring_Order) ) {
      self::$instance = new WC_Horizon_GraphQL_Create_Recurrring_Order();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_mutation' ] );
  }

  public function register_mutation() {
    register_graphql_mutation("addRecurringOrder", array(
      "inputFields" => $this->get_input_fields(),
      "outputFields" => WC_Horizon_GraphQL_Recurring_Order_Object::get_recurring_order_fields(),
      "mutateAndGetPayload" => [ $this, 'mutate_and_get_payload' ]
    ));
  }

  function get_input_fields() {
    return array(
      "frequency" => array(
        "type" => "String"
      ),
      "orderId" => array(
        "type" => "Int"
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
      "billing" => array(
        "type" => "CustomerAddressInput"
      ),
      "estShippingCost" => array(
        "type" => "Float"
      )
    );
  }


  public function mutate_and_get_payload( $input ) {
    if( __empty_some_key( $input, [ "frequency", "orderId", "status" ] ) ) {
      throw new UserError("Missing required fields");
    }

    if( __empty_some_key( $input["payment"], ["id", "type"] ) ) {
      throw new UserError( "Payment information missing" );
    }

    $order = wc_get_order( $input["orderId"] );

    if( !$order ) {
      throw new UserError("The order don't exists");
    }

    $user_id = get_current_user_id();

    if( $order->get_customer_id() !== $user_id ) {
      throw new UserError("You don't have permissions to access to the order #" . $order->get_order_number());
    }

    $payment = $input["payment"];
    
    if( $payment["type"] !== 'credit-card' || !function_exists('wc_horizon_get_credit_card') ) {
      throw new UserError("Payment method not allowed");
    }

    $cc = wc_horizon_get_credit_card( $payment["id"], $user_id );

    if( !$cc ) {
      throw new UserError( "You don't have permissions to access to this payment method" );
    }

    do_action('wc_horizon_before_create_recurring_order', $input["frequency"], $input["status"], $input["orderId"], $input["shipping"], $cc);

    $recurring_order = wc_horizon_create_recurring_order( array(
      "user_id" => $user_id,
      "payment" => $payment,
      "frequency" => $input["frequency"],
      "status" => $input["status"],
      "order_id" => $input["orderId"],
      "shipping" => $input["shipping"],
      "estShippingCost" => $input["estShippingCost"]
    ) );

    if( !$recurring_order ) {
      throw new UserError('The recurring order can\'t be created');
    }

    if( is_wp_error( $recurring_order ) ) {
      throw new UserError( $recurring_order->get_error_message() );
    }

    do_action('wc_horizon_after_create_recurring_order', $recurring_order);

    return wc_horizon_recurring_order_post_to_array( $recurring_order );
  }
}

WC_Horizon_GraphQL_Create_Recurrring_Order::init();