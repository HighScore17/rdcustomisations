<?php

class Horizon_AC_Order_Stages {
  private $SHIPPED_STAGE = 0;
  private $DELIVERED_STAGE = 0;
  private $DEAL_KEY = "";

  function __construct( $shipped, $delivered, $deal_key ) {
    $this->SHIPPED_STAGE = $shipped;
    $this->DELIVERED_STAGE = $delivered;
    $this->DEAL_KEY = $deal_key;
    $this->add_hooks();
  }

  public function add_hooks() {
    add_action( 'woocommerce_order_status_in-transit', [$this, 'move_deal_to_shipped'], 10, 1);
    add_action( 'woocommerce_order_status_completed', [$this, 'move_deal_to_delivered'], 10, 1);
  }

  public function move_deal_to_shipped ( $order_id ) {
    if( $this->SHIPPED_STAGE ) {
      $this->move_deal( $order_id, $this->SHIPPED_STAGE );
    }
  }

  public function move_deal_to_delivered ( $order_id ) {
    if ( $this->DELIVERED_STAGE ) {
      $this->move_deal( $order_id, $this->DELIVERED_STAGE );
    }
  }

  private function move_deal( $order_id, $stage ) {
    $deal_id = intval(get_post_meta( $order_id, $this->DEAL_KEY, true ));

    if( $deal_id ) {
      Active_Campaign_Deal::update_deal_stage( $deal_id, $stage );
    }
  }
}