<?php

class WC_Horizon_Recurrring_Order extends WC_Horizon_Post {
  protected $object_type = "recurring_orders";
  protected $public_meta = array(
    'ownerid',
    'frequency',
    'line-items',
    'last-order',
    'linked_order',
    'status',
    'payment',
    'subtotal',
    'discount',
    'discount_total',
    'total',
    'shipping_first_name',
    'shipping_last_name',
    'shipping_company',
    'shipping_address_1',
    'shipping_address_2',
    'shipping_city',
    'shipping_state',
    'shipping_postcode',
    'shipping_country',
    'shipping_phone',
    'shipping_email',
    'est-shipping-cost',
    'shipping_cost_precalculated'
  );

  const ALLOWED_STATUS = [
    'active',
    'inactive'
  ];

  const ALLOWED_DISCOUNTS = [
    'fixed',
    'percentage'
  ];

  function __construct($id = 0) {
    parent::__construct($id);
  }

  function get_address_type( $type ) {
    return $type === "billing" || $type === "shipping" ? $type : "billing";
  }

  function get_owner() {
    return $this->get_prop("ownerid");
  }

  function get_frequency() {
    return $this->get_prop("frequency");
  }

  function get_lineitems() {
    return $this->get_prop("line-items");
  }

  function get_linked_order() {
    return $this->get_prop( 'linked_order' );
  }

  function get_last_order() {
    return $this->get_prop("last-order");
  }

  function get_status() {
    return $this->get_prop("status");
  }

  function get_subtotal() {
    return $this->get_prop("subtotal");
  }

  function get_estimated_shipping_cost() {
    $cost = $this->get_prop("est-shipping-cost");
    return empty( $cost ) ? 0 : $cost;
  }

  function get_precalculated_shipping_cost() {
    return $this->get_prop("shipping_cost_precalculated");
  }

  function get_discount() {
    return $this->get_prop("discount");
  }

  function get_total() {
    return $this->get_prop("total");
  }

  function get_upcoming_delivery() {
    $order = wc_get_order( $this->get_last_order() );
    $frequency = $this->get_frequency();

    if( !$frequency ) {
      return null;
    }

    $base_date = $this->get_created_at();

    if ( is_a( $order, 'WC_Order' ) ) {
      $paid = $order->get_date_paid();
      $created = $order->get_date_created();

      if( $paid ) {
        $base_date = $paid->modify("+1 day");
      } else if ( $created ) {
        $base_date = $created->modify("+2 day");
      }
    }

    return $base_date->modify("+". $frequency["value"] . " day")->format("Y-m-d H:i:s");
  }

  function get_created_at() {
    return new WC_DateTime( get_the_date("Y-m-d H:i:s", get_post( $this->get_id() ) ) );
  }

  function get_payment() {
    return $this->get_prop("payment");
  }

  function get_discount_total() {
    return $this->get_prop("discount_total");
  }

  function get_address( $type = "billing" ) {
    $address = $this->get_address_type($type) . "_";
    return array(
      "first_name" => $this->get_prop($address . "first_name"),
      "last_name" => $this->get_prop($address . "last_name"),
      "address_1" => $this->get_prop($address . "address_1"),
      "address_2" => $this->get_prop($address . "address_2"),
      "city" => $this->get_prop($address . "city"),
      "state" => $this->get_prop($address . "state"),
      "postcode" => $this->get_prop($address . "postcode"),
      "country" => $this->get_prop($address . "country"),
      "phone" => $this->get_prop($address . "phone"),
      "company" => $this->get_prop($address . "company"),
      "email" => $this->get_prop($address . "email")
    );
  }

  function set_owner( $ownerid ) {
    $this->set_prop("ownerid", $ownerid);
  }

  function set_frequency($frequency) {
    $this->set_prop("frequency", $frequency);
  }

  function set_lineitems( $items ) {
    $this->set_prop("line-items", array_filter( $items, function($item) {
      return $item["product_id"] && is_numeric( $item["quantity"] ) && $item["quantity"] > 0;
    } ));
  }

