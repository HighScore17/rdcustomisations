<?php
use Defuse\Crypto\KeyProtectedByPassword;
use Defuse\Crypto\Crypto;

class System_Data_Encryptation {
  static function generate_system_key() {
    if( empty( self::get_system_key() ) ) {
      $protected_key = KeyProtectedByPassword::createRandomPasswordProtectedKey(DEFUSE_CRIPTO_PASSWORD);
      $protected_key_encoded = $protected_key->saveToAsciiSafeString();
      update_option("defuse_system_encryptation_key", $protected_key_encoded);
      return $protected_key_encoded;
    }
  }

  static function get_system_key() {
    return get_option( 'defuse_system_encryptation_key', "" );
  }

  static function encrypt_and_encode(  $value ) {
    try {
      $protected_key_encoded = self::get_system_key();
      $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString( $protected_key_encoded );
      $sys_key = $protected_key->unlockKey( DEFUSE_CRIPTO_PASSWORD );
      return Crypto::encrypt($value, $sys_key);
    } catch ( Exception $e) {
      return null;
    }
  }

  static function decrypt_encoded(  $value ) {
    try {
      $protected_key_encoded = self::get_system_key();
      $protected_key = KeyProtectedByPassword::loadFromAsciiSafeString( $protected_key_encoded );
      $user_key = $protected_key->unlockKey( DEFUSE_CRIPTO_PASSWORD );
      return Crypto::decrypt( $value, $user_key );
    } catch (Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $ex) {
      
    }
  }
}