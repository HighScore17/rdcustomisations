<?php
require_once 'admin/autoloader.php';



class Horizon_Customisations_Admin {
  public static $instance;

  /**
   * Admin page
   */
  private $page = 'horizon_customisations';

  /**
   * Tabs to render
   */
  private $tabs = [];

  /**
   * Shipping options
   */
  private $shipping;
  private $prices;
  private $free_box;

  public static function start()
  {
    if( !self::$instance instanceof Horizon_Customisations_Admin ) {
      self::$instance = new Horizon_Customisations_Admin();
      self::$instance->init();
      self::$instance->shipping = new Admin_Shipping( self::$instance->page );
      self::$instance->shipping->enable();
      self::$instance->prices = new Admin_Prices();
      self::$instance->free_box = new Admin_Free_Box();
    }
  }

  function init()
  {
    add_action('admin_menu', [ $this, 'add_menu_wc_subpage']);
  }

  function add_menu_wc_subpage()
  {
    $page = add_submenu_page( 
      'woocommerce', 
      __( 'Horizon Customizations', 'horizon' ), 
      __( 'Horizon Customizations', 'horizon' ), 
      'manage_woocommerce', 
      'horizon_customisations', 
      [$this, 'print_submenu_page']
    );
  }
 
  function print_submenu_page()
  {
    $this->init_tabs();
    require_once  HORIZON_CUSTOMISATIONS_DIR . "/custom/views/menu.php";
  }

  function init_tabs()
  {
    $tabs = apply_filters("theme_customizations_get_tabs", array());
    array_push( $this->tabs, WC_Horizon_Admin_Tab_API_Keys::get_tab());
    array_push( $this->tabs, $this->prices->get_tab());
    array_push( $this->tabs, $this->shipping->get_tab());
    array_push($this->tabs, $this->free_box->get_tab() );
    array_push($this->tabs, Admin_Bulk_User::get_tab() );
    array_push($this->tabs, WC_Horizon_Admin_Tab_Brands::get_tab() );
    array_push( $this->tabs, WC_Horizon_Admin_Tab_AC_And_Apollo::get_tab() );
    array_push( $this->tabs, WC_Horizon_Admin_Tab_AC_Integration::get_tab() );
    array_push( $this->tabs, WC_Horizon_Admin_Tab_Encryptation::get_tab() );
    if( count($tabs) ) {
      array_push( $this->tabs, $tabs );
    }
  }

  
}
Horizon_Customisations_Admin::start();
