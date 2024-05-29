<?php
class WC_Horizon_Admin_Tab_AC_And_Apollo {
  static function get_tab() {
    return array(
      'name' => 'AC and Apollo',
      'group' => 'ac-and-apollo',
      'type' => array (
        'code' => 'custom_page',
        'file' => HORIZON_CUSTOMISATIONS_DIR . "/custom/views/ac-and-apollo/ac-and-apollo.php"
      )
    );
  }
}