<?php
  require_once __DIR__ . '/../tools/PriceExport.php';
  require_once __DIR__ . '/../tools/PriceImport.php';
  class Admin_Prices {
    function get_tab()
    {
      return array(
        'name' => 'Prices',
        'group' => 'prices',
        'type' => array (
          'code' => 'custom_page',
          'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/prices.php"
        )
      );
    }
  }