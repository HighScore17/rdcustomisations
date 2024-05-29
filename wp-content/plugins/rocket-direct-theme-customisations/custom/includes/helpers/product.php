<?php

class Horizon_Product_Helper {
  static function get_b2c_price( $product, $quantity ) {
    $object = $product;

    if( !is_object($object) ) {
      $object = wc_get_product($product);
    }

    if( !$object || $quantity < 1 ) {
      return null;
    }

    $tiers = explode(";", $object->get_meta('b2bking_product_pricetiers_group_b2c' ) );
    $length = count($tiers);
    $found = false; $i = 0;
    $price = null;

    while(!$found && $i < $length) {
      $tier_data = explode(":", $tiers[$i]);
      $tier_quantity = intval($tier_data[0]);
      $tier_price = floatval($tier_data[1]);
      
      if( $tier_quantity < 0 || $tier_price < 0 ) {
        continue;
      }

      if( $quantity >= $tier_quantity ) {
        $price = $tier_price;
      }
      else {
        $found = true;
      }
      $i++;
    }
    return $price;
  }

  static function get_b2b_price( $product, $quantity, $group ) {
    $object = $product;

    if( !is_object($object) ) {
      $object = wc_get_product($product);
    }

    if( !$object || $quantity < 1 ) {
      return null;
    }

    $tiers = explode(";", $object->get_meta('b2bking_product_pricetiers_group_' . $group ) );
    $length = count($tiers);
    $found = false; $i = 0;
    $price = null;

    while(!$found && $i < $length) {
      $tier_data = explode(":", $tiers[$i]);
      $tier_quantity = intval($tier_data[0]);
      $tier_price = floatval($tier_data[1]);
      
      if( $tier_quantity < 0 || $tier_price < 0 ) {
        continue;
      }

      if( $quantity >= $tier_quantity ) {
        $price = $tier_price;
      }
      else {
        $found = true;
      }
      $i++;
    }
    return $price;
  }

  static function user_has_special_price( $user_id, $product, $quantity ) {
    if( get_user_meta( $user_id, 'b2bking_b2buser', true ) !== "yes" ) {
      return false;
    }

    $group = get_user_meta( $user_id, 'b2bking_customergroup', true );

    $price_b2c = self::get_b2c_price( $product, $quantity );
    $price_b2b = self::get_b2b_price( $product, $quantity, $group );

    if( !$price_b2c || !$price_b2b ) {
      return false;
    }

    return $price_b2b < $price_b2c;
  }
}

function get_discount_amount_from_data( $type, $value, $amount ) {
  if( $type === "percentage" )
    return $amount * ( $value / 100 );
  return $value;
}

function __get_product_size_label( $size ) {
  $size = strtoupper($size);
  $sizes = array(
    "XS" => "Extra Small",
    "S" => "Small",
    "M" => "Medium",
    "L" => "Large", 
    "XL" => "Extra Large"
  );
  return isset($sizes[$size]) ? $sizes[$size] : "";
}

 function find_product_presentation_variation( WC_Product $product, WC_Product $variation, $to_search = "case"  ) {
  $size = $variation->get_attribute('pa_size');
  $delivery = $variation->get_attribute('pa_delivery');
  $variations = $product->get_children();

  return __array_find( $variations, function( $variationId ) use ( $delivery, $size, $to_search ) {
    $variation = wc_get_product( $variationId );
    $presentation =  $variation->get_attribute("pa_presentation");
    $variationDelivery =  $variation->get_attribute("pa_delivery");
    $variationSize =  $variation->get_attribute("pa_size");
    return 
      strtolower( $presentation ) === $to_search && 
      strtolower( $variationDelivery ) === strtolower( $delivery ) &&
      ( $size ? strtolower( $size ) === strtolower( $variationSize ) : true ); 
  } );
}