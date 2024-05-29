<?php


function wc_horizon_graphql_register_lineitems_input() {
  $input_fields = [
    "product_id" => [ "type" => "Int" ],
    "variantion_id" => [ "type" => "Int" ],
    "quantity" => [ "type" => "Int" ],
    "metaData"=> [ "type" => [ "list_of" => "MetaDataInput" ] ]
  ];

  register_graphql_input_type("horizonLineItemsInput", [
    "description" => "Line Items",
    "fields" => $input_fields
  ]);
}

add_action('graphql_register_types', 'wc_horizon_graphql_register_lineitems_object');

//CustomerAddressInput
//OrderToLineItemConnection