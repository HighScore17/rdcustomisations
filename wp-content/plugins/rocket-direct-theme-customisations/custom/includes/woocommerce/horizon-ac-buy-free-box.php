<?php

class Horizon_AC_1st_Buy_After_Free_Box {
  public static $instance = null;
  const AC_DEAL_GROUP = 6;
  const AC_CUSTOMER_STAGE = 36;
  const AC_SHIPPED_STAGE = 62;
  const AC_DELIVERED_STAGE = 60;

  static function init() {
    if( !self::$instance instanceof Horizon_AC_1st_Buy_After_Free_Box ) {
      self::$instance = new Horizon_AC_1st_Buy_After_Free_Box();
      self::$instance->add_hooks();
    }
  }

  public function add_hooks() {
    add_action( 'woocommerce_order_status_in-transit', [$this, 'move_deal_to_shipped'], 10, 1);
    add_action( 'woocommerce_order_status_completed', [$this, 'move_deal_to_delivered'], 10, 1);
    add_action('rest_api_init', function() {
      register_rest_route('horizon/v1', 'free-box/first-buy', array(
        'methods' => ['POST', 'GET'],
        'callback' => array($this, 'webhook_1st_buy'),
        'permission_callback' => '__return_true'
      ));
    });
  }

  function webhook_1st_buy( WP_REST_Request $request ) {
    $params = $request->get_params();
    $this->log( $params, true );

    if( 
      !is_array( $params["deal"] ) ||
      $params["deal"]["pipelineid"] != self::AC_DEAL_GROUP ||
      $params["deal"]["stageid"] != self::AC_CUSTOMER_STAGE 
    ) {
      return new WP_Error( 'invalid_request', 'Invalid request', array( 'status' => 400 ) );
    }

    $orders_args = array('customer' => $params["deal"]["contact_email"], 'limit' => 1);
    $orders = wc_get_orders( $orders_args );

    if(!count($orders)) {
      $this->log( "No orders found", true );
      return new WP_Error( 'no_orders', 'No orders found', array( 'status' => 400 ) );
    }

    $deal_id = $params["deal"]["id"];
    $order = $orders[0];
    
    if(!$order || !$deal_id) {
      $this->log("Failed to find order or deal id");
      return new WP_Error( 'error', 'Failed to find order or deal id', array( 'status' => 500 ) );
    }

    $order->update_meta_data("ac_1st_buy_deal_id", $deal_id);
    $order->save();
    $this->log( "Order #{$order->get_id()} updated with deal id {$deal_id}" );
    return array(
      "success" => true,
    );
  }

  function get_order_number_from_note( $note ) {
    if(preg_match("/^Order #([0-9]+)$/", $note))
      return intval(str_replace("Order #", "", $note ));
    return 0;
  }

  function move_deal_to_shipped( $order_id ) {
    $this->move_deal( $order_id, self::AC_SHIPPED_STAGE );
  }

  function move_deal_to_delivered( $order_id ) {
    $this->move_deal( $order_id, self::AC_DELIVERED_STAGE );
  }

  function move_deal( $order_id, $stage_id ) {
    $order = wc_get_order( $order_id );
    if(!$order) {
      return;
    }

    $deal_id = $order->get_meta('ac_1st_buy_deal_id');

    if(empty($deal_id)) {
      return;
    }

    Active_Campaign_Deal::update_deal_stage($deal_id, $stage_id);
  }

  private function log( $message, $use_print_r = false ) {
    $logger = wc_get_logger();
    $logger->info( $use_print_r ? wc_print_r( $message, true ) : $message, array("source" => "ac-1st-buy-free-box" ) );
  }
}

Horizon_AC_1st_Buy_After_Free_Box::init();