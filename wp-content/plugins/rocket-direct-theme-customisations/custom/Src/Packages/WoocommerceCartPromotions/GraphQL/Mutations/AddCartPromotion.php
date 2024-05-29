<?php

use GraphQL\Error\UserError;
use WPGraphQL\WooCommerce\Data\Mutation\Cart_Mutation;

class WoocommerceAddCartPromotionGraphql {
  static $instance = null;

  static function init() {
    if( !self::$instance instanceof WoocommerceAddCartPromotionGraphql ) {
      self::$instance = new WoocommerceAddCartPromotionGraphql();
      self::$instance->addHooks();
    }
  }

  function addHooks() {
    add_action( 'graphql_register_types', [ $this, 'registerMutation' ] );
  }

  function registerMutation() {
    register_graphql_mutation( 'addCartPromotion', [
      'inputFields' => [
          'id' => ['type' => 'String'],
          'variation_id' => ['type' => 'Int']
      ],
      'outputFields' => [
          "applied" => ["type" => "Bool"],
          "cart" => Cart_Mutation::get_cart_field( true )
      ],
      'mutateAndGetPayload' => [ $this,  'MutateAndGetPayload' ]
  ]);
  }

  function MutateAndGetPayload( $input ) {
    if( !$input["id"] ) {
      throw new UserError("The promotion ID was not provided");
    }

    $manager = new WoocommerceCartPromotions();
    $result = $manager->addPromotion( $input["id"], $input["variation_id"] );

    if( is_wp_error( $result ) ) {
      throw new UserError( $result->get_error_message() );
    }

    return array(
      "applied" => $result
    );
  }
}

WoocommerceAddCartPromotionGraphql::init();