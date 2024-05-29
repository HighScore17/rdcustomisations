<?php

class WC_Horizon_CIN7_Stock_Sizes_Integration {
  static function get_stock( $product_id, $cin7_ids ) {
    return array(
      "S" => 999998,
      "M" => 999998,
      "L" => 999998,
      "XL" => 999998
    );
    $product = wc_get_product();

    $stock_disabled = $product->get_meta("cin7_stock_disable") === "on";
    $stock_backup = $product->get_meta('cin7_stock_backup');

    if( $stock_disabled && $stock_backup ) {
      $stocks = explode( ",", $stock_backup );
      return ["S" => $stocks[0],  "M" =>  $stocks[1], "L" =>  $stocks[2], "XL" =>  $stocks[3]];
    }

    $ids = explode(",", $cin7_ids);

    if( count($ids) > 4 ) {
      $ids = array_slice( $ids, 0, 4 );
    }

    $i = 0;
    $sizes = [ "S", "M", "L", "XL" ];
    $sizes_ids = array();

    foreach( $ids as $id ) {
      $sizes_ids[ intval($id)] = $sizes[$i]; 
      $i++;
    }

    $stock = WC_Horizon_CIN7_Stock_Integration::get_stock( $product_id, $ids );

    if( !is_array($stock) || !count($stock) ) {
      return ["S" => 0,  "L" => 0, "M" => 0, "XL" => 0];
    }
    
    $stock_sizes = array();
    foreach( $stock as $id => $stock ) {
      $stock_sizes[ $sizes_ids[ $id ] ] = $stock;
    }

    foreach( $sizes as $size ) {
      if( !isset($stock_sizes[$size]) )
        $stock_sizes[$size] = 0;
    }

    return $stock_sizes;
  }
}