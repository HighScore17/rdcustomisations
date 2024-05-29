<?php

add_action( 'graphql_register_types',function() {
  wc_horizon_graphql_register_credit_card_object_type();
  wc_horizon_grahql_register_credit_cards_object_types();
});

function wc_horizon_get_graphql_credit_card_object() {
  return array(
    "id" => array(
      "type" => "Int"
    ),
    "username" => array(
      "type" => "String"
    ),
    "cardtype" => array(
      "type" => "String"
    ),
    "placeholder" => array(
      "type" => "String"
    ),
    "isPrimary" => array(
      "type" => "Boolean"
    ),
    "billing" => array(
      "type" => "CCAddressObject"
    )
  );
}

function wc_horizon_graphql_register_credit_card_object_type() {
  register_graphql_object_type("CreditCardObject", [
    "fields" => wc_horizon_get_graphql_credit_card_object()
  ]);
}

function wc_horizon_grahql_register_credit_cards_object_types() {
  register_graphql_object_type("CreditCardObjects", [
    "fields" => array(
      "nodes" => array(
        "type" => array(
          "list_of" => "CreditCardObject"
        )
      )
    )
  ]);
}

function wc_horizon_credit_card_to_array( $credit_card ) {
  if( !is_a( $credit_card, 'WC_Horizon_Credit_Card' ) ) {
    return array();
  }
  
  return array(
    "id" => $credit_card->get_id(),
    "username" => $credit_card->get_name(),
    "cardtype" => $credit_card->get_card_type(),
    "placeholder" => $credit_card->get_placeholder(),
    "isPrimary" => $credit_card->is_primary(),
    "billing" => $credit_card->get_billing(),
  );
}
