<?php

class Active_Campaign_Free_Sample {
  public static $instance;

  const AC_DEAL_GROUP = 7;
  const AC_HOLD_STAGE = 45;
  const AC_SHIPPED_STAGE = 51;
  const AC_DELIVERED_STAGE = 57;
  const FEDEX_DELIVERED_CODE = "DL";


  static function init() {
    if( !self::$instance instanceof Active_Campaign_Free_Sample ) {
      self::$instance = new Active_Campaign_Free_Sample();
      self::$instance->add_hooks();
    }
  }

  private function add_hooks() {
    add_action('rest_api_init', function() {
      register_rest_route('horizon/v1', 'free-sample/deal', array(
        'methods' => ['POST', 'GET'],
        'callback' => array($this, 'on_free_sample_dead_added'),
        'permission_callback' => '__return_true'

      ));
      register_rest_route('horizon/v1', 'free-sample/deal', array(
        'methods' => ['PUT'],
        'callback' => array($this, 'update_deal_stage'),
        'permission_callback' => '__return_true'

      ));
      register_rest_route('horizon/v1', 'free-sample/orders', array(
        'methods' => ['POST'],
        'callback' => array($this, 'check_order_delivery_status'),
        'permission_callback' => '__return_true'

      ));
    });
    add_action('admin_menu', [ $this, 'register_woocommerce_submenu_page']);
  }

  public function on_free_sample_dead_added(WP_REST_Request $request) {
    file_put_contents( get_temp_dir() . '/free-sample-deal.txt', json_encode($request->get_params()));
    $params = $request->get_params();
    
    if( !key_exists("type", $params) || $params["type"] !== "deal_add" || !is_array($params["deal"]) ) {
      return [];
    }

    if( intval($params["deal"]["pipelineid"]) !== self::AC_DEAL_GROUP ) {
      return [];
    }

    $this->save_deal($params["deal"]);
    $this->create_free_sample_order($params["deal"]);
    
    return array(
      "success" => true,
      "dir" => get_temp_dir(),
    );
  }

  private function update_deal( $dealId, $stageId, $trackingNumber ) {
    global $wpdb;
    $wpdb->update(Active_Campaign_Free_Sample::get_table_name(), array(
      "stage_id" => $stageId,
      "tracking_number" => $trackingNumber
    ), array(
      "deal_id" => $dealId
    ));
  }

  private function save_deal( $deal  ) {
    global $wpdb;
    $wpdb->insert( $this->get_table_name(), array(
      "deal_id" => intval($deal["id"]) ,
      "stage_id" => intval($deal["stageid"]),
      "pipeline_id" => intval($deal["pipelineid"]),
      "contact_id" => intval($deal["contactid"]),
      "deal_title" => $deal["title"],
      "stage_title" => $deal["stage_title"],
      "value_raw" => $deal["value_raw"],
      "email" => $deal["contact_email"],
      "firstname" => $deal["contact_firstname"],
      "lastname" => $deal["contact_lastname"],
    ));
  }

  static function get_table_name() {
    global $wpdb;
    return $wpdb->prefix . "ac_free_sample_deal";
  }

  function register_woocommerce_submenu_page() {
    add_submenu_page( 
      'woocommerce', 
      __( 'Free Samples', 'horizon' ), 
      __( 'Free Samples', 'horizon' ), 
      'manage_woocommerce', 
      'free_samples', 
      [$this, 'print_submenu_page']
    );
  }

  function print_submenu_page() {
    require HORIZON_CUSTOMISATIONS_DIR . "/custom/views/free_samples.php";
  }

