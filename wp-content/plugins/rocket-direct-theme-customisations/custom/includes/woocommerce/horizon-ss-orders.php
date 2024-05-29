<?php
class Horizon_ShipStation_Orders {
  public static $instance = null;
  public $logger = null;
  const FEDEX_DELIVERED_CODE = "DL";

  static function init() {
    if( !self::$instance instanceof Horizon_ShipStation_Orders ) {
      self::$instance = new Horizon_ShipStation_Orders();
      self::$instance->add_hooks();
      self::$instance->logger = wc_get_logger();
    }
  }

  public function add_hooks() {
    //add_action( 'woocommerce_new_order', [$this, 'on_new_order'], 10, 1 );
    //woocommerce_new_order
    add_action( 'woocommerce_order_status_processing', [$this, 'on_proccessing'], 10, 1);
    add_action( 'horizon_update_in_transit_orders', [$this, 'update_order_delivery_status'], 10, 1 );
    add_action('rest_api_init', function() {
      register_rest_route('horizon/v1', 'shipstation/order-shipped', array(
        'methods' => ['POST'],
        'callback' => array($this, 'order_shipped_webhook'),
        'permission_callback' => '__return_true'
      ));
      register_rest_route('horizon/v1', 'shipstation/order-synchronized', array(
        'methods' => ['POST'],
        'callback' => array($this, 'order_synchronized'),
        'permission_callback' => '__return_true'
      ));
    });
  }

  public function on_proccessing($order_id) {
    $order = wc_get_order( $order_id );
    $this->update_order_status($order);
  }

  private function update_order_status( $order ) {
    $ss_order_id = $order->get_meta("shipstation_order_id");
    if( empty($ss_order_id) ) {
      return;
    }
    $result = ShipStation_Orders::update_order( $order, $ss_order_id);
    if(!$result) {
      slack_post_message(":x: Failed to update order #" . $order->get_order_number() ." in ShipStation", __slack_channel("tickets"));
    }
  }

  public function order_shipped_webhook( WP_REST_Request $request ) {
    $params = $request->get_params();
    $logger = wc_get_logger();
    $logger->info( "Webhook params: " . wc_print_r($params, true), array( "source"  => "Horizon ShipStation Orders" ) );

    if( empty( $params["resource_url"] ) || empty( $params["resource_type"] ) || $params["resource_type"] !== "SHIP_NOTIFY" ) {
      return array("error" => "BAD_REQUEST");
    }

    $url = str_replace( "includeShipmentItems=False", "includeShipmentItems=True", $params["resource_url"] );

    $response = ShipStation_SDK::request( $url, "GET" );

    if( !key_exists("shipments", $response) || !is_array( $response["shipments"] ) ) {
      return array("error" => "BAD_BODY");
    }

    foreach( $response["shipments"] as $shipment ) {
      $logger->info( "Webhook Shipment: " . wc_print_r($shipment, true), array( "source"  => "Horizon ShipStation Orders" ) );
      $order = $this->get_order_by_ss_key( $shipment );

      if( !$order || $order->get_billing_email() !== $shipment["customerEmail"] ) {
        continue;
      }

      if( $shipment["trackingNumber"] ) {
        $_POST["shipment_tracking_number"] = $shipment["trackingNumber"];
        $_POST["shipment_provider"] = "FedEx";
        $_POST["shipment_date"] = Date('m/d/Y', strtotime('+5 days'));
        send_email_shipping_tracking( $order->get_id() );
        
        $old_traking = $order->get_meta( "shipment_tracking_number" ) ?? "";
        $order->update_meta_data(
          "shipment_tracking_number", 
          $old_traking ? $old_traking . "," . $shipment["trackingNumber"] : $shipment["trackingNumber"]
        );
        $order->update_meta_data("shipment_provider", "FedEx");
        $order->update_meta_data("shipment_date", Date('m/d/Y', strtotime('+5 days')));
      }

      if( $order->get_meta( "has_backordered_items" ) === "yes" ) {
        $order_items = $this->get_ss_order_items( $order, false );

        if( key_exists("shipmentItems", $shipment) && is_array( $shipment["shipmentItems"] ) ) {
          foreach( $shipment["shipmentItems"] as $item ) {
            $id = strval( $item["orderItemId"] );
            if( !in_array( $id, $order_items ) ) {
              array_push( $order_items, $id );
            }
          }
        }
        $this->set_ss_order_items( $order, $order_items, false );
        $order_items_created = $this->get_ss_order_items( $order );

        if( count( $order_items_created ) === count( $order_items ) ) {
          $order->set_status("wc-in-transit", "ShipStation has shipped this order");
        } else {
          $order->set_status( "wc-in-transit-backordered", "ShipStation has send some items" );
        }

      } else {
        $order->set_status("wc-in-transit", "ShipStation has shipped this order");
      }

      $order->save();
      do_action('horizon_ss_order_shipped', $order);
    }
    
    return array("success" => true);
  }

