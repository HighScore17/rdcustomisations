<?php
  define('CIN7_AUTHORIZATION', 'Basic Um9ja2V0RGlzdHJpYnV0b1VTOjY1N2ViN2IwYjM1YTQwZTc4M2Y3MDFiOTAwMTBiZmM1');
  /**
   * 
   * Product Dashboard Admin
   * 
   */
  add_action( 'cmb2_admin_init', 'cin7_init_cmb2_product_meta' );

  function cin7_init_cmb2_product_meta()
  {
    $cmb = new_cmb2_box( array(
      'id'           => 'cin7_cmb2_metabox',
      'title'        => 'CIN7 Products',
      'object_types' => array( 'product' ),
    ) );

    $cmb->add_field( array(
      'name' => 'Products ID',
      'id'   => 'cin7_products_id',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );

    $cmb->add_field( array(
      'name' => 'Backup Stock (SM, MD, LG, XL)',
      'id'   => 'cin7_stock_backup',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );

    $cmb->add_field( array(
      'name' => 'Disable CIN7 temporally',
      'id'   => 'cin7_stock_disable',
      'type' => 'checkbox',
      'desc' => 'Disable CIN7 connection'
    ) );

    $cmb->add_field( array(
      'name' => 'Stock Type',
      'id'   => 'cin7_stock_type',
      'type' => 'select',
      'options' => array(
        'item' => __('Item', 'cmb2'),
        'box' => __('Box', 'cmb2'),
        'case' => __('Case', 'cmb2'),
      )
    ) );

    $cmb->add_field( array(
      'name' => 'Items per type',
      'id'   => 'cin7_stock_items_per_type',
      'type' => 'text',
      'column' => array(
          'position' => 2,
          'name' => 'CMB2 Custom Column2',
      ),
    ) );
  }

  add_action('woocommerce_checkout_create_order_line_item','adding_custom_data_in_order_items_meta', 10, 4 );
  function adding_custom_data_in_order_items_meta( $item, $cart_item_key, $values, $order ) {
    if( array_key_exists('size', $values) ) {
      $item->update_meta_data( 'Size', $values['size'] );
    }
    if( array_key_exists('custom_title', $values) ) {
      $item->update_meta_data( 'Title', $values['custom_title'] );
    }
    if( array_key_exists('custom_promotion', $values) ) {
      $item->update_meta_data( 'Custom Promotion', $values['custom_promotion'] );
    }
    if( array_key_exists('cart_promotion_item', $values) ) {
      $item->update_meta_data('Cart Promotion', $values['cart_promotion_item']);
    }
  }
?>