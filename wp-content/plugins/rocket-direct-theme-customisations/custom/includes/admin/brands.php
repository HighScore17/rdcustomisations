<?php
class WC_Horizon_Admin_Tab_Brands {
  static function get_tab() {
    return array(
      'name' => 'Brands',
      'group' => 'brands',
      'type' => array (
        'code' => 'custom_page',
        'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/brands.php"
      )
    );
  }
}