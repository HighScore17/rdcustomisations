<?php 
use WPGraphQL\WooCommerce\Model\Order;
global $recurring_order_frequencies;
$recurring_order_frequencies = array(
  array(
    "id" => "two-weeks",
    "label" => "Two Weeks",
    "value" => 14
  ),
  array(
    "id" => "one-month",
    "label" => "One Month",
    "value" => 30
  ),
  array(
    "id" => "two-months",
    "label" => "Two Months",
    "value" => 60
  ),
);

global $recurring_order_discount;
$recurring_order_discount = array(
  "type" => "percentage",
  "value" => 5
);

/**
 * Get a single recurring orders from a user
 */
function wc_horizon_get_recurring_order( $recurring_order_id  ) {
  $recurring_order_post =  get_post( $recurring_order_id );

  if( 
    !$recurring_order_post ||
    $recurring_order_post->post_type != "recurring_orders"
  ) {
    return null;
  }
  
  $recurring_order = new WC_Horizon_Recurrring_Order( $recurring_order_id );
  $recurring_order->load_props_from_post();
  return $recurring_order;
}

/**
 * Get all recurrent orders from a user
 */
function wc_horizon_get_recurring_orders( $user_id = 0 ) {
  if( !$user_id ) {
    $user_id = get_current_user_id();
  }

  if( !$user_id ) {
    return null;
  }

  $posts = get_posts(array(
    "numberposts" => -1,
    "post_type" => "recurring_orders",
    "post_status" => "publish",
    "meta_key" => "ownerid",
    "meta_value" => $user_id,
    "order" => "DESC",
  ));

  return array_map(function($post): WC_Horizon_Recurrring_Order {
    $card = new WC_Horizon_Recurrring_Order( $post->ID );
    $card->load_props_from_post();
    return $card;
  }, $posts);
}

/**
 * Create a recurring order
 */
function wc_horizon_create_recurring_order( $args ) {
  global $recurring_order_frequencies;
  $frequency = __array_find( $recurring_order_frequencies, function( $frequency ) use ($args) {
    return $frequency["id"] === $args["frequency"];
  } );
  $lineitems = wc_horizon_get_lineitems_from_order( $args["order_id"], $args["user_id"] );

  if( !$lineitems ) {
    return new WP_Error("recurring-order", "The order provided hasn't items");
  }

  $recurring_order = new WC_Horizon_Recurrring_Order();
  $recurring_order->set_owner( $args["user_id"] );
  $recurring_order->set_frequency( $frequency ? $frequency : $recurring_order_frequencies[0] );
  $recurring_order->set_address( "shipping", $args["shipping"] );
  $recurring_order->set_payment( $args["payment"]["type"], $args["payment"]["id"] );
  $recurring_order->set_lineitems( $lineitems );
  $recurring_order->set_linked_order( $args["order_id"] );
  $recurring_order->set_status( $args["status"] );
  $recurring_order->set_estimated_shipping_cost( $args["estShippingCost"] ?? 0.0 );
  $recurring_order->save();
  $recurring_order->recalculate_total();
  $recurring_order->save();

  do_action('wc_horizon_recurring_order_created', $recurring_order);

  return $recurring_order;
}

/**
 * Get the lineitems from a order
 */
function wc_horizon_get_lineitems_from_order( $order_id, $user_id ) {
  global $recurring_order_discount;
  $order = wc_get_order( intval( $order_id ) );
  $lineitems = [];

  if( !$order ) {
    return false;
  }
  
  foreach( $order->get_items() as $key => $item ) {
    $size = $item->get_meta("Size");
    $presentation = $item->get_meta("pa_presentation");
    $product = $item->get_product();

    if( $size ) {
      $variation = wc_horizon_get_product_by_size( $item->get_product_id(), $size, $presentation );
    } else {
      $variation = wc_get_product( $item->get_variation_id() );
    }


    if( !$product || !$variation) {
      continue;
    }

    $image_id = $product->get_image_id();
    $image_src = wp_get_attachment_image_url( $image_id, "full" );
    $price = floatval( __get_lineitem_current_price( $item ) ?? 0 );
    $subtotal = $price * $item->get_quantity();
    $need_discount = $user_id ? !Horizon_Product_Helper::user_has_special_price( $user_id, $product, $item->get_quantity() ) : false;
    $discount = $need_discount ? get_discount_amount_from_data( $recurring_order_discount["type"], $recurring_order_discount["value"], $subtotal )  : 0;
    $lineitems[] = array(
      'product_id'    => $item->get_product_id(),
			'variation_id'  => $variation->get_id(),
      'image'         => $image_src ?? "",
			'name'          => $item->get_name(),
			'quantity'      => $item->get_quantity(),
			'subtotal'      => $subtotal,
      'discount'      => array(
        "type" => $recurring_order_discount["type"],
        "value" => $recurring_order_discount["value"],
        "amount" => $discount
      ),
			'total'         => $subtotal - $discount,
			'metaData'      => wc_horizon_recurring_order_filter_meta( $item->get_meta_data(), $variation ),
			'type'          => $item->get_type(),
    );
  }
  return $lineitems;
}

function wc_horizon_recurring_order_filter_meta( $meta, WC_Product $variation ) {
  $meta = array_filter( $meta, function( $item ) {
    return $item->key !== "Size";
  });

  $meta[] = new WC_Meta_Data(array(
    "key" => "pa_size",
    "value" => $variation->get_attribute("pa_size")
  ));

  return $meta;
}

/**
 * Search a product by variation
 */
