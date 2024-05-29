<?php
class WC_Horizon_Admin_Tab_AC_Integration {
  static function get_tab() {
    return array(
      'name' => 'AC Integration',
      'group' => 'ac-integration',
      'type' => array (
        'code' => 'custom_page',
        'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/ac-integration/view.php"
      )
    );
  }
}