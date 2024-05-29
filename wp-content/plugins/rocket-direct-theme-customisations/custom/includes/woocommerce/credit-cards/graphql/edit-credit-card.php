<?php
use GraphQL\Error\UserError;

class WC_Horizon_Credit_Cards_Edit_Graphql {
  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof WC_Horizon_Credit_Cards_Edit_Graphql ) {
      self::$instance = new WC_Horizon_Credit_Cards_Edit_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ], 20 );
  }

  function register_graphql_types() {
    register_graphql_mutation("editCreditCard", array(
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
      "billing" => array(
        "type" => "CCAddressInput"
      ),
    );
  }

  function get_output_fields() {
    return wc_horizon_get_graphql_credit_card_object();
  }

  function mutate_and_get_payload( $input ) {
    if( 
      __empty_some_key($input["billing"], ["firstname", "lastname", "address1", "city", "state", "postcode" ]) 
    ) {
      throw new UserError("Missing required fields");
    }

    $id = isset($input["id"]) ? intval($input["id"]) : 0;
    $credit_card = wc_horizon_get_credit_card( $id );

    if( !is_a( $credit_card, 'WC_Horizon_Credit_Card' ) || !$credit_card->get_id() ) {
      throw new UserError("Credit card not found.");
    }

    $billing = $input["billing"];

    if( !is_array( $billing ) ) {
      throw new UserError("Invalid Billing Address");
    }

    $name = $billing["firstname"] + " " + $billing["lastname"];

    if( $name !== $credit_card->get_name() && !empty( $name ) ) {
      $credit_card->set_name( $name );
    }

    $credit_card->set_billing( __sanitize_array( $input["billing"] ) );
    $credit_card->save();

    return wc_horizon_credit_card_to_array( $credit_card);
  }
}

WC_Horizon_Credit_Cards_Edit_Graphql::init();


