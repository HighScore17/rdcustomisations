<?php
class Horizon_Cart {

  public static $instance = null;

  public static function instantiate() {
    if( !self::$instance instanceof Horizon_Cart ) {
      self::$instance = new Horizon_Cart();
      self::$instance->run();
    }
  }

  private function run() {
    add_filter( 'active_campaign_get_abandoned_cart_price', [ $this, 'active_campaign_get_b2bking_tiered_pricing'], 10, 2 );
  }

  /**
   * Set Active Campaign Cart item price based on tier quantity
   */
  public function active_campaign_get_b2bking_tiered_pricing( $product, $quantity ) {
    $tiers = $product->get_meta('b2bking_product_pricetiers_group_b2c');
    $tiers_ranges = explode( ";", $tiers ? $tiers : "" );
    
    if( count( $tiers_ranges ) > 0 ) {
      $tiered_price = 0.0000;

      // Iteranting beetwen all tiers
      foreach( $tiers_ranges as $tiers_range ) {
        $tier_data = explode( ":", $tiers_range );
        // Tier has quantity and price
        if( count( $tier_data ) === 2 ) {
          $tier_quantity = intval($tier_data[0]);
          $tier_price = floatval($tier_data[1]);
          if( $quantity >= $tier_quantity  ) {
            $tiered_price = $tier_price;
          } else {
          }
        }
      }
      return $tiered_price;
    }
    return $product->get_price();
  }
}

Horizon_Cart::instantiate();