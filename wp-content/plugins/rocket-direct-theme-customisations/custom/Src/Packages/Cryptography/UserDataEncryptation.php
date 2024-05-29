<?php

use Defuse\Crypto\KeyProtectedByPassword;
use Defuse\Crypto\Crypto;

define( 'USER_DEFUSE_KEY_META_NAME', 'defuse-crypto-protected-key-encoded' );


class User_Data_Encryptation {
  static function decrypt_encoded( $user_id, $value ) {
    try {
      $protected_key_encoded = get_user_meta( $user_id, USER_DEFUSE_KEY_META_NAME, true );
      $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString( $protected_key_encoded );
      $user_key = $protected_key->unlockKey( DEFUSE_CRIPTO_PASSWORD );
      return Crypto::decrypt( $value, $user_key );
    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {

    }
  }

  static function encrypt_and_encode( $user_id, $value ) {
    try {
      $protected_key_encoded = get_user_meta( $user_id, USER_DEFUSE_KEY_META_NAME, true );
      $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString( $protected_key_encoded );
      $user_key = $protected_key->unlockKey( DEFUSE_CRIPTO_PASSWORD );
      return Crypto::encrypt($value, $user_key);
    } catch ( Exception $e) {
      return null;
    }
  }

  static function generate_user_encriptation_key( $user_id = 0 ) {
    $current_key = get_user_meta( $user_id, USER_DEFUSE_KEY_META_NAME, true );
    if( !$user_id || !empty( $current_key ) ) {
      return false;
    }

    $protected_key = KeyProtectedByPassword::createRandomPasswordProtectedKey(DEFUSE_CRIPTO_PASSWORD);
    $protected_key_encoded = $protected_key->saveToAsciiSafeString();
    update_user_meta($user_id, USER_DEFUSE_KEY_META_NAME, $protected_key_encoded);
    return true;
  }

  
}
