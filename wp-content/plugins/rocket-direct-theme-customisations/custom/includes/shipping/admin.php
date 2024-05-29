<?php
class Horizon_Shipping_Admin
{
  public static $instance;

  public static function start()
  {
    if( !self::$instance instanceof Horizon_Shipping_Admin )
    {
      self::$instance = new Horizon_Shipping_Admin();
      self::$instance->init();
    }
  }

  public function init()
  {
    add_action( 'cmb2_admin_init', [$this, 'set_product_data'] );
    add_action( 'cmb2_admin_init', [$this, 'set_product_shipping_data'] );
  }

  function set_product_data()
  {
    $cmb = new_cmb2_box( array(
      'id'           => 'shipment_cmb2_metabox',
      'title'        => 'Case Shipment',
      'object_types' => array( 'product' ),
    ) );

    $cmb->add_field( array(
      'name' => 'Case Length',
      'id'   => 'shipment_dimensions_length',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );

    $cmb->add_field( array(
      'name' => 'Case Width',
      'id'   => 'shipment_dimensions_width',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );

    $cmb->add_field( array(
      'name' => 'Case Height',
      'id'   => 'shipment_dimensions_height',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );

    $cmb->add_field( array(
      'name' => 'Case Weight',
      'id'   => 'shipment_dimensions_weight',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );

    $cmb->add_field( array(
      'name' => 'Items per Case',
      'id'   => 'shipment_case_items',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );
    $cmb->add_field( array(
      'name' => 'Case Class',
      'id'   => 'shipment_case_class',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );
    $cmb->add_field( array(
      'name' => 'Cases per pallets',
      'id'   => 'shipment_case_per_pallets',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );
  }

  function set_product_shipping_data() {
    $cmb = new_cmb2_box( array(
      'id'           => 'shipment_provider_cmb2_metabox',
      'title'        => 'Shipment Provider',
      'object_types' => array( 'product' ),
    ) );

    $cmb->add_field( array(
      'name' => 'Max Parcel Cases',
      'id'   => 'shipment_max_ups',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );

    $cmb->add_field( array(
      'name' => 'Max LTL Pallets',
      'id'   => 'shipment_max_pallets',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );
  }
}

Horizon_Shipping_Admin::start();
