<?php
use GraphQL\Error\UserError;

class WC_Horizon_Checkout_Validation {

  public static $instance = null;

  public static function init() {
    if( !self::$instance instanceof WC_Horizon_Checkout_Validation ) {
      self::$instance = new WC_Horizon_Checkout_Validation();
      self::$instance->add_hooks();
    }
  }

  private function add_hooks() {
    add_action( 'woocommerce_before_checkout_process', [ $this, 'validate_recaptcha' ] );
  }
  
  public function validate_recaptcha() {
    
    if( is_admin() ) {
      return;
    }

    if( !isset($_POST["recaptchaToken"]) || empty($_POST["recaptchaToken"]) ) {
      throw new UserError("ReCaptcha token not provided");
    }

    $response = validateReCaptcha( $_POST["recaptchaToken"] );

    if( $response !== "success" ) {
      throw new UserError($response);
    }
  }
}

WC_Horizon_Checkout_Validation::init();