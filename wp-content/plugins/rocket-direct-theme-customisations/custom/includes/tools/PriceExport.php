<?php

  class PriceExportTool{

    /**
     * Tiers allowed 
     */
    private $tiers_size = 6;

    /**
     * Self Instance
     */
    public static $instance;

    public static function init()
    {
      if( !isset(self::$instance ) || !self::$instance instanceof PriceExportTool )
      {
        self::$instance = new PriceExportTool();
        self::$instance->enable();
      }
    }

    function enable()
    {
      add_action('wp_ajax_print_prices_csv', [$this, 'print_prices_csv']);
    }

    function print_prices_csv()
    {
      $this->print_variations_csv( intval($_POST["id"]),  $_POST["group"] , $_POST["advanced"] === "true" );
      die();
    }

    function print_variations_csv( $id, $group, $advanced )
    {

      $titles = array(
        'ID', 'Product', 'Attributes', 
        '<1 pallet', '1-4 Pallets','5-9 Pallets', '10-14 Pallets', '+15 Pallets', 'TruckLoad',
      );
      $titles = $this->get_titles( $advanced );
      
      if( $group && $group !== 'b2c' ) {
        $groups_wholesale = get_post( intval( $group ) );
      }

      if( ( !$groups_wholesale || !is_a( $groups_wholesale, 'WP_Post' ) || $groups_wholesale->post_type !== 'b2bking_group') && $group !== 'b2c') {
        return;
      }

      $filecsv = fopen('php://temp', 'w');
      fputcsv($filecsv, $titles); 
      
      $products_ids = [ $id ];
      foreach( $products_ids as $product_id )
      {
        $product = wc_get_product( $product_id );
        foreach ($product->get_children() as $child_id)
        {
            $variation = wc_get_product($child_id);
            $range = $variation->get_meta("tiers_range");
            if( $group === 'b2c' ) {
              $tiers = $variation->get_meta( 'b2bking_product_pricetiers_group_b2c' );
              $prices = $this->get_prices( $tiers );
            } else {
              $tiers = $variation->get_meta( 'b2bking_product_pricetiers_group_' . $groups_wholesale->ID );
              $prices = $this->get_prices( $tiers );
            }
            fputcsv($filecsv, array_merge(
              array( $variation->get_id(), $variation->get_name(), $variation->get_attribute("pa_presentation") . " - " . $variation->get_attribute("pa_delivery")),
              $prices,
              $advanced ? $this->get_ranges( $range ) : array(),
              $advanced ? array( $variation->get_meta( 'contains_item' ) ) : array()
            ));
        }
      }
      rewind( $filecsv );
      echo stream_get_contents( $filecsv );
      fclose($filecsv);
    }

    function get_titles( $advanced ) {
      return array_merge( array(
          'ID', 'Product', 'Attributes', 
          '<1 pallet', '1-4 Pallets','5-9 Pallets', '10-14 Pallets', '+15 Pallets', 'TruckLoad',
        ),
        $advanced ? array(
          '<1 pallet tiers', '1-4 Pallets tiers','5-9 Pallets tiers', '10-14 Pallets tiers', '+15 Pallets tiers', 'TruckLoad tiers', 'How many items contain'
        ) : array()
      );
    }

    function get_prices( $metaData )
    {
      $data = substr($metaData, -1) === ";" ? substr($metaData, 0, -1) : $metaData;
      $tierPrices = explode(";", $data);
      $prices = array_fill(0, $this->tiers_size, '');
      for($i = 0; $i < count($tierPrices); $i++)
      {
        $tierPrice = $tierPrices[$i];
        $separated = explode(":", $tierPrice);
        $prices[$i] = is_array($separated) && count($separated) >= 2 ? $separated[1] : '';
      }
      return $prices;
    }

    function get_ranges( $metaData )
    {
      $tiers = array_fill(0, $this->tiers_size, '');
      $tiers_arr = explode("\n", implode("", explode("\r", $metaData)));
      for($i = 0; $i < count($tiers_arr); $i++)
      {
        $tiers[$i] = $tiers_arr[$i];
      }
      return $tiers;
    }

    function order_by_attrs()
    {

    }
  }
  PriceExportTool::init();
?>