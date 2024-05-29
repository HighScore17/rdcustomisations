<?php
/**
 * Plugin Name: Display Category Image For WooCommerce
 * Plugin URI: https://wordpress.org/plugins/display-category-image
 * Text Domain: display-category-image
 * Description: Display Category Image on Product Category Archives. 
 * Domain Path: /languages/
 * Version: 1.0
 * Author: Rajdip Sinha Roy
 * Author URI: https://rajdip.tech
 * Developer: Rajdip Sinha Roy
 * Developer URI: https://rajdip.tech
 * WC requires at least: 3.0.0
 * WC tested up to: 4.2.2
*/



if (! defined('ABSPATH')) {
    exit;
}



/**
 * Display category image on category archive
 */
add_action( 'woocommerce_archive_description', 'woocommerce_category_image', 2 );
function woocommerce_category_image() {
    if ( is_product_category() ){
	    global $wp_query;
	    $cat = $wp_query->get_queried_object();
	    $thumbnail_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
	    $image = wp_get_attachment_url( $thumbnail_id );
	    if ( $image ) {
		    echo '<img src="' . $image . '" alt="' . $cat->name . '" />';
		}
	}
}