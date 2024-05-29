<?php

$caction = isset( $_POST["caction"] ) ? $_POST["caction"] : "";
$cmessage = array("to" => $caction, "content" => "");
$system_key = System_Data_Encryptation::get_system_key();

switch( $caction ) {
  case "create-users-keys";
    $data = create_users_encryptations_keys();
    $cmessage["content"] = $data[0] . " users found. " . $data[1] . " encryptation keys created";
    break;
  case "encrypt-cc-tokens":
    $data = encrypt_credit_cards_tokens();
    $cmessage["content"] = $data[0] . " CC's tokens found. " . $data[1] . " encrypted";
    break;
  case "encrypt-decrypt-system":
    $cmessage["encrypted"] = $_POST["value-to-encrypt"] ? encrypt_system_value( $_POST["value-to-encrypt"] ) : "";
    $cmessage["decrypted"] = $_POST["value-to-decrypt"] ? decrypt_system_value( $_POST["value-to-decrypt"] ) : "";
    break;
  case "generate-system-key":
    if( !$system_key ) {
      $system_key = System_Data_Encryptation::generate_system_key();
    }
    break;
  default:
    break;
}

function create_users_encryptations_keys() {
  $created_count = 0;
  $users = get_users(array(
    "meta_key" => USER_DEFUSE_KEY_META_NAME,
    "meta_compare" => "NOT EXISTS"
  ));
  foreach( $users as $user ) {  
    if( User_Data_Encryptation::generate_user_encriptation_key($user->ID) )
      $created_count++;
  }
  return array( count( $users ), $created_count );
}

function encrypt_credit_cards_tokens() {
  $found = 0; $updated = 0;
  foreach_paginated_posts( function( $post_id ) use (&$found, &$updated)  {
    $credit_card = wc_horizon_get_credit_card_by_id( $post_id );

    if( !$credit_card || $credit_card->is_encrypted() ) {
      return;
    }

    $token_encrypted = User_Data_Encryptation::encrypt_and_encode( intval( $credit_card->get_owner() ), $credit_card->get_token() );
    $found++;

    if( !$token_encrypted ) {
      return null;
    }

    $credit_card->set_token( $token_encrypted );
    $credit_card->set_is_encrypted( true );  
    $credit_card->save();
    $updated++;
  
  }, 'credit-card-payment' );
  return array( $found, $updated );
}

function encrypt_system_value( $value ) {
  return System_Data_Encryptation::encrypt_and_encode( $value );
}

function decrypt_system_value( $value ) {
  return System_Data_Encryptation::decrypt_encoded( $value );
}