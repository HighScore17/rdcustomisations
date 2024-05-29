<?php
  class PriceImportTool{

    /**
     * Tiers allowed 
     */
    private $tiers_size = 6;

    /**
     * basic fields
     */
    private $MAX_FIELDS_BASIC = 9;

    /**
     * basic fields
     */
    private $MAX_FIELDS_ADVANCED = 16;

    /**
     * Self Instance
     */
    public static $instance;

    public static function init()
    {
      if( !isset(self::$instance ) || !self::$instance instanceof PriceImportTool )
      {
        self::$instance = new PriceImportTool();
        self::$instance->enable();
      }
    }

    function enable() {
      add_action('wp_ajax_csv_import_prices', [$this, 'import_prices_csv']);
    }

    function import_prices_csv() {
      if( isset( $_FILES['prices'] ) ) {
        $this->import_variations_csv( fopen( $_FILES['prices']['tmp_name'], "r" ), $_POST["ids"] );
      }
      else {
        wp_send_json_error($_FILES);
      }

      die();
    }

    function import_variations_csv( $prices, $ids )
    {
      if( !$prices ) {
        wp_send_json_error();
        return;
      }
      // Removing header
      fgetcsv( $prices, 1000 );

      $counter = 1;

      $headers = array(
        'id' => -1,
        'name' => '',
        'attrs' => '',
        'prices' => array(),
        'tiers' => array(),
        'contain' => '1',
        //'b2b_prices' => array(),
        //'tiers' => array()
      );
      $modified_products = "";
      while( ( $data = fgetcsv( $prices, 300, "," ) ) !== FALSE) {
        $length = count( $data );
        $is_advance = $length === $this->MAX_FIELDS_ADVANCED;
        
        if( $length !== $this->MAX_FIELDS_BASIC && $length !== $this->MAX_FIELDS_ADVANCED ) {
          continue;
        }

        $current = $headers;
        $product = wc_get_product( $data[0] );

        if( $product !== NULL && $product !== FALSE ) {
          $current['name'] = $data[1];
          $current['attrs'] = $data[2];
          $current['prices'] = $this->get_prices( $data );
          $current['tiers'] = $is_advance ? $this->get_ranges_csv( $data ) : $this->get_ranges($product->get_meta("tiers_range"));
          $current['contain'] = $is_advance ? $data[15] : $product->get_meta("contains_item");
          $meta = "";
          
          for( $i = 0; $i < $this->tiers_size; $i++ ) {
            $ranges = explode( "-", $current['tiers'][$i] );
            $meta .= $ranges[0] . ":" . $current['prices'][$i] . ";";
          }

          // Updating prices
          $groups_wholesale_ids = explode( ",", $ids );
          foreach( $groups_wholesale_ids as $group_id ) {
            if( $group_id === 'b2c' ) {
              $product->update_meta_data('b2bking_product_pricetiers_group_b2c', $meta );
              $product->update_meta_data('b2bking_product_pricetiers_group_', $meta );
              $product->set_regular_price( $current['prices'][0] );
              $product->set_price( $current['prices'][0] );
            } else {
              $group_wholesale = get_post( intval( $group_id ) );
              if( !is_a( $group_wholesale, 'WP_Post' ) || $group_wholesale->post_type !== 'b2bking_group' ) {
                continue;
              }
              $product->update_meta_data('b2bking_product_pricetiers_group_' . $group_wholesale->ID, $meta );
              $product->update_meta_data( 'b2bking_regular_product_price_group_' . $group_wholesale->ID, $current['prices'][0] );
            }
          }
          
          $product->update_meta_data('tiers_range', implode( "\r\n", $current["tiers"] ) );
          $product->update_meta_data( 'contains_item', $current['contain'] );
          $product->save();

        }
        
        $counter++;
        if($counter > 100) {
          break;
        }
      }
      wp_send_json_success( $modified_products );
    }

    function get_prices( $data ) {
      $start = 3;
      $end = 8;
      $prices = array();
      for( $i = $start; $i <= $end; $i++ ){
        $prices[] = $data[$i];
      }
      return $prices;
    }

    function get_ranges_csv( $data ) {
      $start = 9;
      $end = 14;
      $ranges = array();
      for( $i = $start; $i <= $end; $i++ ){
        $ranges[] = $data[$i];
      }
      return $ranges;
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
  PriceImportTool::init();
?>