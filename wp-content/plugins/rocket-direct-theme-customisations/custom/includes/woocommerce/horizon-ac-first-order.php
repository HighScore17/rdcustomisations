<?php

class Horizon_AC_First_Order_Stages {
  private $AC_PIPELINE = 0;
  private $HOLD_STAGE = 0;
  private $SHIPPED_STAGE = 0;
  private $DELIVERED_STAGE = 0;
  private $prefix = "";

  function __construct( $pipeline, $hold, $shipped, $delivered, $prefix ) {
    $this->AC_PIPELINE = $pipeline;
    $this->HOLD_STAGE = $hold;
    $this->SHIPPED_STAGE = $shipped;
    $this->DELIVERED_STAGE = $delivered;
    $this->prefix = $prefix;
  }

  public function add_hooks() {
    add_action( 'woocommerce_order_status_in-transit', [$this, 'move_deal_to_shipped'], 10, 1);
    add_action( 'woocommerce_order_status_completed', [$this, 'move_deal_to_delivered'], 10, 1);
    add_action( 'horizon_ac_deal', [$this, 'on_new_deal'], 10, 1);
    add_action('rest_api_init', function() {
      register_rest_route('horizon/v1', 'customer/first-buy', array(
        'methods' => ['POST', 'GET'],
        'callback' => array($this, 'webhook_1st_buy'),
        'permission_callback' => '__return_true'
      ));
    });
  }


  function webhook_1st_buy( WP_REST_Request $request ) {
    $params = $request->get_params();

    if( !is_array( $params["deal"] ) ) {
      return new WP_Error( 'invalid_request', 'Invalid request', array( 'status' => 400 ) );
    }

    $orders_args = array('customer' => $params["deal"]["contact_email"], 'limit' => 1);
    $orders = wc_get_orders( $orders_args );

    if(!count($orders)) {
      return new WP_Error( 'no_orders', 'No orders found', array( 'status' => 400 ) );
    }

    do_action( 'horizon_ac_first_buy', $params["deal"], $request );
  }
}