<?php

class  WC_Horizon_CIN7_Stock_Integration {
  static function get_stock( $product_id, $cin7_ids = array(), $group = '' ) {

    if( !count($cin7_ids) ) {
      return;
    }
    
    $cached_stock = get_post_meta( $product_id, "cin7-data-stock-" . $group, true );
    $cached_stock_time = intval(get_post_meta( $product_id, "cin7-time-stock-" . $group, true ));


    if( $cached_stock && $cached_stock_time && (time() - $cached_stock_time) <= 3600 ) {
      return $cached_stock;
    }

    $ids = implode(",", $cin7_ids);
    $product_url = "https://api.cin7.com/api/v1/Products?where=id%20IN%20($ids)";
    $args = array(
      'headers' => array(
        'Authorization' => 'Basic Um9ja2V0RGlzdHJpYnV0b1VTOjY1N2ViN2IwYjM1YTQwZTc4M2Y3MDFiOTAwMTBiZmM1'
      ));

    $response = wp_remote_get( $product_url, $args );
    $remote_body = wp_remote_retrieve_body( $response );

    if( empty( $remote_body ) ) {
      return;
    }
    $body = json_decode( $remote_body );

    $stock = array();
    
    foreach( $body as $item ) {
      if( !count($item->productOptions) )
        continue;
      $stock[$item->id] = intval( $item->productOptions[0]->stockAvailable );
    }

    if( !count($stock) ) {
      return;
    }

    update_post_meta( $product_id, "cin7-data-stock-" . $group, $stock );
    update_post_meta( $product_id, "cin7-time-stock-" . $group, time() );

    return $stock;
  }
}