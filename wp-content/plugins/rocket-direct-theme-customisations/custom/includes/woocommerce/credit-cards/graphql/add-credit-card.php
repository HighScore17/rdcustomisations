<?php
use GraphQL\Error\UserError;

class WC_Horizon_Credit_Cards_Add_Graphql {
  static $instance = null;

  public static function init()
  {
    if( !self::$instance instanceof WC_Horizon_Credit_Cards_Add_Graphql ) {
      self::$instance = new WC_Horizon_Credit_Cards_Add_Graphql();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'graphql_register_types', [ $this, 'register_graphql_types' ] );
  }

  function register_graphql_types() {
    $this->register_address_type();
    register_graphql_mutation("addCreditCard", array(
      "inputFields" => $this->get_input_fields(),
      "outputFields" => $this->get_output_fields(),
      "mutateAndGetPayload" => [ $this, 'mutate_and_get_payload' ]
    ));
  }

  function register_address_type() {
    $fields = [
      "firstname" => [ "type" => "String" ],
      "lastname" => [ "type" => "String" ],
      "address1" => [ "type" => "String" ],
      "address2" => [ "type" => "String" ],
      "city" => [ "type" => "String" ],
      "state" => [ "type" => "String" ],
      "postcode" => [ "type" => "String" ],
      "country" => [ "type" => "String" ],
      "phone" => [ "type" => "String" ],
      "company" => [ "type" => "String" ],
      "email" => [ "type" => "String" ],
    ];
    register_graphql_input_type("CCAddressInput", [
      "description" => "Address",
      "fields" => $fields,
    ]);
    register_graphql_object_type( "CCAddressObject", [
      "fields" => $fields,
    ]  );
  }

  function get_input_fields() {
    return array(
      "token" => array(
        "type" => "String"
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
        "type" => "CCAddressInput"
      )
    );
  }

  function get_output_fields() {
    return wc_horizon_get_graphql_credit_card_object();
  }

  function mutate_and_get_payload( $input ) {

    if( !class_exists( 'User_Data_Encryptation' ) ) {
      throw new UserError("Store credit card feature is disabled at this moment.");
    }
    
    if( 
      __empty_some_key($input, ["token", "username", "cardtype", "placeholder"]) || 
      __empty_some_key($input["billing"], ["firstname", "lastname", "address1", "city", "state", "postcode" ]) 
    ) {
      throw new UserError("Missing required fields");
    }

    $user_id = get_current_user_id();

    if( !$user_id ) {
      throw new UserError("User not logged in");
    }
    
    $usio_settings = get_option("woocommerce_usio_settings");
    $usio = new USIO_SDK( $usio_settings["testmode"] === "yes" ? "AEAE82F9-5A34-47C3-A61E-1E8EE37BE3AD" : $usio_settings["api_key"] );
    $cc_token = $usio->tokenize_cc( $input["token"], array(
      "FirstName" => $input["billing"]["firstname"],
      "LastName" => $input["billing"]["lastname"],
      "Address1" => $input["billing"]["address1"],
      "Address2" => $input["billing"]["address2"],
      "City" => $input["billing"]["city"],
      "State" => $input["billing"]["state"],
      "Zip" => $input["billing"]["postcode"]
    ) );

    $cc_token = User_Data_Encryptation::encrypt_and_encode( $user_id, $cc_token );

    if( !$cc_token ) {
      throw new UserError( "Credit card token can't be encrypted and stored." );
    }

    if( is_wp_error( $cc_token ) ) {
      throw new UserError( $cc_token->get_error_message() );
    }

    if( $input["isPrimary"] ) {
      wc_horizon_clear_primary_credit_card();
    }

    $cc = new WC_Horizon_Credit_Card();
    $cc->set_token( sanitize_text_field( $cc_token )  );
    $cc->set_owner( $user_id );
    $cc->set_name( sanitize_text_field($input["username"]) );
    $cc->set_card_type( sanitize_text_field( $input["cardtype"] ) );
    $cc->set_placeholder( sanitize_text_field( $input["placeholder"] ) );
    $cc->set_billing( __sanitize_array( $input["billing"] ) );
    $cc->set_is_primary( $input["isPrimary"] );
    $cc->set_is_encrypted( true );
    $cc->save();

    if( !$cc->get_id() ) {
      throw new UserError("Failed to save credit card");
    }

    return wc_horizon_credit_card_to_array( $cc );
  }
}

WC_Horizon_Credit_Cards_Add_Graphql::init();


