<?php

/**
 * Fires when the plugin is uninstalled.
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if Keep Data and Settings on Uninstall option is activated. If activated, do not erase data and settings
$keep_data_setting = boolval(get_option( 'b2bking_keepdata_setting', 1 ));

// If "keep data" option is NOT activated
if (!$keep_data_setting) {

	// List all options
	$optionlist = array('b2bking_all_products_visible_all_users_setting', 'b2bking_enabletags_setting','b2bking_keepdata_setting', 'b2bking_enable_subaccounts_setting', 'b2bking_enable_bulk_order_form_setting', 'b2bking_enable_purchase_lists_setting', 'b2bking_enable_offers_setting', 'b2bking_enable_conversations_setting', 'b2bking_approval_required_all_users_setting', 'b2bking_registration_roles_dropdown_setting', 'b2bking_guest_access_restriction_setting', 'b2bking_current_tab_setting', 'b2bking_plugin_status_setting', ); 

	// Delete all options
	foreach ($optionlist as $option_name){ 
		delete_option($option_name);
	} 
	  
}