function wc_horizon_get_product_by_size( $product_id, $size, $presentation = "case") {
  $product = wc_get_product( $product_id );

  if( !$product ) {
    return;
  }

  $variations = $product->get_children();
  $variation = __array_find( $variations, function($variation_id) use ( $size, $presentation ) {
    $variation = wc_get_product( $variation_id );

    if( !$variation ) {
      return;
    }
    
    $pa_presentation = $variation->get_attribute("pa_presentation");
    $pa_size = $variation->get_attribute("pa_size");

    return strtolower( $pa_presentation ) === strtolower($presentation) && strtolower( $pa_size ) === strtolower( $size );
  });

  return $variation ? wc_get_product( $variation ) : null;
}

/**
 * Update a recurring order
 */
function wc_horizon_update_recurring_order( $recurring_order_id, $user_id, $args ) {
  global $recurring_order_frequencies;
  $recurring_order = wc_horizon_get_recurring_order( $recurring_order_id, $user_id );

  if( !$recurring_order ) {
    return null;
  }

  if( $args["frequency"] ) {
    $recurring_order->set_frequency( $recurring_order_frequencies[$args["frequency"]] ?? $recurring_order_frequencies["two-weeks"] );
  }

  if( $args["shipping"] ) {
    $recurring_order->set_address( "shipping", $args["shipping"] );
  }

  if( $args["billing"] ) {
    $recurring_order->set_address( "billing", $args["billing"] );
  }

  if( $args["payment"] ) {
    $recurring_order->set_payment( $args["payment"]["type"], $args["payment"]["id"] );
  }

  if( $args["lineitems"] ) {
    $recurring_order->set_lineitems( $args["lineitems"] );
  }

  if( $args["status"] ) {
    $recurring_order->set_status( $args["status"] );
  }

  if( $args["last_order"] ) {
    $recurring_order->set_last_order( $args["last_order"] );
  }

  $recurring_order->save();

  return $recurring_order;
}

/**
 * Delete a recurring order 
 */
function wc_horizon_delete_recurring_order( $recurring_order_id, $user_id ) {
  $recurring_order = wc_horizon_get_recurring_order( $recurring_order_id, $user_id );

  if( !$recurring_order ) {
    return false;
  }

  wp_delete_post( $recurring_order->get_id(), true );

  return true;
}

function wc_horizon_recurring_order_post_to_array( WC_Horizon_Recurrring_Order $recurring_order ) {
  $last_order_id = $recurring_order->get_last_order();
  $linked_order_id = $recurring_order->get_linked_order();
  return array(
    'id' => $recurring_order->get_id(),
    'frequency' => $recurring_order->get_frequency(),
    'ownerId' => $recurring_order->get_owner(),
    'lastOrder' => $last_order_id ? new Order( $last_order_id ) : null,
    'linkedOrder' => $linked_order_id && get_post_status( $linked_order_id ) !== false ? new Order( $linked_order_id ) : null,
    'status' => $recurring_order->get_status(),
    'payment' => wc_horizon_credit_card_to_array( wc_horizon_get_credit_card( $recurring_order->get_payment()["id"] ) ),
    'shipping' => $recurring_order->get_address('shipping'),
    'billing' => $recurring_order->get_address('billing'),
    'upcomingDelivery' => $recurring_order->get_upcoming_delivery(),
    'createdAt' => $recurring_order->get_created_at(),
    'subtotal' => $recurring_order->get_subtotal(),
    'discount' => $recurring_order->get_discount_total(),
    'estShippingCost' => $recurring_order->get_estimated_shipping_cost(),
    'total' => $recurring_order->get_total(),
    'lineItems' => array(
      "nodes" => $recurring_order->get_lineitems()
    ) 
  );
}


function wc_horizon_recurring_order_get_shipping_cost( WC_Horizon_Recurrring_Order $recurring_order ) {
  $cart = new WC_Cart();
  $lineitems = $recurring_order->get_lineitems();
  $shipping_address = $recurring_order->get_address("shipping");

  foreach( $lineitems as $item ) {
    $cart->add_to_cart( $item["product_id"], $item["quantity"], $item["variation_id"] );
  }
  
  $calculator = new Shipping_Calculator_V2();

  $_POST["shipTo"] = array(
    "Address" => array(
      "PostalCode" => $shipping_address["postcode"],
      "City" => $shipping_address["city"],
      "StateProvinceCode" => $shipping_address["state"]
    )
  );

  $cart_content = $cart->get_cart();

  if( !count( $cart_content ) ) {
    return new WP_Error("cart-empty", "There aren't items in your order");
  }

  $items = $calculator->cart_to_packages( $cart_content );
  $rates = $calculator->get_rates( $items );

  if( !is_array( $rates ) || !is_array( $rates["rates"] ) ) {
    return new WP_Error("empty-rates", "Shipping rates not found. Please verify your shipping address.");
  }

  $lower_rate = null;

  foreach( $rates["rates"] as $rate ) {
    if( !$lower_rate || floatval( $rate["total"] ) < $lower_rate ) {
      $lower_rate = $rate;
    }
  }

  if( is_null( $lower_rate ) ) {
    return new WP_Error("empty-rates", "Shipping rates not found");
  }

  return $lower_rate;
}

function wc_horizon_recurring_order_detect_changes( WC_Horizon_Recurrring_Order $old, WC_Horizon_Recurrring_Order $new ) {
  $changes = [];

  if( $old->get_frequency()["value"] !== $new->get_frequency()["value"] ) {
    $changes[] = "frequency";
  }

  if( $old->get_payment()["id"] !== $new->get_payment()["id"] ) {
    $changes[] = "payment";
  }

  if( implode(",", $old->get_address("shipping")) !== implode( ",", $new->get_address("shipping") ) ) {
    $changes[] = "shipping_address";
  }

  return $changes;
}