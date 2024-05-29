<?php

class WC_Checkout_Limit_Validation {
  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Checkout_Limit_Validation ) {
      self::$instance = new WC_Checkout_Limit_Validation();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    add_action( 'woocommerce_usio_before_process_payment', [ $this, 'validateRateLimit' ] );
    add_action( 'woocommerce_usio_payment_declined', [ $this, 'onUsioPaymentFailed' ] );
    add_filter( 'woocommerce_usio_payment_error', [ $this, 'getUsioPaymentErrorMessage' ] );
  }

  public function validateRateLimit() {
    $ip = WC_Geolocation::get_ip_address();
    $limitier = get_woocommerce_checkout_rate_limitier();
    $message = "You have exceeded the limit of payment attempts. Your user is temporarily blocked for 1 hour. Please come back after that time to complete payment ($ip).";

    if( !$limitier->canCheckout( $ip ) ) {
      if ( class_exists( 'GraphQL\\Error\\UserError' ) ) {
        throw new GraphQL\Error\UserError( $message );
      } else {
        throw new WP_Error( 'checkout-blocked', $message );
      }
    }
  }

  public function onUsioPaymentFailed() {
    if( defined( "WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS" ) && WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS === "yes" ) {
      return;
    }
    
    $limitier = get_woocommerce_checkout_rate_limitier();
    $limitier->addRate( WC_Geolocation::get_ip_address(), 5, 60 * 60 );
  }

  public function getUsioPaymentErrorMessage( WP_Error $error ) {
    $ip = WC_Geolocation::get_ip_address();
    $limitier = get_woocommerce_checkout_rate_limitier();
    $remainingAttemps = $limitier->getRemainingAttemps( WC_Geolocation::get_ip_address() );

    if( $remainingAttemps === 1 ) {
      return new WP_Error( 'checkout_pre_block', 'Please check your payment information. You have 1 more attempt to complete payment. If payment fails, your user will be blocked for 1 hour. You may complete your payment after that time.' );
    } else if ( $remainingAttemps === 0 ) {
      return new WP_Error('checkout_blocked', "You have exceeded the limit of payment attempts. Your user is temporarily blocked for 1 hour. Please come back after that time to complete payment ($ip).");
    }

    return $error;
  }
}

WC_Checkout_Limit_Validation::init();