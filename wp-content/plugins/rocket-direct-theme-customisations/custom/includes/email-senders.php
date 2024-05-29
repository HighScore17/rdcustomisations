<?php

class WC_Horizon_Email_Sender {

  public static function get_sender( $type = "account") {
    $brand = wc_horizon_get_brand_name();
    $senders = self::get_email_senders();
    if( key_exists( $brand, $senders ) ) {
      return $senders[$brand][$type];
    }
    return $senders[BRAND_DEFAULT][$type];
  }

  static function get_email_senders() {
    return array(
      "rdirect" => array(
        "account" => "customerservice@rocket.direct",
        "contact" => "contact@rocket.direct",
        "order" => "customerservice@rocket.direct",
        "tracking" => "customerservice@rocket.direct",
        "sales" => "sales@rocket.direct",
        "subscription" => "customerservice@rocket.direct",
        "cart" => "sales@rocket.direct"
      ),
      "amerisano" => array(
        "account" => "support@amerisano.com",
        "contact" => "support@amerisano.com",
        "order" => "support@amerisano.com",
        "tracking" => "logistics@amerisano.com",
        "sales" => "support@amerisano.com",
        "subscription" => "support@amerisano.com",
        "cart" => "support@amerisano.com"
      )
    );
  }
}


function wc_horizon_get_email_sender( $type ) {
  return WC_Horizon_Email_Sender::get_sender( $type );
}