<?php


class WoocommerceCartPromotionsCalculator {
  static function getAvailablePromotions() {
    $promotions = self::getPromotionItems();
    $cart = WC()->cart->get_cart();
    $available_promotions = array();

    foreach( $promotions as $promotion ) {
      $is_applicable = self::checkIfPromotionIsAppicable( $promotion, $cart );
      if( $is_applicable && is_array( $is_applicable ) ) {
        $available_promotions[] = $is_applicable;
      }
    }

    return $available_promotions;
  }

  static function checkIfPromotionIsAppicable( $promotion, $cart ) {
    $counter = array();

    if( !$promotion["enabled"] ) {
      return false;
    }

    //print_r($cart);

    foreach( $cart as $item ) {

      if( !$item || isset( $item["cart_promotion_item"] ) && !empty( $item["cart_promotion_item"] ) ) {
        continue;
      }

      $product_id = $item["data"]->get_parent_id() ?? $item["data"]->get_id();
      $product_parent = wc_get_product( $product_id );

      if( !$product_parent || !self::checkIfProductIsApplicable( $promotion, $product_id ) ) {
        continue;
      }

      if( !$counter[ $product_id ] ) {
        $counter[ $product_id ] = array(
          "id" => $product_id,
          "itemsPerCase" => $product_parent->get_meta("shipment_case_items"),
          "itemsInCart" => 0,
          "price" => 0,
        );
      }

      $price = $item["line_subtotal"] / $item["quantity"];
      $counter[ $product_id ]["itemsInCart"] += intval( $item["data"]->get_meta("contains_item") ) * $item["quantity"];
      if( $price > $counter[ $product_id ][ "price" ] ) {
        $counter[ $product_id ][ "price" ] = $price;
      }


    }

    $total_cases = array_reduce( array_map( function( $item ) {
      return floor( $item["itemsInCart"] / $item["itemsPerCase"] );
    }, $counter ), function( $carry, $cases ) {
      return $carry + $cases;
    }, 0);


    if( $total_cases >= $promotion["minCases"] ) {
      usort( $counter, function( $p1, $p2 ) {
        return $p1["price"] > $p2["price"];
      } );

      return array(
        "id" => $promotion["id"],
        "productId" =>  $counter[0]["id"]
      );
    }

    return false;
  }

  /**
   * Check if product can apply to the current promotion count
   */
  static function checkIfProductIsApplicable( $promotion, $product_id ) {
    if( !count( $promotion["tags"] ) ) {
      return true;
    }
    
    $tags = wp_get_post_terms( $product_id, "product_tag" );

    if( !count( $tags ) ) {
      return false;
    }

    $tags = array_map(function( $tag ){ return $tag->name; }, $tags);

    return count( array_intersect( $tags, $promotion["tags"] ) ) > 0;
  }

  /**
   * Enabled Promotions
   */
  static function getPromotionItems() {
    return array(
      array(
        "id" => "Gloves Free Case",
        "tags" => ["glove"],
        "minCases" => "10",
        "enabled" => true,
        "toAdd" => array(
          "product" => "minPrice",
          "quantity" => 1,
        )
      )
    );
  }
}

WoocommerceCartPromotions::addHoooks();