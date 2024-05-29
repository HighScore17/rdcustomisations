<?php

class Woocommerce_Checkout_Limitier_Admin {
  public function init() {
    add_action('admin_menu', [ $this, 'add_menu_wc_subpage']);
    add_action( 'admin_notices', [ $this, 'add_memcached_message' ] );
  }

  function add_menu_wc_subpage() {
    $page = add_submenu_page( 
      'woocommerce', 
      __( 'Blocked Customers', 'horizon' ), 
      __( 'Blocked Customers', 'horizon' ), 
      'manage_woocommerce', 
      'checkout_rate_limitier', 
      [$this, 'print_submenu_page']
    );
  }
 
  function print_submenu_page() {
    if( class_exists( 'WoocommerceCheckoutRateLimitier' ) ) {
      require_once  __DIR__ . "/Views/CustomerBlockeds.php";
    }
  }

  function add_memcached_message() {
    require_once __DIR__ . "/Views/RateLimiterNotice.php";
  }

}

$aaaaa = new Woocommerce_Checkout_Limitier_Admin();
$aaaaa->init();