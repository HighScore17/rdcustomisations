<?php

class Admin_Shipping {
  /**
   * Admin page where settings will be renderer
   */
  private $admin_page;

  /**
   * Options saved
   */
  private $options;

  function __construct( $page )
  {
    $this->admin_page = $page;
  }

  function enable()
  {
    $this->options = get_option( "shipping_calculator" );
    add_action('admin_init', [ $this, 'register_options']);
  }

  function get_tab()
  {
    return array(
      'name' => 'Shipping',
      'group' => 'shipping_calculator_group',
      'type' => array (
        'code' => 'options',
      )
    );
  }

  function register_options()
  {
    register_setting(
      'shipping_calculator_group',
      'shipping_calculator'
    );
    $this->register_shipstation_options();
    $this->register_fedex_options();
    $this->register_estes_express_options();
    $this->register_shipping_address_options();
    $this->register_shipping_weight_options();
  }

  function register_shipstation_options() {
    add_settings_section(
      'shipping_calculator_shipstation',
      'ShipStation API',
      array( $this, 'fields_description' ),
      'horizon_customisations'
    );

    add_settings_field(
      'shipstation_api_key',
      'API Key',
      array( $this, 'ss_print_api_key' ),
      'horizon_customisations',
      'shipping_calculator_shipstation',
    );
    add_settings_field(
      'shipstation_api_secret',
      'API Secret',
      array( $this, 'ss_print_api_secret' ),
      'horizon_customisations',
      'shipping_calculator_shipstation',
    );
  }

  function register_fedex_options() {
    add_settings_section(
      'shipping_calculator_fedex',
      'FedEx API',
      array( $this, 'fields_description' ),
      'horizon_customisations'
    );

    add_settings_field(
      'fedex_client_id',
      'Client ID',
      array( $this, 'fedex_print_client_key' ),
      'horizon_customisations',
      'shipping_calculator_fedex',
    );
    add_settings_field(
      'fedex_client_secret',
      'Client Secret',
      array( $this, 'fedex_print_client_secret' ),
      'horizon_customisations',
      'shipping_calculator_fedex',
    );
  }

  function register_estes_express_options() {
    add_settings_section(
      'shipping_calculator_estes_express',
      'Estes Express',
      array( $this, 'fields_description' ),
      'horizon_customisations'
    );
    add_settings_field(
      'estes_express_account',
      'Account Number',
      array( $this, 'estes_express_print_account' ),
      'horizon_customisations',
      'shipping_calculator_estes_express',
    );
    add_settings_field(
      'estes_express_username',
      'Username',
      array( $this, 'estes_express_print_username' ),
      'horizon_customisations',
      'shipping_calculator_estes_express',
    );
    add_settings_field(
      'estes_express_password',
      'Password',
      array( $this, 'estes_express_print_password' ),
      'horizon_customisations',
      'shipping_calculator_estes_express',
    );
  }

  function register_shipping_address_options() {
    add_settings_section(
      'shipping_calculator_address',
      'Shipping Address',
      array( $this, 'fields_description' ),
      'horizon_customisations'
    );
    add_settings_field(
      'shipper_name',
      'Shipper Name',
      array( $this, 'shipper_print_name' ),
      'horizon_customisations',
      'shipping_calculator_address',
    );
    add_settings_field(
      'shipper_address',
      'Shipper Address',
      array( $this, 'shipper_print_address' ),
      'horizon_customisations',
      'shipping_calculator_address',
    );
    add_settings_field(
      'shipper_city',
      'Shipper City',
      array( $this, 'shipper_print_city' ),
      'horizon_customisations',
      'shipping_calculator_address',
    );
    add_settings_field(
      'shipper_state',
      'Shipper State',
      array( $this, 'shipper_print_state' ),
      'horizon_customisations',
      'shipping_calculator_address',
    );
    add_settings_field(
      'shipper_postcode',
      'Shipper Postcode',
      array( $this, 'shipper_print_postcode' ),
      'horizon_customisations',
      'shipping_calculator_address',
    );
  }

  function register_shipping_weight_options() {
    add_settings_section(
      'shipping_weight',
      'Shipping Weight',
      array( $this, 'fields_description' ),
      'horizon_customisations'
    );
    add_settings_field(
      'shipping_max_ups_weight',
      'UPS Max Weight',
      array( $this, 'shipping_ups_weight' ),
      'horizon_customisations',
      'shipping_weight',
    );
    add_settings_field(
      'shipping_max_ltl_weight',
      'LTL Max Weight',
      array( $this, 'shipping_ltl_weight' ),
      'horizon_customisations',
      'shipping_weight',
    );
    add_settings_field(
      'shipping_max_ltl_pallets',
      'LTL Max Pallets',
      array( $this, 'shipping_ltl_pallets' ),
      'horizon_customisations',
      'shipping_weight',
    );
  }

