<?php
class WC_Horizon_Admin_Tab_Encryptation {
  static function get_tab() {
    return array(
      'name' => 'Encryptation',
      'group' => 'encryptation',
      'type' => array (
        'code' => 'custom_page',
        'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/encryptation/view.php"
      )
    );
  }
}