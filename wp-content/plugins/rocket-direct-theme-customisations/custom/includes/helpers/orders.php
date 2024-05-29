<?php

function __get_order_admin_url( WC_Order $order ) {
  return admin_url( 'post.php?post=' . absint( $order->get_id() ) ) . '&action=edit';
}

function __get_wc_order( $order ) : WC_Order {
  return !is_object( $order ) ? wc_get_order( $order ) : $order;
}

function __get_order_address_formated( WC_Order $order ) {
  return $order->get_billing_address_1() . ", " . $order->get_billing_city() . " " . $order->get_billing_state() . " " . $order->get_billing_postcode();
}

function __customer_has_boughts( $email ) {
  $orders = wc_get_orders( array(
		'email' => $email,
    'limit' => 50,
	) );
  $orders = is_array( $orders ) ? $orders : array( $orders );
  $orders = array_filter( $orders, function(WC_Order $order) {
    return $order->get_status() !== 'cancelled' && $order->get_total() > 0;
  } );

  return count( $orders ) > 0;
}


function __get_lineitem_current_price( $source, $group = null ) {
  $product = wc_get_product( $source->get_product_id() );

    if( !$product ) {
      return null;
    }

    if( $product->is_type('variable') ) {
      $product = wc_get_product( $source->get_variation_id() );
    }

    if( $group && $group === "b2c" ) {
      return Horizon_Product_Helper::get_b2c_price( $product, $source->get_quantity() );
    }

    if( $group && $group !== "b2c" ) {
      return Horizon_Product_Helper::get_b2b_price( $product, $source->get_quantity(), $group );
    }

    $user_id = get_current_user_id();
    
    if(get_user_meta( $user_id, "b2bking_b2buser", true ) !== "yes") {
      return Horizon_Product_Helper::get_b2c_price( $product, $source->get_quantity() );
    }

    return Horizon_Product_Helper::get_b2b_price( $product, $source->get_quantity(), get_user_meta( $user_id, "b2bking_customergroup", true ) );
}

function __order_has_backordered_items( WC_Order $order ) {
  return __array_find( $order->get_items(), function( WC_Order_Item $item ) {
    $product = $item->get_product();
    return $product && method_exists( $product, 'is_on_backorder' ) ? 
      $product->is_on_backorder( $item->get_quantity() ) : false;
  } ) !== null;
}


function __customer_has_orders_paid( $value = 0 ) {
  if ( is_numeric( $value ) && !is_user_logged_in() ) {
    return false;
  }

  global $wpdb;
  
  // Based on user ID (registered users)
  if ( is_numeric( $value) ) { 
      $meta_key   = '_customer_user';
      $meta_value = $value == 0 ? (int) get_current_user_id() : (int) $value;
  } 
  // Based on billing email (Guest users)
  else { 
      $meta_key   = '_billing_email';
      $meta_value = sanitize_email( $value );
  }
  
  $paid_order_statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );

  $count = $wpdb->get_var( $wpdb->prepare("
      SELECT COUNT(p.ID) FROM {$wpdb->prefix}posts AS p
      INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
      WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $paid_order_statuses ) . "' )
      AND p.post_type LIKE 'shop_order'
      AND pm.meta_key = '%s'
      AND pm.meta_value = %s
      AND p.post_date >= '2022-06-03'
      LIMIT 1
  ", $meta_key, $meta_value ) );

  // Return a boolean value based on orders count
  return $count > 0;
}

function __is_valid_wc_order( $order, $validate_id = false ) {
  if( !is_object( $order ) ) {
    $order = wc_get_order( intval( $order ) );
  }

  $valid = is_a( $order, 'WC_Order' );

  if( $validate_id ) {
    $order_id = $order->get_id();
    $post = get_post( $order_id );
    $valid = $post && $post->post_type === "shop_order";
  }

  return $valid;
}

