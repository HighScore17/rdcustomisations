<?php


function wc_horizon_graphql_register_payment_type() {
  $input_fields = [
    "id" => [ "type" => "Int" ],
    "type" => [ "type" => "String" ],
  ];

  register_graphql_input_type("HorizonPaymentTypeInput", [
    "description" => "Payment type input",
    "fields" => $input_fields
  ]);
  register_graphql_object_type("horizonPaymentTypeObject", [
    "description" => "Payment Type Object",
    "fields" => $input_fields
  ]);
}

add_action('graphql_register_types', 'wc_horizon_graphql_register_payment_type');