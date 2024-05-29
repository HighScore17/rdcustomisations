<?php

class Horizon_AC_Sync_Deal_Orders {
  private $AC_PIPELINE = 0;
  private $HOLD_STAGE = 0;
  private $DEAL_KEY = "";
  private $SHIPPED_STAGE = 0;
  private $DELIVERED_STAGE = 0;
  private $config = array();

  function __construct( $pipeline, $hold, $shipped, $delivered, $config ) {
    $this->AC_PIPELINE = $pipeline;
    $this->HOLD_STAGE = $hold;
    $this->SHIPPED_STAGE = $shipped;
    $this->DELIVERED_STAGE = $delivered;
    $this->DEAL_KEY = $config["source"] . "_deal_id";
    $this->config = $config;
    $this->add_hooks();
  }

  public function add_hooks() {
    add_action( 'horizon_ac_new_deal_added', array( $this, 'on_new_deal' ), 10, 2 );
    new Horizon_AC_Order_Stages( $this->SHIPPED_STAGE, $this->DELIVERED_STAGE, $this->DEAL_KEY );
  }

  public function on_new_deal( $deal, $request ) {
    if( $deal["pipelineid"] != $this->AC_PIPELINE || $deal["stageid"] != $this->HOLD_STAGE ) {
      return;
    }
    $ac_deal = Active_Campaign_Deal::get_deal( $deal["id"] );

    if( !$ac_deal || $ac_deal["deal"]["group"] != $this->AC_PIPELINE || $ac_deal["deal"]["stage"] != $this->HOLD_STAGE) {
      return;
    }
    $this->sync_order( $deal );
  }

  function sync_order( $deal ) {
    $address = $this->get_customer_address($deal["contact_email"]);

    if( !$address ) {
      return;
    }

    $order = $this->create_woocommerce_order( $deal, $address, $this->config["sync_woocommerce"] );
    print_r($this->config);
    if( $this->config["sync_shipstation"] ) {
      $this->create_shipstation_order( $order );
    }
  }

  function create_woocommerce_order( $deal, $address, $save = true ) {
    $order = wc_create_order( array(
      'status' => $this->config["order_status"] ?? 'wc-processing',
      'created_via' => $this->config["source"] ?? "unknown",
    ) );
    foreach ( $this->config["products"] ?? [] as $product ) {
      $order->add_product(null, 1, array(
        "name" => $product["name"],
        "product_id" => 999999,
        'variation_id' => 0,
        'variation' => array(),
        'subtotal' => $product["subtotal"],
        'total' => $product["total"],
      ));
    }
    
    $order->calculate_totals();
    $order->set_address($address, 'billing');
    $order->set_address($address, 'shipping');
    $order->update_meta_data(  $this->DEAL_KEY, $deal["id"]);
    $order->update_meta_data( "disable_default_notifications", $this->config["disable_notifications"] ? "yes" : "no" );

    $metaItems = $this->config["get_meta"] ? $this->config["get_meta"]( $deal, $address ) : [];

    foreach( $metaItems as $meta ) {
      $order->update_meta_data( $meta["key"], $meta["value"]);
    }

    if( $save ) {
      $order->save();
    }

    return $order;
  }

  function create_shipstation_order( WC_Order $order ) {
    $ss_order = ShipStation_Orders::create_order($order, ShipStation_Orders::AWAITING_SHIPMENT_STATUS);
    if( !$ss_order ) {
      return slack_post_message(":x: Failed to create order #" . $order->get_order_number() ." in ShipStation", __slack_channel("tickets"));
    }
    if( $ss_order && $order->get_id() ) {
      $order->update_meta_data("shipstation_order_key", strval($ss_order["orderKey"]));
      $order->update_meta_data("shipstation_order_id", strval($ss_order["orderId"]));
      $order->save();
    }
    return $ss_order;
  }

   /**
   * Get the customer address from the AC Contact API
   */
  public function get_customer_address( $email ) {
    $fields_ids = array(
      "3" => "company",
      "24" => "address_1",
      "25" => "address_2",
      "26" => "city",
      "27" => "state",
      "28" => "postcode",
      "29" => "country",
      "38" => "glove_size"
    );
    $customer = Active_Campaign_Contacts::get_by_email( $email );

    if(!$customer) {
      return null;
    }

    $fields = Active_Campaign_Contacts::get_fields($customer["links"]["fieldValues"]);

    if(!$fields || count($fields) === 0) {
      return null;
    }
    $address = array(
      "email" => $customer["email"],
      "first_name" => $customer["firstName"],
      "last_name"  => $customer["lastName"],
      "phone"      => $customer["phone"],
    );
    foreach($fields as $field) {
      if(key_exists($field["field"], $fields_ids)) {
        $address[$fields_ids[$field["field"]]] = $field["value"];
      }
    }
    return $address;
  }
}


new Horizon_AC_Sync_Deal_Orders( 12, 117, 110, 111, array(
  "source" => "active_campaign_thinner_gloves_sample_box_dentists",
  "sync_woocommerce" => true,
  "sync_shipstation" => true,
  "disable_notifications" => true,
  "products" => array(
    array(
      "name" => "Free box of Thinner Gloves",
      "subtotal" => 0,
      "total" => 0
    )
  )
) );
