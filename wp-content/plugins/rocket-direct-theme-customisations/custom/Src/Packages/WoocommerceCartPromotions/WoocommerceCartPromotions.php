<?php

require_once __DIR__ . "/GraphQL/Autoload.php";
require_once __DIR__ . "/WoocommerceCartPromotionsCalculator.php";

class WoocommerceCartPromotions {

  static function addHoooks() {
    // Pricing
    add_action( 'woocommerce_before_calculate_totals', 'WoocommerceCartPromotions::setPromotionProductsPrices', 999999, 1 );
    add_filter('woocommerce_product_get_price', 'WoocommerceCartPromotions::filterPromotionPrices', 9999999, 2);
    add_filter('woocommerce_product_get_regular_price', 'WoocommerceCartPromotions::filterPromotionPrices', 9999999, 2);
    add_filter('woocommerce_product_variation_get_price', 'WoocommerceCartPromotions::filterPromotionPrices', 9999999, 2);
    add_filter('woocommerce_product_variation_get_regular_price', 'WoocommerceCartPromotions::filterPromotionPrices', 9999999, 2);
    add_filter('woocommerce_product_variation_prices_price', 'WoocommerceCartPromotions::filterPromotionPrices', 9999999, 2);
    add_filter('woocommerce_variation_prices_regular_price', 'WoocommerceCartPromotions::filterPromotionPrices', 9999999, 2);
    add_filter('woocommerce_variation_prices_price', 'WoocommerceCartPromotions::filterPromotionPrices', 9999999, 2);
    // Remove items
    add_action( 'woocommerce_cart_item_removed', 'WoocommerceCartPromotions::verifyCartPromotions', 10, 2 );
    add_action( 'woocommerce_cart_item_restored', 'WoocommerceCartPromotions::verifyCartPromotions', 10, 2 );
    add_action( 'woocommerce_add_to_cart', function( $key ) {
      self::verifyCartPromotions( $key, WC()->cart );
    }, 9999 );
    add_action( 'woocommerce_cart_item_set_quantity', function( $key, $qty, $cart ) {
      self::verifyCartPromotions( $key, $cart );
    }, 10, 3 );

    // Prevent use multiples promotions
    add_action( 'woocommerce_applied_coupon', 'WoocommerceCartPromotions::removePromotions' );

  }

  static function removePromotions() {
    foreach( WC()->cart->get_cart() as $key => $item ) {
      if( isset($item["cart_promotion_item"]) && !empty( $item["cart_promotion_item"] ) ) {
        WC()->cart->remove_cart_item( $key );
      }
    }
  }


  /**
   * Change the promotions product prices
   */

  static function filterPromotionPrices( $price, $product ) {
    $promo_price = $product->get_meta('__promotion_price');
    if( $promo_price || $promo_price === 0 )
      return $promo_price;
    return $price;
  }

  /**
   * Change the promotions product prices
   */
  static function setPromotionProductsPrices( $cart ) {
    if( is_admin() && !defined( 'DOING_AJAX' ) ) {
      return;
    }
    
    foreach( $cart->cart_contents as $key => $item ) {
      if( $item['cart_promotion_item'] ) {
        $item['data']->set_price( 0 );
        $item['data']->update_meta_data( '__promotion_price', 0 );
      }
    }
  }

  /**
   * Remove from the cart the promotions that aren't valid
   */
  static function verifyCartPromotions( $key, $cart ) {
    $promotions = WoocommerceCartPromotionsCalculator::getPromotionItems();
    $current_cart = $cart->get_cart();


    foreach( $current_cart as $key => $item) {
      if( !$item["cart_promotion_item"] ) continue;
      
      $promotion = __array_find($promotions, function( $promo ) use ( $item ) { 
        return $promo["id"] === $item["cart_promotion_item"]; 
      });

      if( !$promotion ) {
        WC()->cart->remove_cart_item( $key );
        continue;
      };

      $product_id = $item["product_id"];
      $is_applicable = WoocommerceCartPromotionsCalculator::checkIfPromotionIsAppicable( $promotion, $current_cart );

      if( !$is_applicable || !is_array( $is_applicable ) || $is_applicable["productId"] !== $product_id ) {
        WC()->cart->remove_cart_item( $key );
      }
    }
  }

  /**
   * Add a promotion to the cart
   */
  function addPromotion( $promotion_id, $variation_id ) {
    $promotions = WoocommerceCartPromotionsCalculator::getPromotionItems();
    $promotion = __array_find($promotions, function($item) use ($promotion_id) { 
      return $item["id"] === $promotion_id; 
    });

    if( !$promotion ) {
      return new WP_Error( 'promotion-not-exists',  'The promotion couldn\'t be found' );
    }

    $is_applicable = WoocommerceCartPromotionsCalculator::checkIfPromotionIsAppicable( $promotion, WC()->cart->get_cart() );


    if( !$is_applicable || !is_array( $is_applicable ) ) {
      return new WP_Error( 'promotion-not-valid', 'This promotion is not applicable for you.' );
    }

    $product = wc_get_product( $is_applicable["productId"] );

    if( !$product ) {
      return new WP_Error('product-not-found', 'The applicable products were not found.');
    }

    $this->resetPromotions();

    WC()->cart->add_to_cart( $product->get_id(), 1, $variation_id, array(), array(
      "cart_promotion_item" => $is_applicable["id"],
    ) );

    return true;
  }


  /**
   * Get total items in the cart required by a promotion
   */
  function getCartTotalItemsOf( $productId, $cart ) {
    $qty = 0;
    
    foreach( $cart as $key => $item ) {
      if( intval( $item['product_id'] ) !== intval( $productId ) ) {
        continue;
      }

      $product = wc_get_product( $item['variation_id'] );

      if( !$product ) {
        continue;
      }

      $qty += intval($product->get_meta('contains_item')) * intval( $item["quantity"] );
    }

    return $qty;
  }


  private function resetPromotions() {
    WC()->cart->remove_coupons();
    $this->removePromotions();
  }
}

WoocommerceCartPromotions::addHoooks();