<?php
class Horzon_AC_Deal_Stages {
  private $AC_PIPELINE = 0;
  private $AC_HOLD_STAGE = 0;
  private $AC_SHIPPED_STAGE = 0;
  private $AC_DELIVERED_STAGE = 0;
  private $prefix = "";
  private $options = array();

  function __construct( $pipeline, $hold, $shipped, $delivered, $prefix, $options = array() ) {
    $this->AC_PIPELINE = $pipeline;
    $this->AC_HOLD_STAGE = $hold;
    $this->AC_SHIPPED_STAGE = $shipped;
    $this->AC_DELIVERED_STAGE = $delivered;
    $this->prefix = $prefix;
    $this->options = $options;
    $this->add_hooks();
  }

  public function add_hooks() {
    add_action( 'woocommerce_order_status_in-transit', [$this, 'move_deal_to_shipped'], 10, 1);
    add_action( 'woocommerce_order_status_completed', [$this, 'move_deal_to_delivered'], 10, 1);
    add_action( 'horizon_ac_deal', [$this, 'on_new_deal'], 10, 1);
  }

  function on_new_deal( WP_REST_Request $request ) {
    $params = $request->get_params();
    if( !key_exists("type", $params) || $params["type"] !== "deal_add" || !is_array($params["deal"]) ) {
      return [];
    }
    if( intval($params["deal"]["pipelineid"]) !== $this->AC_PIPELINE || intval($params["deal"]["stageid"]) !== $this->AC_HOLD_STAGE ) {
      return [];
    }
    $this->update_free_box_order($params["deal"]);
    return array(
      "success" => true,
    );
  }

  function move_deal_to_shipped( $order_id ) {
    $order = wc_get_order( $order_id );
    $deal_id = $order->get_meta( $this->prefix . 'ac_new_deal_id');
    $tracking_number = $order->get_meta("shipment_tracking_number");

    if( !$deal_id ) {
      return;
    }

    if(!$this->update_deal_stage($deal_id, $this->AC_SHIPPED_STAGE, $tracking_number )) {
      return slack_post_message("Failed to update deal stage to shipped for order #" . $order->get_order_number(), __slack_channel("tickets"));
    }

    $fedex_track_url = "https://www.fedex.com/fedextrack/?trknbr=" . trim($tracking_number);
    $fullname = $order->get_billing_first_name() . " " . $order->get_billing_last_name();
    $address = __get_order_address_formated($order);
    $this->add_deal_note(
      $deal_id,
      "A Lead has received his tracking number email
      Tracking number: $fedex_track_url
      Full Name: $fullname
      Email: {$order->get_billing_email()}
      Shipping Adress: {$address}
      Gloves Size : {$order->get_meta( $this->prefix . 'ac_box_size')}"
    );
  }

  function add_deal_note($deal_id, $note) {
    $AC = new Active_Campaign_SDK();
    $result =  $AC->call("deals/$deal_id/notes", array(
      "note" => array(
        "note" => $note
      )
    ));
    return $result;
  }

  function move_deal_to_delivered( $order_id ) {
    $order = wc_get_order( $order_id );
    $deal_id = $order->get_meta( $this->prefix . 'ac_new_deal_id');

    if( !$deal_id ) {
      return;
    }

    
    if( !$this->update_deal_stage($deal_id, $this->AC_DELIVERED_STAGE) ) {
      slack_post_message("Failed to update deal stage to delivered for order #" . $order->get_order_number(), __slack_channel("tickets"));
    }
  }

  function update_deal_stage( $deal_id, $stage_id, $tracking_number = "" ) {
    $AC = new Active_Campaign_SDK();
    $body = array(
      "deal" => array(
        "stage" => $stage_id,
      )
    );
    if(!empty($tracking_number)) {
      $body["deal"]["fields"][] = array(
        "customFieldId" => "2",
        "fieldValue" => $tracking_number
      );
    }

    $response = $AC->call("deals/" . $deal_id, $body, "PUT");
    $body_response = wp_remote_retrieve_body($response);

    if(empty($body_response)) {
      return null; 
    }
    return array(
      "response" => $body_response
    );
  }

  /**
   * Create a new order based on the deal got by the AC Webhook
   */
  private function update_free_box_order($deal) {
    $orders = wc_get_orders(array(
      'customer' => $deal["contact_email"],
      'created_via' => 'ac_free_sample',
      'limit' => 1
    ));

    if( !count( $orders ) ) {
      return;
    }

    $order = $orders[0];
    $order->update_meta_data( $this->prefix . "ac_new_deal_id", $deal["id"]);
    $order->save();

    // Sync order with shipstation
    $ss_order = ShipStation_Orders::create_order($order, ShipStation_Orders::AWAITING_SHIPMENT_STATUS);
    if($ss_order) {
      $order->update_meta_data("shipstation_order_key", strval($ss_order["orderKey"]));
      $order->update_meta_data("shipstation_order_id", strval($ss_order["orderId"]));
      $order->save();
    } else {
      slack_post_message(":x: Failed to create order #" . $order->get_order_number() ." in ShipStation", __slack_channel("tickets"));
    }
    if( key_exists("withNotifications", $this->options) && $this->options["withNotifications"] ) {
      $this->send_notification_email($order);
    }
  }

  /**
   * Get the customer address from the AC Contact API
   */
  public function get_customer_address($email) {
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

  function send_notification_email( $order ) {
    $context = array(
      "first_name" => $order->get_shipping_first_name(),
      "fullname" => $order->get_shipping_first_name() . " " . $order->get_shipping_last_name(),
      "address" => $order->get_shipping_address_1(),
      "city" => $order->get_shipping_city(),
      "zip" => $order->get_shipping_postcode(),
      "state" => $order->get_shipping_state(),
      "country" => $order->get_shipping_country(),
    );
    postmark_send_email("rd-dentist-request-sample", $context, "customerservice@rocket.direct", $order->get_billing_email());
    postmark_send_email("rd-dentist-request-sample", $context, "customerservice@rocket.direct", "grecia@horizon.com.pr,nick@rocketdistributors.com,javier@horizon.com.pr");
  }
}

new Horzon_AC_Deal_Stages(6, 32, 38, 44, "ac_free_box_" );
new Horzon_AC_Deal_Stages(8, 64, 69, 70, "ac_free_box_dentists_", array(
  "withNotifications" => true
));


add_action('rest_api_init', function() {
  register_rest_route('horizon/v1', 'free-box/deal', array(
    'methods' => ['POST', 'GET'],
    'callback' => function(WP_REST_Request $request) {
      wc_get_logger()->info("Free box deal request: " . wc_print_r($request->get_params(), true), array(
        "source" => "horizon-free-box-deal-request",
      ));
      do_action('horizon_ac_deal', $request);
      return ["success" => true];
    },
    'permission_callback' => '__return_true'
  ));
});
