<?php

class USPS_SDK_Helper {
  static function getURL( $endpoint ) {
    return "https://secure.shippingapis.com/{$endpoint}";
  }

  static function getUserID() {
    $userID = get_option( "horizon-api-key-usps-user-id", "" );
    return System_Data_Encryptation::decrypt_encoded( $userID );
  }
}