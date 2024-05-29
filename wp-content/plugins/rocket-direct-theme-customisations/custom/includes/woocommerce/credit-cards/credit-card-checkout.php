<?php
class Woocommerce_CreditCard_Checkout {
  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof Woocommerce_CreditCard_Checkout) {
      self::$instance = new Woocommerce_CreditCard_Checkout();
      self::$instance->add_hooks();
    }
  }

  public function add_hooks() {
    add_action('woocommerce_usio_after_process_payment', [ $this, 'on_process_payment_with_creditcard' ], 10, 4 );
  }

  public function on_process_payment_with_creditcard( WC_Order $order, $token, $payment_id, $confirmation ) {
    if( !isset( $_POST["saveCC"] ) || $_POST["saveCC"] !== 'yes' ) {
      return;
    }

    if( !$confirmation ) {
      return;
    }

    if( !empty( $payment_id ) ) {
      return;
    }

    $user_id = get_current_user_id();

    if( !$user_id ) {
      return;
    }

    $encrypted = class_exists( 'User_Data_Encryptation' );
    $cc_token = $encrypted ? User_Data_Encryptation::encrypt_and_encode( $user_id, $confirmation ): $confirmation;


    $cc = new WC_Horizon_Credit_Card();
    $cc->set_token( sanitize_text_field( $cc_token ) );
    $cc->set_is_encrypted( $encrypted );
    $cc->set_owner( $user_id );
    $cc->set_name( sanitize_text_field( $order->get_billing_first_name() . " " . $order->get_billing_last_name() ) );
    $cc->set_card_type( "" );
    $cc->set_placeholder( sanitize_text_field( $_POST["card_type"] ) );
    $cc->set_billing( __sanitize_array( array(
      "firstname" => $order->get_billing_first_name(),
      "lastname" => $order->get_billing_last_name(),
      "address1" => $order->get_billing_address_1(),
      "address2" => $order->get_billing_address_2(),
      "city" => $order->get_billing_city(),
      "state" => $order->get_billing_state(),
      "postcode" => $order->get_billing_postcode(),
      "country" => $order->get_billing_country(),
      "phone" => $order->get_billing_phone(),
      "company" => $order->get_billing_company(),
      "email" => $order->get_billing_email(),
    ) ) );
    $cc->set_is_primary( false );
    $cc->save();
  }
}

Woocommerce_CreditCard_Checkout::init();