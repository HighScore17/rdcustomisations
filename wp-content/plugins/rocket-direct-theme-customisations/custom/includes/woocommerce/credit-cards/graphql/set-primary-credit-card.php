<?php
use GraphQL\Error\UserError;

class WC_Horizon_Credit_Cards_Primary_Graphql {
  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof WC_Horizon_Credit_Cards_Primary_Graphql ) {
      self::$instance = new WC_Horizon_Credit_Cards_Primary_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
  }

  function register_graphql_types() {
    register_graphql_mutation("setPrimaryCreditCard", array(
      "inputFields" => $this->get_input_fields(),
      "outputFields" => $this->get_output_fields(),
      "mutateAndGetPayload" => [ $this, 'mutate_and_get_payload' ]
    ));
  }

  function get_input_fields() {
    return array(
      "id" => array(
        "type" => "ID"
      ),
    );
  }

  function get_output_fields() {
    return wc_horizon_get_graphql_credit_card_object();
  }

  function mutate_and_get_payload( $input ) {
    $credit_card = wc_horizon_get_credit_card( $input['id'] );

    if( !$credit_card ) {
      throw new UserError( "Credit card not found" );
    }

    wc_horizon_clear_primary_credit_card();
    $credit_card->set_is_primary(true);
    $credit_card->save();

    return wc_horizon_credit_card_to_array( $credit_card );
  }
}

WC_Horizon_Credit_Cards_Primary_Graphql::init();


