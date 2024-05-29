<?php

class WC_Horizon_Slack_Sender {
  public static function get_sender( $type = "tickets" ) {
    $brand = wc_horizon_get_brand_name();
    $senders = self::get_slack_senders();
    if( key_exists( $brand, $senders ) ) {
      return $senders[$brand][$type];
    }
    return $senders[BRAND_DEFAULT][$type];
  }

  static function get_slack_senders() {
    return array(
      "rdirect" => array(
        "tickets" => "#rocketdirect-tickets",
        "cart" => "#rocketdirect-tickets-abandonedcarts",
        "test" => "#slack-api-test",
        "logistics" => "#rockedirect-tickets",
        "subscriptions" => "#rocket-subscription-notice"
      ),
      "amerisano" => array(
        "tickets" => "#amerisano-tickets",
        "cart" => "#amerisano-abandoned-carts",
        "test" => "#slack-api-test",
        "logistics" => "#amerisano-logistics",
        "subscriptions" => "#amerisano-subscription-notice"
      )
    );
  }
}

function __slack_channel( $type ) {
  return WC_Horizon_Slack_Sender::get_sender( $type );
}