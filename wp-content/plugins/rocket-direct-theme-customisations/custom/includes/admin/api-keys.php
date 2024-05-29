<?php
class WC_Horizon_Admin_Tab_API_Keys {
  static function get_tab() {
    return array(
      'name' => 'API Keys',
      'group' => 'api-keys',
      'type' => array (
        'code' => 'custom_page',
        'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/api-keys.php"
      )
    );
  }
}