<?php

class WC_Horizon_Exclude_Products_From_All_Coupons {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof WC_Horizon_Exclude_Products_From_All_Coupons ) {
      self::$instance = new WC_Horizon_Exclude_Products_From_All_Coupons();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'woocommerce_product_options_inventory_product_data', [ $this, 'add_product_checkbox_disable_coupons' ] );
    add_action( 'woocommerce_process_product_meta', [ $this, 'save_product_checkbox_disable_coupons' ] );
    //add_filter( 'woocommerce_coupon_get_discount_amount', [ $this, 'zero_discount_for_excluded_products' ], 12, 5 );
    add_filter('woocommerce_coupon_is_valid_for_product', [ $this, 'set_coupon_validity_for_excluded_products' ], 12, 4);
  }

  function add_product_checkbox_disable_coupons() {
    echo '<div class="product_custom_field">';
    global $post;
    woocommerce_wp_checkbox( array(
        'id'        => '_disabled_for_coupons',
        'label'     => __('Disabled for coupons', 'woocommerce'),
        'description' => __('Disable this products from coupon discounts', 'woocommerce'),
        'value' => get_post_meta( $post->ID, '_disabled_for_coupons', true ) === "yes" ? "yes" : "no"
    ) );
    echo '</div>';;
  }

  function save_product_checkbox_disable_coupons( $post_id ) {
    $product_disabled = isset( $_POST['_disabled_for_coupons'] ) && $_POST['_disabled_for_coupons'] === "yes";
    $disabled_products = get_option( '_products_disabled_for_coupons', array() );
    
    if( !is_array( $disabled_products ) ) {
      $disabled_products = array();
    }

    if( !$product_disabled ) {
      $disabled_products = array_filter( $disabled_products, function( $item ) use ( $post_id ) {
        return $item !== $post_id;
      });
    } else if( !in_array( $post_id, $disabled_products ) ) {
      $disabled_products[] = $post_id;
    }

    update_option( "_products_disabled_for_coupons", array_unique( $disabled_products ) );
    update_post_meta( $post_id, '_disabled_for_coupons', $product_disabled ? "yes" : "no" );
  }

  function set_coupon_validity_for_excluded_products($valid, WC_Product $product, $coupon, $values ) {
    $disabled_products = get_option( '_products_disabled_for_coupons' );
    

    $id = $product->get_parent_id() ?? $product->get_id();
    
    if( !count( $disabled_products ) )  {
      return $valid;
    }

    if( in_array( $id, $disabled_products ) ) {
      return false;
    }

    return $valid;
  }

  function zero_discount_for_excluded_products($discount, $discounting_amount, $cart_item, $single, $coupon ){
    $disabled_products = get_option( '_products_disabled_for_coupons' );
    
    if( !count( $disabled_products ) )  {
      return $discount;
    }

    if( in_array( $cart_item['product_id'], $disabled_products ) )
      $discount = 0;
      
    return $discount;
  }
}

WC_Horizon_Exclude_Products_From_All_Coupons::init();