  static function update_deal_stage( WP_REST_Request $request ) {
    $AC = new Active_Campaign_SDK();
    $free_sample = new Active_Campaign_Free_Sample();
    $params = $request->get_params();

    if( !$params["deal_id"] || !$params["stage_id"] || !Active_Campaign_Free_Sample::validate_deal_stage( intval($params["stage_id"]) ) ) {
      return ["err" => "Invalid deal id or stage id"];
    }

    $response = $AC->call("deals/" . $params["deal_id"], array(
      "deal" => array(
        "title" => "Api deal",
        "stage" => $params["stage_id"],
        "fields" => array (
          array(
            "customFieldId" => "2",
            "fieldValue" => $params["tracking_number"]
          )
        ) 
      )
    ), "PUT");
    
    $body_response = wp_remote_retrieve_body($response);

    if(empty($body_response)) {
      return ["err" => "Empty response"];
    }
    $free_sample->update_deal( $params["deal_id"], $params["stage_id"], $params["tracking_number"] );
    return array(
      "response" => $body_response
    );
  }

  static function validate_deal_stage( $stage ) {
    if( $stage === self::AC_HOLD_STAGE || $stage === self::AC_SHIPPED_STAGE || $stage === self::AC_DELIVERED_STAGE ) {
      return true;
    }
    return false;
  }

  public function check_order_delivery_status() {
    $fedex = new FEDEX_SDK();
    $orders = wc_get_orders(array(
      'status' => array('wc-in-transit'),
      'limit' => -1,
    ));

    foreach($orders as $order) {
      $tracking = array(
        "number" => $order->get_meta("shipment_tracking_number"),
        "carrier" => $order->get_meta("shipment_provider"),
      );

      if( empty( $tracking["number"] ) || $tracking["carrier"] !== "FedEx" ) {
        continue;
      }

      $body = $fedex->request("track/v1/trackingnumbers", array(
        "body" => $this->get_fedex_tracking_request_body($tracking),
        "retrieve_body" => true,
      ));

      if( !is_array($body["output"])) {
        return;
      }

      $latest_status = $body["output"]["completeTrackResults"][0]["trackResults"][0]["latestStatusDetail"]["code"];
      if($latest_status === Active_Campaign_Free_Sample::FEDEX_DELIVERED_CODE) {
        $order->update_status("completed");
        wc_horizon_set_order_brand( $order );
        slack_post_message(":truck: The order of " . $order->get_billing_email() . " was delivered.", __slack_channel("logistics"));
      }
    }
  }

  private function get_fedex_tracking_request_body( $tracking ) {
    return json_encode( array(
      "includeDetailedScans" => false,
      "trackingInfo" => array(
        array(
          "trackingNumberInfo" => array(
            "trackingNumber" => $tracking["number"],
          )
        )
      )
    ));
  }

  private function create_free_sample_order($deal) {
    $address = $this->get_customer_address($deal["contact_email"]);
    $order = wc_create_order( array(
      'status' => 'wc-pending',
      'created_via' => 'ac_free_sample',
    ) );
    $order->add_product(null, 1, array(
      "name" => "Free Gloves Box",
      "product_id" => 999999,
      'variation_id' => 0,
      'variation' => array(),
      'subtotal' => 0,
      'total' => 0,
    ));
    $order->calculate_totals();
    $order->set_address($address, 'billing');
    $order->set_address($address, 'shipping');
    $order->update_meta_data("ac_deal_id", $deal["id"]);
    $order->save();
    slack_post_message(":package: :free: " . $deal["contact_email"] . " placed a new free sample order.", __slack_channel("tickets"));
    
    // Sync order with shipstation
    $ss_order = ShipStation_Orders::create_order($order);
    if($ss_order) {
      $order->update_meta_data("shipstation_order_key", strval($ss_order["orderKey"]));
      $order->update_meta_data("shipstation_order_id", strval($ss_order["orderId"]));
      $order->save();
    } else {
      slack_post_message(":x: Failed to create order #" . $order->get_order_number() ." in ShipStation", __slack_channel("tickets"));
    }
  }

  public function get_customer_address($email) {
    $fields_ids = array(
      "3" => "company",
      "24" => "address_1",
      "25" => "address_2",
      "26" => "city",
      "27" => "state",
      "28" => "postcode",
      "29" => "country",
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


Active_Campaign_Free_Sample::init();