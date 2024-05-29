<?php
  /**
    * Plugin Name: WP Manage metadata
    * Description: Manage product metadata.
    * Author:  Horizon trade solutions
    * Version: 0.1
    */

  /**
  * Admin page
  */
  add_action('admin_menu', 'init_dashboard_page');
  function init_dashboard_page()
  {
    $menu = add_menu_page(
      'WP Manage metadata',
      'WP Manage metadata',
      'edit_pages',
      'wp_manage_metadata',
      'wp_manage_metadata_admin_page'
    );
    add_action( 'admin_print_scripts-' . $menu, __DIR__ . '/assets/js/main.js' );
  }
  
  function wp_manage_metadata_admin_page()
  {
    require_once __DIR__ . "/views/menu.php";
  }
  /**
   * Update meta
   */
  function wp_manage_metadata( $id, $key, $value )
  {
    $product = wc_get_product( $id );
    $product->update_meta_data( $key, $value );
    $product->save();
  }
  function wp_ajax_set_manage_metadata()
  {
    wp_manage_metadata( intval($_POST['id']), $_POST['key'], $_POST['value'] );
    echo json_encode( array(
      'success' => 'true'
    ) );
    die();
  }
  add_action('wp_ajax_set_manage_metadata', 'wp_ajax_set_manage_metadata');

  /**
   * Get meta
   */
  function wp_manage_get_metadata( $id )
  {
    $product = wc_get_product( $id );
    return $product->get_meta_data();
  }
  function wp_ajax_get_manage_metadata()
  {
    $metadata = wp_manage_get_metadata( intval($_POST['id']));
    echo json_encode( $metadata );
    die();
  }
  add_action('wp_ajax_get_manage_metadata', 'wp_ajax_get_manage_metadata');
?>