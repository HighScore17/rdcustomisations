<?php
  class Price_Tool{

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
      if( !isset(self::$instance ) || !self::$instance instanceof Price_Tool )
      {
        self::$instance = new Price_Tool();
        self::$instance->enable();
      }
    }

    function enable()
    {
      add_action('wp_ajax_print_prices_csv', [$this, 'print_prices_csv']);
    }

    function print_prices_csv()
    {
      $this->print_variations_csv($_POST["id"]);
      die();
    }

    function print_variations_csv( $id )
    {

      $titles = array(
        'Product', 'Attributes', 
        '<1 pallet', '1-4 Pallets','5-9 Pallets', '10-14 Pallets', '+15 Pallets', 'TruckLoad',
      );

		  $groups_wholesale = get_posts( array( 'post_type' => 'b2bking_group','post_status'=>'publish','numberposts' => -1) )[0];
      $filecsv = fopen('php://temp', 'w');
      fputcsv($filecsv, $titles); 
      
      $products_ids = [ intval($id) ];/*get_posts(
        array(
          'posts_per_page' => -1,
          'post_type' => array('product'),
          'fields' => 'ids',
        )
      );*/

      foreach( $products_ids as $product_id )
      {
        $product = wc_get_product( $product_id );
        foreach ($product->get_children() as $child_id)
        {
            $variation = wc_get_product($child_id);
            $range = $variation->get_meta("tiers_range");
            $tiers = $variation->get_meta( 'b2bking_product_pricetiers_group_b2c' );
            $tiers_wholesale = $variation->get_meta( 'b2bking_product_pricetiers_group_' . $groups_wholesale->ID );
            $prices = $this->get_prices( $tiers );
            $prices_wholesale = $this->get_prices( $tiers_wholesale );
            fputcsv($filecsv, array_merge(
              array( $variation->get_name(), $variation->get_attribute("pa_presentation") . " - " . $variation->get_attribute("pa_delivery")),
              $prices,
              //$prices_wholesale,
              //$this->get_ranges( $range ),
              //array( $groups_wholesale->ID )
            ));
        }
      }
      rewind( $filecsv );
      echo stream_get_contents( $filecsv );
      fclose($filecsv);
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
  }
  Price_Tool::init();
?>