  function ss_print_api_key()
  {
    printf(
      '<input type="text" id="shipstation_api_key" name="shipping_calculator[shipstation_api_key]" value="%s" />',
      isset( $this->options["shipstation_api_key"] ) ? $this->options["shipstation_api_key"] : '' 
    );
  }

  function ss_print_api_secret()
  {
    printf(
      '<input type="password" id="shipstation_api_secret" name="shipping_calculator[shipstation_api_secret]" value="%s" />',
      isset( $this->options["shipstation_api_secret"] ) ? $this->options["shipstation_api_secret"] : '' 
    );
  }


  function fedex_print_client_key()
  {
    printf(
      '<input type="text" id="fedex_client_id" name="shipping_calculator[fedex_client_id]" value="%s" />',
      isset( $this->options["fedex_client_id"] ) ? $this->options["fedex_client_id"] : '' 
    );
  }

  function fedex_print_client_secret()
  {
    printf(
      '<input type="password" id="fedex_client_secret" name="shipping_calculator[fedex_client_secret]" value="%s" />',
      isset( $this->options["fedex_client_secret"] ) ? $this->options["fedex_client_secret"] : '' 
    );
  }


  function shipper_print_name() {
    printf(
      '<input type="text" id="shipper_name" name="shipping_calculator[shipper_name]" value="%s" />',
      isset( $this->options["shipper_name"] ) ? $this->options["shipper_name"] : '' 
    );
  }

  function estes_express_print_account() {
    printf(
      '<input type="text" id="estes_express_account" name="shipping_calculator[estes_express_account]" value="%s" />',
      isset( $this->options["estes_express_account"] ) ? $this->options["estes_express_account"] : '' 
    );
  }
  function estes_express_print_username() {
    printf(
      '<input type="text" id="estes_express_username" name="shipping_calculator[estes_express_username]" value="%s" />',
      isset( $this->options["estes_express_username"] ) ? $this->options["estes_express_username"] : '' 
    );
  }
  function estes_express_print_password() {
    printf(
      '<input type="password" id="estes_express_password" name="shipping_calculator[estes_express_password]" value="%s" />',
      isset( $this->options["estes_express_password"] ) ? $this->options["estes_express_password"] : '' 
    );
  }

  function shipper_print_address() {
    printf(
      '<input type="text" id="shipper_address" name="shipping_calculator[shipper_address]" value="%s" />',
      isset( $this->options["shipper_address"] ) ? $this->options["shipper_address"] : '' 
    );
  }
  function shipper_print_city() {
    printf(
      '<input type="text" id="shipper_city" name="shipping_calculator[shipper_city]" value="%s" />',
      isset( $this->options["shipper_city"] ) ? $this->options["shipper_city"] : '' 
    );
  }
  function shipper_print_state() {
    printf(
      '<input type="text" id="shipper_state" name="shipping_calculator[shipper_state]" value="%s" />',
      isset( $this->options["shipper_state"] ) ? $this->options["shipper_state"] : '' 
    );
  }
  function shipper_print_postcode() {
    printf(
      '<input type="text" id="shipper_postcode" name="shipping_calculator[shipper_postcode]" value="%s" />',
      isset( $this->options["shipper_postcode"] ) ? $this->options["shipper_postcode"] : '' 
    );
  }

  function shipping_ups_weight() {
    printf(
      '<input type="text" id="shipping_max_ups_weight" name="shipping_calculator[shipping_max_ups_weight]" value="%s" />',
      isset( $this->options["shipping_max_ups_weight"] ) ? $this->options["shipping_max_ups_weight"] : '' 
    );
  }

  function shipping_ltl_weight() {
    printf(
      '<input type="text" id="shipping_max_ltl_weight" name="shipping_calculator[shipping_max_ltl_weight]" value="%s" />',
      isset( $this->options["shipping_max_ltl_weight"] ) ? $this->options["shipping_max_ltl_weight"] : '' 
    );
  }

  function shipping_ltl_pallets() {
    printf(
      '<input type="text" id="shipping_max_ltl_pallets" name="shipping_calculator[shipping_max_ltl_pallets]" value="%s" />',
      isset( $this->options["shipping_max_ltl_pallets"] ) ? $this->options["shipping_max_ltl_pallets"] : '' 
    );
  }

  function fields_description() {
    return null;
  }
}