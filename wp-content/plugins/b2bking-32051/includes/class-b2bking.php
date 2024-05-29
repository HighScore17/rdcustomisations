<?php

class B2bking {

	function __construct() {

		// Include dynamic rules code
		require_once ( B2BKING_DIR . 'public/class-b2bking-dynamic-rules.php' );

		add_action('init', function(){
			// visibility query for pre_get_posts, must be run on init
			$this->get_visibility_set_transient();
		});

		// Handle Ajax Requests
		if ( wp_doing_ajax() ){

			// interferes in the product page for some reason with variation loading

			add_action('plugins_loaded', function(){

				// Add content shortcode
				add_action( 'init', array($this, 'b2bking_content_shortcode'));

		   		if (intval(get_option('b2bking_search_product_description_setting', 0)) === 0){
		   			// if search product description is disabled, search by title only
	   				add_filter('posts_search', array($this, 'b2bking_search_by_title_only'), 500, 2);
			   	}

		   	   	// Check that plugin is enabled
		   	   	if ( get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){
		   	   	/* Groups */
		   			// Set up product/category user/user group visibility rules
		   	   		if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){
			   			if (intval(get_option('b2bking_disable_visibility_setting', 0)) === 0){

			   				// if user is not admin or shop manager
			   				if (!current_user_can( 'manage_woocommerce' )){
			   					// if caching is enabled
			   					if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){
			   						add_action( 'pre_get_posts', array($this, 'b2bking_product_categories_visibility_rules') );
			   					}
			   				}
			   			}
			   		}
		   		}
			
				// Add Fixed Price Rule to AJAX product searches
				// Check if plugin status is B2B OR plugin status is Hybrid and user is B2B user.
				if(isset($_COOKIE['b2bking_userid'])){
					$cookieuserid = sanitize_text_field($_COOKIE['b2bking_userid']);
				} else {
					$cookieuserid = '999999999999';
				}
				if (get_option('b2bking_plugin_status_setting', 'disabled') === 'b2b' || (get_option('b2bking_plugin_status_setting', 'disabled') === 'hybrid' && (get_user_meta( get_current_user_id(), 'b2bking_b2buser', true ) === 'yes' || get_user_meta( $cookieuserid, 'b2bking_b2buser', true ) === 'yes'))){

					if (intval(get_option('b2bking_disable_dynamic_rule_fixedprice_setting', 0)) === 0){
						// check the number of rules saved in the database
						if (get_option('b2bking_have_fixed_price_rules', 'yes') === 'yes'){
							// check if the user's ID or group is part of the list.
							$list = get_option('b2bking_have_fixed_price_rules_list', 'yes');
							if ($this->b2bking_user_is_in_list($list) === 'yes'){
								add_filter('woocommerce_product_get_price', array( 'B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_fixed_price' ), 9999, 2 );
								add_filter('woocommerce_product_get_regular_price', array( 'B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_fixed_price' ), 9999, 2 );
								// Variations 
								add_filter('woocommerce_product_variation_get_regular_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_fixed_price' ), 9999, 2 );
								add_filter('woocommerce_product_variation_get_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_fixed_price' ), 9999, 2 );
								add_filter( 'woocommerce_variation_prices_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_fixed_price'), 9999, 2 );
								add_filter( 'woocommerce_variation_prices_regular_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_fixed_price'), 9999, 2 );
							}
						}
					}
				}


				// Add Discount rule to AJAX product searches
				if (get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){
					
					if (intval(get_option('b2bking_disable_dynamic_rule_discount_sale_setting', 0)) === 0){
						if (get_option('b2bking_have_discount_everywhere_rules', 'yes') === 'yes'){
							// check if the user's ID or group is part of the list.
							$list = get_option('b2bking_have_discount_everywhere_rules_list', 'yes');
							if ($this->b2bking_user_is_in_list($list) === 'yes'){
								add_filter( 'woocommerce_product_get_regular_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_regular_price'), 9999, 2 );
								add_filter( 'woocommerce_product_variation_get_regular_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_regular_price'), 9999, 2 );
								// Generate "sale price" dynamically
								add_filter( 'woocommerce_product_get_sale_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_sale_price'), 9999, 2 );
								add_filter( 'woocommerce_product_variation_get_sale_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_sale_price'), 9999, 2 );
								add_filter( 'woocommerce_variation_prices_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_sale_price'), 9999, 2 );
								add_filter( 'woocommerce_variation_prices_sale_price', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_sale_price'), 9999, 2 );
								add_filter( 'woocommerce_get_variation_prices_hash', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_sale_price_variation_hash'), 99, 1);
								 
								// Displayed formatted regular price + sale price
								add_filter( 'woocommerce_get_price_html', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_display_dynamic_price'), 9999, 2 );
								// Set sale price in Cart
								add_action( 'woocommerce_before_calculate_totals', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_display_dynamic_price_in_cart'), 9999, 1 );
								// Function to make this work for MiniCart as well
								add_filter('woocommerce_cart_item_price',array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_display_dynamic_price_in_cart_item'),9999,3);
								
								// Change "Sale!" badge text
								add_filter('woocommerce_sale_flash', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_display_dynamic_sale_badge'), 9999, 3);
							}
						}
					}
				}

				if (intval(get_option('b2bking_disable_dynamic_rule_hiddenprice_setting', 0)) === 0){
					if (get_option('b2bking_have_hidden_price_rules', 'yes') === 'yes'){
						// check if the user's ID or group is part of the list.
						$list = get_option('b2bking_have_hidden_price_rules_list', 'yes');
						if ($this->b2bking_user_is_in_list($list) === 'yes'){
							// Add product purchasable filter, so that it works with Bulk Order Form checks
							add_filter( 'woocommerce_get_price_html', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_hidden_price'), 99999, 2 );
							add_filter( 'woocommerce_variation_price_html', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_hidden_price'), 99999, 2 );
							// Dynamic rule Hidden price - disable purchasable
							add_filter( 'woocommerce_is_purchasable', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_hidden_price_disable_purchasable'), 10, 2);
							add_filter( 'woocommerce_variation_is_purchasable', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_hidden_price_disable_purchasable'), 10, 2);
						}
					}
				}

				if (intval(get_option('b2bking_disable_dynamic_rule_requiredmultiple_setting', 0)) === 0){
					if (get_option('b2bking_have_required_multiple_rules', 'yes') === 'yes'){
						// check if the user's ID or group is part of the list.
						$list = get_option('b2bking_have_required_multiple_rules_list', 'yes');
						if ($this->b2bking_user_is_in_list($list) === 'yes'){
							// add quantity step in product page
							add_filter( 'woocommerce_quantity_input_args', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple_quantity'), 10, 2 );
							add_filter( 'woocommerce_available_variation', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple_quantity'), 10, 2 );
							
						}
					}
				}

				if (get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){

					// Add tiered pricing to AJAX as well
					/* Set Tiered Pricing via Fixed Price Dynamic Rule */
					add_filter('woocommerce_product_get_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter('woocommerce_product_get_regular_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					// Variations 
					add_filter('woocommerce_product_variation_get_regular_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter('woocommerce_product_variation_get_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter( 'woocommerce_variation_prices_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter( 'woocommerce_variation_prices_regular_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );

					// Pricing and Discounts in the Product Page: Add to AJAX
					/* Set Individual Product Pricing (via product tab) */
					add_filter('woocommerce_product_get_price', array($this, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter('woocommerce_product_get_regular_price', array($this, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					// Variations 
					add_filter('woocommerce_product_variation_get_regular_price', array($this, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter('woocommerce_product_variation_get_price', array($this, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_price', array($this, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_regular_price', array($this, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					// Set sale price as well
					add_filter( 'woocommerce_product_get_sale_price', array($this, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					add_filter( 'woocommerce_product_variation_get_sale_price', array($this, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_price', array($this, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_sale_price', array($this, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					// display html
					// Displayed formatted regular price + sale price
					add_filter( 'woocommerce_get_price_html', array($this, 'b2bking_individual_pricing_discount_display_dynamic_price'), 999, 2 );
					// Set sale price in Cart
					add_action( 'woocommerce_before_calculate_totals', array($this, 'b2bking_individual_pricing_discount_display_dynamic_price_in_cart'), 999, 1 );
					// Function to make this work for MiniCart as well
					add_filter('woocommerce_cart_item_price',array($this, 'b2bking_individual_pricing_discount_display_dynamic_price_in_cart_item'),999,3);
				}


				if (!is_user_logged_in()){
					if (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'hide_prices'){	
						add_filter( 'woocommerce_get_price_html', array($this, 'b2bking_hide_prices_guest_users'), 9999, 2 );
						add_filter( 'woocommerce_variation_get_price_html', array($this, 'b2bking_hide_prices_guest_users'), 9999, 2 );
						// Hide add to cart button as well / purchasable capabilities
						add_filter( 'woocommerce_is_purchasable', array($this, 'b2bking_disable_purchasable_guest_users'));
						add_filter( 'woocommerce_variation_is_purchasable', array($this, 'b2bking_disable_purchasable_guest_users'));
					}
				}
				
			});

			// Conversations
			add_action( 'wp_ajax_b2bkingconversationmessage', array($this, 'b2bkingconversationmessage') );
    		add_action( 'wp_ajax_nopriv_b2bkingconversationmessage', array($this, 'b2bkingconversationmessage') );
    		add_action( 'wp_ajax_b2bkingsendinquiry', array($this, 'b2bkingsendinquiry') );
    		add_action( 'wp_ajax_nopriv_b2bkingsendinquiry', array($this, 'b2bkingsendinquiry') );
    		// Request custom quote from cart
    		add_action( 'wp_ajax_b2bkingrequestquotecart', array($this, 'b2bkingrequestquotecart') );
    		add_action( 'wp_ajax_nopriv_b2bkingrequestquotecart', array($this, 'b2bkingrequestquotecart') );
    		// Add offer to cart
    		add_action( 'wp_ajax_b2bkingaddoffer', array($this, 'b2bkingaddoffer') );
    		add_action( 'wp_ajax_nopriv_b2bkingaddoffer', array($this, 'b2bkingaddoffer') );
    		// Approve and Reject users
    		add_action( 'wp_ajax_b2bkingapproveuser', array($this, 'b2bkingapproveuser') );
    		add_action( 'wp_ajax_nopriv_b2bkingapproveuser', array($this, 'b2bkingapproveuser') );
    		add_action( 'wp_ajax_b2bkingrejectuser', array($this, 'b2bkingrejectuser') );
    		add_action( 'wp_ajax_nopriv_b2bkingrejectuser', array($this, 'b2bkingrejectuser') );
    		// Download file (e.g. registration files, company license etc)
    		add_action( 'wp_ajax_b2bkinghandledownloadrequest', array($this, 'b2bkinghandledownloadrequest') );
    		// Subaccounts
    		add_action( 'wp_ajax_nopriv_b2bking_create_subaccount', array($this, 'b2bking_create_subaccount') );
    		add_action( 'wp_ajax_b2bking_create_subaccount', array($this, 'b2bking_create_subaccount') );
    		add_action( 'wp_ajax_nopriv_b2bking_update_subaccount', array($this, 'b2bking_update_subaccount') );
    		add_action( 'wp_ajax_b2bking_update_subaccount', array($this, 'b2bking_update_subaccount') );
    		// Bulk order
    		add_action( 'wp_ajax_nopriv_b2bking_ajax_search', array($this, 'b2bking_ajax_search') );
    		add_action( 'wp_ajax_b2bking_ajax_search', array($this, 'b2bking_ajax_search') );

    		add_action( 'wp_ajax_nopriv_b2bking_accountingsubtotals', array($this, 'b2bking_accountingsubtotals') );
    		add_action( 'wp_ajax_b2bking_accountingsubtotals', array($this, 'b2bking_accountingsubtotals') );

    		add_action( 'wp_ajax_nopriv_b2bking_ajax_get_price', array($this, 'b2bking_ajax_get_price') );
    		add_action( 'wp_ajax_b2bking_ajax_get_price', array($this, 'b2bking_ajax_get_price') );
    		add_action( 'wp_ajax_nopriv_b2bking_bulkorder_add_cart', array($this, 'b2bking_bulkorder_add_cart') );
    		add_action( 'wp_ajax_b2bking_bulkorder_add_cart', array($this, 'b2bking_bulkorder_add_cart') );
    		add_action( 'wp_ajax_nopriv_b2bking_bulkorder_save_list', array($this, 'b2bking_bulkorder_save_list') );
    		add_action( 'wp_ajax_b2bking_bulkorder_save_list', array($this, 'b2bking_bulkorder_save_list') );
    		// Purchase lists
    		add_action( 'wp_ajax_nopriv_b2bking_purchase_list_update', array($this, 'b2bking_purchase_list_update') );
    		add_action( 'wp_ajax_b2bking_purchase_list_update', array($this, 'b2bking_purchase_list_update') );
    		add_action( 'wp_ajax_nopriv_b2bking_purchase_list_delete', array($this, 'b2bking_purchase_list_delete') );
    		add_action( 'wp_ajax_b2bking_purchase_list_delete', array($this, 'b2bking_purchase_list_delete') );
    		add_action( 'wp_ajax_nopriv_b2bking_save_cart_to_purchase_list', array($this, 'b2bking_save_cart_to_purchase_list') );
    		add_action( 'wp_ajax_b2bking_save_cart_to_purchase_list', array($this, 'b2bking_save_cart_to_purchase_list') );
    		// Dismiss "activate woocommerce" admin notice permanently
    		add_action( 'wp_ajax_b2bking_dismiss_activate_woocommerce_admin_notice', array($this, 'b2bking_dismiss_activate_woocommerce_admin_notice') );
    		// Save Special group settings (b2c and guests) in groups
    		add_action( 'wp_ajax_nopriv_b2bking_b2c_special_group_save_settings', array($this, 'b2bking_b2c_special_group_save_settings') );
    		add_action( 'wp_ajax_b2bking_b2c_special_group_save_settings', array($this, 'b2bking_b2c_special_group_save_settings') );
    		add_action( 'wp_ajax_nopriv_b2bking_logged_out_special_group_save_settings', array($this, 'b2bking_logged_out_special_group_save_settings') );
    		add_action( 'wp_ajax_b2bking_logged_out_special_group_save_settings', array($this, 'b2bking_logged_out_special_group_save_settings') );
    		// Tools
    		add_action( 'wp_ajax_nopriv_b2bkingdownloadpricelist', array($this, 'b2bkingdownloadpricelist') );
    		add_action( 'wp_ajax_b2bkingdownloadpricelist', array($this, 'b2bkingdownloadpricelist') );

    		add_action( 'wp_ajax_nopriv_b2bkingdownloadpurchaselist', array($this, 'b2bkingdownloadpurchaselist') );
    		add_action( 'wp_ajax_b2bkingdownloadpurchaselist', array($this, 'b2bkingdownloadpurchaselist') );
    		

    		add_action( 'wp_ajax_nopriv_b2bkingdownloadtroubleshooting', array($this, 'b2bkingdownloadtroubleshooting') );
    		add_action( 'wp_ajax_b2bkingdownloadtroubleshooting', array($this, 'b2bkingdownloadtroubleshooting') );
    		add_action( 'wp_ajax_nopriv_b2bkingbulksetusers', array($this, 'b2bkingbulksetusers') );
    		add_action( 'wp_ajax_b2bkingbulksetusers', array($this, 'b2bkingbulksetusers') );
    		add_action( 'wp_ajax_nopriv_b2bkingbulksetcategory', array($this, 'b2bkingbulksetcategory') );
    		add_action( 'wp_ajax_b2bkingbulksetcategory', array($this, 'b2bkingbulksetcategory') );
    		add_action( 'wp_ajax_nopriv_b2bkingbulksetsubaccounts', array($this, 'b2bkingbulksetsubaccounts') );
    		add_action( 'wp_ajax_b2bkingbulksetsubaccounts', array($this, 'b2bkingbulksetsubaccounts') );

    		// Backend Customers Panel
    		add_action( 'wp_ajax_nopriv_b2bking_admin_customers_ajax', array($this, 'b2bking_admin_customers_ajax') );
    		add_action( 'wp_ajax_b2bking_admin_customers_ajax', array($this, 'b2bking_admin_customers_ajax') );
    		// Backend Update User Data
    		add_action( 'wp_ajax_nopriv_b2bkingupdateuserdata', array($this, 'b2bkingupdateuserdata') );
    		add_action( 'wp_ajax_b2bkingupdateuserdata', array($this, 'b2bkingupdateuserdata') );
    		// Validate VAT for checkout registration 
    		add_action( 'wp_ajax_nopriv_b2bkingvalidatevat', array($this, 'b2bkingvalidatevat') );
    		add_action( 'wp_ajax_b2bkingvalidatevat', array($this, 'b2bkingvalidatevat') );
    		// Check delivery country for VAT Validation
    		add_action( 'wp_ajax_nopriv_b2bkingcheckdeliverycountryvat', array($this, 'b2bkingcheckdeliverycountryvat') );
    		add_action( 'wp_ajax_b2bkingcheckdeliverycountryvat', array($this, 'b2bkingcheckdeliverycountryvat') );
    		// AJAX Search Image
    		add_action( 'wp_ajax_nopriv_b2bking_ajax_search_image', array($this, 'b2bking_ajax_search_image') );
    		add_action( 'wp_ajax_b2bking_ajax_search_image', array($this, 'b2bking_ajax_search_image') );
		}

		// Add invoice gateway
		add_filter( 'woocommerce_payment_gateways',  array( $this, 'b2bking_add_invoice_gateway' ) );
		// Add purchase order gateway
		add_filter( 'woocommerce_payment_gateways',  array( $this, 'b2bking_add_purchase_order_gateway' ) );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'b2bking_display_order_number' ) );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'b2bking_display_order_number' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'b2bking_display_order_number' ) );
		add_action( 'wc_pip_after_body', array( $this, 'b2bking_po_number_pip' ), 10, 4 );

		// Add email classes
		add_filter( 'woocommerce_email_classes', array($this, 'b2bking_add_email_classes') );
		// Add extra email actions (account approved finish)
		add_filter( 'woocommerce_email_actions', array($this, 'b2bking_add_email_actions'));
		// Include metadata in REST API
		add_action('rest_api_init', array($this, 'register_metadata'));
		// flush cache for scheduled
		add_action( 'transition_post_status', array($this,'b2bking_flush_cache_scheduled'), 10, 3 );
		add_action( 'save_post', array($this,'b2bking_flush_cache_for_api'), 10, 1 );
		add_action( "rest_insert_b2bking_rule", array($this,'b2bking_flush_cache_for_api_rule'), 10, 3 );



		// Run Admin/Public code 
		if ( is_admin() ) { 
			require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
			$admin = new B2bking_Admin();
		} else if ( !$this->b2bking_is_login_page() ) {
			require_once B2BKING_DIR . '/public/class-b2bking-public.php';
			$public = new B2bking_Public();
		}
	}

	function b2bking_flush_cache_for_api_rule( $post, $request, $true) {

    	// delete all b2bking transients
    	global $wpdb;
    	$plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%transient_b2bking%'" );
    	foreach( $plugin_options as $option ) {
    	    delete_option( $option->option_name );
    	}
    	wp_cache_flush();

    	require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
    	B2bking_Admin::b2bking_calculate_rule_numbers_database();
	    
	}
	function b2bking_flush_cache_for_api( $post_id) {
		if (isset($_POST['_inline_edit'])){
			if (wp_verify_nonce($_POST['_inline_edit'], 'inlineeditnonce')){
			    return;
			}
		}
	    if (get_post_type($post_id) === 'product' || get_post_type($post_id) === 'b2bking_rule'){

	    	// delete all b2bking transients
	    	global $wpdb;
	    	$plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%transient_b2bking%'" );
	    	foreach( $plugin_options as $option ) {
	    	    delete_option( $option->option_name );
	    	}
	    	wp_cache_flush();

	    	require_once B2BKING_DIR . '/admin/class-b2bking-admin.php';
	    	B2bking_Admin::b2bking_calculate_rule_numbers_database();
		    
	    }
	}
	function b2bking_flush_cache_scheduled( $new, $old, $post ) {
		$post_id = $post->ID;
	    if (get_post_type($post_id) === 'product'){

	    	if ($new === 'publish' && $old === 'future' ){

		    	// delete all b2bking transients
		    	global $wpdb;
		    	$plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE '%transient_b2bking%'" );
		    	foreach( $plugin_options as $option ) {
		    	    delete_option( $option->option_name );
		    	}
		    	wp_cache_flush();
		    }
	    }
	}

	// B2BKing Content Shortcode
	function b2bking_content_shortcode(){
		add_shortcode('b2bking_content', array($this, 'b2bking_content_shortcode_content'));
	}
	function b2bking_content_shortcode_content($atts = array(), $content = null){


		$atts = shortcode_atts(
	        array(
	            'show_to' => 'none',
	        ), 
	    $atts);
	    if ($atts['show_to'] === 'none'){
	    	return '';
	    } else {
	    	$groups_array=explode(',',$atts['show_to']);
	    	// check if current user has access
	    	$current_user_id = get_current_user_id();
	    	$current_user_group = get_user_meta($current_user_id,'b2bking_customergroup',true);

	    	$user_is_b2b = get_user_meta($current_user_id,'b2bking_b2buser',true);
	    	if ($user_is_b2b !== 'yes'){
	    		if (is_user_logged_in()){
	    			$current_user_group = 'b2c';
	    		} else {
	    			$current_user_group = 'loggedout';
	    		}
	    	}

	    	if (in_array($current_user_group,$groups_array)){
	    		return $content;
	    	} else {
	    		// check if user is b2b in general
	    		if ($user_is_b2b === 'yes' && in_array('b2b', $groups_array)){
	    			return $content;
	    		} else{
	    			// check user's specific username
	    			$user_login = wp_get_current_user()->user_login;
	    			if (in_array($user_login,$groups_array)){
	    				return $content;
	    			} else {
	    				return '';
	    			}
	    		}
	    	}
	    }

	}

	function register_metadata(){

		$array_options = array('b2bking_rule_what','b2bking_rule_howmuch','b2bking_rule_applies','b2bking_rule_who','b2bking_rule_quantity_value','b2bking_rule_discount_show_everywhere','b2bking_rule_conditions','b2bking_rule_discountname','b2bking_rule_applies_multiple_options','b2bking_rule_who_multiple_options','b2bking_rule_taxname','b2bking_rule_replaced','b2bking_rule_showtax','b2bking_rule_requires','b2bking_rule_tax_shipping','b2bking_rule_paymentmethod','b2bking_rule_currency');


		foreach ($array_options as $option){
			register_meta('post', $option, [
			  'object_subtype' => 'b2bking_rule',
			  'show_in_rest' => true
			]);
		}
	}


	function b2bking_user_is_in_list($list){
		// get user data
		$user_data_current_user_id = get_current_user_id();
		if (intval($user_data_current_user_id) === 0){
			// check cookies
			if (isset($_COOKIE['b2bking_userid'])){
				$user_data_current_user_id = $_COOKIE['b2bking_userid'];
			}
		}
		$user_data_current_user_b2b = get_user_meta($user_data_current_user_id, 'b2bking_b2buser', true);
		$user_data_current_user_group = get_user_meta($user_data_current_user_id, 'b2bking_customergroup', true);
		// checks based on user id, b2b status and group, if it's part of an applicable rules list
		$is_in_list = 'no';
		$list_array = explode(',',$list);
		if (intval($user_data_current_user_id) !== 0){
			if (in_array('everyone_registered', $list_array)){
				return 'yes';
			}
			if ($user_data_current_user_b2b === 'yes'){
				// user is b2b
				if (in_array('everyone_registered_b2b', $list_array)){
					return 'yes';
				}
				if (in_array('group_'.$user_data_current_user_group, $list_array)){
					return 'yes';
				}
			} else {
				// user is b2c
				if (in_array('everyone_registered_b2c', $list_array)){
					return 'yes';
				}
			}
			if (in_array('user_'.$user_data_current_user_id, $list_array)){
				return 'yes';
			}

		} else if (intval($user_data_current_user_id) === 0){
			if (in_array('user_0', $list_array)){
				return 'yes';
			}
		}

		return $is_in_list;
	}

	// Add email classes to the list of email classes that WooCommerce loads
	function b2bking_add_email_classes( $email_classes ) {

	    $email_classes['B2bking_New_Customer_Email'] = include B2BKING_DIR .'/includes/emails/class-b2bking-new-customer-email.php';

	    $email_classes['B2bking_New_Message_Email'] = include B2BKING_DIR .'/includes/emails/class-b2bking-new-message-email.php';

	    $email_classes['B2bking_New_Customer_Requires_Approval_Email'] = include B2BKING_DIR .'/includes/emails/class-b2bking-new-customer-requires-approval-email.php';

	    $email_classes['B2bking_Your_Account_Approved_Email'] = include B2BKING_DIR .'/includes/emails/class-b2bking-your-account-approved-email.php';

	    return $email_classes;
	}

	// Add email actions
	function b2bking_add_email_actions( $actions ) {
	    $actions[] = 'b2bking_account_approved_finish';
	    $actions[] = 'b2bking_new_message';
	    return $actions;
	}

	// Add invoice payment gateway
	function b2bking_add_invoice_gateway ( $methods ){
		if ( ! class_exists( 'B2BKing_Invoice_Gateway' ) ) {
			include_once('class-b2bking-invoice-gateway.php');
			$methods[] = 'B2BKing_Invoice_Gateway';
		}
    	return $methods;
	}

	// Add purchase order gateway
	function b2bking_add_purchase_order_gateway ( $methods ){
		if ( ! class_exists( 'B2BKing_Purchase_Order_Gateway' ) ) {
			include_once('class-b2bking-purchase-order-gateway.php');
			$methods[] = 'B2BKing_Purchase_Order_Gateway';
		}
    	return $methods;
	}

	function b2bking_po_number_pip( $type, $action, $document, $order ) {
		if ( 'invoice' != $type ) {
			return;
		}

		$payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
		$order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();

		if ( 'B2BKing_Purchase_Order_Gateway' === $payment_method ) {
			$po_number = get_post_meta( $order_id, '_po_number', true );
			/* translators: Placeholder: %1$s - opening <strong> tag, %2$s - coupons count (used in order), %3$s - closing </strong> tag - %4$s - coupons list */
			printf( '<div class="purchase-order-number">' . __( '%1$sPurchase order number:%2$s %3$s', 'woocommerce-gateway-purchase-order' ) . '</div>', '<strong>', '</strong>', esc_html( $po_number ) );
		}
	}


	function b2bking_display_order_number ( $order ) {
		$payment_method = version_compare( WC_VERSION, '3.0', '<' ) ? $order->payment_method : $order->get_payment_method();
		$order_id = version_compare( WC_VERSION, '3.0', '<' ) ? $order->id : $order->get_id();

		if ( 'B2BKing_Purchase_Order_Gateway' === $payment_method ) {
			$po_number = get_post_meta( $order_id, '_po_number', true );
			if ( '' != $po_number ) {
				if ( 'woocommerce_order_details_after_order_table' == current_filter() ) {
					echo '<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">';
					echo '<li class="woocommerce-order-overview__purchase-order purchase-order">' . __( 'Purchase Order Number:', 'woocommerce-gateway-purchase-order' ) . '<strong>' . $po_number . '</strong></li>';
					echo '</ul>';
				} else {
					echo '<p class="form-field form-field-wide"><strong>' . __( 'Purchase Order Number:', 'woocommerce-gateway-purchase-order' ) . '</strong><h2>' . $po_number . '</h2></p>' . "\n";
				}
			}
		}
	}


	// Helps prevent public code from running on login / register pages, where is_admin() returns false
	function b2bking_is_login_page() {
		if(isset($GLOBALS['pagenow'])){
	    	return in_array( $GLOBALS['pagenow'],array( 'wp-login.php', 'wp-register.php', 'admin.php' ),  true  );
	    }
	}

	function b2bking_ajax_search_image(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
			wp_send_json_error( 'Invalid security token sent.' );
			wp_die();
		}

		$searched_term = sanitize_text_field($_POST['searchValue']);
		$str = wp_get_attachment_image_src(get_post_thumbnail_id($searched_term));
		if($str && (count($str) > 0)){
			// return image url
			echo($str[0]);
		}

		exit();
	}

	function b2bking_admin_customers_ajax(){
    	// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$start = sanitize_text_field($_POST['start']);
		$length = sanitize_text_field($_POST['length']);
		$search = sanitize_text_field($_POST['search']['value']);
		$pagenr = ($start/$length)+1;

		$args = array(
		    'role'    => 'customer',
		    'number'  => $length,
		    'search' => "*{$search}*",
		    'search_columns' => array(
		        'display_name',
	        ),
		    'paged'   => floatval($pagenr),
		    'fields'=> array('ID', 'display_name'),
		);

		$users = get_users( $args );

		$data = array(

			'length'=> $length,
			'data' => array()
		);

		foreach ( $users as $user ) {

			$user_id = $user->ID;
			$original_user_id = $user_id;
			$username = $user->display_name;

			// first check if subaccount. If subaccount, user is equivalent with parent
			$account_type = get_user_meta($user_id, 'b2bking_account_type', true);
			if ($account_type === 'subaccount'){
				// get parent
				$parent_account_id = get_user_meta ($user_id, 'b2bking_account_parent', true);
				$user_id = $parent_account_id;
				$account_type = esc_html__('Subaccount','b2bking');
			} else {
				$account_type = esc_html__('Main business account','b2bking');
			}

			$company_name = get_user_meta($user_id, 'billing_company', true);
			if (empty($company_name)){
				$company_name = '-';
			}

			$b2b_enabled = get_user_meta($user_id, 'b2bking_b2buser', true);
			if ($b2b_enabled === 'yes'){
				$b2b_enabled = 'Business';
			} else {
				$b2b_enabled = 'Consumer';
				$account_type = '-';
			}

			$group_name = get_the_title(get_user_meta($user_id, 'b2bking_customergroup', true));
			if (empty($group_name)){
				$group_name = '-';
				if ($b2b_enabled !== 'yes'){
					$group_name = 'B2C Users';
				}
			}

			$approval = get_user_meta($user_id, 'b2bking_account_approved', true);
			if (empty($approval)){
				$approval = '-';
			} else if ($approval === 'no'){
				$approval = esc_html__('Waiting Approval','b2bking');
			}

			$name_link = '<a href="'.esc_attr(get_edit_user_link($original_user_id)).'">'.esc_html( $username ).'</a>';
			array_push($data['data'],array($name_link, $company_name, $group_name, $account_type, $approval));
			
		}

		echo json_encode($data);
		
		exit();
	} 
	
 	// Update conversation with user message meta
	function b2bkingconversationmessage(){

    	// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		// If nonce verification didn't fail, run further
		$message = sanitize_textarea_field($_POST['message']);
		$conversationid = sanitize_text_field($_POST['conversationid']);

		$currentuser = wp_get_current_user()->user_login;
		$conversationuser = get_post_meta ($conversationid, 'b2bking_conversation_user', true);

		// Check message not empty
		if ($message !== NULL && trim($message) !== ''){
			// Check user permission against Conversation user meta. Check subaccounts as well
			$current_user_id = get_current_user_id();
		    $subaccounts_list = get_user_meta($current_user_id,'b2bking_subaccounts_list', true);
		    $subaccounts_list = explode(',', $subaccounts_list);
		    $subaccounts_list = array_filter($subaccounts_list);
		    array_push($subaccounts_list, $current_user_id);

		    $subaccounts_list = apply_filters('b2bking_conversation_permission_list', $subaccounts_list, $conversationid, $current_user_id, $conversationuser);

		    // if current account is subaccount AND has permission to view all account conversations, add parent account+all subaccounts lists
		    $account_type = get_user_meta($current_user_id, 'b2bking_account_type', true);
		    if ($account_type === 'subaccount'){
		    	$permission_view_all_conversations = filter_var(get_user_meta($current_user_id, 'b2bking_account_permission_view_conversations', true),FILTER_VALIDATE_BOOLEAN);
		    	if ($permission_view_all_conversations === true){
		    		// has permission
		    		$parent_account = get_user_meta($current_user_id, 'b2bking_account_parent', true);
		    		$parent_subaccounts_list = explode(',', get_user_meta($parent_account, 'b2bking_subaccounts_list', true));
		    		$parent_subaccounts_list = array_filter($parent_subaccounts_list); // filter blank, null, etc.
		    		array_push($parent_subaccounts_list, $parent_account); // add parent itself to form complete parent accounts list

		    		$subaccounts_list = array_merge($subaccounts_list, $parent_subaccounts_list);
		    	}
		    }

		    foreach ($subaccounts_list as $user){
		    	$subaccounts_list[$user] = get_user_by('id', $user)->user_login;
		    }

		    if (in_array($conversationuser, $subaccounts_list)){
				$nr_messages = get_post_meta ($conversationid, 'b2bking_conversation_messages_number', true);
				$current_message_nr = $nr_messages+1;
				update_post_meta( $conversationid, 'b2bking_conversation_message_'.$current_message_nr, $message);
				update_post_meta( $conversationid, 'b2bking_conversation_messages_number', $current_message_nr);
				update_post_meta( $conversationid, 'b2bking_conversation_message_'.$current_message_nr.'_author', $currentuser );
				update_post_meta( $conversationid, 'b2bking_conversation_message_'.$current_message_nr.'_time', time() );

				// if status is new, change to open
				$status = get_post_meta ($conversationid, 'b2bking_conversation_status', true);
				if ($status === 'new'){
					update_post_meta( $conversationid, 'b2bking_conversation_status', 'open');
				}


				// send email notification. Check if it has been 10 minutes since the previous message
				$previous_message_time = intval(get_post_meta($conversationid, 'b2bking_conversation_message_'.$nr_messages.'_time',true ));

				if ((time()-$previous_message_time) > 600){
					$recipient = get_option( 'admin_email' );

					$recipient = apply_filters('b2bking_recipient_new_message', $recipient, $conversationid);

					do_action( 'b2bking_new_message', $recipient, $message, $current_user_id, $conversationid );
				}
			}
		}
	}


	// Create new conversation by user
	function b2bkingsendinquiry(){

    	// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		// If nonce verification didn't fail, run further
		$message = sanitize_textarea_field($_POST['message']);
		$title = sanitize_text_field($_POST['title']);
		$type = sanitize_text_field($_POST['type']);
		$currentuser = wp_get_current_user()->user_login;
		$conversationid = '';

		// Check message not empty
		if ($message !== NULL && trim($message) !== ''){
			// Insert post
			$args = array(
				'post_title' => $title, 
				'post_type' => 'b2bking_conversation',
				'post_status' => 'publish', 
			);
			$conversationid = wp_insert_post( $args);

			update_post_meta( $conversationid, 'b2bking_conversation_user', $currentuser);
			update_post_meta( $conversationid, 'b2bking_conversation_status', 'new' );
			update_post_meta( $conversationid, 'b2bking_conversation_type', $type );
			update_post_meta( $conversationid, 'b2bking_conversation_message_1', $message);
			update_post_meta( $conversationid, 'b2bking_conversation_messages_number', 1);
			update_post_meta( $conversationid, 'b2bking_conversation_message_1_author', $currentuser );
			update_post_meta( $conversationid, 'b2bking_conversation_message_1_time', time() );

			// Add vendor if DOKAN
			if (isset($_POST['vendor'])){
				$vendor_id = sanitize_text_field($_POST['vendor']);
				$vendor_username = get_user_meta($vendor_id,'dokan_store_name', true);
				update_post_meta($conversationid,'b2bking_conversation_vendor', $vendor_username);
				// add conversation to vendor's list of conversations
				$list_conversations = get_user_meta($vendor_id,'b2bking_dokan_vendor_conversations_list_ids', true);
				$list_conversations .= ','.$conversationid.',';
				update_user_meta($vendor_id, 'b2bking_dokan_vendor_conversations_list_ids', $list_conversations);
			}
			$recipient = get_option( 'admin_email' );
			$recipient = apply_filters('b2bking_recipient_new_message', $recipient, $conversationid);

			// send email notification
			do_action( 'b2bking_new_message', $recipient, $message, get_current_user_id(), $conversationid );
		}
		
		// return conversation id URL
		echo esc_url(add_query_arg('id', $conversationid, wc_get_account_endpoint_url('conversation')));
		exit();
	}

	function b2bkingrequestquotecart(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		// If nonce verification didn't fail, run further
		$message = sanitize_textarea_field($_POST['message']);
		$messagecart = esc_html__('Requested items:','b2bking').' <br />';
		// Add cart details and quantities at the beginning of the message
		$items = WC()->cart->get_cart();
		foreach($items as $item => $values) { 
            $product =  wc_get_product( $values['data']->get_id());
            $name = $product->get_formatted_name();
            $product_name = apply_filters('b2bking_filter_product_name_quote',$name,$product);
            $messagecart .= "<b>".$product_name.'</b>  <br> '.esc_html__('Quantity: ','b2bking').$values['quantity'].'<br>'; 
        }
        $message = $messagecart.'<br />'.esc_html__('Message:','b2bking').'<br />'.$message;

		$title = sanitize_text_field($_POST['title']);
		$type = sanitize_text_field($_POST['type']);
		$currentuser = wp_get_current_user()->user_login;

		// if quote request is made by guest or B2C
		if ( !(is_user_logged_in()) || get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes'){
			$guest_name = sanitize_text_field($_POST['name']);
			$guest_email = sanitize_text_field($_POST['email']);
			$currentuser = sanitize_text_field(esc_html__('Name: ', 'b2bking')).$guest_name.' '.sanitize_text_field(esc_html__(' Email: ', 'b2bking')).$guest_email;

			$guest_quote_message = esc_html__('We have received your quote request and will be in touch with you shortly','b2bking');
			do_action( 'b2bking_new_message', $guest_email, $guest_quote_message, 'Quoteemail:1', 0);
			
		}
		$conversationid = '';

		// Insert post
		$args = array(
			'post_title' => $title, 
			'post_type' => 'b2bking_conversation',
			'post_status' => 'publish', 
		);
		$conversationid = wp_insert_post( $args);

		update_post_meta( $conversationid, 'b2bking_conversation_user', $currentuser);
		update_post_meta( $conversationid, 'b2bking_conversation_status', 'new' );
		update_post_meta( $conversationid, 'b2bking_conversation_type', $type );
		update_post_meta( $conversationid, 'b2bking_conversation_message_1', $message);
		update_post_meta( $conversationid, 'b2bking_conversation_messages_number', 1);
		update_post_meta( $conversationid, 'b2bking_conversation_message_1_author', $currentuser );
		update_post_meta( $conversationid, 'b2bking_conversation_message_1_time', time() );

		// if DOKAN vendor, set vendor
		if (isset($_POST['vendor'])){
			$vendor_store = sanitize_text_field($_POST['vendor']);
			if (empty(trim($vendor_store)) || $vendor_store === null){
				// do nothing, quote request is to site admin
			} else {
				$vendor_users = get_users(array('meta_key' => 'dokan_store_name', 'meta_value' => $vendor_store));
				$vendorobj = $vendor_users[0];
				update_post_meta($conversationid,'b2bking_conversation_vendor',$vendorobj->user_login);
				// add conversation to vendor's list of conversations
				$list_conversations = get_user_meta($vendorobj->ID,'b2bking_dokan_vendor_conversations_list_ids', true);
				$list_conversations .= ','.$conversationid.',';
				update_user_meta($vendorobj->ID, 'b2bking_dokan_vendor_conversations_list_ids', $list_conversations);
			}
		}

		if (!(is_user_logged_in()) || get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes'){
			update_post_meta( $conversationid, 'b2bking_conversation_message_2', sanitize_text_field(esc_html__('ATTENTION!: This quote request was started by a guest user (or B2C). The messaging system below will not work. Please email the user directly!', 'b2bking')));
			update_post_meta( $conversationid, 'b2bking_conversation_messages_number', 2);
			update_post_meta( $conversationid, 'b2bking_conversation_message_2_author', $currentuser );
			update_post_meta( $conversationid, 'b2bking_conversation_message_2_time', time() );
		}

		// send email notification
		$recipient = get_option( 'admin_email' );
		$recipient = apply_filters('b2bking_recipient_new_message_quote', $recipient, $conversationid);

		if (!(is_user_logged_in()) || get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes'){
			do_action( 'b2bking_new_message', $recipient, $message, $currentuser, $conversationid );
		} else {
			do_action( 'b2bking_new_message', $recipient, $message, get_current_user_id(), $conversationid );
		}

		// empty cart
		WC()->cart->empty_cart();

		// return conversation id URL
		echo esc_url(add_query_arg('id', $conversationid, wc_get_account_endpoint_url('conversation')));
		exit();
	}

	function b2bkingaddoffer(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		// If nonce verification didn't fail, run further

		$offer_id = sanitize_text_field($_POST['offer']);

		// Run permission check on offer
		$user = wp_get_current_user() -> user_login;
		$currentusergroupidnr = get_user_meta( get_current_user_id(), 'b2bking_customergroup', true );

		// If permission check is true
		if (intval(get_post_meta($offer_id, 'b2bking_user_'.$user, true)) === 1 || intval(get_post_meta($offer_id, 'b2bking_group_'.$currentusergroupidnr, true)) === 1){

			// Add offer to cart
			$offer_details = get_post_meta(apply_filters( 'wpml_object_id', $offer_id, 'post' , true), 'b2bking_offer_details', true);
			$products = explode ('|', $offer_details);
			$cart_item_data['b2bking_offer_id'] = $offer_id;
			$cart_item_data['b2bking_offer_name'] = get_the_title(apply_filters( 'wpml_object_id', $offer_id, 'post' , true));
			$cart_item_data['b2bking_numberofproducts'] = count($products);
			$i = 1;
			foreach($products as $product){
				$details = explode(';',$product);

				// if item is in the form product_id, change title
				$isproductid = explode('_', $details[0]); 
				if ($isproductid[0] === 'product'){
					// it is a product+id, get product title
					$newproduct = wc_get_product($isproductid[1]);
					$details[0] = $newproduct->get_name();

					//if product is a variation with 3 or more attributes, need to change display because get_name doesnt 
					// show items correctly
					if (is_a($newproduct,'WC_Product_Variation')){
						$attributes = $newproduct->get_variation_attributes();
						$number_of_attributes = count($attributes);
						if ($number_of_attributes > 2){
							$details[0].=' - ';
							foreach ($attributes as $attribute){
								$details[0].=$attribute.', ';
							}
							$details[0] = substr($details[0], 0, -2);
						}
					}
				}

				$cart_item_data['b2bking_product_'.$i.'_name'] = $details[0];
				$cart_item_data['b2bking_product_'.$i.'_quantity'] = $details[1];
				$cart_item_data['b2bking_product_'.$i.'_price'] = $details[2];
				$i++;
			}

			$cart_item_data = apply_filters('b2bking_before_add_offer_to_cart', $cart_item_data);

			// Create B2B offer product if it doesn't exist
			$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
			if ( !get_post_status ( $offer_id ) ) {
				$offer = array(
				    'post_title' => 'Offer',
				    'post_status' => 'customoffer',
				    'post_type' => 'product',
				    'post_author' => 1,
				);
				$product_id = wp_insert_post($offer);
				//Set product hidden: 
				$terms = array( 'exclude-from-catalog', 'exclude-from-search' );
				wp_set_object_terms( $product_id, $terms, 'product_visibility' );
				wp_set_object_terms( $product_id, 'simple', 'product_type' );
				update_post_meta( $product_id, '_visibility', 'hidden' );
				update_post_meta( $product_id, '_stock_status', 'instock');
				update_post_meta( $product_id, '_regular_price', '' );
				update_post_meta( $product_id, '_sale_price', '' );
				update_post_meta( $product_id, '_purchase_note', '' );
				update_post_meta( $product_id, '_sku', 'SKU11' );
				update_post_meta( $product_id, '_product_attributes', array() );
				update_post_meta( $product_id, '_sale_price_dates_from', '' );
				update_post_meta( $product_id, '_sale_price_dates_to', '' );
				update_post_meta( $product_id, '_price', '1' );
				update_post_meta( $product_id, '_sold_individually', '' );

				// set option to product id
				update_option( 'b2bking_offer_product_id_setting', $product_id );
				$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
			}
			
			$offer_id = apply_filters('b2bking_offer_id_before_add_offer_to_cart', $offer_id, $cart_item_data['b2bking_offer_id']);

			WC()->cart->add_to_cart( $offer_id, 1, 0, array(), $cart_item_data);

		} else {
			// do nothing
		}

		echo 'success';
		exit();	
	}

	function b2bkingcheckdeliverycountryvat(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		// If nonce verification didn't fail, run further


		// Apply VAT Rules
		B2bking_Dynamic_Rules::b2bking_dynamic_rule_tax_exemption();
		echo 'success';
		exit();	
	}

	function b2bkingvalidatevat(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		// If nonce verification didn't fail, run further

		$vat_number_inputted = sanitize_text_field($_POST['vat']);
		$vat_number_inputted = strtoupper(str_replace(array('.', ' '), '', $vat_number_inputted));
		$country_inputted = sanitize_text_field($_POST['country']);

		// validate number
		$error_details = '';
		$validation = new stdClass();
		$validation -> valid = 1;
		$debug = '1';
		// check vat
		try {
			$client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
			$country_code = substr($vat_number_inputted, 0, 2); // take first 2 chars
			$vat_number = substr($vat_number_inputted, 2); // remove first 2 chars

			$validation = $client->checkVat(array(
			  'countryCode' => $country_code,
			  'vatNumber' => $vat_number
			));
			$error_details = '';

			// check country is same as VAT country
			if (trim(strtolower($country_inputted)) !== trim(strtolower($country_code))){
				// check exception Greece (GR) has EL VAT code
				if( (trim(strtolower($country_inputted)) === 'gr') && (trim(strtolower($country_code)) === 'el')){
					// if indeed the VAT number is EL and country is GR, do nothing
				} else {
					$validation->valid=0;
				}
			}

		} catch (Exception $e) {
			$error = $e->getMessage();
			$error_array = array(
			    'INVALID_INPUT'       => esc_html__('CountryCode is invalid or the VAT number is empty', 'b2bking'),
			    'SERVICE_UNAVAILABLE' => esc_html__('VIES VAT Service is unavailable. Try again later.', 'b2bking'),
			    'MS_UNAVAILABLE'      => esc_html__('VIES VAT Member State Service is unavailable.', 'b2bking'),
			    'TIMEOUT'             => esc_html__('Service timeout. Try again later', 'b2bking'),
			    'SERVER_BUSY'         => esc_html__('VAT Server is too busy. Try again later.', 'b2bking'),
			);

			if ( array_key_exists( $error , $error_array ) ) {
			    $error_details .= $error_array[ $error ];
			}
			$validation->valid=0;
		}

		if(intval($validation->valid) === 1){
			echo 'valid';
		} else {
			echo 'invalid';
		}

		exit();	
	}

	function b2bkingapproveuser(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		// If nonce verification didn't fail, run further

		$user_id = sanitize_text_field($_POST['user']);
		$group = sanitize_text_field($_POST['chosen_group']);

		// approve account
		update_user_meta($user_id, 'b2bking_account_approved', 'yes');
		// place user in customer group 
		update_user_meta($user_id, 'b2bking_customergroup', $group);
		// add role
		$user_obj = new WP_User($user_id);
		$user_obj->add_role('b2bking_role_'.$group);
		// set user as b2b enabled
		update_user_meta($user_id, 'b2bking_b2buser', 'yes');


		// create action hook to send "account approved" email
		$email_address = sanitize_text_field(get_user_by('id', $user_id)->user_email);
		do_action( 'b2bking_account_approved_finish', $email_address );

		echo 'success';
		exit();	
	}

	function b2bkingrejectuser(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		// If nonce verification didn't fail, run further
		$user_id = sanitize_text_field($_POST['user']);

		// check if this function is being run by delete subaccount in the frontend
		if(isset($_POST['issubaccount'])){
			$current_user = get_current_user_id();
			// remove subaccount from user meta
			$subaccounts_number = get_user_meta($current_user, 'b2bking_subaccounts_number', true);
			$subaccounts_number = $subaccounts_number - 1;
			update_user_meta($current_user, 'b2bking_subaccounts_number', sanitize_text_field($subaccounts_number));

			$subaccounts_list = get_user_meta($current_user, 'b2bking_subaccounts_list', true);
			$subaccounts_list = str_replace(','.$user_id,'',$subaccounts_list);
			update_user_meta($current_user, 'b2bking_subaccounts_list', sanitize_text_field($subaccounts_list));

			// assign orders to parent
			$args = array(
			    'customer_id' => $user_id
			);
			$orders = wc_get_orders($args);
			foreach ($orders as $order){
				$order_id = $order->get_id();
				$parent_user_id = get_user_meta($user_id,'b2bking_account_parent', true);

				update_post_meta($order_id,'_customer_user', $parent_user_id);
			}
		}

		// delete account
		wp_delete_user($user_id);

		echo 'success';
		exit();	
	}

	// Handles AJAX Download requests, enabling the download of user attachment during registration
	function b2bkinghandledownloadrequest(){

    	// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		
		$requested_file = $_REQUEST['attachment'];
		// If nonce verification didn't fail, run further
		$file = wp_get_attachment_url( $requested_file );

		if( ! $file ) {
			return;
		}

		//clean the fileurl
		$file_url  = stripslashes( trim( $file ) );
		//get filename
		$file_name = basename( $file );

		header("Expires: 0");
		header("Cache-Control: no-cache, no-store, must-revalidate"); 
		header('Cache-Control: pre-check=0, post-check=0, max-age=0', false); 
		header("Pragma: no-cache");	
		header("Content-Disposition:attachment; filename={$file_name}");
		header("Content-Type: application/force-download");

		readfile("{$file_url}");
		exit();

	}

	function b2bking_create_subaccount(){

		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$parent_user_id = get_current_user_id();
		$subaccounts_maximum_limit = 1000;

		// Test subaccounts number
		$current_subaccounts_number = get_user_meta($parent_user_id, 'b2bking_subaccounts_number', true);
		if ($current_subaccounts_number === NULL){
			$current_subaccounts_number = 0;
		}
		
		if (intval($current_subaccounts_number) < $subaccounts_maximum_limit){
			// proceed
			$username = sanitize_text_field($_POST['username']);
			$password = sanitize_text_field($_POST['password']);
			$name = sanitize_text_field($_POST['name']);
			$job_title = sanitize_text_field($_POST['jobTitle']);
			$phone = sanitize_text_field($_POST['phone']);
			$email = sanitize_text_field($_POST['email']);
			$permission_buy = sanitize_text_field($_POST['permissionBuy']);
			$permission_view_orders = sanitize_text_field($_POST['permissionViewOrders']);
			$permission_view_offers = sanitize_text_field($_POST['permissionViewOffers']);
			$permission_view_conversations = sanitize_text_field($_POST['permissionViewConversations']);
			$permission_view_lists = sanitize_text_field($_POST['permissionViewLists']);

			$user_id = wc_create_new_customer($email, $username, $password);

			if ( ! (is_wp_error($user_id))){
				// no errors, proceed
				// set user meta
				update_user_meta($user_id, 'b2bking_account_type', 'subaccount');
				update_user_meta($user_id, 'b2bking_account_parent', $parent_user_id);
				update_user_meta($user_id, 'b2bking_account_name', $name);
				update_user_meta($user_id, 'b2bking_account_phone', $phone);
				update_user_meta($user_id, 'b2bking_account_job_title', $job_title);
				update_user_meta($user_id, 'b2bking_account_permission_buy', $permission_buy); // true or false
				update_user_meta($user_id, 'b2bking_account_permission_view_orders', $permission_view_orders); // true or false
				update_user_meta($user_id, 'b2bking_account_permission_view_offers', $permission_view_orders); // true or false
				update_user_meta($user_id, 'b2bking_account_permission_view_conversations', $permission_view_orders); // true or false
				update_user_meta($user_id, 'b2bking_account_permission_view_lists', $permission_view_lists); // true or false

				// set parent subaccount details meta
				$current_subaccounts_number = $current_subaccounts_number + 1;
				update_user_meta($parent_user_id, 'b2bking_subaccounts_number', $current_subaccounts_number);

				$current_subaccounts_list = get_user_meta($parent_user_id, 'b2bking_subaccounts_list', true);
				$current_subaccounts_list = $current_subaccounts_list.','.$user_id;
				update_user_meta($parent_user_id, 'b2bking_subaccounts_list', $current_subaccounts_list);

				$userobj = new WP_User($user_id);
				$userobj->set_role('customer');

				do_action('b2bking_after_subaccount_created', $user_id);

				echo $user_id;
			} else {
				echo 'error'.$user_id->get_error_message();
			}

		} else {
			echo 'error_maximum_subaccounts';
		}
		
		exit();
	}

	function b2bking_update_subaccount(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$subaccount_id = sanitize_text_field($_POST['subaccountId']);
		$name = sanitize_text_field($_POST['name']);
		$job_title = sanitize_text_field($_POST['jobTitle']);
		$phone = sanitize_text_field($_POST['phone']);
		$permission_buy = sanitize_text_field($_POST['permissionBuy']);
		$permission_view_orders = sanitize_text_field($_POST['permissionViewOrders']);
		$permission_view_offers = sanitize_text_field($_POST['permissionViewOffers']);
		$permission_view_conversations = sanitize_text_field($_POST['permissionViewConversations']);
		$permission_view_lists = sanitize_text_field($_POST['permissionViewLists']);

		// set user meta
		update_user_meta($subaccount_id, 'b2bking_account_name', $name);
		update_user_meta($subaccount_id, 'b2bking_account_phone', $phone);
		update_user_meta($subaccount_id, 'b2bking_account_job_title', $job_title);
		update_user_meta($subaccount_id, 'b2bking_account_permission_buy', $permission_buy); // true or false
		update_user_meta($subaccount_id, 'b2bking_account_permission_view_orders', $permission_view_orders); // true or false
		update_user_meta($subaccount_id, 'b2bking_account_permission_view_offers', $permission_view_offers); // true or false
		update_user_meta($subaccount_id, 'b2bking_account_permission_view_conversations', $permission_view_conversations); // true or false
		update_user_meta($subaccount_id, 'b2bking_account_permission_view_lists', $permission_view_lists); // true or false

		echo $subaccount_id;
		exit();
	}

	function b2bking_search_by_title_only( $search, $wp_query ){
		global $wpdb;
	    if(empty($search)) {
	        return $search; // skip processing - no search term in query
	    }
	    $q = $wp_query->query_vars;
	    $n = !empty($q['exact']) ? '' : '%';
	    $search =
	    $searchand = '';
	    foreach ((array)$q['search_terms'] as $term) {
	        $term = esc_sql($wpdb->esc_like($term));
	        $search .= "{$searchand}($wpdb->posts.post_title LIKE '{$n}{$term}{$n}')";
	        $searchand = ' AND ';
	    }
	    if (!empty($search)) {
	        $search = " AND ({$search}) ";
	        if (!is_user_logged_in())
	            $search .= " AND ($wpdb->posts.post_password = '') ";
	    }
	    return $search;
	}

	function b2bking_accountingsubtotals(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		$pricevalue = sanitize_text_field($_POST['pricesent']);
		echo wc_price(floatval($pricevalue));

		exit();
	}
	
	function b2bking_ajax_search(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		if (isset($_POST['searchby'])){
			$searchby = sanitize_text_field($_POST['searchby']);
		} else {
			$searchby = 'productname';
		}
		$searched_term = sanitize_text_field($_POST['searchValue']);
		if (isset($_POST['searchType'])){
			$search_type = sanitize_text_field($_POST['searchType']);
			if ($search_type === 'purchaseListLoading'){
				$searched_term = substr($searched_term, 0, 13);
			}
		}

		$search_each_variation = get_option('b2bking_search_each_variation_setting',0);
		$search_what = 'product';
		if (intval($search_each_variation) === 1){
			$search_what = array('product','product_variation');
		}

		// if product visibility is set to all,
		if ((intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) === 1)){
			if ($searchby === 'productname'){
				$queryAllparams = array(
				'no_found_rows' => true,
				'post_status' => 'publish',
			    'posts_per_page' => -1,
			    'post_type' => $search_what,
			    'meta_query' => array(
		            'relation' => 'AND',
		            array(
		                'key' => '_price',
		                'value' => '',
		                'compare' => '!=',
		            ),
		            array(
		                'key' => '_price',
		                'compare' => 'EXISTS',
		            )
		        ),
			    'fields' => 'ids',
			    's' => $searched_term
				);

				$queryAll = new WP_Query($queryAllparams);
				$allTheIDs = $queryAll->posts;

			} else if ($searchby === 'sku'){
				// search by SKU 
				$querySKUparams = array(
				'no_found_rows' => true,
				'post_status' => 'publish',
			    'posts_per_page' => -1,
			    'post_type' => $search_what,
			    'meta_query' => array(
		            'relation' => 'AND',
		            array(
		                'key' => '_price',
		                'value' => '',
		                'compare' => '!=',
		            ),
		            array(
		                'key' => '_price',
		                'compare' => 'EXISTS',
		            ),
		            array(
		                'key' => '_sku',
		                'value' => $searched_term,
		                'compare' => 'LIKE',
		            ),
		            array(
		                'key' => '_stock_status',
		                'value' => 'outofstock',
		                'compare' => '!=',
		            ),
		        ),
			    'fields' => 'ids',
				);

				$querySKU = new WP_Query($querySKUparams);
				$allTheIDs = $querySKU->posts;
			}

		} else {

			// Get current user's data: group, id, login, etc
		    $currentuserid = get_current_user_id();
	    	$account_type = get_user_meta($currentuserid,'b2bking_account_type', true);
	    	if ($account_type === 'subaccount'){
	    		// for all intents and purposes set current user as the subaccount parent
	    		$parent_user_id = get_user_meta($currentuserid, 'b2bking_account_parent', true);
	    		$currentuserid = $parent_user_id;
	    	}
	        $currentuser = get_user_by('id', $currentuserid);

			$currentuserlogin = $currentuser -> user_login;
			$currentusergroupidnr = get_user_meta( $currentuserid, 'b2bking_customergroup', true );

			// if user is guest, set to 0
			if ($currentuser === false){
				$currentuserlogin = 0;
				$currentusergroupidnr = 0;
			}
			

			/*
			* 
			*	There are 2 separate queries that need to be made:
			* 	1. Query of all Categories visible to the USER AND all Categories visible to the USER'S GROUP 
			*	2. Query of all Products set to Manual visibility mode, visible to the user or the user's group 
			*
			*/

			// Build Visible Categories for the 1st Query
			$visiblecategories = array();
			$hiddencategories = array();

			// Get all categories
			$terms = get_terms( array( 
			    'taxonomy' => 'product_cat',
			    'hide_empty' => false
			) );
			foreach ($terms as $term){

				/* 
				* If category is visible to GROUP OR category is visible to USER
				* Push category into visible categories array
				*/

				// first check group
				if (intval(get_term_meta( $term-> term_id, 'b2bking_group_'.$currentusergroupidnr, true )) === 1){
					array_push($visiblecategories, $term->term_id);
				// else check user
				} else {
					$userlistcommas = get_term_meta( $term-> term_id, 'b2bking_category_users_textarea', true );
					$userarray = explode(',', $userlistcommas);
					foreach ($userarray as $user){
						if (trim($user) === $currentuserlogin){
							array_push($visiblecategories, $term->term_id);
							break;
						}
					}
					// reached this point, therefore category is hidden
					array_push($hiddencategories, $term->term_id);
				}
			}

			$product_category_visibility_array = array(
			           'taxonomy' => 'product_cat',
			           'field' => 'term_id',
			           'terms' => $visiblecategories, 
			           'operator' => 'IN'
			);

			// if user has enabled "hidden has priority", override setting
			if (intval(get_option( 'b2bking_hidden_has_priority_setting', 0 )) === 1){
				$product_category_visibility_array = array(
				           'taxonomy' => 'product_cat',
				           'field' => 'term_id',
				           'terms' => $hiddencategories, 
				           'operator' => 'NOT IN'
				);
			}

			/* Get all items that do not have manual visibility set up */
			// get all products ids
			$items_not_manual_visibility_array = get_transient('b2bking_not_manual_visibility_array');
			if (!$items_not_manual_visibility_array){
				$all_prods = new WP_Query(array(
			        'posts_per_page' => -1,
			        'post_type' => 'product',
			        'fields' => 'ids'));
				$all_prod_ids = $all_prods->posts;

				// get all products with manual visibility ids
				$all_prods_manual = new WP_Query(array(
			        'posts_per_page' => -1,
			        'post_type' => 'product',
			        'fields' => 'ids',
    		        'meta_query'=> array(
                            'relation' => 'AND',
                            array(
                                'key' => 'b2bking_product_visibility_override',
                                'value' => 'manual',
                            )
                        )));
				$all_prod_manual_ids = $all_prods_manual->posts;
				// get the difference
				$items_not_manual_visibility_array = array_diff($all_prod_ids,$all_prod_manual_ids);
				set_transient('b2bking_not_manual_visibility_array', $items_not_manual_visibility_array);
			}

			// Build first query
		    $queryAparams = array(
		    	'no_found_rows' => true,
		    	'post_status' => 'publish',
		        'posts_per_page' => -1,
		        'post_type' => $search_what,
		        'fields' => 'ids',
		        's' => $searched_term,
		        'tax_query' => array(
		        	$product_category_visibility_array
		        ),
		        'meta_query' => array(
					'relation' => 'AND',
					array(
					    'key' => '_price',
					    'value' => '',
					    'compare' => '!=',
					),
					array(
					    'key' => '_stock_status',
					    'value' => 'outofstock',
					    'compare' => '!=',
					),
					array(
					    'key' => '_price',
					    'compare' => 'EXISTS',
					),
		        ),
		        'post__in' => $items_not_manual_visibility_array,
		    );

		    // Build 2nd query: all manual visibility products with USER OR USER GROUP visibility
		    $queryBparams = array(
		    	'no_found_rows' => true,
		    	'post_status' => 'publish',
		        'posts_per_page' => -1,
		        'post_type' => $search_what,
		        'fields' => 'ids',
		        's' => $searched_term,
		        'meta_query'=> array(
	                    'relation' => 'AND',
	                    array(
	                        'relation' => 'OR',
	                        array(
	                            'key' => 'b2bking_group_'.$currentusergroupidnr,
	                            'value' => '1'
	                        ),
	                        array(
	                            'key' => 'b2bking_user_'.$currentuserlogin,
	                            'value' => '1'
	                        )
	                    ),
	                    array(
	                        'key' => 'b2bking_product_visibility_override',
	                        'value' => 'manual',
	                    ),
                    	array(
                    	    'key' => '_price',
                    	    'value' => '',
                    	    'compare' => '!=',
                    	),
                    	array(
                    	    'key' => '_stock_status',
                    	    'value' => 'outofstock',
                    	    'compare' => '!=',
                    	),
                    	array(
                    	    'key' => '_price',
                    	    'compare' => 'EXISTS',
                    	),
	                ));

			/* Get all items that do not have manual visibility set up */
			// get all products ids
			$items_not_manual_visibility_array = get_transient('b2bking_not_manual_visibility_array');
			if (!$items_not_manual_visibility_array){
				$all_prods = new WP_Query(array(
			        'posts_per_page' => -1,
			        'post_type' => 'product',
			        'fields' => 'ids'));
				$all_prod_ids = $all_prods->posts;

				// get all products with manual visibility ids
				$all_prods_manual = new WP_Query(array(
			        'posts_per_page' => -1,
			        'post_type' => 'product',
			        'fields' => 'ids',
    		        'meta_query'=> array(
                            'relation' => 'AND',
                            array(
                                'key' => 'b2bking_product_visibility_override',
                                'value' => 'manual',
                            )
                        )));
				$all_prod_manual_ids = $all_prods_manual->posts;
				// get the difference
				$items_not_manual_visibility_array = array_diff($all_prod_ids,$all_prod_manual_ids);
				set_transient('b2bking_not_manual_visibility_array', $items_not_manual_visibility_array);
			}
	    	
    		// Build Queries A and B with SKU
			// Build first query
		    $queryASKUparams = array(
		    	'no_found_rows' => true,
		    	'post_status' => 'publish',
		        'posts_per_page' => -1,
		        'post_type' => $search_what,
		        'fields' => 'ids',
		        'tax_query' => array(
		        	$product_category_visibility_array
		        ),
		        'meta_query' => array(
					'relation' => 'AND',
					array(
					    'key' => '_price',
					    'value' => '',
					    'compare' => '!=',
					),
					array(
					    'key' => '_stock_status',
					    'value' => 'outofstock',
					    'compare' => '!=',
					),
					array(
					    'key' => '_price',
					    'compare' => 'EXISTS',
					),
					array(
					    'key' => '_sku',
					    'value' => $searched_term,
					    'compare' => 'LIKE',
					),
		        ),
		        'post__in' => $items_not_manual_visibility_array,
		    );
		    $queryBSKUparams = array(
		    	'no_found_rows' => true,
		    	'post_status' => 'publish',
		        'posts_per_page' => -1,
		        'post_type' => $search_what,
		        'fields' => 'ids',
		        'meta_query'=> array(
	                    'relation' => 'AND',
	                    array(
	                        'relation' => 'OR',
	                        array(
	                            'key' => 'b2bking_group_'.$currentusergroupidnr,
	                            'value' => '1'
	                        ),
	                        array(
	                            'key' => 'b2bking_user_'.$currentuserlogin,
	                            'value' => '1'
	                        )
	                    ),
	                    array(
	                        'key' => 'b2bking_product_visibility_override',
	                        'value' => 'manual',
	                    ),
                    	array(
                    	    'key' => '_price',
                    	    'value' => '',
                    	    'compare' => '!=',
                    	),
                    	array(
                    	    'key' => '_stock_status',
                    	    'value' => 'outofstock',
                    	    'compare' => '!=',
                    	),
                    	array(
                    	    'key' => '_price',
                    	    'compare' => 'EXISTS',
                    	),
                    	array(
                    	    'key' => '_sku',
                    	    'value' => $searched_term,
                    	    'compare' => 'LIKE',
                    	),
	                ));

	   		
	   		if ($searchby === 'productname'){
		   		$queryA = new WP_Query($queryAparams);
			    $queryB = new WP_Query($queryBparams);
	   		} elseif ($searchby === 'sku'){
	   			$queryA = new WP_Query($queryASKUparams);
		    	$queryB = new WP_Query($queryBSKUparams); 
	   		}

			$allTheIDs = array_merge($queryA->posts,$queryB->posts);
		}
	    
	    require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
	    $helper = new B2bking_Helper();

	    $results = array();
	    $i = 10; // show maximum 8 search results
	    foreach ($allTheIDs as $product_id){
	    	if($i > 0){
	    		$product = wc_get_product( $product_id );

	    		if ($product->is_purchasable() && $product->is_in_stock() ){
	    			if ($product->is_type('variable')){
	    				$children_ids = $product->get_children();
	    				foreach ($children_ids as $variation_id){
	    					$productvariation = new WC_Product_Variation($variation_id);
	    					if ($productvariation->is_in_stock()){
		    					if( $productvariation->is_on_sale() ) {
		    						$product_price = $productvariation->get_sale_price();
		    					} else {
		    						$product_price = $productvariation->get_price();
		    					}
		    					$product_price = round(floatval($helper->b2bking_wc_get_price_to_display( $productvariation, array( 'price' => $product_price))),2);
		    					$product_title = $productvariation->get_formatted_name();
		    					$results[$variation_id] = $product_title;
		    					$results[$variation_id.'B2BKINGPRICE'] = $product_price;
		    				}
	    				}

	    			} else {
	    				if( $product->is_on_sale() ) {
	    					$product_price = $product->get_sale_price();
	    				} else {
	    				   $product_price = $product->get_price();	
	    				}
			    		
			    		$product_price = round(floatval($helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $product_price))),2);
			    		$product_title = $product->get_formatted_name();
			    		$results[$product_id] = $product_title;
			    		$results[$product_id.'B2BKINGPRICE'] = $product_price;
		    		}
		    		$i--;
		    	}
	    	}
	    }

	    if (empty($results)){
	    	$results = 1234;
	    	echo $results;
	    } else {
	    	echo json_encode($results);
	    }

		
		exit();
	}

	function b2bking_ajax_get_price(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$product_id = sanitize_text_field($_POST['productid']);
		$product_price = wc_get_product( $product_id ) -> get_price();

		echo intval($product_price);
		exit();
	}

	function b2bking_bulkorder_add_cart(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$productstring = sanitize_text_field($_POST['productstring']);
		$products_array = explode('|', $productstring);
		foreach($products_array as $product){
			$product_id = explode(':', $product)[0];
			$product_qty = explode(':', $product)[1];
			WC()->cart->add_to_cart( $product_id, $product_qty);
		}

		echo 'success';
		exit();
	}

	function b2bking_bulkorder_save_list(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$productstring = sanitize_text_field($_POST['productstring']);
		$title = sanitize_text_field($_POST['title']);

		$purchase_list = array(
		    'post_title' => $title,
		    'post_status' => 'publish',
		    'post_type' => 'b2bking_list',
		    'post_author' => get_current_user_id(),
		);
		$purchase_list_id = wp_insert_post($purchase_list);
		update_post_meta($purchase_list_id, 'b2bking_purchase_list_details', $productstring);

		echo $purchase_list_id;
		exit();
	}

	function b2bking_purchase_list_update(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$productstring = sanitize_text_field($_POST['productstring']);
		$list_id = sanitize_text_field($_POST['listid']);

		update_post_meta($list_id, 'b2bking_purchase_list_details', $productstring);

		echo $list_id;
		exit();
	}

	function b2bking_purchase_list_delete(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$list_id = sanitize_text_field($_POST['listid']);
		wp_delete_post($list_id);

		echo 'success';
		exit();
	}

	function b2bking_save_cart_to_purchase_list(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$productstring = '';
		$items = WC()->cart->get_cart();
		foreach($items as $item => $values) { 
            $product_id = $values['data']->get_id(); 
            $product_qty = $values['quantity'];
            $productstring .= $product_id.':'.$product_qty.'|';
        }

        // if cart not empty, save as list
        if ($productstring !== ''){

			$title = sanitize_text_field($_POST['title']);
			$purchase_list = array(
			    'post_title' => $title,
			    'post_status' => 'publish',
			    'post_type' => 'b2bking_list',
			    'post_author' => get_current_user_id(),
			);
			$purchase_list_id = wp_insert_post($purchase_list);

        	update_post_meta($purchase_list_id, 'b2bking_purchase_list_details', $productstring);
        }
        
		echo 'success';
		exit();
	}

	function b2bking_send_feedback(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$message = sanitize_text_field($_POST['message']);
		$email = sanitize_text_field($_POST['email']);

		wp_mail('contact@webwizards.dev', esc_html__('New feedback message','b2bking'), $message.' '.esc_html__('Message was sent by:','b2bking').$email);

		echo 'success';
		exit();

	}

	function b2bkingupdateuserdata(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$user_id = sanitize_text_field($_POST['userid']);
		$fields_string = sanitize_text_field($_POST['field_strings']);
		$fields_array = explode(',',$fields_string);
		foreach ($fields_array as $field_id){
			if ($field_id !== NULL && !empty($field_id)){

				// first check if field is VAT, then update user meta if field not empty
				$billing_connection = get_post_meta($field_id,'b2bking_custom_field_billing_connection', true);
				if ($billing_connection !== 'billing_vat'){
					// proceed normally,this is not VAT
					update_user_meta($user_id, 'b2bking_custom_field_'.$field_id, sanitize_text_field($_POST['field_'.$field_id]));
				} else {
					// check if VIES is enabled
					$vies_enabled = get_post_meta($field_id, 'b2bking_custom_field_VAT_VIES_validation', true);
					
					if (intval($vies_enabled) === 1){
						// run VIES check on the data
						$vatnumber = sanitize_text_field($_POST['field_'.$field_id]);
						$vatnumber = strtoupper(str_replace(array('.', ' '), '', $vatnumber));

						$client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
						$country_code = substr($vatnumber, 0, 2); // take first 2 chars
						$vat_number = substr($vatnumber, 2); // remove first 2 chars

						$validation = new \stdClass();
						$validation->valid = false;
						
						// check vat
						try {
							$validation = $client->checkVat(array(
							  'countryCode' => $country_code,
							  'vatNumber' => $vat_number
							));

						} catch (Exception $e) {
							$error = $e->getMessage();
							$validation->valid=0;
						}

						if (intval($validation->valid) === 1){
							// update data
							update_user_meta($user_id, 'b2bking_custom_field_'.$field_id, $vatnumber);
							// also set validated vat
							update_user_meta( $user_id, 'b2bking_user_vat_status', 'validated_vat');
						} else {
							echo 'vatfailed';
						}
					}
				}
			}
		}

		echo 'success';
		exit();
	}

	function b2bking_dismiss_activate_woocommerce_admin_notice(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_notice_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		update_user_meta(get_current_user_id(), 'b2bking_dismiss_activate_woocommerce_notice', 1);

		echo 'success';
		exit();
	}

	function b2bking_b2c_special_group_save_settings(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		
		$shipping_methods = array();

		$delivery_zones = WC_Shipping_Zones::get_zones();
        foreach ($delivery_zones as $key => $the_zone) {
            foreach ($the_zone['shipping_methods'] as $value) {
                array_push($shipping_methods, $value);
            }
        }

		foreach ($shipping_methods as $shipping_method){
			$user_setting = sanitize_text_field($_POST['b2bking_b2c_users_shipping_method_'.$shipping_method->id.$shipping_method->instance_id]);
			if( intval($user_setting) === 1){
			    update_option('b2bking_b2c_users_shipping_method_'.$shipping_method->id.$shipping_method->instance_id, 1);
			} else if( intval($user_setting) === 0){
				update_option('b2bking_b2c_users_shipping_method_'.$shipping_method->id.$shipping_method->instance_id, 0);
			}
		}

		$payment_methods = WC()->payment_gateways->payment_gateways();

		foreach ($payment_methods as $payment_method){
			$user_setting = sanitize_text_field($_POST['b2bking_b2c_users_payment_method_'.$payment_method->id]);
			if( intval($user_setting) === 1){
			    update_option('b2bking_b2c_users_payment_method_'.$payment_method->id, 1);
			} else if( intval($user_setting) === 0){
				update_option('b2bking_b2c_users_payment_method_'.$payment_method->id, 0);
			}
		}

		echo 'success';
		exit();
	}

	function b2bking_logged_out_special_group_save_settings(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}
		// get all shipping methods
		$shipping_methods = array();

		$delivery_zones = WC_Shipping_Zones::get_zones();
        foreach ($delivery_zones as $key => $the_zone) {
            foreach ($the_zone['shipping_methods'] as $value) {
                array_push($shipping_methods, $value);
            }
        }
		foreach ($shipping_methods as $shipping_method){
			$user_setting = sanitize_text_field($_POST['b2bking_logged_out_users_shipping_method_'.$shipping_method->id.$shipping_method->instance_id]);
			if( intval($user_setting) === 1){
			    update_option('b2bking_logged_out_users_shipping_method_'.$shipping_method->id.$shipping_method->instance_id, 1);
			} else if( intval($user_setting) === 0){
				update_option('b2bking_logged_out_users_shipping_method_'.$shipping_method->id.$shipping_method->instance_id, 0);
			}
		}

		$payment_methods = WC()->payment_gateways->payment_gateways();

		foreach ($payment_methods as $payment_method){
			$user_setting = sanitize_text_field($_POST['b2bking_logged_out_users_payment_method_'.$payment_method->id]);
			if( intval($user_setting) === 1){
			    update_option('b2bking_logged_out_users_payment_method_'.$payment_method->id, 1);
			} else if( intval($user_setting) === 0){
				update_option('b2bking_logged_out_users_payment_method_'.$payment_method->id, 0);
			}
		}

		echo 'success';
		exit();
	}

	function b2bkingdownloadpurchaselist(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$listid = sanitize_text_field($_REQUEST['list']);

		$list_name = esc_html__('b2bking_purchase_list','b2bking');

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=".$list_name."_".$listid.".csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$output = fopen("php://output", "wb");
		// build header
		$headerrow = array(esc_html__('Name','b2bking'), esc_html__('SKU','b2bking'), esc_html__('Quantity','b2bking'));

		fputcsv($output, $headerrow);


		// parse list and for each line write data
		$list_details = get_post_meta($listid,'b2bking_purchase_list_details', true);
		$list_items = explode('|', $list_details);
		$list_items = array_filter($list_items);

		foreach ($list_items as $list_item){
			
			$item = explode(':', $list_item);
			$product_id = $item[0];
			$product_qty = $item[1];
			$productobj = wc_get_product($product_id);

			$product_title = $productobj -> get_name();
			$product_sku = $productobj -> get_sku();
			if (empty($product_sku)){
				$product_sku = '-';
			}

			$csv_array = array($product_title, $product_sku, $product_qty);

			fputcsv($output, $csv_array); 
		
		}


		fclose($output);
		exit();
	}

	function b2bkingdownloadpricelist(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		// build and download list
		global $wpdb;

		$tableprefix = $wpdb->prefix;
		$table_name = $tableprefix.'posts';

		$queryresult = $wpdb->get_results( 
			"
		    SELECT `id` FROM $table_name WHERE post_status = 'publish' AND (post_type = 'product' OR post_type = 'product_variation')
			"
		, ARRAY_N);

		// get all groups
		$groups = get_posts( array( 'post_type' => 'b2bking_group','post_status'=>'publish','numberposts' => -1) );

		header("Content-type: text/csv");
		header("Content-Disposition: attachment; filename=b2bking_price_list.csv");
		header("Pragma: no-cache");
		header("Expires: 0");

		$output = fopen("php://output", "wb");
		// build header
		$headerrow = array("Product / Variation ID");
		foreach ($groups as $group){
			array_push($headerrow, $group->ID.': '.$group->post_title.' '.esc_html__('Regular Price'));
			array_push($headerrow, $group->ID.': '.$group->post_title.' '.esc_html__('Sale Price'));
		}
		fputcsv($output, $headerrow);


		// build rows
		foreach ($queryresult as $key => $value){
			$id = intval($value[0]);
			$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
			if ($id !== 0 && $id !== 3225464 && $id !== $offer_id){  // deprecated offer nr
				$temparray = array();
				array_push($temparray,$value[0].': '.get_the_title($value[0]));
				foreach ($groups as $group){
					$group_price = get_post_meta($value[0],'b2bking_regular_product_price_group_'.$group->ID, true);
					array_push($temparray, $group_price);
					$group_price = get_post_meta($value[0],'b2bking_sale_product_price_group_'.$group->ID, true);
					array_push($temparray, $group_price);
				}
				fputcsv($output, $temparray); 
			}
		}

		fclose($output);
		exit();
		
	}

	function b2bkingdownloadtroubleshooting(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		global $wpdb;

		ob_start();

		echo 'PLUGIN OPTIONS<br />';
		// plugin options
		$plugin_options = $wpdb->get_results( "SELECT `option_name`, `option_value` FROM $wpdb->options WHERE option_name LIKE '%b2bking%'" );
		foreach( $plugin_options as $option ) {
			var_dump($option);
		}
		echo 'PLUGIN RULES<br />';
		$rules = get_posts( array( 'post_type' => 'b2bking_rule','post_status'=>'publish','numberposts' => -1) );
		foreach ($rules as $rule){
			var_dump($rule);
			var_dump(get_post_meta($rule->ID));
		}
		echo 'PLUGIN RULES END<br />';
		echo 'PLUGIN Reg ROLES<br />';
		$rules = get_posts( array( 'post_type' => 'b2bking_custom_role','post_status'=>'publish','numberposts' => -1) );
		foreach ($rules as $rule){
			var_dump($rule);
			var_dump(get_post_meta($rule->ID));
		}
		echo 'PLUGIN Reg ROLES END<br />';
		echo 'PLUGIN Reg FIELDS<br />';
		$rules = get_posts( array( 'post_type' => 'b2bking_custom_field','post_status'=>'publish','numberposts' => -1) );
		foreach ($rules as $rule){
			var_dump($rule);
			var_dump(get_post_meta($rule->ID));
		}
		echo 'PLUGIN Reg FIELDS END<br />';
		echo 'PLUGIN GROUPS<br />';
		$rules = get_posts( array( 'post_type' => 'b2bking_group','post_status'=>'publish','numberposts' => -1) );
		foreach ($rules as $rule){
			var_dump($rule);
			var_dump(get_post_meta($rule->ID));
		}
		echo 'PLUGIN GROUPS END<br />';
		echo 'PLUGIN OFFERS<br />';
		$rules = get_posts( array( 'post_type' => 'b2bking_offer','post_status'=>'publish','numberposts' => -1) );
		foreach ($rules as $rule){
			var_dump($rule);
			var_dump(get_post_meta($rule->ID));
		}
		echo 'PLUGIN OFFERS END<br />';



		$contentoptions = ob_get_contents();
		ob_end_clean();

		header('Content-Disposition: attachment; filename="b2bking_troubleshooter_'.substr(sha1(time()), 0, 8).'.xml"');
		die($contentoptions); 

		

		exit();
	}

	function b2bkingbulksetusers(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$group = sanitize_text_field($_POST['chosen_group']);

		// get users
		$users = get_users(array(
			'fields'=> 'ids',
		));
		
		if (!empty($users)) {
		    // loop trough each author
		    foreach ($users as $user){
		       // move all users to the group
		       if ($group === 'b2cuser'){
		       		update_user_meta($user, 'b2bking_customergroup', 'no');
		       		update_user_meta($user, 'b2bking_b2buser', 'no');
		       } else {
		       		update_user_meta($user, 'b2bking_customergroup', $group);
		       		update_user_meta($user, 'b2bking_b2buser', 'yes');
		       }
		    }
		}


		echo 'success';
		exit();

	}

	function b2bkingbulksetcategory(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$option = sanitize_text_field($_POST['chosen_option']);

		// get categories
		$terms = get_terms(array(
			'fields'=> 'ids',
			'post_status' => 'publish',
			'numberposts' => -1,
		));

		$groups = get_posts([
		  'post_type' => 'b2bking_group',
		  'post_status' => 'publish',
		  'numberposts' => -1,
		  'fields' =>'ids',
		]);
		
		if (!empty($terms)) {
		    // loop trough each term
		    foreach ($terms as $term){
		       // move all users to the group
		       if ($option === 'visibleallgroups'){
					update_term_meta($term, 'b2bking_group_b2c', 1);
					update_term_meta($term, 'b2bking_group_0', 1);
					foreach ($groups as $group){
						update_term_meta($term, 'b2bking_group_'.$group, 1);
					}
		       } else if ($option === 'notvisibleallgroups') {
		       		update_term_meta($term, 'b2bking_group_b2c', 0);
		       		update_term_meta($term, 'b2bking_group_0', 0);
		       		foreach ($groups as $group){
		       			update_term_meta($term, 'b2bking_group_'.$group, 0);
		       		}
		       }
		    }
		}


		echo 'success';
		exit();
	}

	function b2bkingbulksetsubaccounts(){
		// Check security nonce. 
		if ( ! check_ajax_referer( 'b2bking_security_nonce', 'security' ) ) {
		  	wp_send_json_error( 'Invalid security token sent.' );
		    wp_die();
		}

		$option_first = trim(sanitize_text_field($_POST['option_first']));
		$option_second = trim(sanitize_text_field($_POST['option_second']));

		$subaccount_ids = explode(',',$option_first);
		$parent_id = trim($option_second);

		foreach ($subaccount_ids as $subaccount_id){
			$subaccount_id_trimmed = trim($subaccount_id);
			update_user_meta($subaccount_id_trimmed,'b2bking_account_type', 'subaccount');
			update_user_meta($subaccount_id_trimmed,'b2bking_account_parent', $parent_id);

			$current_subaccounts_list = get_user_meta($parent_id,'b2bking_subaccounts_list', true);
			update_user_meta($parent_id,'b2bking_subaccounts_list', $current_subaccounts_list.','.$subaccount_id_trimmed);
		}

		echo 'success';
		exit();

	}

	//copied from Public
	// Hide prices to guest users
	function b2bking_hide_prices_guest_users( $price, $product ) {
		// if user is guest, OR multisite B2B/B2C separation is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){
			return get_option('b2bking_hide_prices_guests_text_setting', esc_html__('Login to view prices','b2bking'));
		} else {
			return $price;
		}
	}

	function b2bking_disable_purchasable_guest_users($purchasable){
		// if user is guest, or multisite b2b/b2b separation is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){
			return false;
		} else {
			return $purchasable;
		}
	}

	function tofloat($num) {
	    $dotPos = strrpos($num, '.');
	    $commaPos = strrpos($num, ',');
	    $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos :
	        ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);
	  
	    if (!$sep) {
	        return floatval(preg_replace("/[^0-9]/", "", $num));
	    }

	    return floatval(
	        preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' .
	        preg_replace("/[^0-9]/", "", substr($num, $sep+1, strlen($num)))
	    );
	}

	// Tiered pricing for AJAX
	function b2bking_tiered_pricing_fixed_price($price, $product){
		
			if (is_user_logged_in()){
				$user_id = get_current_user_id();
		    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
		    	if ($account_type === 'subaccount'){
		    		// for all intents and purposes set current user as the subaccount parent
		    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
		    		$user_id = $parent_user_id;
		    	}

		    	// check transient to see if the current price has been set already via another function
		    	if (get_transient('b2bking_user_'.$user_id.'_product_'.$product->get_id().'_custom_set_price') === $price){
		    		return $price;
		    	}

		    	$is_b2b_user = get_user_meta( $user_id, 'b2bking_b2buser', true );
				$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
				if ($is_b2b_user === 'yes'){
					// Search price tiers
					$price_tiers = get_post_meta($product->get_id(), 'b2bking_product_pricetiers_group_'.$currentusergroupidnr, true );

					// if no tiers, get regular
					if (empty($price_tiers)){
						$price_tiers = get_post_meta($product->get_id(), 'b2bking_product_pricetiers_group_b2c', true );
					}
					
					if (!empty($price_tiers)){
						// if there are price tiers, check product quantity in cart and set price accordingly

						// find product quantity in cart
						$product_id = $product->get_id();
						$quantity = 0;
					    foreach( WC()->cart->get_cart() as $cart_item ){
					        if ( $product_id === $cart_item['product_id'] || $product_id === $cart_item['variation_id']){
					            $quantity = $cart_item['quantity'];
					            break;
					        }
					    }

					    if ($quantity !== 0){
							$price_tiers = explode(';', $price_tiers);
							$quantities_array = array();
							$prices_array = array();
							// first eliminate all quantities larger than the quantity in cart
							foreach($price_tiers as $tier){
								$tier_values = explode(':', $tier);
								if ($tier_values[0] <= $quantity && !empty($tier_values[0])){
									array_push($quantities_array, $tier_values[0]);
									$prices_array[$tier_values[0]] = $this->tofloat($tier_values[1]);
								}
							}

							// if any number remains
							if(count($quantities_array) !== 0){
								// get the largest number
								$largest = max($quantities_array);
								// clear cache mostly for variable products
								WC_Cache_Helper::get_transient_version( 'product', true );
								
								// if regular table exist, but group table does not exist
								// apply tiered pricing only if the user's group price is not already smaller than tier price
								if (empty(get_post_meta($product->get_id(), 'b2bking_product_pricetiers_group_'.$currentusergroupidnr, true ))){
									if (floatval($price) > floatval($prices_array[$largest])){
										return $prices_array[$largest];
									} else {
										// return regular price
										return $price;
									}
								} else {
									return $prices_array[$largest];
								}

							} else {
								return $price;
							}

						} else {
							return $price;
						}

					} else {
						return $price;
					}
				} else {
					return $price;
				}
			} else {
				return $price;
			}
	}

	// Individual product pricing functions for AJAX below
	function b2bking_individual_pricing_fixed_price($price, $product){
		
			if (is_user_logged_in()){
				$user_id = get_current_user_id();
		    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
		    	if ($account_type === 'subaccount'){
		    		// for all intents and purposes set current user as the subaccount parent
		    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
		    		$user_id = $parent_user_id;
		    	}

		    	// check transient to see if the current price has been set already via another function
		    	if (get_transient('b2bking_user_'.$user_id.'_product_'.$product->get_id().'_custom_set_price') === $price){
		    		return $price;
		    	}

		    	$is_b2b_user = get_user_meta( $user_id, 'b2bking_b2buser', true );
				$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
				if ($is_b2b_user === 'yes'){
					// Search if there is a specific price set for the user's group
					$b2b_price = $this->tofloat(get_post_meta($product->get_id(), 'b2bking_regular_product_price_group_'.$currentusergroupidnr, true ));
					if (!empty($b2b_price)){
						// ADD WOOCS COMPATIBILITY
			    		if (class_exists('WOOCS')) {
							global $WOOCS;
							$currrent = $WOOCS->current_currency;
							if ($currrent != $WOOCS->default_currency) {
								$currencies = $WOOCS->get_currencies();
								$rate = $currencies[$currrent]['rate'];
								$b2b_price = $b2b_price / ($rate);
							}
						}

						return $b2b_price;
					} else {
						return $price;
					}
				} else {
					return $price;
				}
			} else {
				return $price;
			}
	}

	function b2bking_individual_pricing_discount_sale_price( $sale_price, $product ){

		if (is_user_logged_in()){
			$user_id = get_current_user_id();
	    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
	    	if ($account_type === 'subaccount'){
	    		// for all intents and purposes set current user as the subaccount parent
	    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
	    		$user_id = $parent_user_id;
	    	}

	    	$is_b2b_user = get_user_meta( $user_id, 'b2bking_b2buser', true );
			$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
			if ($is_b2b_user === 'yes'){
				// Search if there is a specific price set for the user's group
				$b2b_price = $this->tofloat(get_post_meta($product->get_id(), 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
				if (!empty($b2b_price)){
					// ADD WOOCS COMPATIBILITY
		    		if (class_exists('WOOCS')) {
						global $WOOCS;
						$currrent = $WOOCS->current_currency;
						if ($currrent != $WOOCS->default_currency) {
							$currencies = $WOOCS->get_currencies();
							$rate = $currencies[$currrent]['rate'];
							$b2b_price = $b2b_price / ($rate);
						}
					}

					return $b2b_price;
				} else {
					// we have reached here = sale price is empty
					// if there is a regular price, but the b2c sale price is smaller, return false instead of b2c sale price
					$b2b_regular_price = $this->tofloat(get_post_meta($product->get_id(), 'b2bking_regular_product_price_group_'.$currentusergroupidnr, true ));

					if (floatval($sale_price) < floatval($b2b_regular_price)){
						return false;
					} else {
						return $sale_price;
					}
				}
			} else {
				return $sale_price;
			}
		} else {
			return $sale_price;
		}
	}

	function b2bking_individual_pricing_discount_display_dynamic_price( $price_html, $product ) {
		if( $product->is_type('variable') && !class_exists('WOOCS')) { // add WOOCS compatibility
			return $price_html;
		}


		if (is_user_logged_in()){
			$user_id = get_current_user_id();
	    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
	    	if ($account_type === 'subaccount'){
	    		// for all intents and purposes set current user as the subaccount parent
	    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
	    		$user_id = $parent_user_id;
	    	}

	    	$is_b2b_user = get_user_meta( $user_id, 'b2bking_b2buser', true );
			$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
			if ($is_b2b_user === 'yes'){
				// Search if there is a specific price set for the user's group
				$b2b_price = $this->tofloat(get_post_meta($product->get_id(), 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
				if (!empty($b2b_price)){

					if( $product->is_type('variable') && class_exists('WOOCS')) { // add WOOCS compatibility

						global $WOOCS;
						$currrent = $WOOCS->current_currency;
						if ($currrent != $WOOCS->default_currency) {
							$currencies = $WOOCS->get_currencies();
							$rate = $currencies[$currrent]['rate'];

							// apply WOOCS rate to price_html
							$min_price = $product->get_variation_price( 'min' ) / ($rate);
							$max_price = $product->get_variation_price( 'max' ) / ($rate);
							$price_html = wc_format_price_range( $min_price, $max_price );
						}

					} else { 

		    			$price_html = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price() ) ), wc_get_price_to_display(  $product, array( 'price' => $product->get_sale_price() ) ) ) . $product->get_price_suffix();
					}
		    	}
		    }
		}

	    return $price_html;
	}

	function b2bking_individual_pricing_discount_display_dynamic_price_in_cart($cart){
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ){
		    return;
		}

		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ){
		    return;
		}

		// Get current user
    	$user_id = get_current_user_id();
    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
    	if ($account_type === 'subaccount'){
    		// for all intents and purposes set current user as the subaccount parent
    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
    		$user_id = $parent_user_id;
    	}

    	$is_b2b_user = get_user_meta( $user_id, 'b2bking_b2buser', true );
		$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
		if ($is_b2b_user === 'yes'){
			// Iterate through each cart item
			foreach( $cart->get_cart() as $cart_item ) {
				// Search if there is a specific price set for the user's group
				if (isset($cart_item['variation_id']) && intval($cart_item['variation_id']) !== 0){
					$b2b_price = $this->tofloat(get_post_meta($cart_item['variation_id'], 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
					$product_id_set = $cart_item['variation_id'];
				} else {
					$b2b_price = $this->tofloat(get_post_meta($cart_item['product_id'], 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
					$product_id_set = $cart_item['product_id'];
				}
				
				if (!empty($b2b_price)){
					$cart_item['data']->set_price( $b2b_price );
					set_transient('b2bking_user_'.$user_id.'_product_'.$product_id_set.'_custom_set_price', $b2b_price);
		    	}
		    }
	    }

	}

	function b2bking_individual_pricing_discount_display_dynamic_price_in_cart_item( $price, $cart_item, $cart_item_key){

		// Get current user
    	$user_id = get_current_user_id();
    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
    	if ($account_type === 'subaccount'){
    		// for all intents and purposes set current user as the subaccount parent
    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
    		$user_id = $parent_user_id;
    	}

    	$is_b2b_user = get_user_meta( $user_id, 'b2bking_b2buser', true );
		$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
		if ($is_b2b_user === 'yes'){
			if (isset($cart_item['variation_id']) && intval($cart_item['variation_id']) !== 0){
				$b2b_price = $this->tofloat(get_post_meta($cart_item['variation_id'], 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
				$product_id_set = $cart_item['variation_id'];
			} else {
				$b2b_price = $this->tofloat(get_post_meta($cart_item['product_id'], 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
				$product_id_set = $cart_item['product_id'];
			}

			if (!empty($b2b_price)){

				require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
				$helper = new B2bking_Helper();
				
				$discount_price = $helper->b2bking_wc_get_price_to_display( wc_get_product($product_id_set), array( 'price' => $cart_item['data']->get_sale_price() ) ); // get sale price
				
				if ($discount_price !== NULL && $discount_price !== ''){
					$price = wc_price($discount_price, 4); 
				}
			} 
		}
		return $price;
	}

	// Visibility rules, copied from public
	function b2bking_product_categories_visibility_rules( $q ){

		if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){

			if ( get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){

				$allTheIDs = get_transient('b2bking_user_'.get_current_user_id().'_ajax_visibility');

				if ($allTheIDs){
				    if(!empty($allTheIDs)){
				    	$q->set('post__in',$allTheIDs);
					}
				}
			}
		}
	}

	function get_visibility_set_transient(){
		if (!get_transient('b2bking_user_'.get_current_user_id().'_ajax_visibility')){

			if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){

				if ( get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){

					$user_is_b2b = get_user_meta( get_current_user_id(), 'b2bking_b2buser', true );

					// if user logged in and is b2b
					if (is_user_logged_in() && ($user_is_b2b === 'yes')){
						// Get current user's data: group, id, login, etc
					    $currentuserid = get_current_user_id();
				    	$account_type = get_user_meta($currentuserid,'b2bking_account_type', true);
				    	if ($account_type === 'subaccount'){
				    		// for all intents and purposes set current user as the subaccount parent
				    		$parent_user_id = get_user_meta($currentuserid, 'b2bking_account_parent', true);
				    		$currentuserid = $parent_user_id;
				    	}
				        $currentuser = get_user_by('id', $currentuserid);
						$currentuserlogin = $currentuser -> user_login;
						$currentusergroupidnr = get_user_meta( $currentuserid, 'b2bking_customergroup', true );
					// if user is b2c
					} else if (is_user_logged_in() && ($user_is_b2b !== 'yes')){
						$currentuserlogin = 'b2c';
						$currentusergroupidnr = 'b2c';
					} else {
						$currentuserlogin = 0;
						$currentusergroupidnr = 0;
					}
					/*
					* 
					*	There are 2 separate queries that need to be made:
					* 	1. Query of all Categories visible to the USER AND all Categories visible to the USER'S GROUP 
					*	2. Query of all Products set to Manual visibility mode, visible to the user or the user's group 
					*
					*/

					// Build Visible Categories for the 1st Query
					$visiblecategories = array();
					$hiddencategories = array();

					$terms = get_terms( array( 
					    'taxonomy' => 'product_cat',
					    'fields' => 'ids',
					    'hide_empty' => false
					) );

					foreach ($terms as $term){

						/* 
						* If category is visible to GROUP OR category is visible to USER
						* Push category into visible categories array
						*/

						// first check group
						$group_meta = get_term_meta( $term, 'b2bking_group_'.$currentusergroupidnr, true );
						if (intval($group_meta) === 1){
							array_push($visiblecategories, $term);
						// else check user
						} else {
							$userlistcommas = get_term_meta( $term, 'b2bking_category_users_textarea', true );
							$userarray = explode(',', $userlistcommas);
							foreach ($userarray as $user){
								if (trim($user) === $currentuserlogin){
									array_push($visiblecategories, $term);
									break;
								}
							}
							// has reached this point, therefore category is not visible
							array_push($hiddencategories, $term);
						}
					}

					$product_category_visibility_array = array(
					           'taxonomy' => 'product_cat',
					           'field' => 'term_id',
					           'terms' => $visiblecategories, 
					           'operator' => 'IN'
					);

					// if user has enabled "hidden has priority", override setting
					if (intval(get_option( 'b2bking_hidden_has_priority_setting', 0 )) === 1){
						$product_category_visibility_array = array(
						           'taxonomy' => 'product_cat',
						           'field' => 'term_id',
						           'terms' => $hiddencategories, 
						           'operator' => 'NOT IN'
						);
					}

					/* Get all items that do not have manual visibility set up */
					// get all products ids
					$items_not_manual_visibility_array = get_transient('b2bking_not_manual_visibility_array');
					if (!$items_not_manual_visibility_array){
						$all_prods = new WP_Query(array(
					        'posts_per_page' => -1,
					        'post_type' => 'product',
					        'fields' => 'ids'));
						$all_prod_ids = $all_prods->posts;

						// get all products with manual visibility ids
						$all_prods_manual = new WP_Query(array(
					        'posts_per_page' => -1,
					        'post_type' => 'product',
					        'fields' => 'ids',
		    		        'meta_query'=> array(
		                            'relation' => 'AND',
		                            array(
		                                'key' => 'b2bking_product_visibility_override',
		                                'value' => 'manual',
		                            )
		                        )));
						$all_prod_manual_ids = $all_prods_manual->posts;
						// get the difference
						$items_not_manual_visibility_array = array_diff($all_prod_ids,$all_prod_manual_ids);
						set_transient('b2bking_not_manual_visibility_array', $items_not_manual_visibility_array);
					}

					// Build first query
				    $queryAparams = array(
				        'posts_per_page' => -1,
				        'post_type' => 'product',
				        'fields' => 'ids',
				        'tax_query' => array(
				        	$product_category_visibility_array
				        ),
					    'post__in' => $items_not_manual_visibility_array,
					);

				    // Build 2nd query: all manual visibility products with USER OR USER GROUP visibility
				    $queryBparams = array(
				        'posts_per_page' => -1,
				        'post_type' => 'product',
				        'fields' => 'ids',
				        'meta_query'=> array(
		                        'relation' => 'AND',
		                        array(
		                            'relation' => 'OR',
		                            array(
		                                'key' => 'b2bking_group_'.$currentusergroupidnr,
		                                'value' => '1'
		                            ),
		                            array(
		                                'key' => 'b2bking_user_'.$currentuserlogin,
		                                'value' => '1'
		                            )
		                        ),
		                        array(
		                            'key' => 'b2bking_product_visibility_override',
		                            'value' => 'manual',
		                        )
		                    ));

				    $queryA = new WP_Query($queryAparams);
				    $queryB = new WP_Query($queryBparams);

				    // Merge the 2 queries in an IDs array
				    $allTheIDs = array_merge($queryA->posts,$queryB->posts);
				    set_transient('b2bking_user_'.get_current_user_id().'_ajax_visibility', $allTheIDs);
				}
			}
		}
	}

}

