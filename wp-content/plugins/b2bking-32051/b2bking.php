<?php
/*
/**
 * Plugin Name:       B2BKing
 * Plugin URI:        woocommerce-b2b-plugin.com
 * Description:       B2BKing is the complete solution for turning WooCommerce into an enterprise-level B2B e-commerce platform.
 * Version:           3.2.0
 * Author:            WebWizards
 * Author URI:        webwizards.dev
 * Text Domain:       b2bking
 * Domain Path:       /languages
 * WC requires at least: 3.0.0
 * WC tested up to: 5.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'B2BKING_DIR', plugin_dir_path( __FILE__ ) );

function b2bking_activate() {
	require_once B2BKING_DIR . 'includes/class-b2bking-activator.php';
	B2bking_Activator::activate();

}
register_activation_hook( __FILE__, 'b2bking_activate' );

// Check B2BKing Lite or another version does not already exist
add_action( 'activate_plugin', 'b2bking_check_activation_lite', 10, 2 );
function b2bking_check_activation_lite( $plugin, $network_wide ) {
	if (defined('B2BKINGLITE_DIR')){
		// trigger error
		wp_die('B2BKing cannot be activated because another plugin version such as B2BKing Lite is already active. Please deactivate the other version first.');
		exit;
	}
}

require B2BKING_DIR . 'includes/class-b2bking.php';

// Load plugin language
add_action( 'plugins_loaded', 'b2bking_load_language');
function b2bking_load_language() {
   load_plugin_textdomain( 'b2bking', FALSE, basename( dirname( __FILE__ ) ) . '/languages');
}

// Begins execution of the plugin.
function b2bking_run() {
	$plugin = new B2bking();
}

b2bking_run();
