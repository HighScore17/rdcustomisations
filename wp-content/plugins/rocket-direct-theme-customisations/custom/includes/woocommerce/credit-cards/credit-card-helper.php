<?php

function wc_horizon_get_credit_cards( $user_id = 0 )  {
  
  if( !$user_id ) {
    $user_id = get_current_user_id();
  }

  if( !$user_id ) {
    return null;
  }

  $posts = get_posts(array(
    "numberposts" => -1,
    "post_type" => "credit-card-payment",
    "post_status" => "publish",
    "meta_key" => "ownerid",
    "meta_value" => $user_id,
    "order" => "ASC",
  ));

  $credit_cards = array_map(function($post): WC_Horizon_Credit_Card {
    $card = new WC_Horizon_Credit_Card( $post->ID );
    $card->load_props_from_post();
    return $card;
  }, $posts);

  return $credit_cards;

}

function wc_horizon_get_credit_card ( $credit_card_id, $user_id = 0 ) {
  if( !$user_id ) {
    $user_id = get_current_user_id();
  }

  if( !$user_id ) {
    return null;
  }

  $credit_card = get_post( $credit_card_id );

  if( 
    !$credit_card ||
    $credit_card->post_type != "credit-card-payment" ||
    get_post_meta( $credit_card_id, "ownerid", true ) != $user_id

  ) {
    return null;
  }
  
  $credit_card = new WC_Horizon_Credit_Card( $credit_card_id );
  $credit_card->load_props_from_post();
  return $credit_card;
}

function wc_horizon_get_credit_card_by_id( $credit_card_id ) {
  $credit_card = get_post( $credit_card_id );

  if( 
    !$credit_card ||
    $credit_card->post_type != "credit-card-payment"
  ) {
    return null;
  }
  
  $credit_card = new WC_Horizon_Credit_Card( $credit_card_id );
  $credit_card->load_props_from_post();
  return $credit_card;
}

function wc_horizon_get_primary_credit_card( $user_id = 0 ) {
  if( !$user_id ) {
    $user_id = get_current_user_id();
  }

  if( !$user_id ) {
    return;
  }

  $args = array(
    'post_type'  => 'credit-card-payment',
    'meta_query' => array(
        array(
            'key'     => 'ownerid',
            'value'   => $user_id,
        ),
        array(
            'key'     => 'is_primary',
            'value'   => 'yes',
        ),
    ),
  );
  $query = new WP_Query( $args );

  if( !is_a( $query->post, 'WP_Post' ) ) {
    return null;
  }

  return wc_horizon_get_credit_card( $query->post->ID );
}

function wc_horizon_clear_primary_credit_card (  $user_id = 0 ) {
  if( !$user_id ) {
    $user_id = get_current_user_id();
  }

  if( !$user_id ) {
    return;
  }

  $credit_cards = wc_horizon_get_credit_cards( $user_id );

  if( !$credit_cards || !count($credit_cards)) {
    return;
  }

  foreach( $credit_cards as $credit_card ) {
    if( $credit_card->is_primary() ) {
      $credit_card->set_prop( "is_primary", "no" );
      $credit_card->save();
    }
  }
}

function wc_horizon_get_credit_card_token( $credit_card_id, $user_id = 0 ) {

  if(  !class_exists( 'User_Data_Encryptation' ) ) {
    return new WP_Error('encryptation-class', 'Payments with stored credit cards are not allowed at this moment. Please enter your credit cart manually.');
  }

  if( !$user_id ) {
    $user_id = get_current_user_id();
  }

  $credit_card = wc_horizon_get_credit_card( $credit_card_id, $user_id );
  
  if(!$credit_card) {
    return new WP_Error('cc-not-found', 'Credit cart not found');
  }

  if( !$credit_card->is_encrypted() ) {
    return new WP_Error('invalid-cc', 'Credit Cart invalid');
  }
  $token = User_Data_Encryptation::decrypt_encoded( $user_id, $credit_card->get_token() );

  if( !$token ) {
    return new WP_Error("token-error", "Credit card token can't be decrypted");
  }

  return $token;
}