  public function order_synchronized( WP_REST_Request $request ) {
    $params = $request->get_params();

    if( empty( $params["resource_url"] ) || empty( $params["resource_type"] ) || $params["resource_type"] !== "ORDER_NOTIFY" ) {
      return array("error" => "BAD_REQUEST");
    }
    
    $response = ShipStation_SDK::request( $params["resource_url"], "GET" );

    if( !key_exists("orders", $response) || !is_array( $response["orders"] ) ) {
      return array("error" => "BAD_BODY");
    }

    foreach( $response["orders"] as $ss_order ) {
      $order = $this->get_order_by_ss_key( $ss_order );

      if( !$order || !key_exists("items", $ss_order) || !is_array( $ss_order["items"] ) ) {
        continue;
      }

      $order_items = $this->get_ss_order_items( $order );

      foreach( $response["items"] as $item ) {
        if( !in_array( strval($item["orderItemId"]), $order_items ) ) {
          array_push( $order_items, strval($item["orderItemId"]) );
        }
      }

      $order->update_meta_data( "ss_items_created", $order_items );
      $order->save();
    }
  }

  public function get_ss_order_items( $order, $created = true ) {
    $order_items = $order->get_meta( "ss_items_". $created ? "created": "shipped" );

    if( !is_array( $order_items ) ) {
      $order_items = array();
    }

    return $order_items;
  }

  public function set_ss_order_items( WC_Order $order, $items, $created = true ) {
    $order->update_meta_data( "ss_items_". $created ? "created": "shipped", $items );
    $order->save();
  }

  public function get_order_by_ss_key( $order ) {
    if( preg_match("/^rd-(\d+)$/", $order["orderNumber"]) ) {
      $order_id = intval( str_replace("rd-", "", $order["orderNumber"]) );
      return wc_get_order( $order_id );
    } else if ( preg_match("/^\d+$/", $order["orderNumber"]) ) {
      $order_id = intval( $order["orderNumber"] );
      return wc_get_order( $order_id );
    } else {
      return null;
    }
  }

  /**
   * Check the order status on Fedex and update it if the status is delivered
   */
  public function update_order_delivery_status() {
    $fedex = new FEDEX_SDK();
    $orders = wc_get_orders(array(
      'status' => array('wc-in-transit'),
      'limit' => -1,
    ));

    foreach($orders as $order) {
      $carrier = $order->get_meta("shipment_provider");
      $tracking_numbers = explode(",", $order->get_meta("shipment_tracking_number"));
      $traking_numbers_counter = count($tracking_numbers);

      if( 
        $carrier !== "FedEx" ||
        !$tracking_numbers || 
        $traking_numbers_counter === 0 || 
        ( $traking_numbers_counter === 1 && $tracking_numbers[0] === "")
      ) {
        continue;
      }
      

      $body = $fedex->request("track/v1/trackingnumbers", array(
        "body" => $this->get_fedex_tracking_request_body($tracking_numbers),
        "retrieve_body" => true,
      ));


      if( !is_array($body["output"]["completeTrackResults"])) {
        return;
      }

      $not_delivered = __array_find($body["output"]["completeTrackResults"], function($item) {
        if( 
          $item["trackResults"][0]["error"] ||
          $item["trackResults"][0]["latestStatusDetail"]["code"] !== self::FEDEX_DELIVERED_CODE
        ) {
          return true;
        }
        return false;
      });

      
      if(!$not_delivered) {
        $order->update_status( "completed", "Fedex has delivered the order." );
        wc_horizon_set_order_brand( $order );
        slack_post_message(":house: The order #" . $order->get_order_number() . " of " . $order->get_billing_email() . " was delivered.", __slack_channel("logistics"));
      }
    }
  }

  private function log( $message, $source = "horizon-fedex-orders-status" ) {
    if(!$this->logger) {
      return;
    }
    $this->logger->info( $message , array( "source"  => $source ) );
  }

  private function get_fedex_tracking_request_body( $tracking_numbers ) {
    $body = array(
      "includeDetailedScans" => false,
      "trackingInfo" => array()
    );
    foreach($tracking_numbers as $tracking_number) {
      $body["trackingInfo"][] = array(
        "trackingNumberInfo" => array(
          "trackingNumber" => trim($tracking_number) ,
        )
      );
    }
    return json_encode($body);
  }
}

Horizon_ShipStation_Orders::init();