<?php

class Graphql_Mutation_Custom_Post_Data {
  public static $insance = null;

  public static function init() {
    if( self::$insance === null ) {
      self::$insance = new Graphql_Mutation_Custom_Post_Data();
      self::$insance->add_hooks();
    }
  }

  function add_hooks() {
     add_action( 'graphql_register_types', [ $this, 'register_mutation' ] );
  }

  function register_mutation() {
    register_graphql_mutation("addPostData", array(
      "inputFields" => $this->get_input_fields(),
      "outputFields" => $this->get_output_fields(),
      "mutateAndGetPayload" => [ $this, 'mutate_and_get_payload' ]
    ));
  }

  function get_input_fields() {
    return array(
      'data' => array(
        'type' => array(
          'list_of' => 'MetaDataInput'
        )
      )
    );
  }

  function get_output_fields() {
    return array(
      "success" => array(
        'type' => 'Boolean'
      )
    );
  }

  function mutate_and_get_payload( $input ) {
    if( !is_array($input["data"]) ) {
      return array("success" => false);
    }
    foreach( $input["data"] as $item ) {
      $_POST[$item["key"]] = $item["value"];
    }
    return array( "success" => true );
  }
}

Graphql_Mutation_Custom_Post_Data::init();