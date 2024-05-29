<?php


function wc_horizon_graphql_register_generic_objects() {
  register_graphql_object_type("horizonOptionWithLabel", [
    "description" => "Option with label",
    "fields" => array(
      "id" => array( "type" => "String" ),
      "label" => array( "type" => "String" ),
      "value" => array( "type" => "String" ),
    )
  ]);
  register_graphql_object_type("horizonDiscountType", [
    "description" => "Option with label",
    "fields" => array(
      "type" => array( "type" => "String" ),
      "amount" => array( "type" => "Float" ),
      "value" => array( "type" => "Float" ),
    )
  ]);
  register_graphql_object_type("horizonOptionWithLabelList", [
    "description" => "Option with label",
    "fields" => array(
      "nodes" => array( "type" => array("list_of" => "horizonOptionWithLabel") ),
    )
  ]);
}

add_action('graphql_register_types', 'wc_horizon_graphql_register_generic_objects');