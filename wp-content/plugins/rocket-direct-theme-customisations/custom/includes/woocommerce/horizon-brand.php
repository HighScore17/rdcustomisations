<?php
/*
class WC_Horizon_Brand_Filter_For_Woocommerce{ 
  function add_hooks() {
    add_action( 'cmb2_admin_init', [$this, 'add_product_metaboxes'] );
    add_action('woocommerce_product_variation_get_catalog_visibility', [$this, 'check_visibility_by_variable_product'], 10, 2);
    //add_action('woocommerce_product_get_catalog_visibility', [$this, 'check_visibility_by_product'], 10, 2);

  }

  function add_product_metaboxes() {
    $cmb = new_cmb2_box( array(
      'id' => 'wc_brand_filter_metabox',
      'title' => 'Brands',
      'context' => 'side',
      'object_types' => array( 'product' )
    ) );
      
    $brands = wc_horizon_get_allowed_brands();
    $options = [ "all" => "All" ];

    foreach( $brands as $brand ) {
      $options[$brand] = $brand;
    }

    $cmb->add_field( array(
      'name' => 'Visible for brands',
      'id'   => 'wch_brands_allowed',
      'type' => 'multicheck',
      'options' => $options
    ) );
  }

  function check_visibility_by_variable_product( $visibility, WC_Product_Variation $product_variable ) {
    $brand = wc_horizon_get_brand_name();
    //get_option('wc_brand_filter_metabox');
    //$brands_allowed = $product_variable->get_meta( 'wch_brands_allowed' );
    return 'hiden';
    if( !wc_horizon_brand_exists( $brand )) {
      return $visibility;
    }
    throw new WP_Error('2', 'asd');

   

    return $visibility;
  }

  function check_visibility_by_product( $visibility, $product ) {
    
  }
}
$brands = new WC_Horizon_Brand_Filter_For_Woocommerce();
$brands->add_hooks();
*/