  function set_linked_order( $order_id ) {
    $this->set_prop( 'linked_order', $order_id );
  }

  function set_last_order( WC_Order $order ) {
    $this->set_prop("last-order", $order->get_id());
  }

  function set_status( $status ) {
    $current_status = in_array( $status, self::ALLOWED_STATUS, true ) ? $status : 'inactive';
    $this->set_prop("status", $current_status);
  }

  function set_payment( $type = '', $id = 0 ) {
    $this->set_prop("payment", array(
      "type" => $type,
      "id" => $id
    ));
  }


  function set_estimated_shipping_cost( $cost ) {
    $this->set_prop("est-shipping-cost", $cost);
  }

  function set_precalculated_shipping_cost( $shipping ) {
    return $this->set_prop("shipping_cost_precalculated", $shipping);
  }

  function set_discount( $type, $amount ) {
    $this->set_prop("discount", array(
      "type" => in_array( $type, self::ALLOWED_DISCOUNTS ) ? $type : "fixed",
      "amount" => intval( $amount )
    ));
  }

  private function set_discount_total( $total ) {
    $this->set_prop("discount_total", $total );
  }

  function recalculate_total() {
    $items = $this->get_lineitems();
    $subtotal = 0;

    foreach( $items as $item ) {
      $subtotal += floatval( $item["total"] );
    }

    $discount_total = $this->calculate_discount_total( $subtotal, $this->get_discount() ?? [ "type" => "unknown", "amount" => 0 ] );
    $total = $subtotal - $discount_total;
    $this->set_prop("subtotal", $subtotal);
    $this->set_discount_total( $discount_total );
    $this->set_prop("total", $total);
  }

  function calculate_discount_total( $amount, $discount ) {
    if( $discount["type"] === "fixed" ) {
      return $discount["amount"];
    }

    if( $discount["type"] === "percentage" && $discount["amount"] > 0 && $discount["amount"] <= 100 ) {
      return $amount * ($discount["amount"] / 100);
    }

    return 0;
  }

  function is_active() {
    return $this->get_prop("status") === "active";
  }

  function set_address( $type, $address ) {
    $address_type = $this->get_address_type($type) . "_";
    $this->set_prop($address_type . "first_name", $address["firstName"]);
    $this->set_prop($address_type . "last_name", $address["lastName"]);
    $this->set_prop($address_type . "address_1", $address["address1"]);
    $this->set_prop($address_type . "address_2", $address["address2"]);
    $this->set_prop($address_type . "city", $address["city"]);
    $this->set_prop($address_type . "state", $address["state"]);
    $this->set_prop($address_type . "postcode", $address["postcode"]);
    $this->set_prop($address_type . "country", $address["country"]);
    $this->set_prop($address_type . "phone", $address["phone"]);
    $this->set_prop($address_type . "company", $address["company"]);
    $this->set_prop($address_type . "email", $address["email"]);
  }

  protected function get_post_title( $id ) {
    return "Subscription #" . $id;
  }
}

add_action('init', function() {
  register_post_type("recurring_orders", array(
    "label" => "Recurring Orders",
    "public" => false,
    "exclude_from_search" => true,
    "show_ui" => true,
    "has_archive" => false,
    "rewrite" => false,
    "show_in_rest" => true,
    'supports' => array( 'title', 'custom-fields' )
  ));
});

add_action( 'add_meta_boxes',function( $post_type ) {
  $post_types = array('recurring_orders');     //limit meta box to certain post types
  if ( in_array( $post_type, $post_types ) ) {
      add_meta_box(
          'recurring_orders_details_metabox'
          ,esc_html__( 'Products', 'horizon_recurring_Orders' )
          ,'asdddddddd'
          ,$post_type
          ,'advanced'
          ,'high'
      );
  }
} );

function asdddddddd() {
  //require_once HORIZON_CUSTOMISATIONS_DIR . "/custom/views/recurring-orders/products.php";
}