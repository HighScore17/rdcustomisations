<?php

class Woocommerce_Horizon_Freeshipping_Cart_Message {
  public static $instance;

  CONST CART_MIN_VALUE = 500;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_Horizon_Freeshipping_Cart_Message ) {
      self::$instance = new Woocommerce_Horizon_Freeshipping_Cart_Message();
      self::$instance->add_hooks();
    }
  }

  function add_hooks() {
    add_action( 'horizon_woocommerce_cart_messages', [ $this, 'get_freeshipping_message' ] );
  }

  function get_freeshipping_message( $messages ) {
    $cart = \WC()->cart;
    $subtotal = $cart->get_subtotal();
    $remaining = self::CART_MIN_VALUE - $subtotal;

    if( $remaining > 0  ) {
      $remaining = '$' . __safe_number_format( $remaining, 2 );
      $messages[] = array(
        'type' => 'shipping',
        'payload' => "Add $remaining to your order and get free shipping",
        'payloadType' => 'string'
      );
    }
    return $messages;
  }
}

Woocommerce_Horizon_Freeshipping_Cart_Message::init();