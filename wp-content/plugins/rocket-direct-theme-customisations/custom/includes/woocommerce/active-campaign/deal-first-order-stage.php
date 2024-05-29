<?php

class Horizon_AC_First_Order_Stages {
  private $AC_PIPELINE = 0;
  private $HOLD_STAGE = 0;
  private $SHIPPED_STAGE = 0;
  private $DELIVERED_STAGE = 0;
  private $prefix = "";
  private $DEAL_KEY = "";

  function __construct( $pipeline, $hold, $shipped, $delivered, $prefix ) {
    $this->AC_PIPELINE = $pipeline;
    $this->HOLD_STAGE = $hold;
    $this->SHIPPED_STAGE = $shipped;
    $this->DELIVERED_STAGE = $delivered;
    $this->prefix = $prefix;
    $this->DEAL_KEY = $this->prefix . "_hac_first_order_deal_id";
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

    $orders_args = array('email' => $deal["contact_email"], 'limit' => 1);
    $orders = wc_get_orders( $orders_args );
  
    if(!count($orders)) {
      return new WP_Error( 'no_orders', 'No orders found', array( 'status' => 400 ) );
    }

    $order = $orders[0];
    $order->update_meta_data( $this->DEAL_KEY, $deal["id"] );
    $order->save();
  }
}


new Horizon_AC_First_Order_Stages( 8, 67, 74, 72, "colorado_dentists" );
new Horizon_AC_First_Order_Stages( 9, 79, 86, 84, "colorado_dentists_group1" );
new Horizon_AC_First_Order_Stages( 15, 145, 0, 152, "am_first_purchase" );
