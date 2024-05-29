<?php

class B2bking_Public{

	function __construct() {

		// Include dynamic rules code
		require_once ( B2BKING_DIR . 'public/class-b2bking-dynamic-rules.php' );

		add_action('plugins_loaded', function(){

			// Only load if WooCommerce is activated
			if ( class_exists( 'woocommerce' ) ) {

				// Load colors
				add_action( 'wp_head', array( $this, 'b2bking_custom_color' ) );

				$user_data_current_user_id = get_current_user_id();

				$account_type = get_user_meta($user_data_current_user_id,'b2bking_account_type', true);
				if ($account_type === 'subaccount'){
					// for all intents and purposes set current user as the subaccount parent
					$parent_user_id = get_user_meta($user_data_current_user_id, 'b2bking_account_parent', true);
					$user_data_current_user_id = $parent_user_id;

					// Mention in order notes that order is placed by subaccount and point to main accounts
					add_action( 'woocommerce_thankyou', array( $this, 'b2bking_subaccount_order_note') );
				}

				$user_data_current_user_b2b = get_user_meta($user_data_current_user_id, 'b2bking_b2buser', true);

				$user_data_current_user_group = get_user_meta($user_data_current_user_id, 'b2bking_customergroup', true);

				// Check that plugin is enabled
				if ( get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){

					// register post types in frontend, for API access
					require_once ( B2BKING_DIR . 'admin/class-b2bking-admin.php' );
					add_action( 'init', array('B2bking_Admin', 'b2bking_register_post_type_customer_groups'), 0 );
					add_action( 'init', array('B2bking_Admin', 'b2bking_register_post_type_conversation'), 0 );
					add_action( 'init', array('B2bking_Admin', 'b2bking_register_post_type_offer'), 0 );
					add_action( 'init', array('B2bking_Admin', 'b2bking_register_post_type_dynamic_rules'), 0 );
					add_action( 'init', array('B2bking_Admin', 'b2bking_register_post_type_custom_role'), 0 );
					add_action( 'init', array('B2bking_Admin', 'b2bking_register_post_type_custom_field'), 0 );

					// set hidden categories transient
					add_action('init', function(){
						$this->b2bking_init_set_excluded_categories();
					});

					if (intval(get_option('b2bking_disable_registration_setting', 0)) === 0){
						// Custom user registration fields
						add_action( 'woocommerce_register_form', array($this,'b2bking_custom_registration_fields') );

						// only show registration at checkout if user is not already logged in
						if (!is_user_logged_in()){
							if ( intval(get_option('b2bking_registration_at_checkout_setting', 0)) === 1 ){
								add_action( 'woocommerce_after_checkout_registration_form', array($this,'b2bking_custom_registration_fields_checkout') );
							}
						}

						// Check registration form for errors
						add_filter( 'woocommerce_process_registration_errors', array($this,'b2bking_custom_registration_fields_check_errors'), 10, 3 );
						// Save custom registration data
						// use user_register hook as well, seems to fix issues in certain installations
						add_action('woocommerce_created_customer', array($this,'b2bking_save_custom_registration_fields') );
						add_action('user_register', array($this,'b2bking_save_custom_registration_fields') );
						// Add B2B registration shortcodes
						add_action( 'init', array($this, 'b2bking_b2b_registration_shortcode'));
						add_action( 'init', array($this, 'b2bking_b2b_registration_only_shortcode'));
						add_action( 'init', array($this, 'b2bking_b2b_registration_separate_shortcode'));

						add_filter( 'option_woocommerce_myaccount_page_id', array($this,'b2bking_separate_page_registration_b2b'), 10, 1 );


						// Add b2bking content shortcode
						add_action( 'init', array($this, 'b2bking_content_shortcode'));
						// If user approval is manual, stop automatic login on registration
						add_action('woocommerce_registration_redirect', array($this,'b2bking_check_user_approval_on_registration'), 2);
						// Allow file upload in registration form for WooCommerce
						add_action( 'woocommerce_register_form_tag', array($this,'b2bking_custom_registration_fields_allow_file_upload') );
						// Check for approval meta on login
						add_filter('woocommerce_process_login_errors', array($this,'b2bking_check_user_approval_on_login'), 10, 3);
						if ( intval(get_option('b2bking_registration_at_checkout_setting', 0)) === 1 ){
							add_action( 'woocommerce_thankyou', array($this,'b2bking_check_user_approval_on_registration_checkout'));
						}
						// Modify new account email to include notice of manual account approval, if needed
						add_action( 'woocommerce_email_footer', array($this,'b2bking_modify_new_account_email'), 10, 1 );
						// add custom fields to order meta
						add_action( 'woocommerce_checkout_update_order_meta', array($this,'b2bking_save_billing_details') );

						/* Coupons */
						// check coupon is valid based on user role
						add_filter( 'woocommerce_coupon_is_valid', array($this, 'b2bking_filter_woocommerce_coupon_is_valid'), 10, 3 );
						
					}
					// Hide offer post from normal product query (hidden already due to category visibility, but just to be safe)
					add_filter('parse_query', array($this, 'b2bking_hide_offer_post'));

					// If Multisite option is enabled and user is not B2B, but visiting the B2B website, do not give access to my account page. Log User out directly
					if (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta($user_data_current_user_id, 'b2bking_b2buser', true) !== 'yes'){
						add_action( 'template_redirect', array($this, 'b2bking_multisite_logout_user_myaccount'), 20 );
					}

					// Add Request a Quote button
					add_action( 'woocommerce_cart_actions', array($this, 'b2bking_add_request_quote_button') );
					/* Guest access restriction settings: */
					// Hide prices
					if (!is_user_logged_in()){
						if (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'hide_prices'){	
							add_filter( 'woocommerce_get_price_html', array($this, 'b2bking_hide_prices_guest_users'), 9999, 2 );
							add_filter( 'woocommerce_variation_get_price_html', array($this, 'b2bking_hide_prices_guest_users'), 9999, 2 );
							// Hide add to cart button as well / purchasable capabilities
							add_filter( 'woocommerce_is_purchasable', array($this, 'b2bking_disable_purchasable_guest_users'));
							add_filter( 'woocommerce_variation_is_purchasable', array($this, 'b2bking_disable_purchasable_guest_users'));
							// Code that removes the button completely for variations
							remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
							// Hide prices from google search
							add_filter( 'woocommerce_structured_data_product_offer', '__return_empty_array' );

						}
						// Hide website
						if (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'hide_website'){

							if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){
								if (intval(get_option( 'b2bking_hidden_has_priority_setting', 0 )) === 0){
									// Hide Categories
									add_filter( 'get_terms_args', array($this,'b2bking_categories_restrict'), 10, 2 );
								}
							}
							// Hide Products
							add_action( 'woocommerce_product_query', array($this, 'b2bking_hide_products') );
							add_action( 'woocommerce_shortcode_products_query', array($this, 'b2bking_hide_products_shortcode'), 99999, 1 );
							add_filter( 'woocommerce_products_widget_query_args', array($this, 'b2bking_hide_products_shortcode'), 99999, 1);
							add_filter( 'woocommerce_recently_viewed_products_widget_query_args', array($this, 'b2bking_hide_products_shortcode'), 99999, 1);
							add_filter( 'woocommerce_top_rated_products_widget_args', array($this, 'b2bking_hide_products_shortcode'), 99999, 1);


							// Replace "No products found" with "Please login to see B2B Portal" and show Login 
							add_action( 'woocommerce_no_products_found', array($this, 'b2bking_show_login'), 9 );
							add_action( 'woocommerce_shortcode_products_loop_no_results', array($this, 'b2bking_show_login'), 9 );
							// If go directly to product page, redirect to my account
							add_action( 'template_redirect', array($this, 'b2bking_product_redirection_to_account'), 100 );
						}
						// Hide website completely ( force login )
						if (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'hide_website_completely'){
							add_action( 'wp', array($this, 'b2bking_member_only_site') );
						}
					}
					// Replace with Request a Quote
					if ($this->dynamic_replace_prices_with_quotes() === 'yes' || (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'replace_prices_quote') && (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta($user_data_current_user_id, 'b2bking_b2buser', true) !== 'yes')) ){
						// Hide prices
						add_filter( 'woocommerce_get_price_html', array($this, 'b2bking_hide_prices_request_quote'), 9999, 2 );
						add_filter( 'woocommerce_variation_get_price_html', array($this, 'b2bking_hide_prices_request_quote'), 9999, 2 );
						// Replace "Add to cart" with "Request a quote"
						add_filter('woocommerce_product_single_add_to_cart_text', array($this,'b2bking_replace_add_to_cart_text'));
						add_filter('woocommerce_product_add_to_cart_text', array($this,'b2bking_replace_add_to_cart_text'));

						// Hide prices on cart page
						add_filter( 'woocommerce_cart_item_price', array($this, 'b2bking_hide_prices_cart'), 10, 3 );
						add_filter( 'woocommerce_cart_item_subtotal', array($this, 'b2bking_hide_prices_cart'), 10, 3 );
						add_filter( 'woocommerce_cart_subtotal', array($this, 'b2bking_hide_prices_cart'), 10, 3 );
						add_filter( 'woocommerce_cart_total', array($this, 'b2bking_hide_prices_cart'), 10, 3 );

						// If go to checkout page, redirect to cart
						add_action( 'template_redirect', array($this, 'b2bking_checkout_redirect_to_cart'), 100 );
						// Hide proceed to checkout button
						remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 ); 
						// Hide cart totals entirely
						remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cart_totals', 10 );
						// Hide "on sale" flash badge
						add_filter( 'woocommerce_sale_flash', '__return_false' );
						// Hide coupon
						add_filter( 'woocommerce_coupons_enabled', '__return_false' );

						// If user is logged in, disable offers, bulk order form, purchase lists as they no longer apply
						if (is_user_logged_in()){
							// Get current user
							$user_id = $user_data_current_user_id;

					    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
					    	if ($account_type === 'subaccount'){
					    		// for all intents and purposes set current user as the subaccount parent
					    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
					    		$user_id = $parent_user_id;
					    	}
					    	set_transient('b2bking_replace_prices_quote_user_'.$user_id, 'yes');

						}

					}
					

					/* Groups */
					// Set up product/category user/user group visibility rules
					if (intval(get_option('b2bking_disable_visibility_setting', 0)) === 0){
						if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){
							// Hide Categories
							// if caching is enabled
							if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){
								if (intval(get_option( 'b2bking_hidden_has_priority_setting', 0 )) === 0){
									add_filter( 'get_terms_args', array($this,'b2bking_categories_restrict'), 10, 2 );
								}
							}
							// Hide products
							add_action( 'woocommerce_product_query', array($this, 'b2bking_product_categories_visibility_rules'), 9999, 1 );
							add_action( 'woocommerce_shortcode_products_query', array($this, 'b2bking_product_categories_visibility_rules_shortcode'), 9999, 1 );
							add_filter( 'woocommerce_products_widget_query_args', array($this, 'b2bking_product_categories_visibility_rules_shortcode'), 99999, 1);
							add_filter( 'woocommerce_top_rated_products_widget_args', array($this, 'b2bking_product_categories_visibility_rules_shortcode'), 99999, 1);
							add_filter( 'woocommerce_recently_viewed_products_widget_query_args', array($this, 'b2bking_product_categories_visibility_rules_shortcode'), 99999, 1);
							// Change category count
							add_filter( 'get_terms', array($this, 'b2bking_category_count_filter'), 9999, 2 );
							
							// If user/group accesses invisible product, redirect to my account
							add_action( 'template_redirect', array($this, 'b2bking_invisible_product_redirection_to_account'), 100 );
						}
					}

					// enable dynamic rules IF: plugin is in B2B mode, OR hybrid mode + b2b user OR hybrid mode + option
					if (get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled'){
						
						/* Dynamic Rules */
						// Dynamic rule Discounts via fees 
						if (intval(get_option('b2bking_disable_dynamic_rule_discount_setting', 0)) === 0){
							if (get_option('b2bking_have_discount_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_discount_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									add_action('woocommerce_cart_calculate_fees' , array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_cart_discount'));
								}
							}
						}
						// Dynamic rule discounts via sale/regular price
						// Generate "regular price" dynamically

						if (intval(get_option('b2bking_disable_dynamic_rule_discount_sale_setting', 0)) === 0){
							if (get_option('b2bking_have_discount_everywhere_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_discount_everywhere_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){

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
									add_filter('woocommerce_sale_flash', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_display_dynamic_sale_badge'), 999999, 3);
								}
							}
						}
						
						if (intval(get_option('b2bking_disable_dynamic_rule_addtax_setting', 0)) === 0){
							// check the number of rules saved in the database
							if (get_option('b2bking_have_add_tax_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_add_tax_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){

									// Dynamic rule add tax / fee (percentage)
									add_action('woocommerce_cart_calculate_fees' , array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_add_tax_fee'));
								}
							}
						}
						if (intval(get_option('b2bking_disable_dynamic_rule_fixedprice_setting', 0)) === 0){
							// check the number of rules saved in the database
							if (get_option('b2bking_have_fixed_price_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_fixed_price_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									// Simple, grouped and external products
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

						if (intval(get_option('b2bking_disable_dynamic_rule_freeshipping_setting', 0)) === 0){
							if (get_option('b2bking_have_free_shipping_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_free_shipping_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									// Dynamic rule Free Shipping
									WC_Cache_Helper::get_transient_version( 'shipping', true );
									add_filter( 'woocommerce_shipping_free_shipping_is_available', array('B2bking_Dynamic_Rules','b2bking_dynamic_rule_free_shipping'), 10, 3 );
								
								}
							}
						}
						if (intval(get_option('b2bking_disable_dynamic_rule_minmax_setting', 0)) === 0){
							if (get_option('b2bking_have_minmax_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_minmax_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									// Dynamic rule Minimum Order
									add_action( 'woocommerce_checkout_process', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_minmax_order_amount') );
									add_action( 'woocommerce_before_cart' , array('B2bking_Dynamic_Rules', 'b2bking_dynamic_minmax_order_amount'));

									// set quantity inputs
									add_filter( 'woocommerce_quantity_input_args', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_minmax_order_amount_quantity'), 100, 2 );
									add_filter( 'woocommerce_available_variation', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_minmax_order_amount_quantity_variation'), 100, 3 );
									// Set product quantity added to cart (handling ajax add to cart)
									add_filter( 'woocommerce_add_to_cart_quantity',array('B2bking_Dynamic_Rules', 'b2bking_dynamic_minmax_order_amount_quantity_number'), 100, 2 );

								}
							}
						}
						if (intval(get_option('b2bking_disable_dynamic_rule_hiddenprice_setting', 0)) === 0){
							if (get_option('b2bking_have_hidden_price_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_hidden_price_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									// Dynamic rule Hidden price
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
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									// Dynamic rule Required Multiple
									add_action( 'woocommerce_check_cart_items', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple') );
									// add quantity step in product page
									add_filter( 'woocommerce_quantity_input_args', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple_quantity'), 10, 2 );
									add_filter( 'woocommerce_available_variation', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple_quantity_variation'), 10, 3 );
									// Set product quantity added to cart (handling ajax add to cart)
									add_filter( 'woocommerce_add_to_cart_quantity',array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple_quantity_number'), 10, 2 );
									
								}
							}
						}
						if (intval(get_option('b2bking_disable_dynamic_rule_zerotax_setting', 0)) === 0){
							if (get_option('b2bking_have_tax_exemption_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_tax_exemption_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									// Dynamic rule Zero Tax Product
									add_filter( 'woocommerce_product_get_tax_class', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_zero_tax_product'), 10, 2 );
									add_filter( 'woocommerce_product_variation_get_tax_class', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_zero_tax_product'), 10, 2 );
								}
							}
						}
						if (intval(get_option('b2bking_disable_dynamic_rule_taxexemption_setting', 0)) === 0){
							if (get_option('b2bking_have_tax_exemption_user_rules', 'yes') === 'yes'){
								// check if the user's ID or group is part of the list.
								$list = get_option('b2bking_have_tax_exemption_user_rules_list', 'yes');
								if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){
									// Dynamic rule Tax Exemption (user)
									add_action( 'init', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_tax_exemption') );
									add_action('woocommerce_cart_calculate_fees' , array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_tax_exemption_fees'));
									add_action( 'woocommerce_cart_totals_before_shipping', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_tax_exemption_fees_display_only'));
									add_action( 'woocommerce_review_order_before_shipping', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_tax_exemption_fees_display_only'));

									// Clear user tax exemption cache when checkout is rendered
									add_action( 'woocommerce_checkout_update_order_review', array($this, 'b2bking_clear_tax_cache_checkout'), 1 );

								}
							}
						}

						if (get_option('b2bking_have_currency_rules', 'yes') === 'yes'){
							// check if the user's ID or group is part of the list.
							$list = get_option('b2bking_have_currency_rules_list', 'yes');
							if ($this->b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list) === 'yes'){

								add_filter('woocommerce_currency_symbol', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_currency_symbol'), 10, 2);

								add_filter( 'option_woocommerce_currency', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_currency'));
							}
						}

					
					}
					/* Set Tiered Pricing via Fixed Price Dynamic Rule */
					
						add_filter('woocommerce_product_get_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
						add_filter('woocommerce_product_get_regular_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
						// Variations 
						add_filter('woocommerce_product_variation_get_regular_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
						add_filter('woocommerce_product_variation_get_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
						add_filter( 'woocommerce_variation_prices_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
						add_filter( 'woocommerce_variation_prices_regular_price', array($this, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );

						// Show table for tiered prices in product / variation page 
						add_action('woocommerce_after_add_to_cart_button', array($this,'b2bking_show_tiered_pricing_table'));
						add_filter( 'woocommerce_available_variation', array($this,'b2bking_show_tiered_pricing_table_variation'), 10, 3 );


						// Show custom info table in product page
						add_action('woocommerce_after_add_to_cart_button', array($this,'b2bking_show_custom_information_table'));

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
						add_filter( 'woocommerce_get_variation_prices_hash', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_discount_sale_price_variation_hash'), 99, 1);
					

					if (intval(get_option('b2bking_disable_shipping_payment_control_setting', 0)) === 0){
						// Disable shipping methods based on group rules
						add_action( 'woocommerce_package_rates', array($this, 'b2bking_disable_shipping_methods'), 1 );
						// Disable payment methods based on group rules
						add_filter('woocommerce_available_payment_gateways', array($this,'b2bking_disable_payment_methods'),1);
						// Disable payment methods based on dynamic rule payment methods
						add_filter('woocommerce_available_payment_gateways', array($this,'b2bking_disable_payment_methods_dynamic_rule'),9999, 1);
					}

					// Enqueue resources
					add_action('wp_enqueue_scripts', array($this, 'enqueue_public_resources'));


				}

				// Check if plugin status is B2B OR plugin status is Hybrid and user is B2B user.
				if (get_option('b2bking_plugin_status_setting', 'disabled') === 'b2b' || (get_option('b2bking_plugin_status_setting', 'disabled') === 'hybrid' && (get_user_meta( $user_data_current_user_id, 'b2bking_b2buser', true ) === 'yes'))){

					if (intval(get_option('b2bking_disable_registration_setting', 0)) === 0){
						/* Custom Fields and Registration Fields */
						// Display custom registration data in My account details
						add_action('woocommerce_edit_account_form', array($this,'b2bking_display_custom_registration_fields'));
						// Save custom fields after edit
						add_action( 'woocommerce_save_account_details', array($this,'b2bking_save_custom_registration_fields_edit'), 10, 1 );
						// Validate custom fields (especially VAT) on account edit
						add_action( 'woocommerce_save_account_details_errors',array($this,'b2bking_save_custom_registration_fields_validate'), 10, 1 );
						// Validate custom fields (especially VAT) on address edit
						add_action( 'woocommerce_after_save_address_validation',array($this,'b2bking_save_custom_registration_fields_validate'),10,1);
						// Add custom fields to billing
						add_filter('woocommerce_billing_fields', array($this, 'b2bking_custom_woocommerce_billing_fields'));
						// ADD Vat Validate button to billing VAT if registration is not enabled in checkout
						add_action('woocommerce_after_checkout_billing_form', array($this, 'b2bking_validate_vat_registration_disabled'));

						// Add custom fields to order meta
						add_action( 'woocommerce_checkout_update_order_meta',  array($this,'b2bking_add_custom_fields_to_order_meta') );
						// Add checkout VAT VIES validation
						add_action('woocommerce_after_checkout_validation', array($this,'b2bking_checkout_vat_vies_validation'));
					}

					/* Add items to "My Account" */
					// Add custom items to My account WooCommerce user menu
					add_filter( 'woocommerce_account_menu_items', array($this, 'b2bking_my_account_custom_items'), 10, 1 );
					// Add custom endpoints
					add_action( 'init', array($this, 'b2bking_custom_endpoints') );
					if (intval(get_option('b2bking_force_permalinks_setting', 1)) === 0){
						// Add redirects by default to prevent 404 problems
						add_action ('template_redirect', array($this, 'b2bking_redirects_my_account_default'));
						// adds "id" query var to WP list. Makes the query recognizable by wp
						add_filter( 'query_vars', array($this, 'b2bking_add_query_vars_filter') );
					}

					if (intval(get_option('b2bking_force_permalinks_flushing_setting', 0)) === 1){
						add_action( 'init', array($this, 'force_permalinks_rewrite') );
					}
					if (intval(get_option('b2bking_enable_conversations_setting', 1)) === 1){
						/* Conversations */
						// Add content to conversations endpoint
						add_action( 'woocommerce_account_conversations_endpoint', array($this, 'b2bking_conversations_endpoint_content') );
						// Add content to individual conversation endpoint
						add_action( 'woocommerce_account_conversation_endpoint', array($this, 'b2bking_conversation_endpoint_content') );
					}

					if (intval(get_option('b2bking_enable_offers_setting', 1)) === 1){
						/* Offers */
						// Add content to offers endpoint
						add_action( 'woocommerce_account_offers_endpoint', array($this, 'b2bking_offers_endpoint_content') );
						// Change product price in the cart for offers
						add_action( 'woocommerce_before_calculate_totals', array($this, 'b2bking_offer_change_price_cart') );
						// Change product price in the minicart for offers
						add_filter('woocommerce_cart_item_price', array($this, 'b2bking_offer_change_price_minicart'), 10, 3);
						// Add offer item metadata to order (checkout + backend)
						add_action( 'woocommerce_checkout_create_order_line_item', array($this,'b2bking_add_item_metadata_to_order'), 20, 4 );
						// Display offer item metadata in cart
						add_filter('woocommerce_cart_item_name', array($this, 'b2bking_display_metadata_cart'),1,3);
						// Add shortcode
						add_action( 'init', array($this, 'b2bking_offers_shortcode'));
					}

					if (intval(get_option('b2bking_enable_bulk_order_form_setting', 1)) === 1){
						/* Bulk order */
						// Add content to bulk order endpoint
						add_action( 'woocommerce_account_bulkorder_endpoint', array($this, 'b2bking_bulkorder_endpoint_content') );
						// Add bulk order shortcode
						add_action( 'init', array($this, 'b2bking_bulkorder_shortcode'));
					}

					if (intval(get_option('b2bking_enable_subaccounts_setting', 1)) === 1){
						/* Subaccount */
						// Add content to subaccounts endpoint
						add_action( 'woocommerce_account_subaccounts_endpoint', array($this, 'b2bking_subaccounts_endpoint_content') );
						add_action( 'woocommerce_account_subaccount_endpoint', array($this, 'b2bking_subaccount_endpoint_content') );
						// Subaccount: add "Placed by" column in Orders
						add_filter( 'woocommerce_my_account_my_orders_columns', array($this, 'b2bking_orders_placed_by_column') );
						// Add data to "Placed by" column
						add_action( 'woocommerce_my_account_my_orders_column_order-placed-by', array($this, 'b2bking_orders_placed_by_column_content')  );
						// Add subaccounts orders to main account order query
						add_filter( 'woocommerce_my_account_my_orders_query', array($this, 'b2bking_add_subaccounts_orders_to_main_query'), 10, 1 );
						// Give main account permission to view subaccount orders
						add_filter( 'user_has_cap', array($this, 'b2bking_give_main_account_view_subaccount_orders_permission'), 10, 3 );
						// Give permissions to order again
						add_filter( 'user_has_cap', array($this, 'b2bking_subaccounts_orderagain_cap'), 10, 3 );
						
						// Subaccount checkout permission validation
						add_action('woocommerce_after_checkout_validation', array($this,'b2bking_subaccount_checkout_permission_validation'), 10, 2);
						
						add_action( 'woocommerce_before_cart', array($this,'b2bking_subaccount_checkout_permission_validation_mesage'), 10);

						add_action( 'woocommerce_before_checkout_form', array($this,'b2bking_subaccount_checkout_permission_validation_mesage'), 10);

					}

					/* Reordering features	*/
					// Add a reorder button in account orders (overview)
					add_filter( 'woocommerce_my_account_my_orders_actions', array($this, 'b2bking_add_reorder_button_overview'), 10, 2 );
					// Create order note mentioning it is a reorder and linking to the initial order
					add_action( 'woocommerce_thankyou', array( $this, 'b2bking_reorder_create_order_note_reference') );
					// Save old order id
					add_action( 'woocommerce_ordered_again', array( $this, 'b2bking_reorder_save_old_order_id' ));
					
					/* General */

					if (intval(get_option('b2bking_enable_purchase_lists_setting', 1)) === 1){
						/* Purchase list */
						// Add content to purchase lists endpoints
						add_action( 'woocommerce_account_purchase-lists_endpoint', array($this, 'b2bking_purchase_lists_endpoint_content') );
						add_action( 'woocommerce_account_purchase-list_endpoint', array($this, 'b2bking_purchase_list_endpoint_content') );
						// Add "Save cart as Purchase List" button
						add_action( 'woocommerce_cart_actions', array($this, 'b2bking_purchase_list_cart_button'));
						// Add shortcode
						add_action( 'init', array($this, 'b2bking_purchaselists_shortcode'));
					}

					// Add csv order shortcode
					add_action( 'init', array($this, 'b2bking_csvorder_shortcode'));
					add_action( 'init', array($this, 'b2bking_handle_file_upload'));

				}
			}
		});
	}

	function b2bking_tiered_pricing_fixed_price($price, $product){
		
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

		$currentusergroupidnr = get_user_meta($user_id, 'b2bking_customergroup', true);
			
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
			if (is_object( WC()->cart )){
			    foreach( WC()->cart->get_cart() as $cart_item ){
			        if ( $product_id === $cart_item['product_id'] || $product_id === $cart_item['variation_id']){
			            $quantity = $cart_item['quantity'];
			            break;
			        }
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
	}

	function b2bking_show_tiered_pricing_table(){
		// only for simple products
		global $post;
		$product = wc_get_product($post->ID);
		if (is_object($product)){
			if( $product->is_type( 'simple' ) ){
				// get if 1) pricing table is enabled and 2) there are tiered prices set up
				$is_enabled = get_post_meta($post->ID, 'b2bking_show_pricing_table', true);
				if (!$product->is_purchasable()){
					$is_enabled = 'no';
				}
				if ($is_enabled !== 'no'){
					// get user's group
					$user_id = get_current_user_id();
			    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
			    	if ($account_type === 'subaccount'){
			    		// for all intents and purposes set current user as the subaccount parent
			    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
			    		$user_id = $parent_user_id;
			    	}

					$currentusergroupidnr = get_user_meta($user_id, 'b2bking_customergroup', true );

					$price_tiers = get_post_meta($post->ID, 'b2bking_product_pricetiers_group_'.$currentusergroupidnr, true);

					$user_price = array();
					// if didn't find anything as a price tier, give regular price tiers
					if (!(!empty($price_tiers) && strlen($price_tiers) > 1 )){
						$price_tiers = get_post_meta($post->ID, 'b2bking_product_pricetiers_group_b2c', true);

						// if user is logged in b2b
						if (get_user_meta(get_current_user_id(),'b2bking_b2buser', true) === 'yes'){
							// but first check that there is at least 1 price in the regular tiers that is better than the group price
							$have_better_price = 'no';

							// go through array
							$price_tiers_array = explode(';', $price_tiers);
							$price_tiers_array = array_filter($price_tiers_array);

							$user_group_regular_price = $this->tofloat(get_post_meta($product->get_id(),'b2bking_regular_product_price_group_'.$currentusergroupidnr, true));
							$user_group_sale_price = $this->tofloat(get_post_meta($product->get_id(),'b2bking_sale_product_price_group_'.$currentusergroupidnr, true));
							if (!empty($user_group_sale_price)){
								$user_price = $user_group_sale_price;
							} else {
								$user_price = $user_group_regular_price;
							}

							if (!empty($user_price)){
								// adjust price for tax
								require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
								$helper = new B2bking_Helper();
								$user_price = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $user_price ) ); // get sale price

								foreach ($price_tiers_array as $tier_group){
									$tier_price = explode(':',$tier_group)[1];
									$tier_price = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $tier_price ) ); 
									if (floatval($tier_price) <= $user_price){
										$have_better_price = 'yes';
										break;
									}
								}
							} else {
								// there is no group price, show b2c table
								$have_better_price = 'yes';
							}

							if ($have_better_price === 'no'){
								$price_tiers = array();
							}
						}

					}

					if (!empty($price_tiers) && strlen($price_tiers) > 1 ){
						?>
						<table class="shop_table b2bking_shop_table">
							<thead>
								<tr>
									<th><?php esc_html_e('Product Quantity','b2bking'); ?></th>
									<th><?php esc_html_e('Price per Unit','b2bking'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								$price_tiers_array = explode(';', $price_tiers);
								$price_tiers_array = array_filter($price_tiers_array);

								// need to order this array by the first number (elemnts of form 1:5, 2:5, 6:5)
								$helper_array = array();							
								foreach ($price_tiers_array as $index=> $pair){
									$pair_array = explode(':', $pair);
									$helper_array[$pair_array[0]] = $this->tofloat($pair_array[1]);
								}
								ksort($helper_array);
								$price_tiers_array = array();
								foreach ($helper_array as $index=>$value){
									array_push($price_tiers_array,$index.':'.$value);
								}
								// finished sort

								$number_of_tiers = count($price_tiers_array);
								// only 1 tier
								if ($number_of_tiers === 1){
									$tier_values = explode(':', $price_tiers_array[0]);
									?>
									<tr>
										<td><?php echo esc_html($tier_values[0]).'+'; do_action('b2bking_tiered_table_after_quantity', $post->ID); ?></td>

										<?php 
										// adjust price for tax
										require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
										$helper = new B2bking_Helper();
										$tier_values[1] = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $tier_values[1] ) ); // get sale price
										?>
										<td><?php echo wc_price($tier_values[1]); do_action('b2bking_tiered_table_after_price', $post->ID);?></td>
									</tr>
									<?php
								} else {
									$previous_tier = 'no';
									$previous_value = 'no';
									foreach ($price_tiers_array as $index => $tier){
										$tier_values = explode(':', $tier);
										if ($previous_tier !== 'no'){

											// adjust price for tax
											require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
											$helper = new B2bking_Helper();
											$previous_value = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $previous_value ) ); // get sale price

											// check that tier price is better than group price, else don't show tier
											$show_row = 'yes';
											if (!empty($user_price)){
												if ($previous_value > $user_price){
													$show_row = 'no';
												}
											}
											if ($show_row === 'yes'){
												?>
													<tr>
														<td><?php
														if (floatval($previous_tier) !== floatval($tier_values[0]-1)){
															echo esc_html($previous_tier).' - '.esc_html($tier_values[0]-1);
														} else {
															echo esc_html($previous_tier);
														}
														do_action('b2bking_tiered_table_after_quantity', $post->ID);
														?></td>

														<td><?php echo wc_price($previous_value); do_action('b2bking_tiered_table_after_price', $post->ID);?></td>
													</tr>
												<?php
											}
										}
										$previous_tier = $tier_values[0];
										$previous_value = $tier_values[1];

										// if this tier is the last tier
										if (intval($index+1) === intval($number_of_tiers)){

											?>
											<tr>
												<td><?php echo esc_html($previous_tier).'+'; do_action('b2bking_tiered_table_after_quantity', $post->ID);?></td>
												<?php 
												// adjust price for tax
												require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
												$helper = new B2bking_Helper();
												$previous_value = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $previous_value ) ); // get sale price
												?>
												<td><?php echo wc_price($previous_value); do_action('b2bking_tiered_table_after_price', $post->ID);?></td>
											</tr>
											<?php
										}
									}
								}
								?>
							</tbody>
						</table>
						<?php
					}
				}
			}
		}
	}

	function b2bking_show_custom_information_table(){

		global $post;
		$product = wc_get_product($post->ID);

		if (is_object($product)){
			// get if 1) info table is enabled and 2) there are rows set up
			$is_enabled = get_post_meta($post->ID, 'b2bking_show_information_table', true);
			if ($is_enabled !== 'no'){
				// get user's group
				$user_id = get_current_user_id();
		    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
		    	if ($account_type === 'subaccount'){
		    		// for all intents and purposes set current user as the subaccount parent
		    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
		    		$user_id = $parent_user_id;
		    	}

				$currentusergroupidnr = get_user_meta($user_id, 'b2bking_customergroup', true );

				$customrows = get_post_meta($post->ID, 'b2bking_product_customrows_group_'.$currentusergroupidnr, true);

				// if didn't find anything as a price tier, give regular price tiers
				if (empty($customrows)){
					$customrows = get_post_meta($post->ID, 'b2bking_product_customrows_group_b2c', true);
				}

				if (!empty($customrows)){
					?>
					<table class="shop_table b2bking_shop_table">
						<thead>
							<tr>
								<th><?php esc_html_e('Information Table','b2bking'); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$rows_array = explode(';',$customrows);
							foreach ($rows_array as $row){
								$row_values = explode (':', $row);
								if (!empty($row_values[0]) && !empty($row_values[1])){
									// display row
									?>
									<tr>
										<td><?php echo esc_html($row_values[0]); ?></td>
										<td><?php echo esc_html($row_values[1]); ?></td>
									</tr>
									<?php
								}
							}
							?>							
						</tbody>
					</table>
					<?php
				}
			}
		}
		
	}


	function b2bking_show_tiered_pricing_table_variation( $data, $product, $variation ) {

		ob_start();
		$variation_id = $variation->get_id();
		$product_id = wp_get_post_parent_id($variation_id);

    	// get if 1) pricing table is enabled and 2) there are tiered prices set up
    	$is_enabled = get_post_meta($product_id, 'b2bking_show_pricing_table', true);

    	if (!$variation->is_purchasable()){
    		$is_enabled = 'no';
    	}
    	if ($is_enabled !== 'no'){
    		// get user's group
    		$user_id = get_current_user_id();
        	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
        	if ($account_type === 'subaccount'){
        		// for all intents and purposes set current user as the subaccount parent
        		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
        		$user_id = $parent_user_id;
        	}

        	$currentusergroupidnr = get_user_meta($user_id, 'b2bking_customergroup', true );

    		$price_tiers = get_post_meta($variation_id, 'b2bking_product_pricetiers_group_'.$currentusergroupidnr, true);

    		$user_price = array();
    		// if didn't find anything as a price tier, give regular price tiers if it exists
    		if (!(!empty($price_tiers) && strlen($price_tiers) > 1 )){
   				$price_tiers = get_post_meta($variation_id, 'b2bking_product_pricetiers_group_b2c', true);


   				if (get_user_meta(get_current_user_id(),'b2bking_b2buser', true) === 'yes'){
					// but first check that there is at least 1 price in the regular tiers that is better than the group price
					$have_better_price = 'no';

					// go through array
					$price_tiers_array = explode(';', $price_tiers);
					$price_tiers_array = array_filter($price_tiers_array);

					
					$user_group_regular_price = $this->tofloat(get_post_meta($variation_id,'b2bking_regular_product_price_group_'.$currentusergroupidnr, true));
					$user_group_sale_price = $this->tofloat(get_post_meta($variation_id,'b2bking_sale_product_price_group_'.$currentusergroupidnr, true));
					if (!empty($user_group_sale_price)){
						$user_price = $user_group_sale_price;
					} else {
						$user_price = $user_group_regular_price;
					}

					if (!empty($user_price)){
						// adjust price for tax
						require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
						$helper = new B2bking_Helper();
						$user_price = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $user_price ) ); // get sale price
						foreach ($price_tiers_array as $tier_group){
							$tier_price=explode(':',$tier_group)[1];
							$tier_price = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $tier_price ) );
							if (floatval($tier_price) <= $user_price){
								$have_better_price = 'yes';
								break;
							}
						}
					} else {
						// there is no group price, show b2c table
						$have_better_price = 'yes';
					}

					if ($have_better_price === 'no'){
						$price_tiers = array();
					}
				}

    		}

    		if (!empty($price_tiers) && strlen($price_tiers) > 1 ){
    			?>
    			<table class="shop_table b2bking_shop_table">
    				<thead>
    					<tr>
    						<th><?php esc_html_e('Product Quantity','b2bking'); ?></th>
    						<th><?php esc_html_e('Price per Unit','b2bking'); ?></th>
    					</tr>
    				</thead>
    				<tbody>
    					<?php
    					$price_tiers_array = explode(';', $price_tiers);
    					$price_tiers_array = array_filter($price_tiers_array);

    					// need to order this array by the first number (elemnts of form 1:5, 2:5, 6:5)
    					$helper_array = array();							
    					foreach ($price_tiers_array as $index=> $pair){
    						$pair_array = explode(':', $pair);
    						$helper_array[$pair_array[0]] = $this->tofloat($pair_array[1]);
    					}
    					ksort($helper_array);
    					$price_tiers_array = array();
    					foreach ($helper_array as $index=>$value){
    						array_push($price_tiers_array,$index.':'.$value);
    					}
    					// finished sort

    					$number_of_tiers = count($price_tiers_array);
    					if ($number_of_tiers === 1){
    						$tier_values = explode(':', $price_tiers_array[0]);
    						?>
    						<tr>
    							<td><?php echo esc_html($tier_values[0]).'+';
    								do_action('b2bking_tiered_table_after_quantity', $variation_id); ?></td>

    							<?php 
    							// adjust price for tax
    							require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
    							$helper = new B2bking_Helper();
    							$tier_values[1] = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $tier_values[1] ) ); // get sale price
    							?>
    							<td><?php echo wc_price($tier_values[1]); 
    								do_action('b2bking_tiered_table_after_price', $variation_id); ?></td>
    						</tr>
    						<?php
    					} else {
    						$previous_tier = 'no';
    						$previous_value = 'no';
    						foreach ($price_tiers_array as $index => $tier){
    							$tier_values = explode(':', $tier);
    							if ($previous_tier !== 'no'){

    								// adjust price for tax
    								require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
    								$helper = new B2bking_Helper();
    								$previous_value = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $previous_value ) ); // get sale price

    								// check that tier price is better than group price, else don't show tier
    								$show_row = 'yes';
    								if (!empty($user_price)){
    									if ($previous_value > $user_price){
    										$show_row = 'no';
    									}
    								}
    								if ($show_row === 'yes'){
    									?>
	    									<tr>
	    										<td><?php
	    										if (floatval($previous_tier) !== floatval($tier_values[0]-1)){
	    											echo esc_html($previous_tier).' - '.esc_html($tier_values[0]-1);
	    										} else {
	    											echo esc_html($previous_tier);
	    										}
	    										do_action('b2bking_tiered_table_after_quantity', $variation_id);
	    										?></td>

	    										<td><?php echo wc_price($previous_value); 
	    										do_action('b2bking_tiered_table_after_price', $variation_id);?></td>
	    									</tr>
	    								<?php
	    							}
    							}
    							$previous_tier = $tier_values[0];
    							$previous_value = $tier_values[1];

    							// if this tier is the last tier
    							if (intval($index+1) === intval($number_of_tiers)){
    								?>
    								<tr>
    									<td><?php echo esc_html($previous_tier).'+'; 							do_action('b2bking_tiered_table_after_quantity', $variation_id);?></td>

    									<?php 
    									// adjust price for tax
    									require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
    									$helper = new B2bking_Helper();
    									$previous_value = $helper->b2bking_wc_get_price_to_display( $product, array( 'price' => $previous_value ) ); // get sale price
    									?>
    									<td><?php echo wc_price($previous_value); 
    										do_action('b2bking_tiered_table_after_price', $variation_id); ?></td>
    								</tr>
    								<?php
    							}
    						}
    					}
    					?>
    				</tbody>
    			</table>
    			<?php
    		}
    	}
    	$previous_availability = $data['availability_html'];
        $data['availability_html'] = ob_get_clean().$previous_availability;
	    return $data;
	}


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
			$currentusergroupidnr = get_user_meta($user_id, 'b2bking_customergroup', true);
			if ($is_b2b_user === 'yes'){
				// Search if there is a specific price set for the user's group
				$b2b_price = $this->tofloat(get_post_meta($product->get_id(), 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
				if (!empty($b2b_price)){
					/*
					// ADD WOOCS COMPATIBILITY
		    		if (class_exists('WOOCS')) {
						global $WOOCS;
						$currrent = $WOOCS->current_currency;
						if ($currrent != $WOOCS->default_currency) {
							$currencies = $WOOCS->get_currencies();
							$rate = $currencies[$currrent]['rate'];
							$b2b_price = $b2b_price / ($rate);
						}
					}*/

					// First check that there is no tiered price
					$have_tiered_price = 'no';
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
						if (is_object( WC()->cart )){
						    foreach( WC()->cart->get_cart() as $cart_item ){
						        if ( $product_id === $cart_item['product_id'] || $product_id === $cart_item['variation_id']){
						            $quantity = $cart_item['quantity'];
						            break;
						        }
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
								$have_tiered_price = 'yes';

							}
						}
					} 
					if ($have_tiered_price === 'no'){
						return $b2b_price;
					} else {
						return $sale_price;
					}
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
				$this->tofloat($b2b_price = get_post_meta($product->get_id(), 'b2bking_sale_product_price_group_'.$currentusergroupidnr, true ));
				if (!empty($b2b_price)){

					// check that there is no tiered price
					// First check that there is no tiered price
					$have_tiered_price = 'no';
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
						if (is_object( WC()->cart )){
						    foreach( WC()->cart->get_cart() as $cart_item ){
						        if ( $product_id === $cart_item['product_id'] || $product_id === $cart_item['variation_id']){
						            $quantity = $cart_item['quantity'];
						            break;
						        }
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
								$have_tiered_price = 'yes';

							}
						}
					} 

					if ($have_tiered_price === 'no'){
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

					// First check that there is no tiered price
					$product = wc_get_product($product_id_set);
					$have_tiered_price = 'no';
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
						if (is_object( WC()->cart )){
						    foreach( WC()->cart->get_cart() as $cart_item ){
						        if ( $product_id === $cart_item['product_id'] || $product_id === $cart_item['variation_id']){
						            $quantity = $cart_item['quantity'];
						            break;
						        }
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
								$have_tiered_price = 'yes';

							}
						}
					} 

					if ($have_tiered_price === 'no'){
						$cart_item['data']->set_price( $b2b_price );
						set_transient('b2bking_user_'.$user_id.'_product_'.$product_id_set.'_custom_set_price', $b2b_price);
					}
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

				// First check that there is no tiered price
				$product = wc_get_product($product_id_set);
				$have_tiered_price = 'no';
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
					if (is_object( WC()->cart )){
					    foreach( WC()->cart->get_cart() as $cart_item ){
					        if ( $product_id === $cart_item['product_id'] || $product_id === $cart_item['variation_id']){
					            $quantity = $cart_item['quantity'];
					            break;
					        }
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
							$have_tiered_price = 'yes';

						}
					}
				} 
				if ($have_tiered_price === 'no'){
					require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
					$helper = new B2bking_Helper();
					
					$discount_price = $helper->b2bking_wc_get_price_to_display( wc_get_product($product_id_set), array( 'price' => $cart_item['data']->get_sale_price() ) ); // get sale price
					
					if ($discount_price !== NULL && $discount_price !== ''){
						$price = wc_price($discount_price, 4); 
					}
				}
			} 
		}
		return $price;
	}

	function b2bking_check_user_approval_on_login ($errors, $username, $password) {

		// First need to get the user object
		if (!empty($username)){
			$user = get_user_by('login', $username);
			if(!$user) {
				$user = get_user_by('email', $username);
				if(!$user) {
					return $errors;
				}
			}
		}
		if (isset($user->ID)){
			$user_status = get_user_meta($user->ID, 'b2bking_account_approved', true);
			if($user_status === 'no'){
				$errors->add('access', esc_html__('Your account is waiting for approval. Until approved, you cannot login.','b2bking'));
			}
		}
	    return $errors;
	}


	// Modify new account email - Add approval needed notice
	function b2bking_modify_new_account_email( $email ) { 

		if ( $email->id === 'customer_new_account' ) {
			$user = get_user_by('email', $email->user_email);
			$approval_needed = get_user_meta($user->ID, 'b2bking_account_approved', true);
			if ($approval_needed === 'no'){
				?>
				<p>
					<?php
					$text = esc_html__('Attention! Your account requires manual approval. Our team will review it as soon as possible. Thank you for understanding.', 'b2bking');
					$text = apply_filters('b2bking_new_account_email_approval_notification', $text );
					echo $text;
					?>
				</p>
				<?php
			}
		}
	}

	// add custom fields to order meta
	function b2bking_save_billing_details( $order_id ){

		// build array of groups visible
		$array_groups_visible = array(
            'relation' => 'OR',
        );

		if (!is_user_logged_in()){
			array_push($array_groups_visible, array(
                'key' => 'b2bking_custom_field_multiple_groups',
                'value' => 'group_loggedout',
                'compare' => 'LIKE'
            ));
		} else {
			// if user is b2c
			if (get_user_meta(get_current_user_id(),'b2bking_b2buser', true) !== 'yes'){
				array_push($array_groups_visible, array(
	                'key' => 'b2bking_custom_field_multiple_groups',
	                'value' => 'group_b2c',
	                'compare' => 'LIKE'
	            ));
			} else {
				array_push($array_groups_visible, array(
	                'key' => 'b2bking_custom_field_multiple_groups',
	                'value' => 'group_'.get_user_meta(get_current_user_id(),'b2bking_customergroup', true),
	                'compare' => 'LIKE'
	            ));
			}
		}

		// get all enabled custom fields with no default billing connection (first name, last name etc)
		$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
			                array(
		                        'key' => 'b2bking_custom_field_add_to_billing',
		                        'value' => 1
			                ),
			                $array_groups_visible
		            	)
			    	]);
		foreach ($custom_fields as $custom_field){
			if (isset($_POST['b2bking_custom_field_'.$custom_field->ID])){
				update_post_meta( $order_id, 'b2bking_custom_field_'.$custom_field->ID, sanitize_text_field( $_POST['b2bking_custom_field_'.$custom_field->ID] ) );
			}
		}
	}


	// add custom fields to billing
	function b2bking_custom_woocommerce_billing_fields($fields){

		if (is_user_logged_in()){
			$user_id = get_current_user_id();
			$account_type = get_user_meta($user_id,'b2bking_account_type', true);
			if ($account_type === 'subaccount'){
				// for all intents and purposes set current user as the subaccount parent
				$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
				$user_id = $parent_user_id;
			}
		} else {
			$user_id = 0; 
		}

		// build array of groups visible
		$array_groups_visible = array(
            'relation' => 'OR',
        );

		if (!is_user_logged_in()){
			array_push($array_groups_visible, array(
                'key' => 'b2bking_custom_field_multiple_groups',
                'value' => 'group_loggedout',
                'compare' => 'LIKE'
            ));
		} else {
			// if user is b2c
			if (get_user_meta(get_current_user_id(),'b2bking_b2buser', true) !== 'yes'){
				array_push($array_groups_visible, array(
	                'key' => 'b2bking_custom_field_multiple_groups',
	                'value' => 'group_b2c',
	                'compare' => 'LIKE'
	            ));
			} else {
				array_push($array_groups_visible, array(
	                'key' => 'b2bking_custom_field_multiple_groups',
	                'value' => 'group_'.get_user_meta(get_current_user_id(),'b2bking_customergroup', true),
	                'compare' => 'LIKE'
	            ));
			}
		}

		// get all enabled custom fields with no default billing connection (first name, last name etc)
		$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
			                array(
			                	'relation' => 'OR',
        		                array(
        	                        'key' => 'b2bking_custom_field_billing_connection',
        	                        'value' => 'none'
        		                ),
        		                array(
        	                        'key' => 'b2bking_custom_field_billing_connection',
        	                        'value' => 'billing_vat'
        		                ),
        		            ),			               
			                array(
		                        'key' => 'b2bking_custom_field_add_to_billing',
		                        'value' => 1
			                ),
			                $array_groups_visible,
		            	)
			    	]);

		foreach ($custom_fields as $custom_field){

			$field_type = get_post_meta ($custom_field->ID, 'b2bking_custom_field_field_type', true);
			$required = intval(get_post_meta ($custom_field->ID, 'b2bking_custom_field_required_billing', true));
			$billing_connection = get_post_meta($custom_field->ID, 'b2bking_custom_field_billing_connection', true);
			// check if this field is VAT
			if ($billing_connection === 'billing_vat'){
				// override type and make it a TEXT type input
				$field_type = 'text';
				$required_vat = $required; // remember the actual value of required
				// override required and add it later as a custom validation (reason is that VAT needs to be available only for some countries, and making it required doesn't allow you to conditionally hide/show it)
				$required = 0;

				// check if country applies
				global $woocommerce;
				$customertest = $woocommerce->customer;

				if (is_a($customertest, 'WC_Customer')){
					$billing_country = WC()->customer->get_billing_country();
				} else {
					$billing_country = 'NOTACUSTOMER';
				}
				
				$vat_enabled_countries = get_post_meta($custom_field->ID, 'b2bking_custom_field_VAT_countries', true);
				// set countries in a hidden input
				$fields['b2bking_custom_billing_vat_countries'] = array(
			        'label' => esc_html__('VAT Countries Hidden','b2bking'),
			        'placeholder' => $vat_enabled_countries,
			        'required' => false, 
			        'clear' => false,
			        'type' => 'text',
			        'class' => array('b2bking_vat_countries_hidden'),
			        'default' => $vat_enabled_countries,

			    );
			    // set vat field number in a hidden input
				$fields['b2bking_custom_billing_vat_field_number'] = array(
			        'label' => esc_html__('VAT Field Number','b2bking'),
			        'placeholder' => esc_html__('VAT Field Number','b2bking'),
			        'required' => false, 
			        'clear' => false,
			        'type' => 'text',
			        'class' => array('b2bking_vat_countries_hidden'),
			        'default' => $custom_field->ID
			    );

				if (!empty($billing_country)){
					if(strpos($vat_enabled_countries, $billing_country) !== false){ // use of !== false is deliberate, strpos has an unusual behaviour
						// vat field applies
						$vat_class='b2bking_vat_visible';
					} else {
						// make the field hidden
						$vat_class='b2bking_vat_hidden';
					}
				} else {
					$vat_class='b2bking_vat_hidden';
				}
			}

			if ($field_type !== 'file'){ // not available to files for the moment
				$field_label = get_post_meta ($custom_field->ID, 'b2bking_custom_field_field_label', true);
				$field_placeholder = get_post_meta ($custom_field->ID, 'b2bking_custom_field_field_placeholder', true);
				$field_value = get_user_meta ($user_id, 'b2bking_custom_field_'.$custom_field->ID, true);
				if ($field_value === NULL){
					$field_value = '';
				}
				if ($required === 1){
					$required = true;
				} else {
					$required = false;
				}

				$field_array = array(
			        'label' => sanitize_text_field($field_label),
			        'placeholder' => sanitize_text_field($field_placeholder), 
			        'required' => $required, 
			        'clear' => false,
			        'type' => sanitize_text_field($field_type),
			        'default' => $field_value,
			    );

			    if ($billing_connection === 'billing_vat'){
			    	$field_array['class'] = array($vat_class, 'b2bking_vat_field_container', 'b2bking_vat_field_required_'.$required_vat);
			    }

			    $options_array = array();
			    if ($field_type === 'select'){
			    	$user_choices = get_post_meta ($custom_field->ID, 'b2bking_custom_field_user_choices', true);
			    	$choices_array = explode (',', $user_choices);
			    	foreach ($choices_array as $choice){
			    		$options_array[trim($choice)] = trim($choice);
			    	}
			    }
			    $field_array['options'] = $options_array;
			    $fields['b2bking_custom_field_'.$custom_field->ID] = $field_array;
			}
		}

	    return $fields;
	}

	function b2bking_checkout_vat_vies_validation() {

		$vat_number_inputted = sanitize_text_field($_POST['b2bking_custom_field_'.$_POST['b2bking_custom_billing_vat_field_number']]);
		$vat_number_inputted = strtoupper(str_replace(array('.', ' '), '', $vat_number_inputted));
		$country_inputted = sanitize_text_field($_POST['billing_country']);

		if (!(empty($vat_number_inputted))){

			// check if VIES Validation is enabled in settings
			$vat_field_vies_validation_setting = get_post_meta($_POST['b2bking_custom_billing_vat_field_number'], 'b2bking_custom_field_VAT_VIES_validation', true);

			// proceed only if VIES validation is enabled
			if (intval($vat_field_vies_validation_setting) === 1){

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
							wc_add_notice( esc_html__('VAT Number you entered is for a different country than the country you selected', 'b2bking'), 'error' );
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
				}

				if(isset($validation)){
					if (intval($validation->valid) === 1){
						// VAT IS VALID
						// update vat NR to user meta
						$vat_field = get_posts([
							    		'post_type' => 'b2bking_custom_field',
							    	  	'post_status' => 'publish',
							    	  	'fields' => 'ids',
							    	  	'numberposts' => -1,
							    	  	'meta_query'=> array(
							    	  		'relation' => 'AND',
							                array(
						                        'key' => 'b2bking_custom_field_status',
						                        'value' => 1
							                ),
							                array(
						                        'key' => 'b2bking_custom_field_billing_connection',
						                        'value' => 'billing_vat'
							                ),
						            	)
							    	]);
						if (is_user_logged_in()){
							update_user_meta( get_current_user_id(), 'b2bking_custom_field_'.$vat_field[0], $vat_number_inputted);
							update_user_meta( get_current_user_id(), 'b2bking_user_vat_status', 'validated_vat');
						}
					}
				} else {
					wc_add_notice( esc_html__('VAT Number is Invalid:', 'b2bking').' '.$error_details, 'error' );
				}

			}

		}

	}

	function b2bking_subaccount_checkout_permission_validation($data, $errors){
		$user_id = get_current_user_id();
		// check if subaccount
		$account_type = get_user_meta($user_id, 'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			// if it's subaccount check, if subaccount has permission to checkout
			$permission_checkout = filter_var(get_user_meta($user_id, 'b2bking_account_permission_buy', true),FILTER_VALIDATE_BOOLEAN);
			if ($permission_checkout === false){
				$errors->add( 'validation', esc_html__('Your account does not have permission to checkout', 'b2bking') );

			}
		}
	}

	function b2bking_subaccount_checkout_permission_validation_mesage() {
		$user_id = get_current_user_id();
		// check if subaccount
		$account_type = get_user_meta($user_id, 'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			// if it's subaccount check, if subaccount has permission to checkout
			$permission_checkout = filter_var(get_user_meta($user_id, 'b2bking_account_permission_buy', true),FILTER_VALIDATE_BOOLEAN);
			if ($permission_checkout === false){
				wc_print_notice( esc_html__('Your account does not have permission to checkout', 'b2bking'), 'error' );
			}
		}
	}

	// add custom fields to order meta
	function b2bking_add_custom_fields_to_order_meta( $order_id ) {
		// build array of groups visible
		$array_groups_visible = array(
            'relation' => 'OR',
        );

		if (!is_user_logged_in()){
			array_push($array_groups_visible, array(
                'key' => 'b2bking_custom_field_multiple_groups',
                'value' => 'group_loggedout',
                'compare' => 'LIKE'
            ));
		} else {
			// if user is b2c
			if (get_user_meta(get_current_user_id(),'b2bking_b2buser', true) !== 'yes'){
				array_push($array_groups_visible, array(
	                'key' => 'b2bking_custom_field_multiple_groups',
	                'value' => 'group_b2c',
	                'compare' => 'LIKE'
	            ));
			} else {
				array_push($array_groups_visible, array(
	                'key' => 'b2bking_custom_field_multiple_groups',
	                'value' => 'group_'.get_user_meta(get_current_user_id(),'b2bking_customergroup', true),
	                'compare' => 'LIKE'
	            ));
			}
		}
				
		// get all enabled custom fields with no default billing connection (first name, last name etc)
		$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
			                array(
		                        'key' => 'b2bking_custom_field_billing_connection',
		                        'value' => 'none'
			                ),
			                array(
		                        'key' => 'b2bking_custom_field_add_to_billing',
		                        'value' => 1
			                ),
			                $array_groups_visible
		            	)
			    	]);

		foreach ($custom_fields as $custom_field){
			if ( ! empty( $_POST['b2bking_custom_field_'.$custom_field->ID] ) ) {
				$field_label = get_post_meta ($custom_field->ID, 'b2bking_custom_field_field_label', true);
			    update_post_meta( $order_id, sanitize_text_field( $field_label ), sanitize_text_field( $_POST['b2bking_custom_field_'.$custom_field->ID] ) );
			}
		}
	}

	// Custom Registration Fields
	function b2bking_custom_registration_fields(){
		global $woocommerce;    
		global $b2bking_is_b2b_registration;
		global $b2bking_is_b2b_registration_shortcode_role_id;

		if ($b2bking_is_b2b_registration_shortcode_role_id === NULL || $b2bking_is_b2b_registration_shortcode_role_id === ''){
			$b2bking_is_b2b_registration_shortcode_role_id = 'none';
		}

		// if Registration Roles dropdown is enabled (enabled by default), show custom registration roles and fields
		$registration_role_setting = intval(get_option( 'b2bking_registration_roles_dropdown_setting', 1 ));
		if ($registration_role_setting === 1 || $b2bking_is_b2b_registration === 'yes'){

			// get roles
			$custom_roles = get_posts([
			    		'post_type' => 'b2bking_custom_role',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_role_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_role_status',
		                        'value' => 1
			                ),
		            	)
			    	]);

			?>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide b2bking_registration_roles_dropdown_section <?php if ($b2bking_is_b2b_registration_shortcode_role_id !== 'none'){ echo 'b2bking_registration_roles_dropdown_section_hidden'; } ?>">
				<label for="b2bking_registration_roles_dropdown">
					<?php esc_html_e('User Type','b2bking'); ?>&nbsp;<span class="required">*</span>
				</label>
				<select id="b2bking_registration_roles_dropdown" name="b2bking_registration_roles_dropdown" required>
					<?php
					foreach ($custom_roles as $role){
						echo '<option value="role_'.esc_attr($role->ID).'" '.selected($role->ID,$b2bking_is_b2b_registration_shortcode_role_id,false).'>'.esc_html(get_the_title(apply_filters( 'wpml_object_id', $role->ID, 'post', true ))).'</option>';
					}
					?>
				</select>
			</p>
			<?php
		}

		$custom_fields = array();
		// if dropdown enabled, retrieve all enabled fields. Else, show only "All Roles" fields
		if ($registration_role_setting === 1 || $b2bking_is_b2b_registration === 'yes'){
			$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
		            	)
			    	]);
		}

		// show all retrieved fields
		 	foreach ($custom_fields as $custom_field){
			$billing_exclusive = intval(get_post_meta($custom_field->ID, 'b2bking_custom_field_billing_exclusive', true));
			if ($billing_exclusive !== 1){
				$field_type = get_post_meta($custom_field->ID, 'b2bking_custom_field_field_type', true);
				$field_label = get_post_meta(apply_filters( 'wpml_object_id', $custom_field->ID, 'post', true ), 'b2bking_custom_field_field_label', true);
				$field_placeholder = get_post_meta(apply_filters( 'wpml_object_id', $custom_field->ID, 'post', true ), 'b2bking_custom_field_field_placeholder', true);
				$required = get_post_meta($custom_field->ID, 'b2bking_custom_field_required', true);
				$billing_connection = get_post_meta($custom_field->ID, 'b2bking_custom_field_billing_connection', true);
				// role identifier
				$role = get_post_meta($custom_field->ID, 'b2bking_custom_field_registration_role', true);
				if ($role !== 'multipleroles'){
					$role_class = 'b2bking_custom_registration_'.esc_attr($role);
				} else {
					$field_roles = get_post_meta($custom_field->ID, 'b2bking_custom_field_multiple_roles', true);
					$roles_array = explode(',',$field_roles);
					$role_class = '';
					foreach($roles_array as $role){
						$role_class.='b2bking_custom_registration_'.esc_attr($role).' ';
					}
				}
				// if error, get previous value and show it in the fields, for user friendliness
				$previous_value = '';
				if (isset($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID)])){
					$previous_value = sanitize_text_field($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID)]);
				}

				if (intval($required) === 1){
					$required = 'required';
				} else {
					$required = '';
				}

				$vat_container = '';
				if ($billing_connection === 'billing_vat'){
					$vat_container = 'b2bking_vat_number_registration_field_container';
				}

				$class = '';
				// purely aesthethical fix, add a class to the P in countries, in order to remove the margin bottom
				if ($billing_connection === 'billing_countrystate' || $billing_connection === 'billing_country' || $billing_connection === 'billing_state'){
					$class = 'b2bking_country_or_state';
				}
				
				echo '<div class="'.esc_attr($vat_container).' b2bking_custom_registration_container '.esc_attr($role_class).'">';
				echo '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide '.$class.'">';

				echo '<label>'.esc_html($field_label).'&nbsp;';
					if ($required === 'required'){ 
						echo '<span class="required">*</span>'; 
					}
					echo '</label>';

				// if billing connection is country, replace field with countries dropdown
				if ($billing_connection !== 'billing_countrystate' && $billing_connection !== 'billing_country' && $billing_connection !== 'billing_vat'){

					if ($field_type === 'text'){
						echo '<input type="text" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'textarea'){
						echo '<textarea class="b2bking_custom_registration_field b2bking_custom_registration_field_textarea b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>'.esc_html($previous_value).'</textarea>';
					} else if ($field_type === 'number'){
						echo '<input type="number" step="0.00001" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'email'){
						echo '<input type="email" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'date'){
						echo '<input type="date" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'tel'){
						echo '<input type="tel" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'file'){
						echo '<input type="file" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>'.'<br />'.esc_html__('Supported file types: jpg, jpeg, png, txt, pdf, doc, docx','b2bking');

					} else if ($field_type === 'select'){
						$select_options = get_post_meta(apply_filters( 'wpml_object_id', $custom_field->ID, 'post', true ), 'b2bking_custom_field_user_choices', true);
						$select_options = explode(',', $select_options);

						echo '<select class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
							foreach ($select_options as $option){
								// check if option is simple or value is specified via option:value
								$optionvalue = explode(':', $option);
								if (count($optionvalue) === 2 ){
									// value is specified
									echo '<option value="'.esc_attr(trim($optionvalue[0])).'" '.selected(trim($optionvalue[0]), $previous_value, false).'>'.esc_html(trim($optionvalue[1])).'</option>';
								} else {
									// simple
									echo '<option value="'.esc_attr(trim($option)).'" '.selected($option, $previous_value, false).'>'.esc_html(trim($option)).'</option>';
								}
							}
						echo '</select>';
					} else if ($field_type === 'checkbox'){

						$select_options = get_post_meta($custom_field->ID, 'b2bking_custom_field_user_choices', true);
						$select_options = explode(',', $select_options);
						$i = 1;
						foreach ($select_options as $option){
							
							$previous_value = '';
							if (isset($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID).'_option_'.$i])){
								$previous_value = sanitize_text_field($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID).'_option_'.$i]);
							}
							echo '<p class="form-row">';
							echo '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">';
							echo '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox b2bking_custom_registration_field b2bking_checkbox_registration_field" value="1" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'_option_'.$i.'" '.checked(1, $previous_value, false).'>';
							echo '<span>'.trim(esc_html($option)).'</span></label></p>';

							$i++;
						}

					}

				} else if ($billing_connection === 'billing_country') {
					woocommerce_form_field( 'b2bking_custom_field_'.esc_attr($custom_field->ID), array( 'default' => $previous_value, 'type' => 'country', 'class' => array( 'b2bking_country_field_selector', 'b2bking_custom_registration_field', 'b2bking_custom_field_req_'.esc_attr($required), 'b2bking_country_field_req_'.esc_attr($required))));
					echo '<input type="hidden" id="b2bking_country_registration_field_number" name="b2bking_country_registration_field_number" value="'.esc_attr($custom_field->ID).'">';
				} else if ($billing_connection === 'billing_countrystate') {
					if (isset($_POST['billing_state'])){
						$post_billing_state = sanitize_text_field($_POST['billing_state']);
					} else {
						$post_billing_state = '';
					}
					woocommerce_form_field( 'b2bking_custom_field_'.esc_attr($custom_field->ID), array( 'default' => $previous_value, 'type' => 'country', 'class' => array( 'b2bking_country_field_selector', 'b2bking_custom_registration_field', 'b2bking_custom_field_req_'.esc_attr($required), 'b2bking_country_field_req_'.esc_attr($required))));
					woocommerce_form_field( 'billing_state', array( 'default' => $post_billing_state, 'type' => 'state', 'class' => array( 'b2bking_custom_registration_field', 'b2bking_custom_field_req_'.esc_attr($required))));
					echo '<input type="hidden" id="b2bking_country_registration_field_number" name="b2bking_country_registration_field_number" value="'.esc_attr($custom_field->ID).'">';
				} else if ($billing_connection === 'billing_vat'){
					echo '<input type="text" id="b2bking_vat_number_registration_field" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					$vat_enabled_countries = get_post_meta($custom_field->ID, 'b2bking_custom_field_VAT_countries', true);
					echo '<input type="hidden" id="b2bking_vat_number_registration_field_countries" value="'.esc_attr($vat_enabled_countries).'">';
					echo '<input type="hidden" id="b2bking_vat_number_registration_field_number" name="b2bking_vat_number_registration_field_number" value="'.esc_attr($custom_field->ID).'">';
				}
				echo '</p></div>';
			}
		}
	}

	function b2bking_custom_registration_fields_checkout(){
		global $woocommerce;    
		global $b2bking_is_b2b_registration;
		global $b2bking_is_b2b_registration_shortcode_role_id;

		if ($b2bking_is_b2b_registration_shortcode_role_id === NULL || $b2bking_is_b2b_registration_shortcode_role_id === ''){
			$b2bking_is_b2b_registration_shortcode_role_id = 'none';
		}

		// if Registration Roles dropdown is enabled (enabled by default), show custom registration roles and fields
		$registration_role_setting = intval(get_option( 'b2bking_registration_roles_dropdown_setting', 1 ));
		if ($registration_role_setting === 1 || $b2bking_is_b2b_registration === 'yes'){

			// get roles
			$custom_roles = get_posts([
			    		'post_type' => 'b2bking_custom_role',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_role_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_role_status',
		                        'value' => 1
			                ),
		            	)
			    	]);

			?>
			<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide b2bking_registration_roles_dropdown_section <?php if ($b2bking_is_b2b_registration_shortcode_role_id !== 'none'){ echo 'b2bking_registration_roles_dropdown_section_hidden'; } ?>">
				<label for="b2bking_registration_roles_dropdown">
					<?php esc_html_e('User Type','b2bking'); ?>&nbsp;<span class="required">*</span>
				</label>
				<select id="b2bking_registration_roles_dropdown" name="b2bking_registration_roles_dropdown" required>
					<?php
					foreach ($custom_roles as $role){
						echo '<option value="role_'.esc_attr($role->ID).'" '.selected($role->ID,$b2bking_is_b2b_registration_shortcode_role_id,false).'>'.esc_html(get_the_title(apply_filters( 'wpml_object_id', $role->ID, 'post', true ))).'</option>';
					}
					?>
				</select>
			</p>
			<?php
		}

		$custom_fields = array();
		// if dropdown enabled, retrieve all enabled fields. Else, show only "All Roles" fields
		if ($registration_role_setting === 1 || $b2bking_is_b2b_registration === 'yes'){
			$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
			                array(
			                	'relation' => 'OR',
        		                array(
        	                        'key' => 'b2bking_custom_field_billing_connection',
        	                        'value' => 'none'
        		                ),
        		                array(
        	                        'key' => 'b2bking_custom_field_billing_connection',
        	                        'value' => 'billing_vat'
        		                ),
        		            ),			               
		            	)
			    	]);
		}

		// show all retrieved fields
		 	foreach ($custom_fields as $custom_field){
			$billing_exclusive = intval(get_post_meta($custom_field->ID, 'b2bking_custom_field_billing_exclusive', true));
			if ($billing_exclusive !== 1){
				$field_type = get_post_meta($custom_field->ID, 'b2bking_custom_field_field_type', true);
				$field_label = get_post_meta(apply_filters( 'wpml_object_id', $custom_field->ID, 'post', true ), 'b2bking_custom_field_field_label', true);
				$field_placeholder = get_post_meta(apply_filters( 'wpml_object_id', $custom_field->ID, 'post', true ), 'b2bking_custom_field_field_placeholder', true);
				$required = get_post_meta($custom_field->ID, 'b2bking_custom_field_required', true);
				$billing_connection = get_post_meta($custom_field->ID, 'b2bking_custom_field_billing_connection', true);
				// role identifier
				$role = get_post_meta($custom_field->ID, 'b2bking_custom_field_registration_role', true);
				if ($role !== 'multipleroles'){
					$role_class = 'b2bking_custom_registration_'.esc_attr($role);
				} else {
					$field_roles = get_post_meta($custom_field->ID, 'b2bking_custom_field_multiple_roles', true);
					$roles_array = explode(',',$field_roles);
					$role_class = '';
					foreach($roles_array as $role){
						$role_class.='b2bking_custom_registration_'.esc_attr($role).' ';
					}
				}
				// if error, get previous value and show it in the fields, for user friendliness
				$previous_value = '';
				if (isset($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID)])){
					$previous_value = sanitize_text_field($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID)]);
				}

				if (intval($required) === 1){
					$required = 'required';
				} else {
					$required = '';
				}

				$vat_container = '';
				if ($billing_connection === 'billing_vat'){
					$vat_container = 'b2bking_vat_number_registration_field_container';
				}

				$class = '';
				// purely aesthethical fix, add a class to the P in countries, in order to remove the margin bottom
				if ($billing_connection === 'billing_countrystate' || $billing_connection === 'billing_country' || $billing_connection === 'billing_state'){
					$class = 'b2bking_country_or_state';
				}
				
				echo '<div class="'.esc_attr($vat_container).' b2bking_custom_registration_container '.esc_attr($role_class).'">';
				echo '<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide '.$class.'">';

				echo '<label>'.esc_html($field_label).'&nbsp;';
					if ($required === 'required'){ 
						echo '<span class="required">*</span>'; 
					}
					echo '</label>';

				// if billing connection is country, replace field with countries dropdown
				if ($billing_connection !== 'billing_countrystate' && $billing_connection !== 'billing_country' && $billing_connection !== 'billing_vat'){

					if ($field_type === 'text'){
						echo '<input type="text" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'textarea'){
						echo '<textarea class="b2bking_custom_registration_field b2bking_custom_registration_field_textarea b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>'.esc_html($previous_value).'</textarea>';
					} else if ($field_type === 'number'){
						echo '<input type="number" step="0.00001" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'email'){
						echo '<input type="email" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'date'){
						echo '<input type="date" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'tel'){
						echo '<input type="tel" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
					} else if ($field_type === 'file'){
						echo '<input type="file" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>'.'<br />'.esc_html__('Supported file types: jpg, jpeg, png, txt, pdf, doc, docx','b2bking');

					} else if ($field_type === 'select'){
						$select_options = get_post_meta($custom_field->ID, 'b2bking_custom_field_user_choices', true);
						$select_options = explode(',', $select_options);

						echo '<select class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).'>';
							foreach ($select_options as $option){
								echo '<option value="'.esc_attr($option).'" '.selected($option, $previous_value, false).'>'.esc_html($option).'</option>';
							}
						echo '</select>';
					} else if ($field_type === 'checkbox'){

						$select_options = get_post_meta($custom_field->ID, 'b2bking_custom_field_user_choices', true);
						$select_options = explode(',', $select_options);
						$i = 1;
						foreach ($select_options as $option){
							
							$previous_value = '';
							if (isset($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID).'_option_'.$i])){
								$previous_value = sanitize_text_field($_POST['b2bking_custom_field_'.esc_attr($custom_field->ID).'_option_'.$i]);
							}
							echo '<p class="form-row">';
							echo '<label class="woocommerce-form__label woocommerce-form__label-for-checkbox">';
							echo '<input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox b2bking_custom_registration_field b2bking_checkbox_registration_field" value="1" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'_option_'.$i.'" '.checked(1, $previous_value, false).'>';
							echo '<span>'.trim(esc_html($option)).'</span></label></p>';

							$i++;
						}

					}

				} else if ($billing_connection === 'billing_country') {
					woocommerce_form_field( 'b2bking_custom_field_'.esc_attr($custom_field->ID), array( 'type' => 'country', 'class' => array( 'b2bking_country_field_selector', 'b2bking_custom_registration_field', 'b2bking_custom_field_req_'.esc_attr($required), 'b2bking_country_field_req_'.esc_attr($required))));
					echo '<input type="hidden" id="b2bking_country_registration_field_number" name="b2bking_country_registration_field_number" value="'.esc_attr($custom_field->ID).'">';
				} else if ($billing_connection === 'billing_countrystate') {
					woocommerce_form_field( 'b2bking_custom_field_'.esc_attr($custom_field->ID), array( 'type' => 'country', 'class' => array( 'b2bking_country_field_selector', 'b2bking_custom_registration_field', 'b2bking_custom_field_req_'.esc_attr($required), 'b2bking_country_field_req_'.esc_attr($required))));
					woocommerce_form_field( 'billing_state', array( 'type' => 'state', 'class' => array( 'b2bking_custom_registration_field', 'b2bking_custom_field_req_'.esc_attr($required))));
					echo '<input type="hidden" id="b2bking_country_registration_field_number" name="b2bking_country_registration_field_number" value="'.esc_attr($custom_field->ID).'">';
				} else if ($billing_connection === 'billing_vat'){
					$disabled = '';
					if (isset($_COOKIE['b2bking_validated_vat_number'])){
						$previous_value = sanitize_text_field($_COOKIE['b2bking_validated_vat_number']);
						$disabled = 'readonly="readonly"';		
					}

					echo '<input type="text" id="b2bking_vat_number_registration_field" class="b2bking_custom_registration_field b2bking_custom_field_req_'.esc_attr($required).'" name="b2bking_custom_field_'.esc_attr($custom_field->ID).'" value="'.esc_attr($previous_value).'" placeholder="'.esc_attr($field_placeholder).'" '.esc_attr($required).' '.esc_attr($disabled).'>';
					$vat_enabled_countries = get_post_meta($custom_field->ID, 'b2bking_custom_field_VAT_countries', true);
					echo '<input type="hidden" id="b2bking_vat_number_registration_field_countries" value="'.esc_attr($vat_enabled_countries).'">';
					echo '<input type="hidden" id="b2bking_vat_number_registration_field_number" name="b2bking_vat_number_registration_field_number" value="'.esc_attr($custom_field->ID).'">';

					// since we are at checkout, show VALIDATE VAT NR button
					if (intval(get_option('b2bking_validate_vat_button_checkout_setting', 0)) === 1){
						$textvat = esc_html__('Validate VAT','b2bking');
						$disabled = '';
						if (isset($_COOKIE['b2bking_validated_vat_number'])){
							$textvat = esc_html__('VAT Validated Successfully', 'b2bking');
							$disabled = 'disabled';
						}
						echo '<button type="button" id="b2bking_checkout_registration_validate_vat_button" '.esc_attr($disabled).'>'.esc_html($textvat).'</button>';
					}
				}
				echo '</p></div>';
			}
		}
	}

	// validate vat button for registration disabled
	function b2bking_validate_vat_registration_disabled(){
		// if registration at checkout is disabled and validate button is enabled and there is a VAT field in billing
		if (!is_user_logged_in()){
			if ( intval(get_option('b2bking_registration_at_checkout_setting', 0)) === 0 ){
				if (intval(get_option('b2bking_validate_vat_button_checkout_setting', 0)) === 1){
				
				// check that there is a VAT field there
				// build array of groups visible
				$array_groups_visible = array(
		            'relation' => 'OR',
		        );

				if (!is_user_logged_in()){
					array_push($array_groups_visible, array(
		                'key' => 'b2bking_custom_field_multiple_groups',
		                'value' => 'group_loggedout',
		                'compare' => 'LIKE'
		            ));
				} else {
					// if user is b2c
					if (get_user_meta(get_current_user_id(),'b2bking_b2buser', true) !== 'yes'){
						array_push($array_groups_visible, array(
			                'key' => 'b2bking_custom_field_multiple_groups',
			                'value' => 'group_b2c',
			                'compare' => 'LIKE'
			            ));
					} else {
						array_push($array_groups_visible, array(
			                'key' => 'b2bking_custom_field_multiple_groups',
			                'value' => 'group_'.get_user_meta(get_current_user_id(),'b2bking_customergroup', true),
			                'compare' => 'LIKE'
			            ));
					}
				}
				$vat_fields = get_posts([
					    		'post_type' => 'b2bking_custom_field',
					    	  	'post_status' => 'publish',
					    	  	'numberposts' => -1,
					    	  	'meta_key' => 'b2bking_custom_field_sort_number',
				    	  	    'orderby' => 'meta_value_num',
				    	  	    'order' => 'ASC',
					    	  	'meta_query'=> array(
					    	  		'relation' => 'AND',
					                array(
				                        'key' => 'b2bking_custom_field_status',
				                        'value' => 1
					                ),
	            	                array(
	                                    'key' => 'b2bking_custom_field_billing_connection',
	                                    'value' => 'billing_vat'
	            	                ),			               
					                array(
				                        'key' => 'b2bking_custom_field_add_to_billing',
				                        'value' => 1
					                ),
					                $array_groups_visible,
				            	)
					    	]);
					if (!empty($vat_fields)){
						$textvat = esc_html__('Validate VAT','b2bking');
						$disabled = '';
						if (isset($_COOKIE['b2bking_validated_vat_number'])){
							$textvat = esc_html__('VAT Validated Successfully', 'b2bking');
							$disabled = 'disabled';
						}
						echo '<button type="button" id="b2bking_checkout_registration_validate_vat_button" '.esc_attr($disabled).'>'.esc_html($textvat).'</button>';
					}
				}
			}
		}
	}

	// Save Custom Registration Fields
	function b2bking_save_custom_registration_fields($user_id){

		if (get_user_meta($user_id, 'b2bking_registration_data_saved', true) === 'yes'){
			// function has already run
			return;
		} else {
			update_user_meta($user_id,'b2bking_registration_data_saved', 'yes');
		}

		// not relevant if this is a dokan seller
		if (isset($_POST['role'])){
			if (sanitize_text_field($_POST['role']) === 'seller'){
				return;
			}
		}

		$custom_fields_string = '';

		// get all enabled custom fields
		$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
	  		    	  	'meta_key' => 'b2bking_custom_field_sort_number',
	  	    	  	    'orderby' => 'meta_value_num',
	  	    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),

		            	)
			    	]);
		// loop through fields
		foreach ($custom_fields as $field){

			// if field is checkbox, check checkbox options and save them
			$field_type = get_post_meta($field->ID, 'b2bking_custom_field_field_type', true);

			if ($field_type === 'checkbox'){

				// add field to fields string
				$custom_fields_string .= $field->ID.',';

				$select_options = get_post_meta($field->ID, 'b2bking_custom_field_user_choices', true);
				$select_options = explode(',', $select_options);
				$i = 1;
				foreach ($select_options as $option){

					// get field and check if set
					$field_value = sanitize_text_field(filter_input(INPUT_POST, 'b2bking_custom_field_'.$field->ID.'_option_'.$i)); 
					if (intval($field_value) === 1){
						update_user_meta( $user_id, 'b2bking_custom_field_'.$field->ID.'_option_'.$i, $option);
						// if have a selected value, give a value of 1 to the field, so we know to display it in the backend
						update_user_meta( $user_id, 'b2bking_custom_field_'.$field->ID, 1);
					}
					$i++;
				}

			}

			// get field and check if set
			$field_value = sanitize_text_field(filter_input(INPUT_POST, 'b2bking_custom_field_'.$field->ID)); 
			if ($field_value !== NULL && $field_type !== 'checkbox'){
				update_user_meta( $user_id, 'b2bking_custom_field_'.$field->ID, $field_value);

				// Also set related field data as user meta.
				// Relevant fields: field type, label and user_choices

				// add field to fields string
				$custom_fields_string .= $field->ID.',';

				$field_type = get_post_meta($field->ID, 'b2bking_custom_field_field_type', true);
				$field_label = get_post_meta($field->ID, 'b2bking_custom_field_field_label', true);
				if ($field_type === 'file' ){
					if ( ! empty( $_FILES['b2bking_custom_field_'.$field->ID]['name'] ) ){
					// has already been checked for errors (type/size) in b2bking_custom_registration_fields_check_errors function
				        require_once( ABSPATH . 'wp-admin/includes/image.php' );
						require_once( ABSPATH . 'wp-admin/includes/file.php' );
						require_once( ABSPATH . 'wp-admin/includes/media.php' );

				        // Upload the file
				        $attachment_id = media_handle_upload( 'b2bking_custom_field_'.$field->ID, 0 );
				        // Set attachment author as the user who uploaded it
				        $attachment_post = array(
				            'ID'          => $attachment_id,
				            'post_author' => $user_id
				        );
				        wp_update_post( $attachment_post );   

				        // set attachment id as user meta
				        update_user_meta( $user_id, 'b2bking_custom_field_'.$field->ID, $attachment_id );
				    }
				}

				// if field has billing connection, update billing user meta
				$billing_connection = get_post_meta($field->ID, 'b2bking_custom_field_billing_connection', true);
				if ($billing_connection !== 'none'){
					// special situation for countrystate combined field
					if($billing_connection === 'billing_countrystate'){
						update_user_meta ($user_id, 'billing_country', $field_value);
						// get state as well 
						$state_value = sanitize_text_field(filter_input(INPUT_POST, 'billing_state')); 
						update_user_meta ($user_id, 'billing_state', $state_value);
					} else {
						// field value name is identical to billing user meta field name
						if ($billing_connection !== 'custom_mapping'){
							update_user_meta ($user_id, $billing_connection, $field_value);
						} else {
							update_user_meta ($user_id, sanitize_text_field(get_post_meta($field->ID, 'b2bking_custom_field_mapping', true)), $field_value);
						}
						// if field is first name or last name, add it to account details (Sync)
						if ($billing_connection === 'billing_first_name'){
							update_user_meta( $user_id, 'first_name', $field_value );
						} else if ($billing_connection === 'billing_last_name'){
							update_user_meta( $user_id, 'last_name', $field_value );
						}
					}
				}
			}
		}

		// set string of custom field ids as meta
		if ($custom_fields_string !== ''){
			update_user_meta( $user_id, 'b2bking_custom_fields_string', $custom_fields_string);
		}

		// if user role dropdown enabled, also set user registration role as meta
		if (isset($_POST['b2bking_registration_roles_dropdown'])){
			$user_role = sanitize_text_field(filter_input(INPUT_POST, 'b2bking_registration_roles_dropdown'));
			if ($user_role !== NULL){
				update_user_meta( $user_id, 'b2bking_registration_role', $user_role);
			}
		}

		// if VIES VAT Validation is Enabled AND VAT field is not empty, set vies-validated vat meta
		if (isset($_POST['b2bking_vat_number_registration_field_number'])){
			$vat_number_inputted = sanitize_text_field($_POST['b2bking_custom_field_'.$_POST['b2bking_vat_number_registration_field_number']]);
			$vat_number_inputted = strtoupper(str_replace(array('.', ' '), '', $vat_number_inputted));
			if (!(empty($vat_number_inputted))){
				// check if VIES Validation is enabled in settings
				$vat_field_vies_validation_setting = get_post_meta($_POST['b2bking_vat_number_registration_field_number'], 'b2bking_custom_field_VAT_VIES_validation', true);
				// proceed only if VIES validation is enabled
				if (intval($vat_field_vies_validation_setting) === 1){
					update_user_meta($user_id, 'b2bking_user_vat_status', 'validated_vat');
				}

				// if cookie, set validate vat also
				if (isset($_COOKIE['b2bking_validated_vat_status'])){
					update_user_meta($user_id, 'b2bking_user_vat_status', sanitize_text_field($_COOKIE['b2bking_validated_vat_status']));
				}
			}
		}

		// if settings require approval on all users OR chosen user role requires approval
		if (intval(get_option('b2bking_approval_required_all_users_setting', 0)) === 1){
			update_user_meta( $user_id, 'b2bking_account_approved', 'no');

		} else if (isset($_POST['b2bking_registration_roles_dropdown'])){
			$user_role = sanitize_text_field(filter_input(INPUT_POST, 'b2bking_registration_roles_dropdown'));
			$user_role_id = explode('_', $user_role)[1];
			$user_role_approval = get_post_meta($user_role_id, 'b2bking_custom_role_approval', true);
			$user_role_automatic_customer_group = get_post_meta($user_role_id, 'b2bking_custom_role_automatic_approval_group', true);

			if ($user_role_approval === 'manual'){
				update_user_meta( $user_id, 'b2bking_account_approved', 'no');
				// check if there is a setting to automatically send the user to a particular customer group
				if ($user_role_automatic_customer_group !== 'none' && $user_role_automatic_customer_group !== NULL && $user_role_automatic_customer_group !== ''){
					update_user_meta($user_id,'b2bking_default_approval_manual', $user_role_automatic_customer_group);
				}
			} else if ($user_role_approval === 'automatic'){
				// check if there is a setting to automatically send the user to a particular customer group
				if ($user_role_automatic_customer_group !== 'none' && $user_role_automatic_customer_group !== NULL && $user_role_automatic_customer_group !== ''){
					$group_id = explode('_',$user_role_automatic_customer_group)[1];
					update_user_meta( $user_id, 'b2bking_customergroup', sanitize_text_field($group_id));
					$user_obj = new WP_User($user_id);
					$user_obj->add_role('b2bking_role_'.$group_id);
				}
			}
		}

		// if customer is being approved automatically, and group is other than none, set customer as B2B
		$user_role = sanitize_text_field(filter_input(INPUT_POST, 'b2bking_registration_roles_dropdown'));
		$user_role_id = explode('_', $user_role)[1];
		$user_role_approval = get_post_meta($user_role_id, 'b2bking_custom_role_approval', true);
		if ($user_role_approval === 'automatic'){
			if ($user_role_automatic_customer_group !== 'none' && metadata_exists('post', $user_role_id, 'b2bking_custom_role_automatic_approval_group')){
				update_user_meta($user_id, 'b2bking_b2buser', 'yes');
			} else {
				// user must be b2c, add b2c role
				$user_obj = new WP_User($user_id);
				$user_obj->add_role('b2bking_role_b2cuser');
			}
		}

		$user_is_b2b = get_user_meta($user_id,'b2bking_b2buser', true);

		if (!isset($_POST['b2bking_registration_roles_dropdown']) && $user_is_b2b !== 'yes'){
			// must be a default b2c registration, add b2c role
			$user_obj = new WP_User($user_id);
			$user_obj->add_role('b2bking_role_b2cuser');
		}

	}

	// If user approval is manual, stop automatic login on registration
	function b2bking_check_user_approval_on_registration($redirection_url) {
		$user_id = get_current_user_id();
		$user_approval = get_user_meta($user_id, 'b2bking_account_approved', true);
		$redir_change = 'no';

		if ($user_approval === 'no'){

			// for separate b2b reg
		    $separate_page = get_option( 'b2bking_registration_separate_my_account_page_setting', 'disabled' );
		    if ($separate_page !== 'disabled'){
		    	$redirection_url = get_permalink( $separate_page );
		    	$redir_change = 'yes';
		    }

			wp_logout();

			do_action( 'woocommerce_set_cart_cookies',  true );

			wc_add_notice( esc_html__('Thank you for registering. Your account requires manual approval. Please wait to be approved.', 'b2bking'), 'success' );	

		
		}


		if ($redir_change === 'no'){
			$redirection_url = get_permalink( wc_get_page_id( 'myaccount' ) );
		}


		return $redirection_url;
	}

	function b2bking_check_user_approval_on_registration_checkout($order_id) {
		$user_id = get_current_user_id();
		$user_approval = get_user_meta($user_id, 'b2bking_account_approved', true);

		if ($user_approval === 'no'){
			wp_logout();

			do_action( 'woocommerce_set_cart_cookies',  true );

			wc_add_notice( esc_html__('Thank you for registering. Your account requires manual approval. Please wait to be approved.', 'b2bking'), 'success' );			

		}
	}

	// Check registration for errors (especially file upload errors) also VAT error
	function b2bking_custom_registration_fields_check_errors( $errors, $username, $email ) {
		// get all enabled file upload custom fields
		$file_upload_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_field_type',
		                        'value' => 'file'
			                ),
		            	)
			    	]);

		foreach($file_upload_fields as $file_upload_field){
			// get field and check if set
			$field_value = sanitize_text_field(filter_input(INPUT_POST, 'b2bking_custom_field_'.$file_upload_field->ID)); 
			if ($field_value !== NULL){
				// Allowed file types
				$allowed_file_types = array( "image/jpeg", "image/jpg", "image/png", "text/plain", "application/pdf", "application/msword", "application/vnd.openxmlformats-officedocument.wordprocessingml.document", "application/octet-stream" );
				// Allowed file size -> 5MB
				$allowed_file_size = 5000000;
				$upload_errors = '';
				// Check if has a file 
				if ( ! empty( $_FILES['b2bking_custom_field_'.$file_upload_field->ID]['name'] ) ) {
				    // Check file type
				    if ( ! in_array( $_FILES['b2bking_custom_field_'.$file_upload_field->ID]['type'], $allowed_file_types ) ) {
				        $upload_errors .= '<p>'.esc_html__('Invalid file type','b2bking').': ' . 
				                          $_FILES['b2bking_custom_field_'.$file_upload_field->ID]['type'] . 
				                          '. '.esc_html__('Supported file types','b2bking').': jpg, jpeg, png, txt, pdf, doc, docx </p>';
				    }
				    // Check file size
				    if ( $_FILES['b2bking_custom_field_'.$file_upload_field->ID]['size'] > $allowed_file_size ) {
				        $upload_errors .= '<p>'.esc_html__('File is too large. Max. upload file size is','b2bking').' 5MB</p>';
				    }
				    // If errors, show errors
				    if (! empty( $upload_errors ) ) {
				    	$errors->add( 'username_error', esc_html($upload_errors) );
				    }
				}
			}
		}
		if (isset($_POST['b2bking_vat_number_registration_field_number'])){
			$vat_number_inputted = sanitize_text_field($_POST['b2bking_custom_field_'.$_POST['b2bking_vat_number_registration_field_number']]);
		} else {
			$vat_number_inputted = '';
		}

		if (isset($_POST['b2bking_country_registration_field_number'])){
			$country_inputted = sanitize_text_field($_POST['b2bking_custom_field_'.$_POST['b2bking_country_registration_field_number']]);
		} else {
			$country_inputted = '';
		}

		if (!(empty($vat_number_inputted))){

			// check if VIES Validation is enabled in settings
			$vat_field_vies_validation_setting = get_post_meta($_POST['b2bking_vat_number_registration_field_number'], 'b2bking_custom_field_VAT_VIES_validation', true);

			// proceed only if VIES validation is enabled
			if (intval($vat_field_vies_validation_setting) === 1){
				$error_details = '';
				$validation = new stdClass();
				$validation -> valid = 1;
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
							$errors->add( 'username_error', esc_html__('VAT Number you entered is for a different country than the country you selected', 'b2bking'));
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
					// VAT IS VALID
				} else {
					$errors->add( 'username_error', esc_html__('VAT Number is Invalid:', 'b2bking').' '.esc_html($error_details) );
				}

			}

		}

	return $errors;
	}
	

	function b2bking_display_custom_registration_fields(){

		$user_id = get_current_user_id();

		// Get all enabled editable fields
		$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
			                array(
		                        'key' => 'b2bking_custom_field_editable',
		                        'value' => 1
			                ),
			                // show in My Account only fields with no billing connection OR VAT (billing connection fields are already shown by default by WooCommerce)
			                array(
    			    	  		'relation' => 'OR',
    			                array(
    		                        'key' => 'b2bking_custom_field_billing_connection',
    		                        'value' => 'none'
    			                ),
    			                array(
    		                        'key' => 'b2bking_custom_field_billing_connection',
    		                        'value' => 'billing_vat'
    			                ),
			                )
		            	)
			    	]);

		// loop through fields
		foreach ($custom_fields as $field){

			$field_type = get_post_meta($field->ID, 'b2bking_custom_field_field_type', true);

			if ($field_type !== 'file' && $field_type !== 'checkbox'){
				// get field data
				$field_label = get_post_meta($field->ID, 'b2bking_custom_field_field_label', true);
				$field_user_choices = get_post_meta($field->ID, 'b2bking_custom_field_user_choices', true);
				// get value (from registration
				$field_value = get_user_meta($user_id, 'b2bking_custom_field_'.$field->ID, true);
				if ($field_value === null){
					$field_value = '';
				}

				// display label
				echo '<label>'.esc_html($field_label).'</label>';

				// display field
				if ($field_type === 'text'){
					echo '<input type="text" class="b2bking_custom_registration_field" name="b2bking_custom_field_'.esc_attr($field->ID).'" value="'.esc_attr($field_value).'"><br /><br />';
				} else if ($field_type === 'number'){
					echo '<input type="number" step="0.00001" class="b2bking_custom_registration_field" name="b2bking_custom_field_'.esc_attr($field->ID).'" value="'.esc_attr($field_value).'"><br /><br />';
				} else if ($field_type === 'email'){
					echo '<input type="email" class="b2bking_custom_registration_field" name="b2bking_custom_field_'.esc_attr($field->ID).'" value="'.esc_attr($field_value).'"><br /><br />';
				} else if ($field_type === 'date'){
					echo '<input type="date" class="b2bking_custom_registration_field" name="b2bking_custom_field_'.esc_attr($field->ID).'" value="'.esc_attr($field_value).'"><br /><br />';
				} else if ($field_type === 'tel'){
					echo '<input type="tel" class="b2bking_custom_registration_field" name="b2bking_custom_field_'.esc_attr($field->ID).'" value="'.esc_attr($field_value).'"><br /><br />';
				} else if ($field_type === 'textarea'){
					echo '<textarea class="b2bking_custom_registration_field" name="b2bking_custom_field_'.esc_attr($field->ID).'">'.esc_html($field_value).'</textarea><br /><br />';
				} else if ($field_type === 'select'){
					$user_options = explode(',', $field_user_choices);
					echo '<select class="b2bking_custom_registration_field" name="b2bking_custom_field_'.esc_attr($field->ID).'">';
					foreach ($user_options as $option){
						if ($option !== NULL && $option !== ''){
							// check if option is simple or value is specified via option:value
							$optionvalue = explode(':', $option);
							if (count($optionvalue) === 2 ){
								// value is specified
								echo '<option value="'.esc_attr(trim($optionvalue[0])).'" '.selected(trim($optionvalue[0]), $field_value, false).'>'.esc_html(trim($optionvalue[1])).'</option>';
							} else {
								// simple
								echo '<option value="'.esc_attr(trim($option)).'" '.selected(trim($option),trim($field_value),false).'>'.esc_html(trim($option)).'</option>';
							}
						}
					}
					echo '</select>
					<br /><br />';
				}
			}
		}
	}

	function b2bking_user_is_in_list($user_data_current_user_id, $user_data_current_user_b2b, $user_data_current_user_group, $list){
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

	// Save custom registration fields after edit
	function b2bking_save_custom_registration_fields_edit(){
		$user_id = get_current_user_id();
		$account_type = get_user_meta($user_id,'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			// for all intents and purposes set current user as the subaccount parent
			$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
			$user_id = $parent_user_id;
		}


	    $user = get_user_by('id', $user_id) -> user_login;

		// Get all enabled editable fields
		$custom_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
			                array(
		                        'key' => 'b2bking_custom_field_editable',
		                        'value' => 1
			                ),

		            	)
			    	]);

		// loop through fields
		foreach ($custom_fields as $field){
			// get field and check if set
			$field_value = sanitize_text_field(filter_input(INPUT_POST, 'b2bking_custom_field_'.$field->ID)); 
			$billing_connection = get_post_meta($field->ID,'b2bking_custom_field_billing_connection', true);

			if ($field_value !== NULL){
				

				if ($billing_connection === 'billing_vat'){
					if (!empty($field_value)){
						update_user_meta( $user_id, 'b2bking_custom_field_'.$field->ID, $field_value);
					}
					// check if VIES Validaiton is enabled
					$vat_field_vies_validation_setting = get_post_meta($field->ID, 'b2bking_custom_field_VAT_VIES_validation', true);
					if (intval($vat_field_vies_validation_setting) === 1){
						// has already been validated in b2bking_save_custom_registration_fields_validate function
						// set vat validation status to "validated_vat"
						update_user_meta( $user_id, 'b2bking_user_vat_status', 'validated_vat');
					}
				} else {
					update_user_meta( $user_id, 'b2bking_custom_field_'.$field->ID, $field_value);
				}
			}
		}
	}


	function b2bking_save_custom_registration_fields_validate( $errors ){
		/* If there is vat, validate VAT */
		// Get VAT field
		$vat_fields = get_posts([
			    		'post_type' => 'b2bking_custom_field',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'meta_key' => 'b2bking_custom_field_sort_number',
		    	  	    'orderby' => 'meta_value_num',
		    	  	    'order' => 'ASC',
			    	  	'meta_query'=> array(
			    	  		'relation' => 'AND',
			                array(
		                        'key' => 'b2bking_custom_field_status',
		                        'value' => 1
			                ),
			                array(
		                        'key' => 'b2bking_custom_field_editable',
		                        'value' => 1
			                ),
			                array(
		                        'key' => 'b2bking_custom_field_billing_connection',
		                        'value' => 'billing_vat'
			                ),

		            	)
			    	]);

		foreach ($vat_fields as $vat_field) { // should be only one

		    if ( isset( $_POST['b2bking_custom_field_'.$vat_field->ID] ) ) {

		    	if (!empty($_POST['b2bking_custom_field_'.$vat_field->ID])){

		    		// if VIES Validation is enabled perform new VIES Validation 
		    		$vat_field_vies_validation_setting = get_post_meta($vat_field->ID, 'b2bking_custom_field_VAT_VIES_validation', true);
		    		if (intval($vat_field_vies_validation_setting) === 1){

		    			// check vat
		    			$vat_number_inputted = sanitize_text_field($_POST['b2bking_custom_field_'.$vat_field->ID]);
		    			$vat_number_inputted = strtoupper(str_replace(array('.', ' '), '', $vat_number_inputted));

		    			$error_details = '';
		    			try {
		    				$client = new SoapClient("http://ec.europa.eu/taxation_customs/vies/checkVatService.wsdl");
		    				$country_code = substr($vat_number_inputted, 0, 2); // take first 2 chars
		    				$vat_number = substr($vat_number_inputted, 2); // remove first 2 chars

		    				$validation = $client->checkVat(array(
		    				  'countryCode' => $country_code,
		    				  'vatNumber' => $vat_number
		    				));
		    				$error_details = '';

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
		    			}

		    			if(isset($validation)){
		    				if (intval($validation->valid) === 1){
		    					// VAT IS VALID
		    				} else {
		    					wc_add_notice( esc_html__( 'VAT Number is invalid ', 'b2bking' ).$error_details, 'error' );
		    				}
		    			} else {
		    				wc_add_notice( esc_html__( 'VAT Number is invalid ', 'b2bking' ).$error_details, 'error' );
		    			}

		    		}
		    	}
		
		    }
		}
	} 


	// Allow file upload in registration for WooCommerce
	function b2bking_custom_registration_fields_allow_file_upload() {
	   	echo 'enctype="multipart/form-data"';
	}


	// Disable shipping methods based on user settings (group)
	function b2bking_disable_shipping_methods( $rates ){

		$user_id = get_current_user_id();
		$account_type = get_user_meta($user_id,'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			// for all intents and purposes set current user as the subaccount parent
			$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
			$user_id = $parent_user_id;
		}

		$available = array();
		// if user is guest, disable shipping methods by guest group options
		if (intval($user_id) === 0){

			// For each shipping method, check if it's available. Add it to available options
			foreach ( $rates as $rate_id => $rate ) {
				$user_access = get_option('b2bking_logged_out_users_shipping_method_'.$rate->method_id.$rate->instance_id, 1);
				if (intval($user_access) === 1){
					$available[ $rate_id ] = $rate;
				}
			}

		// else if user is B2C, disable by B2C group options
		} else if (get_user_meta($user_id, 'b2bking_b2buser', true ) !== 'yes'){

			// For each shipping method, check if it's available. Add it to available options
			foreach ( $rates as $rate_id => $rate ) {
				$user_access = get_option('b2bking_b2c_users_shipping_method_'.$rate->method_id.$rate->instance_id, 1);
				if (intval($user_access) === 1){
					$available[ $rate_id ] = $rate;
				}
			}

		// else it means user is B2B so follow B2B rules
		} else {

			// if user override activated, check user access, else check group access
			$user_override = get_user_meta($user_id, 'b2bking_user_shipping_payment_methods_override', true);
			if ($user_override === 'manual'){
				// follow user rules

				// For each shipping method, check if it's available to the current user. Add it to available options
				foreach ( $rates as $rate_id => $rate ) {
					$user_access = get_user_meta($user_id, 'b2bking_user_shipping_method_'.$rate->method_id.$rate->instance_id, true);
					if (intval($user_access) === 1){
						$available[ $rate_id ] = $rate;
					}
				}

			} else {
				// follow group rules
				$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );

				// For each shipping method, check if it's available to the current user's group. Add it to available options
				foreach ( $rates as $rate_id => $rate ) {
					$group_access = get_post_meta($currentusergroupidnr, 'b2bking_group_shipping_method_'.$rate->method_id.$rate->instance_id, true);
					if (intval($group_access) === 1){
						$available[ $rate_id ] = $rate;
					}
				}
			}
		}

		return $available;

	}

	// Disable payment methods based on user settings (group)
	function b2bking_disable_payment_methods($gateways){
	    global $woocommerce;
    	$user_id = get_current_user_id();
    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
    	if ($account_type === 'subaccount'){
    		// for all intents and purposes set current user as the subaccount parent
    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
    		$user_id = $parent_user_id;
    	}

    	// if user is guest, disable shipping methods by guest group options
    	if (intval($user_id) === 0){

    		foreach ($gateways as $gateway_id => $gateway_value){
    			$user_access = get_option('b2bking_logged_out_users_payment_method_'.$gateway_id, 1);
    			if (intval($user_access) !== 1){
    				unset($gateways[$gateway_id]);
    			}
    		}

    	// else if user is B2C, disable by B2C group options
    	} else if (get_user_meta($user_id, 'b2bking_b2buser', true ) !== 'yes'){

    		foreach ($gateways as $gateway_id => $gateway_value){
    			$user_access = get_option('b2bking_b2c_users_payment_method_'.$gateway_id, 1);
    			if (intval($user_access) !== 1){
    				unset($gateways[$gateway_id]);
    			}
    		}

    	// else it means user is B2B so follow B2B rules
    	} else {

		    // if user override activated, check user access, else check group access
			$user_override = get_user_meta($user_id, 'b2bking_user_shipping_payment_methods_override', true);
			if ($user_override === 'manual'){

				// follow user rules
				foreach ($gateways as $gateway_id => $gateway_value){
					$user_access = get_user_meta($user_id, 'b2bking_user_payment_method_'.$gateway_id, true);
					if (intval($user_access) !== 1){
						unset($gateways[$gateway_id]);
					}
				}

			} else {

				// follow group rules
			    $currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );

				foreach ($gateways as $gateway_id => $gateway_value){
					$group_access = get_post_meta($currentusergroupidnr, 'b2bking_group_payment_method_'.$gateway_id, true);
					if (intval($group_access) !== 1){
						unset($gateways[$gateway_id]);
					}
				}
			}
		}

	    return $gateways;
	}


	function b2bking_disable_payment_methods_dynamic_rule($gateways){

			$user_id = get_current_user_id();
	    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
	    	if ($account_type === 'subaccount'){
	    		// for all intents and purposes set current user as the subaccount parent
	    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
	    		$user_id = $parent_user_id;
	    	}
			$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );

			$array_who_multiple = array(
		                'relation' => 'OR',
		                array(
		                    'key' => 'b2bking_rule_who_multiple_options',
		                    'value' => 'group_'.$currentusergroupidnr,
		                	'compare' => 'LIKE'
		                ),
		                array(
		                    'key' => 'b2bking_rule_who_multiple_options',
		                    'value' => 'user_'.$user_id,
		                    'compare' => 'LIKE'
		                ),
		            );

			if ($user_id !== 0){
				array_push($array_who_multiple, array(
	                'key' => 'b2bking_rule_who_multiple_options',
	                'value' => 'everyone_registered',
	                'compare' => 'LIKE'
	            ));

				// add rules that apply to all registered b2b/b2c users
				$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
				if ($user_is_b2b === 'yes'){
					array_push($array_who_multiple, array(
                        'key' => 'b2bking_rule_who_multiple_options',
                        'value' => 'everyone_registered_b2b',
                        'compare' => 'LIKE'
                    ));
				} else if ($user_is_b2b === 'no'){
					array_push($array_who_multiple, array(
                        'key' => 'b2bking_rule_who_multiple_options',
                        'value' => 'everyone_registered_b2c',
                        'compare' => 'LIKE'
                    ));
				}
			}

			$array_who = array(
                'relation' => 'OR',
                array(
                    'key' => 'b2bking_rule_who',
                    'value' => 'group_'.$currentusergroupidnr
                ),
                array(
                    'key' => 'b2bking_rule_who',
                    'value' => 'user_'.$user_id
                ),
                array(
                    'relation' => 'AND',
                    array(
                        'key' => 'b2bking_rule_who',
                        'value' => 'multiple_options'
                    ),
                    $array_who_multiple
                ),
            );
			// if user is registered, also select rules that apply to all registered users
			if ($user_id !== 0){
				array_push($array_who, array(
		                        'key' => 'b2bking_rule_who',
		                        'value' => 'everyone_registered'
		                    ));

				// add rules that apply to all registered b2b/b2c users
				$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
				if ($user_is_b2b === 'yes'){
					array_push($array_who, array(
		                        'key' => 'b2bking_rule_who',
		                        'value' => 'everyone_registered_b2b'
		                    ));
				} else if ($user_is_b2b === 'no'){
					array_push($array_who, array(
		                        'key' => 'b2bking_rule_who',
		                        'value' => 'everyone_registered_b2c'
		                    ));
				}
			}

			// Get all dynamic rules that apply to the user or user's group
			$pmmu_user_ids = get_option('b2bking_have_pmmu_rules_list_ids', '');
			if (!empty($pmmu_user_ids)){
				$pmmu_user_ids = explode(',',$pmmu_user_ids);
			} else {
				$pmmu_user_ids = array();
			}
				
			$pmmu_rules = get_transient('b2bking_pmmu_user_'.get_current_user_id());
			if (!$pmmu_rules){

				if (empty($pmmu_user_ids)){
					$pmmu_user_ids = array(98765432123456789);
				}

				$pmmu_rules = get_posts([
					'post_type' => 'b2bking_rule',
					'post_status' => 'publish',
		    		'post__in' => $pmmu_user_ids,
		    		'fields'        => 'ids', // Only get post IDs
		    	  	'numberposts' => -1,
		    	  	'meta_query'=> array(
		                $array_who,
		            )
		    	]);
				set_transient ('b2bking_pmmu_user_'.get_current_user_id(), $pmmu_rules);
			}
			
	    	// if there are pmmu rules
	    	if (!empty($pmmu_rules)){
	    		foreach ($gateways as $gateway_id => $gateway_value){
	    			$minimum = 'no';
	    			// for each rule, check minimum, and find lowest minimum
	    			foreach ($pmmu_rules as $rule){
	    				// check if rule applies to gateway
	    				$rule_paymentmethod = get_post_meta($rule, 'b2bking_rule_paymentmethod', true);
	    				if ($gateway_id === $rule_paymentmethod){
	    					// gateway applies, check minimum
	    					$minimumrule = get_post_meta($rule, 'b2bking_rule_howmuch', true);
	    					if ($minimum === 'no'){
	    						$minimum = $minimumrule;
	    					} else if (floatval($minimumrule) < floatval($minimum)){
	    						$minimum = $minimumrule;
	    					}
	    				}
	    			} 

	    			if ($minimum !== 'no'){
	    				if (is_object( WC()->cart )){
		    				// check if minimum is met, and if it is, unset gateway
		    				$cart_total = WC()->cart->total;
		    				if (floatval($cart_total) < floatval($minimum)) {
		    					unset($gateways[$gateway_id]);
		    				}
		    			}
	    			}
	    		}

	    	} else {
	    		// do nothing since there are no applicable rules
	    	}

	    return $gateways;
	}


	// Change product price in cart for offers
	function b2bking_offer_change_price_cart( $_cart ){
		// loop through the cart_contents
	    foreach ( $_cart->cart_contents as $cart_item_key => $value ) {
	    	// if product is offer
	    	if (array_key_exists("b2bking_numberofproducts",$value)){
		    	if ($value['b2bking_numberofproducts'] !== NULL){
			    	$bundleprice = 0;
			    	$numberofproducts = $value['b2bking_numberofproducts'];	    	
			    	for ($i=1;$i<=$numberofproducts;$i++){
			    		$bundleprice += intval($value['b2bking_product_'.$i.'_quantity'])*floatval($value['b2bking_product_'.$i.'_price']);
			    	}       
		            $value['data']->set_price($bundleprice);
	        	}
        	}
        }
	}

	// Change product price in minicart for offers
	function b2bking_offer_change_price_minicart( $price, $cart_item, $cart_item_key ){
		// if not offer, skip
		$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
		if (intval($cart_item['product_id']) !== $offer_id && intval($cart_item['product_id']) !== 3225464){ //3225464 is deprecated
			return $price;
		}

    	// if product is offer
    	if (array_key_exists("b2bking_numberofproducts",$cart_item)){
	    	if ($cart_item['b2bking_numberofproducts'] !== NULL){
		    	$bundleprice = 0;
		    	$numberofproducts = $cart_item['b2bking_numberofproducts'];	    	
		    	for ($i=1;$i<=$numberofproducts;$i++){
		    		$bundleprice += intval($cart_item['b2bking_product_'.$i.'_quantity'])*floatval($cart_item['b2bking_product_'.$i.'_price']);
		    	}

		    	// adjust bundle price for tax
		    	// get offer product
		    	$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
		    	$offer_product = wc_get_product($offer_id);
		    	if( wc_prices_include_tax() && ('incl' !== get_option( 'woocommerce_tax_display_shop') || WC()->customer->is_vat_exempt())) {
		    		// if prices are entered including tax, but display is without tax, remove tax 
		    		// get tax rate for the offer product
		    		$tax_rates = WC_Tax::get_base_tax_rates( $offer_product->get_tax_class( 'unfiltered' ) ); 
		    		$taxes = WC_Tax::calc_tax( $bundleprice, $tax_rates, true ); 
		    		$bundleprice = WC_Tax::round( $bundleprice - array_sum( $taxes ) ); 

		    	} else if ( !wc_prices_include_tax() && ('incl' === get_option( 'woocommerce_tax_display_shop') && !WC()->customer->is_vat_exempt())){
		    		// if prices are entered excluding tax, but display is with tax, add tax
		    		$tax_rates = WC_Tax::get_rates( $offer_product->get_tax_class() );
		    		$taxes     = WC_Tax::calc_tax( $bundleprice, $tax_rates, false );
		    		$bundleprice = WC_Tax::round( $bundleprice + array_sum( $taxes ) );
		    	} else {
		    		// no adjustment
		    	}

	            return wc_price($bundleprice);
        	}
    	}
	}

	function b2bking_hide_offer_post($query) {
		$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
		$current_exclude = $query->query_vars['post__not_in'];
		if (is_array($current_exclude)){
			$query->query_vars['post__not_in'] = array_merge(array($offer_id, 3225464), $current_exclude); //3225464 is deprecated
		} else {
        	$query->query_vars['post__not_in'] = array($offer_id, 3225464); //3225464 is deprecated
    	}
	}	

	// Add item metadata to order
	function b2bking_add_item_metadata_to_order( $item, $cart_item_key, $values, $order ) {

		if (isset($values['b2bking_offer_name'])){
		    $item->update_meta_data( esc_html__('Offer name','b2bking'), esc_html($values['b2bking_offer_name']) );
		    // add products to details string
		    $details = '';
		    for ($i=1; $i<=intval($values['b2bking_numberofproducts']); $i++){

		    	$unit_price_display = $values['b2bking_product_'.$i.'_price'];
		    	// adjust for tax
		    	// get offer product
		    	$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
		    	$offer_product = wc_get_product($offer_id);
		    	if( wc_prices_include_tax() && ('incl' !== get_option( 'woocommerce_tax_display_shop') || WC()->customer->is_vat_exempt())) {
		    		// if prices are entered including tax, but display is without tax, remove tax 
		    		// get tax rate for the offer product
		    		$tax_rates = WC_Tax::get_base_tax_rates( $offer_product->get_tax_class( 'unfiltered' ) ); 
		    		$taxes = WC_Tax::calc_tax( $unit_price_display, $tax_rates, true ); 
		    		$unit_price_display = WC_Tax::round( $unit_price_display - array_sum( $taxes ) ); 

		    	} else if ( !wc_prices_include_tax() && ('incl' === get_option( 'woocommerce_tax_display_shop') && !WC()->customer->is_vat_exempt())){
		    		// if prices are entered excluding tax, but display is with tax, add tax
		    		$tax_rates = WC_Tax::get_rates( $offer_product->get_tax_class() );
		    		$taxes     = WC_Tax::calc_tax( $unit_price_display, $tax_rates, false );
		    		$unit_price_display = WC_Tax::round( $unit_price_display + array_sum( $taxes ) );
		    	} else {
		    		// no adjustment
		    	}


		    	$details .= $values['b2bking_product_'.$i.'_name'].' - '.esc_html__('Qty','b2bking').': '.$values['b2bking_product_'.$i.'_quantity'].' - '.esc_html__('Unit Price','b2bking').': '.round($unit_price_display, wc_get_price_decimals() ).' <br />';
		    }

		    $item->update_meta_data( esc_html__('Details','b2bking'), $details);
	    }
	}

	function b2bking_display_metadata_cart($product_name, $values, $cart_item_key ) {
		// If product is an offer
		if (!empty($values['b2bking_numberofproducts'])){
			$details = '';
			for ($i=1; $i<=intval($values['b2bking_numberofproducts']); $i++){
				// adjust unit price for tax
				$unit_price_display = $values['b2bking_product_'.$i.'_price'];
				// get offer product
				$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
				$offer_product = wc_get_product($offer_id);
				if( wc_prices_include_tax() && ('incl' !== get_option( 'woocommerce_tax_display_shop') || WC()->customer->is_vat_exempt())) {
					// if prices are entered including tax, but display is without tax, remove tax 
					// get tax rate for the offer product
					$tax_rates = WC_Tax::get_base_tax_rates( $offer_product->get_tax_class( 'unfiltered' ) ); 
					$taxes = WC_Tax::calc_tax( $unit_price_display, $tax_rates, true ); 
					$unit_price_display = WC_Tax::round( $unit_price_display - array_sum( $taxes ) ); 

				} else if ( !wc_prices_include_tax() && ('incl' === get_option( 'woocommerce_tax_display_shop') && !WC()->customer->is_vat_exempt())){
					// if prices are entered excluding tax, but display is with tax, add tax
					$tax_rates = WC_Tax::get_rates( $offer_product->get_tax_class() );
					$taxes     = WC_Tax::calc_tax( $unit_price_display, $tax_rates, false );
					$unit_price_display = WC_Tax::round( $unit_price_display + array_sum( $taxes ) );
				} else {
					// no adjustment
				}

				$details .= $values['b2bking_product_'.$i.'_name'].' - '.esc_html__('Qty','b2bking').': '.$values['b2bking_product_'.$i.'_quantity'].' - '.esc_html__('Unit Price','b2bking').': '.round($unit_price_display, wc_get_price_decimals() ).' <br />';
			}
			return $product_name.'<br />'.$values['b2bking_offer_name'].'<br /><strong>'.esc_html__('Details','b2bking').':</strong><br />'.$details;
		} else {
			return $product_name;
		}

	}

	// Add custom items to My account WooCommerce user menu
	function b2bking_my_account_custom_items( $items ) {
		// Get current user
		$user_id = get_current_user_id();
    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
    	if ($account_type === 'subaccount'){
    		// for all intents and purposes set current user as the subaccount parent
    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
    		$user_id = $parent_user_id;
    	}
		
		// Add conversations
		if (intval(get_option('b2bking_enable_conversations_setting', 1)) === 1){
	    	$items = array_slice($items, 0, 2, true) +
	    	    array("conversations" => esc_html__( 'Conversations', 'b2bking' )) + 
	    	    array_slice($items, 2, count($items)-2, true);
    	}

    	if (get_transient('b2bking_replace_prices_quote_user_'.$user_id) !== 'yes'){

	    	// Add offers
	    	if (intval(get_option('b2bking_enable_offers_setting', 1)) === 1){
		    	$items = array_slice($items, 0, 3, true) +
		    	    array("offers" => esc_html__( 'Offers', 'b2bking' )) + 
		    	    array_slice($items, 3, count($items)-3, true);
		    }

		    // Add purchase lists
		    if (intval(get_option('b2bking_enable_purchase_lists_setting', 1)) === 1){
			    $items = array_slice($items, 0, 4, true) +
			        array("purchase-lists" => esc_html__( 'Purchase lists', 'b2bking' )) + 
			        array_slice($items, 4, count($items)-4, true);
			}	  	    

	    	// Add bulk order
	    	if (intval(get_option('b2bking_enable_bulk_order_form_setting', 1)) === 1){
		    	$items = array_slice($items, 0, 5, true) +
		    	    array("bulkorder" => esc_html__( 'Bulk order', 'b2bking' )) + 
		    	    array_slice($items, 5, count($items)-5, true);	    
		    }

		}

    	// Add subaccounts
    	if (intval(get_option('b2bking_enable_subaccounts_setting', 1)) === 1){
    		// only show if current account is not itself a subaccount
    		if ($account_type !== 'subaccount'){
		    	$items = array_slice($items, 0, 6, true) +
		    	    array("subaccounts" => esc_html__( 'Subaccounts', 'b2bking' )) + 
		    	    array_slice($items, 6, count($items)-6, true);	
		    }
    	}

	    return $items;

	}

	// Add custom endpoints
	function b2bking_custom_endpoints() {
		
		// Add conversations endpoints
		if (intval(get_option('b2bking_enable_conversations_setting', 1)) === 1){
			add_rewrite_endpoint( 'conversations', EP_ROOT | EP_PAGES | EP_PERMALINK );
			add_rewrite_endpoint( 'conversation', EP_ROOT | EP_PAGES | EP_PERMALINK );
		}
		// Add offers endpoint
		if (intval(get_option('b2bking_enable_offers_setting', 1)) === 1){
			add_rewrite_endpoint( 'offers', EP_ROOT | EP_PAGES | EP_PERMALINK );
		}
		// Bulk order form endpoint
		if (intval(get_option('b2bking_enable_bulk_order_form_setting', 1)) === 1){
			add_rewrite_endpoint( 'bulkorder', EP_ROOT | EP_PAGES | EP_PERMALINK );
		}
		// Subaccounts 
		if (intval(get_option('b2bking_enable_subaccounts_setting', 1)) === 1){
			// only show if current account is not itself a subaccount
			$account_type = get_user_meta(get_current_user_id(),'b2bking_account_type', true);
			if ($account_type !== 'subaccount'){
				add_rewrite_endpoint( 'subaccounts', EP_ROOT | EP_PAGES | EP_PERMALINK );
				add_rewrite_endpoint( 'subaccount', EP_ROOT | EP_PAGES | EP_PERMALINK );
			}
		}
		// Purchase Lists
		if (intval(get_option('b2bking_enable_purchase_lists_setting', 1)) === 1){
			add_rewrite_endpoint( 'purchase-lists', EP_ROOT | EP_PAGES | EP_PERMALINK );
			add_rewrite_endpoint( 'purchase-list', EP_ROOT | EP_PAGES | EP_PERMALINK );
		}

	}

	function b2bking_add_query_vars_filter( $vars ) {
	  $vars[] = "id";
	  return $vars;
	}

	function b2bking_redirects_my_account_default(){

		if (isset($_SERVER['HTTPS']) &&
		        ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === 1) ||
		        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
		        $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
		        $protocol = 'https://';
		        }
		        else {
		        $protocol = 'http://';
		    }

	    $currenturl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	    $currenturl_relative = wp_make_link_relative(remove_query_arg('id',$currenturl));
	    $idqueryvar = get_query_var('id');

	    $bulkorderurl = wp_make_link_relative(wc_get_endpoint_url('bulkorder'));
	    $bulkorderurlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'bulkorder/';

	    $conversationsurl = wp_make_link_relative(wc_get_endpoint_url('conversations'));
	    $conversationsurlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'conversations/';

	    $conversationurl = wp_make_link_relative(wc_get_endpoint_url('conversation'));
	    $conversationurlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'conversation/';

	    $offersurl = wp_make_link_relative(wc_get_endpoint_url('offers'));
	    $offersurlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'offers/';

	    $subaccountsurl = wp_make_link_relative(wc_get_endpoint_url('subaccounts'));
	    $subaccountsurlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'subaccounts/';

	    $subaccounturl = wp_make_link_relative(wc_get_endpoint_url('subaccount'));
	    $subaccounturlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'subaccount/';

	    $purchaselistssurl = wp_make_link_relative(wc_get_endpoint_url('purchase-lists'));
	    $purchaselistssurlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'purchase-lists/';

	    $purchaselisturl = wp_make_link_relative(wc_get_endpoint_url('purchase-list'));
	    $purchaselisturlbuilt = wp_make_link_relative(get_permalink( get_option('woocommerce_myaccount_page_id') )).'purchase-list/';

	    $setredirect = 'no';
	    switch ($currenturl_relative) {

	    	case $bulkorderurl:
	    	case $bulkorderurlbuilt:
	    	    $urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?bulkorder';
	    	    $setredirect = 'yes';
	    	    break;

	    	case $conversationsurl:
	    	case $conversationsurlbuilt:
	    	    $urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?conversations';
	    	    $setredirect = 'yes';
	    	    break;

	    	case $purchaselistssurl:
	    	case $purchaselistssurlbuilt:
	    	    $urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?purchase-lists';
	    	    $setredirect = 'yes';
	    	    break;

	    	case $offersurl:
	    	case $offersurlbuilt:
	    	    $urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?offers';
	    	    $setredirect = 'yes';
	    	    break;

	    	case $subaccountsurl:
	    	case $subaccountsurlbuilt:
	    	    $urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?subaccounts';
	    	    $setredirect = 'yes';
	    	    break;

	    	case $subaccounturl:
	    	case $subaccounturlbuilt:
	    	  	$urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?subaccount&id='.$idqueryvar;
	    	  	$setredirect = 'yes';
	    	    break;

	    	case $purchaselisturl:
	    	case $purchaselisturlbuilt:
	    	  	$urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?purchase-list&id='.$idqueryvar;
	    	  	$setredirect = 'yes';
	    	    break;

	    	case $conversationurl:
	    	case $conversationurlbuilt:
	    	  	$urlto = get_permalink( get_option('woocommerce_myaccount_page_id') ).'?conversation&id='.$idqueryvar;
	    	  	$setredirect = 'yes';
	    	    break;

	        default:
	            return;
	    }

	    if ($setredirect === 'yes'){
	        exit( wp_redirect( $urlto ) );
	    }
		
	}


	// Conversations endpoint content
	function b2bking_conversations_endpoint_content() {

		// Get user login
		$currentuser = wp_get_current_user();
		$currentuserlogin = $currentuser -> user_login;

		$account_type = get_user_meta($currentuser->ID, 'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			// Check if user has permission to view all account conversations
			$permission_view_account_conversations = filter_var(get_user_meta($currentuser->ID, 'b2bking_account_permission_view_conversations', true), FILTER_VALIDATE_BOOLEAN); 
			if ($permission_view_account_conversations === true){
				// for all intents and purposes set current user as the subaccount parent
				$parent_user_id = get_user_meta($currentuser->ID, 'b2bking_account_parent', true);
				$currentuser = get_user_by('id', $parent_user_id);
				$currentuserlogin = $currentuser -> user_login;
			}
		}

		
		$accounts_login_array = array($currentuserlogin);

		// Add subaccounts to accounts array
		$subaccounts_list = get_user_meta($currentuser->ID, 'b2bking_subaccounts_list', true);
		$subaccounts_list = explode(',', $subaccounts_list);
		$subaccounts_list = array_filter($subaccounts_list);
		foreach ($subaccounts_list as $subaccount_id){
			$accounts_login_array[$subaccount_id] = get_user_by('id', $subaccount_id) -> user_login;
		}

		

	    // Define custom query parameters
	    $custom_query_args = array( 'post_type' => 'b2bking_conversation', // only conversations
	    					'posts_per_page' => 8,
					        'meta_query'=> array(	// only the specific user's conversations
					        	'relation' => 'OR',
			                    array(
			                        'key' => 'b2bking_conversation_user',
			                        'value' => $accounts_login_array, 
			                        'compare' => 'IN'
			                    )

			                ));

	    // Get current page and append to custom query parameters array
	    $custom_query_args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

	    // Instantiate custom query
	    $custom_query = new WP_Query( $custom_query_args );

	    // Pagination fix
	    $temp_query = NULL;
	    $wp_query   = NULL;
	    $wp_query   = $custom_query;

	    // Get Conversation Endpoint URL
	    $endpointurl = wc_get_endpoint_url('conversation');

		?>
		<div id="b2bking_myaccount_conversations_container">
			<div id="b2bking_myaccount_conversations_container_top">
				<div id="b2bking_myaccount_conversations_title">
					<?php esc_html_e('Conversations','b2bking'); ?>
				</div>
				<button type="button" id="b2bking_myaccount_make_inquiry_button">
					<svg class="b2bking_myaccount_new_conversation_button_icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 20 20">
					  <path fill="#fff" d="M18 0H2a2 2 0 00-2 2v18l4-4h14a2 2 0 002-2V2a2 2 0 00-2-2zM4 7h12v2H4V7zm8 5H4v-2h8v2zm4-6H4V4h12"/>
					</svg>
					<?php esc_html_e('New Conversation','b2bking'); ?>
				</button>
			</div>

			<!-- New conversation hidden panel-->
			<div class="b2bking_myaccount_new_conversation_container">
	            <div class="b2bking_myaccount_new_conversation_top">
	            	<div class="b2bking_myaccount_new_conversation_top_item b2bking_myaccount_new_conversation_new"><?php esc_html_e('New Conversation','b2bking'); ?></div>
	            	<div class="b2bking_myaccount_new_conversation_top_item b2bking_myaccount_new_conversation_close"><?php esc_html_e('Close X','b2bking'); ?></div>
	            </div>
	            <div class="b2bking_myaccount_new_conversation_content">
	            	<?php do_action('b2bking_start_new_conversation'); ?>
	            	<div class="b2bking_myaccount_new_conversation_content_element">
	            		<div class="b2bking_myaccount_new_conversation_content_element_text"><?php esc_html_e('Type','b2bking'); ?></div>
	            		<select id="b2bking_myaccount_conversation_type">
	            			<option value="inquiry"><?php esc_html_e('Inquiry','b2bking'); ?></option>
	            			<option value="message"><?php esc_html_e('Message','b2bking'); ?></option>
	            			<option value="quote"><?php esc_html_e('Quote Request','b2bking'); ?></option>
	            		</select>
	            	</div>
	            	<div class="b2bking_myaccount_new_conversation_content_element">
	            		<div class="b2bking_myaccount_new_conversation_content_element_text"><?php esc_html_e('Title','b2bking'); ?></div>
	            		<input type="text" id="b2bking_myaccount_title_conversation_start" placeholder="<?php esc_attr_e('Enter the title here...','b2bking') ?>">
	            	</div>
	            	<div class="b2bking_myaccount_new_conversation_content_element">
	            		<div class="b2bking_myaccount_new_conversation_content_element_text"><?php esc_html_e('Message','b2bking'); ?></div>
	            		<textarea id="b2bking_myaccount_textarea_conversation_start" placeholder="<?php esc_attr_e('Enter your message here...','b2bking') ?>"></textarea>
	            	</div>
                    <div class="b2bking_myaccount_start_conversation_bottom">
                    	<button id="b2bking_myaccount_send_inquiry_button" class="b2bking_myaccount_start_conversation_button" type="button">
                    		<svg class="b2bking_myaccount_start_conversation_button_icon" xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" viewBox="0 0 21 21">
                		  	<path fill="#fff" d="M5.243 12.454h9.21v4.612c0 .359-.122.66-.368.906-.246.245-.567.377-.964.396H5.243L1.955 21v-2.632h-.651a1.19 1.19 0 01-.907-.396A1.414 1.414 0 010 17.066V8.52c0-.358.132-.67.397-.934.264-.264.567-.387.907-.368h3.939v5.236zM19.696.002c.378 0 .69.123.936.368.245.245.368.566.368.962V9.85c0 .359-.123.66-.368.906a1.37 1.37 0 01-.936.396h-.652v2.632l-3.287-2.632H6.575v-9.82c0-.377.123-.698.368-.962.246-.264.558-.387.936-.368h11.817z"/>
                			</svg>
                    		<?php esc_html_e('Start Conversation','b2bking'); ?>
                    	</button>
                    </div>
	            </div>
	        </div>


			<?php
			// Display each conversation
			// Output custom query loop
			if ( $custom_query->have_posts() ) {
			    while ( $custom_query->have_posts() ) {
			        $custom_query->the_post();
			        global $post;

			        $conversation_title = $post->post_title;
			        $conversation_type = get_post_meta($post->ID, 'b2bking_conversation_type', true);
			        $username = get_post_meta($post->ID, 'b2bking_conversation_user', true);

			        $nr_messages = get_post_meta ($post->ID, 'b2bking_conversation_messages_number', true);
			        $last_reply_time = intval(get_post_meta ($post->ID, 'b2bking_conversation_message_'.$nr_messages.'_time', true));

			        // build time string
				    // if today
				    if((time()-$last_reply_time) < 86400){
				    	// show time
				    	$conversation_last_reply = date_i18n( 'h:i A', $last_reply_time+(get_option('gmt_offset')*3600) );
				    } else if ((time()-$last_reply_time) < 172800){
				    // if yesterday
				    	$conversation_last_reply = 'Yesterday at '.date_i18n( 'h:i A', $last_reply_time+(get_option('gmt_offset')*3600) );
				    } else {
				    // date
				    	$conversation_last_reply = date_i18n( get_option('date_format'), $last_reply_time+(get_option('gmt_offset')*3600) ); 
				    }

			        ?>
        			<div class="b2bking_myaccount_individual_conversation_container">
                        <div class="b2bking_myaccount_individual_conversation_top">
                        	<div class="b2bking_myaccount_individual_conversation_top_item"><?php esc_html_e('Title','b2bking'); ?></div>
                        	<div class="b2bking_myaccount_individual_conversation_top_item"><?php esc_html_e('Type','b2bking'); ?></div>
                        	<div class="b2bking_myaccount_individual_conversation_top_item"><?php esc_html_e('User','b2bking'); ?></div>
                        	<?php do_action('b2bking_myaccount_conversations_items_title', $post->ID); ?>
                        	<div class="b2bking_myaccount_individual_conversation_top_item"><?php esc_html_e('Last Reply','b2bking'); ?></div>
                        </div>
                        <div class="b2bking_myaccount_individual_conversation_content">
                        	<div class="b2bking_myaccount_individual_conversation_content_item"><?php echo esc_html($conversation_title); ?></div>
                        	<div class="b2bking_myaccount_individual_conversation_content_item"><?php
                        	switch ($conversation_type) {
                        	  case "inquiry":
                        	    esc_html_e('inquiry','b2bking');
                        	    break;
                        	  case "message":
                        	    esc_html_e('message','b2bking');
                        	    break;
                        	  case "quote":
                        	    esc_html_e('quote','b2bking');
                        	    break;
                        	}
                        	?></div>
                        	<div class="b2bking_myaccount_individual_conversation_content_item"><?php echo esc_html($username); ?></div>
                        	<?php do_action('b2bking_myaccount_conversations_items_content', $post->ID); ?>
                        	<div class="b2bking_myaccount_individual_conversation_content_item"><?php echo esc_html($conversation_last_reply); ?></div>
                        </div>
                        <div class="b2bking_myaccount_individual_conversation_bottom">
                        	<a href="<?php echo esc_url(add_query_arg('id',$post->ID,$endpointurl)); ?>">
	                        	<button class="b2bking_myaccount_view_conversation_button" type="button">
	                        		<svg class="b2bking_myaccount_view_conversation_button_icon" xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" viewBox="0 0 21 21">
	                        		  <path fill="#fff" d="M5.243 12.454h9.21v4.612c0 .359-.122.66-.368.906-.246.245-.567.377-.964.396H5.243L1.955 21v-2.632h-.651a1.19 1.19 0 01-.907-.396A1.414 1.414 0 010 17.066V8.52c0-.358.132-.67.397-.934.264-.264.567-.387.907-.368h3.939v5.236zM19.696.002c.378 0 .69.123.936.368.245.245.368.566.368.962V9.85c0 .359-.123.66-.368.906a1.37 1.37 0 01-.936.396h-.652v2.632l-3.287-2.632H6.575v-9.82c0-.377.123-.698.368-.962.246-.264.558-.387.936-.368h11.817z"/>
	                        		</svg>
	                        		<?php esc_html_e('View Conversation','b2bking'); ?>
	                        	</button>
	                        </a>
                        </div>
        	        </div>

			        <?php

			    }
			} else {
				wc_print_notice(esc_html__('No conversations exist.', 'b2bking'), 'notice');
			}

			?>

		</div>

		<?php
		
	    // Reset postdata
	    wp_reset_postdata();
	    ?>
	   	<div class="b2bking_myaccount_conversations_pagination_container">
		    <div class="b2bking_myaccount_conversations_pagination_button b2bking_newer_conversations_button">
		    	<?php previous_posts_link( esc_html__('← Newer conversations','b2bking') ); ?>
		    </div>
		    <div class="b2bking_myaccount_conversations_pagination_button b2bking_older_conversations_button">
		    	<?php next_posts_link( esc_html__('Older conversations →','b2bking'), $custom_query->max_num_pages ); ?>
		    </div>
		</div>
	    <?php

	    // Reset main query object
	    $wp_query = NULL;
	    $wp_query = $temp_query;

	}


	// Individual conversation endpoint
	function b2bking_conversation_endpoint_content() {

		$conversation_id = sanitize_text_field( $_GET['id'] );
		$conversation_title = get_the_title($conversation_id);
		$conversation_type = get_post_meta($conversation_id, 'b2bking_conversation_type',true);
        $starting_time = intval(get_post_meta ($conversation_id, 'b2bking_conversation_message_1_time', true));

        // build time string
	    // if today
	    if((time()-$starting_time) < 86400){
	    	// show time
	    	$conversation_started_time = date_i18n( 'h:i A', $starting_time+(get_option('gmt_offset')*3600));
	    } else if ((time()-$starting_time) < 172800){
	    // if yesterday
	    	$conversation_started_time = 'Yesterday at '.date_i18n( 'h:i A', $starting_time+(get_option('gmt_offset')*3600) );
	    } else {
	    // date
	    	$conversation_started_time = date_i18n( get_option('date_format'), $starting_time+(get_option('gmt_offset')*3600) ); 
	    }

		// Get Conversations Endpoint URL
		$endpointurl = wc_get_endpoint_url('conversations');

		?>
		<div id="b2bking_myaccount_conversation_endpoint_container">
			<div id="b2bking_myaccount_conversation_endpoint_container_top">
				<div id="b2bking_myaccount_conversation_endpoint_title">
					<?php echo esc_html($conversation_title); ?>
				</div>
				<a href="<?php echo esc_url($endpointurl); ?>">
					<button type="button">
						<?php esc_html_e('←  Go Back','b2bking'); ?>
					</button>
				</a>
			</div>
			<div id="b2bking_myaccount_conversation_endpoint_container_top_header">
				<div class="b2bking_myaccount_conversation_endpoint_container_top_header_item"><?php esc_html_e('Type:','b2bking'); ?> <span class="b2bking_myaccount_conversation_endpoint_top_header_text_bold"><?php echo esc_html($conversation_type); ?></span></div>
				<div class="b2bking_myaccount_conversation_endpoint_container_top_header_item"><?php esc_html_e('Date Started:','b2bking'); ?> <span class="b2bking_myaccount_conversation_endpoint_top_header_text_bold"><?php echo esc_html($conversation_started_time); ?></span></div>
			</div>
		<?php
		
		// Check user permission against Conversation user meta
		$user = get_post_meta ($conversation_id, 'b2bking_conversation_user', true);
		// build array of current login + subaccount logins
		$current_user = wp_get_current_user();
		$subaccounts_list = get_user_meta($current_user->ID, 'b2bking_subaccounts_list', true);
		$subaccounts_list = explode (',',$subaccounts_list);
		$subaccounts_list = array_filter($subaccounts_list);
		$logins_array = array($current_user->user_login);
		foreach($subaccounts_list as $subaccount_id){
			$username = get_user_by('id', $subaccount_id)->user_login;
			$logins_array[$subaccount_id] = $username;
		}

		// if current user is a subaccount, give access to parent + subaccounts, IF it has permission to see all account conversations
		$account_type = get_user_meta($current_user->ID, 'b2bking_account_type', true);
		if($account_type === 'subaccount'){
			$permission_view_conversations = filter_var(get_user_meta($current_user->ID, 'b2bking_account_permission_view_conversations', true), FILTER_VALIDATE_BOOLEAN); 
			if ($permission_view_conversations === true){
				// give access to parent
				$parent_id = get_user_meta($current_user->ID, 'b2bking_account_parent', true);
				$parent_user = get_user_by('id', $parent_id);
				$logins_array[$parent_id] = $parent_user->user_login;
				// give access to parent subaccounts
				$parent_subaccounts_list = get_user_meta($parent_id, 'b2bking_subaccounts_list', true);
				$parent_subaccounts_list = explode (',',$parent_subaccounts_list);
				$parent_subaccounts_list = array_filter($parent_subaccounts_list);
				foreach($parent_subaccounts_list as $subaccount_id){
					$username = get_user_by('id', $subaccount_id)->user_login;
					$logins_array[$subaccount_id] = $username;
				}
			}
		}

		// if conversation user is part of the logins array (user + subaccounts), give permission
		if (in_array($user, $logins_array)){
			// Display conversation

			// get number of messages
			$nr_messages = get_post_meta ($conversation_id, 'b2bking_conversation_messages_number', true);
			?>
			<div id="b2bking_conversation_messages_container">
				<?php	
				// loop through and display messages
				for ($i = 1; $i <= $nr_messages; $i++) {
				    // get message details
				    $message = get_post_meta ($conversation_id, 'b2bking_conversation_message_'.$i, true);
				    $author = get_post_meta ($conversation_id, 'b2bking_conversation_message_'.$i.'_author', true);
				    $time = get_post_meta ($conversation_id, 'b2bking_conversation_message_'.$i.'_time', true);
				    // check if message author is self, parent, or subaccounts
				    $current_user_id = get_current_user_id();
				    $subaccounts_list = get_user_meta($current_user_id,'b2bking_subaccounts_list', true);
				    $subaccounts_list = explode(',', $subaccounts_list);
				    $subaccounts_list = array_filter($subaccounts_list);
				    array_push($subaccounts_list, $current_user_id);

					// add parent account+all subaccounts lists
				    $account_type = get_user_meta($current_user_id, 'b2bking_account_type', true);
				    if ($account_type === 'subaccount'){
						$parent_account = get_user_meta($current_user_id, 'b2bking_account_parent', true);
			    		$parent_subaccounts_list = explode(',', get_user_meta($parent_account, 'b2bking_subaccounts_list', true));
			    		$parent_subaccounts_list = array_filter($parent_subaccounts_list); // filter blank, null, etc.
			    		array_push($parent_subaccounts_list, $parent_account); // add parent itself to form complete parent accounts list

			    		$subaccounts_list = array_merge($subaccounts_list, $parent_subaccounts_list);
				    }



				    foreach ($subaccounts_list as $user){
				    	$subaccounts_list[$user] = get_user_by('id', $user)->user_login;
				    }
				    if (in_array($author, $subaccounts_list)){
				    	$self = ' b2bking_conversation_message_self';
				    } else {
				    	$self = '';
				    }
				    // build time string
					    // if today
					    if((time()-$time) < 86400){
					    	// show time
					    	$timestring = date_i18n( 'h:i A', $time+(get_option('gmt_offset')*3600) );
					    } else if ((time()-$time) < 172800){
					    // if yesterday
					    	$timestring = 'Yesterday at '.date_i18n( 'h:i A', $time+(get_option('gmt_offset')*3600) );
					    } else {
					    // date
					    	$timestring = date_i18n( get_option('date_format'), $time+(get_option('gmt_offset')*3600) ); 
					    }
				    ?>
				    <div class="b2bking_conversation_message <?php echo esc_attr($self); ?>">
				    	<?php echo nl2br($message); ?>
				    	<div class="b2bking_conversation_message_time">
				    		<?php echo $author.' - '; ?>
				    		<?php echo esc_html($timestring); ?>
				    	</div>
				    </div>
				    <?php
				}
				?>
			</div>
			<textarea name="b2bking_conversation_user_new_message" id="b2bking_conversation_user_new_message"></textarea><br />
			<input type="hidden" id="b2bking_conversation_id" value="<?php echo esc_attr($conversation_id); ?>">
			<div class="b2bking_myaccount_conversation_endpoint_bottom">
		    	<button id="b2bking_conversation_message_submit" class="b2bking_myaccount_conversation_endpoint_button" type="button">
		    		<svg class="b2bking_myaccount_conversation_endpoint_button_icon" xmlns="http://www.w3.org/2000/svg" width="21" height="21" fill="none" viewBox="0 0 21 21">
				  	<path fill="#fff" d="M5.243 12.454h9.21v4.612c0 .359-.122.66-.368.906-.246.245-.567.377-.964.396H5.243L1.955 21v-2.632h-.651a1.19 1.19 0 01-.907-.396A1.414 1.414 0 010 17.066V8.52c0-.358.132-.67.397-.934.264-.264.567-.387.907-.368h3.939v5.236zM19.696.002c.378 0 .69.123.936.368.245.245.368.566.368.962V9.85c0 .359-.123.66-.368.906a1.37 1.37 0 01-.936.396h-.652v2.632l-3.287-2.632H6.575v-9.82c0-.377.123-.698.368-.962.246-.264.558-.387.936-.368h11.817z"/>
					</svg>
		    		<?php esc_html_e('Send Message','b2bking'); ?>
		    	</button>
			</div>
			<?php
		} else {
			esc_html_e('Conversation does not exist!','b2bking'); // or user does not have permission
		}
		echo '</div>';

	}
	
	function b2bking_offers_endpoint_content() {
		// Title
		?>
		<div id="b2bking_myaccount_offers_container">
		<div id="b2bking_myaccount_offers_title"><?php echo esc_html__('Available Offers','b2bking'); ?></div>
		
		<?php 
		echo do_shortcode('[b2bking_offers]');
	}

	// Quick / Bulk Order Form Endpoint Content
	function b2bking_bulkorder_endpoint_content(){
		?>
		<div id="b2bking_myaccount_bulkorder_container">
			<div id="b2bking_myaccount_bulkorder_title">
				<?php esc_html_e('Quick / Bulk Order Form','b2bking'); ?>
			</div>
		<?php echo do_shortcode('[b2bking_bulkorder]'); ?>
		</div>

		<?php
	}

	// Enable the B2B registration shortcode
	function b2bking_b2b_registration_shortcode(){

		add_shortcode('b2bking_b2b_registration', array($this, 'b2bking_b2b_registration_shortcode_content'));
	}
	function b2bking_b2b_registration_shortcode_content( $atts ){



		$atts = shortcode_atts(
	        array(
	            'registration_role_id' => 'none',
	        ), 
	    $atts);

    	global $b2bking_is_b2b_registration_shortcode_role_id;
	    $b2bking_is_b2b_registration_shortcode_role_id = $atts['registration_role_id'];

		global $b2bking_is_b2b_registration;
		$b2bking_is_b2b_registration = 'yes';
		ob_start();

		// if user is logged in, show message instead of shortcode
		if ( is_user_logged_in() ) {
			echo '<span class="b2bking_already_logged_in_message">';
			esc_html_e('You are already logged in. To apply for a Business account, please logout first. ','b2bking');
			echo '<a href="'.esc_url(wc_logout_url(get_permalink())).'">'.esc_html__('Click here to log out','b2bking').'</a></span>';
		} else {
			wc_print_notices();
			echo do_shortcode('[woocommerce_my_account]');
		}

		$output = ob_get_clean();
		return $output;
	}

	// Enable the B2B registration shortcode
	function b2bking_b2b_registration_separate_shortcode(){
		add_shortcode('b2bking_b2b_registration_separate', array($this, 'b2bking_b2b_registration_separate_shortcode_content'));
	}
	function b2bking_b2b_registration_separate_shortcode_content( $atts ){
		$atts = shortcode_atts(
	        array(
	            'registration_role_id' => 'none',
	        ), 
	    $atts);

    	global $b2bking_is_b2b_registration_shortcode_role_id;
	    $b2bking_is_b2b_registration_shortcode_role_id = $atts['registration_role_id'];

		global $b2bking_is_b2b_registration;
		$b2bking_is_b2b_registration = 'yes';

		global $b2bking_is_b2b_registration_separate;
		$b2bking_is_b2b_registration_separate = 'yes';

		ob_start();

		if (!is_user_logged_in()){
			wc_print_notices(); 
		}
		echo do_shortcode('[woocommerce_my_account]');

		$output = ob_get_clean();
		return $output;
	}

	function b2bking_separate_page_registration_b2b ( $value ){
	    $user_id = get_current_user_id();
	    $user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser',true);
	    if ($user_is_b2b === 'yes'){
	        // check if have setting separate my acc page for b2b users
	        $separate_page = get_option( 'b2bking_registration_separate_my_account_page_setting', 'disabled' );
	        if ($separate_page !== 'disabled'){
	        	return $separate_page;
	        } else {
	        	return $value;
	        }
	    } else {
	        return $value;
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

	// Enable the B2B registration shortcode
	function b2bking_b2b_registration_only_shortcode(){
		add_shortcode('b2bking_b2b_registration_only', array($this, 'b2bking_b2b_registration_only_shortcode_content'));
	}
	function b2bking_b2b_registration_only_shortcode_content( $atts ){

		$atts = shortcode_atts(
	        array(
	            'registration_role_id' => 'none',
	        ), 
	    $atts);

    	global $b2bking_is_b2b_registration_shortcode_role_id;
	    $b2bking_is_b2b_registration_shortcode_role_id = $atts['registration_role_id'];

		global $b2bking_is_b2b_registration;
		$b2bking_is_b2b_registration = 'yes';
		ob_start();

		// if user is logged in, show message instead of shortcode
		if ( is_user_logged_in() ) {
			echo '<span class="b2bking_already_logged_in_message">';
			esc_html_e('You are already logged in. To apply for a Business account, please logout first. ','b2bking');
			echo '<a href="'.esc_url(wc_logout_url(get_permalink())).'">'.esc_html__('Click here to log out','b2bking').'</a></span>';
		} else {
			$message = apply_filters( 'woocommerce_my_account_message', '' );
			if ( ! empty( $message ) ) {
				wc_add_notice( $message );
			}
			wc_print_notices();
			?>
			<h2>
			<?php esc_html_e( 'Register', 'woocommerce' ); ?></h2>
			<div class="woocommerce">
				<form method="post" class="woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?> >

					<?php do_action( 'woocommerce_register_form_start' ); ?>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) { ?>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_username"><?php esc_html_e( 'Username', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
							<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" />
						</p>

					<?php } ?>

					<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
						<label for="reg_email"><?php esc_html_e( 'Email address', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
						<input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" />
					</p>

					<?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) { ?>

						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="reg_password"><?php esc_html_e( 'Password', 'woocommerce' ); ?>&nbsp;<span class="required">*</span></label>
							<input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" />
						</p>

					<?php } else { ?>

						<p><?php esc_html_e( 'A password will be sent to your email address.', 'woocommerce' ); ?></p>

					<?php } ?>

					<?php do_action( 'woocommerce_register_form' ); ?>

					<p class="woocommerce-form-row form-row">
						<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
						<button type="submit" class="woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'Register', 'woocommerce' ); ?>"><?php esc_html_e( 'Register', 'woocommerce' ); ?></button>
					</p>

					<?php do_action( 'woocommerce_register_form_end' ); ?>

				</form>
			</div>
			<?php
		}

		$output = ob_get_clean();
		return $output;
	}

	function b2bking_handle_file_upload(){
		// Stop immidiately if form is not submitted
		if ( ! isset( $_POST['b2bking_submit_csvorder'] ) ) {
			return;
		}

		// Throws a message if no file is selected
		if ( ! $_FILES['b2bking_csvorder']['name'] ) {
			wc_add_notice( esc_html__( 'Please choose a file', 'b2bking' ), 'error' );
		}

		$allowed_extensions = array( 'csv');
		$file_type = wp_check_filetype( $_FILES['b2bking_csvorder']['name'] );
		$file_extension = $file_type['ext'];

		// Check for valid file extension
		if ( ! in_array( $file_extension, $allowed_extensions ) ) {
			wc_add_notice( sprintf(  esc_html__( 'Invalid file extension, only allowed: %s', 'b2bking' ), implode( ', ', $allowed_extensions ) ), 'error' );
		}

		$file_size = $_FILES['b2bking_csvorder']['size'];
		$allowed_file_size = 5512000; // Here we are setting the file size limit to 5.5MB

		// Check for file size limit
		if ( $file_size >= $allowed_file_size ) {
			wc_add_notice( sprintf( esc_html__( 'File size limit exceeded, file size should be smaller than %d KB', 'b2bking' ), $allowed_file_size / 1000 ), 'error' );

		}

		if ( is_wp_error( $upload ) ) {
			wc_add_notice( esc_html__( 'Sorry, there was an error with your file upload.', 'b2bking' ), 'error' );
		} else {
			wc_add_notice( esc_html__( 'Upload successful', 'b2bking' ), 'success' );

			// process upload to add to cart
    	    $csv = array_map('str_getcsv', file($_FILES['b2bking_csvorder']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
			foreach ($csv as $line){

				$lineelementsarray = explode(';',$line[0]);
		
	    		$sku = $lineelementsarray[0];
	    		$qty = $lineelementsarray[1];
	    		$id = wc_get_product_id_by_sku($sku);

	    		if ($id !== 0){
	    			WC()->cart->add_to_cart( $id, $qty);
	    		}
			}
		}

	}
	// Enables csv order shortcode
	function b2bking_csvorder_shortcode(){
		add_shortcode('b2bking_csvorder', array($this, 'b2bking_csvorder_shortcode_content'));
	}
	function b2bking_csvorder_shortcode_content(){
		ob_start();
		?>
		<form method="post" enctype="multipart/form-data">
			<label for="b2bking_csvorder"><?php esc_html_e('Upload .csv files with SKU on first column and quantity on second column.','b2bking');?></label>
			<p class="form-row form-row-wide">
		        <span class="woocommerce-input-wrapper">
		            <input type="file" name="b2bking_csvorder" id="b2bking_csvorder" class="input-text" accept=".csv">
		        </span>
		    </p>
		    <button type="submit" class="button" name="b2bking_submit_csvorder"><?php esc_html_e('Add to Cart','b2bking'); ?></button>
		</form>

		<?php
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	function b2bking_offers_shortcode(){
		add_shortcode('b2bking_offers', array($this, 'b2bking_offers_shortcode_content'));
	}

	function b2bking_offers_shortcode_content(){
		ob_start();

		// Get user login and user group
		$user_id = get_current_user_id();
		$account_type = get_user_meta($user_id,'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			// Check if user has permission to view all account offers
			$permission_view_account_offers = filter_var(get_user_meta($user_id, 'b2bking_account_permission_view_offers', true), FILTER_VALIDATE_BOOLEAN); 
			if ($permission_view_account_offers === true){
				// for all intents and purposes set current user as the subaccount parent
				$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
				$user_id = $parent_user_id;
			}
		}

	    $user = get_user_by('id', $user_id) -> user_login;

		$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );

		// Define custom query parameters
	    $custom_query_args = array( 'post_type' => 'b2bking_offer',
            	  'post_status' => 'publish',
            	  'posts_per_page' => 6,
            	  'meta_query'=> array(
                        'relation' => 'OR',
                        array(
                            'key' => 'b2bking_group_'.$currentusergroupidnr,
                            'value' => '1',
                        ),
                        array(
                            'key' => 'b2bking_user_'.$user, 
                            'value' => '1',
                        ),
                    ));

	    // Get current page and append to custom query parameters array
	    $custom_query_args['paged'] = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

	    // Instantiate custom query
	    $custom_query = new WP_Query( $custom_query_args );

	    // Pagination fix
	    $temp_query = NULL;
	    $wp_query   = NULL;
	    $wp_query   = $custom_query;

	    // Output custom query loop
	    if ( $custom_query->have_posts() ) {
	        while ( $custom_query->have_posts() ) {
	            $custom_query->the_post();
	            global $post;
	            $offer_price = 0;
	            ?>

	            <div class="b2bking_myaccount_individual_offer_container">
	            	<svg class="b2bking_myaccount_individual_offer_top_icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 20 20">
	            	  <path fill="#EDEDED" d="M19.41 9.58l-9-9C10.05.22 9.55 0 9 0H2C.9 0 0 .9 0 2v7c0 .55.22 1.05.59 1.42l9 9c.36.36.86.58 1.41.58.55 0 1.05-.22 1.41-.59l7-7c.37-.36.59-.86.59-1.41 0-.55-.23-1.06-.59-1.42zM3.5 5C2.67 5 2 4.33 2 3.5S2.67 2 3.5 2 5 2.67 5 3.5 4.33 5 3.5 5z"/>
	            	</svg>
	            	<div class="b2bking_myaccount_individual_offer_top">
	            		<?php 
	            			echo esc_html(substr(get_the_title(apply_filters( 'wpml_object_id', $post->ID, 'post' , true)),0,40));
	            			if (strlen(get_the_title(apply_filters( 'wpml_object_id', $post->ID, 'post' , true))) > 40){
	            				echo '...';
	            			} 
	            		?>
	            			
	            	</div>
	            	<div class="b2bking_myaccount_individual_offer_header_line">
	            		<div class="b2bking_myaccount_individual_offer_header_line_item"><?php esc_html_e('Item','b2bking'); ?></div>
	            		<div class="b2bking_myaccount_individual_offer_header_line_item"><?php esc_html_e('Quantity','b2bking'); ?></div>
	            		<div class="b2bking_myaccount_individual_offer_header_line_item"><?php esc_html_e('Unit Price','b2bking'); ?></div>
	            		<div class="b2bking_myaccount_individual_offer_header_line_item"><?php esc_html_e('Subtotal','b2bking'); ?></div>
	            	</div>
	            	<?php 

	            	$details = get_post_meta(apply_filters( 'wpml_object_id', $post->ID, 'post' , true),'b2bking_offer_details', true);
	            	$offer_products = explode('|',$details);
	            	foreach ($offer_products as $product){
	            		$product_details = explode(';', $product);
	            		// if item is in the form product_id, change title
	            		$isproductid = explode('_', $product_details[0]); 
	            		if ($isproductid[0] === 'product'){
	            			// it is a product+id, get product title
	            			$newproduct = wc_get_product($isproductid[1]);

	            			$product_details[0] = $newproduct->get_name();

	            			//if product is a variation with 3 or more attributes, need to change display because get_name doesnt 
	            			// show items correctly
	            			if (is_a($newproduct,'WC_Product_Variation')){
	            				$attributes = $newproduct->get_variation_attributes();
	            				$number_of_attributes = count($attributes);
	            				if ($number_of_attributes > 2){
	            					$product_details[0].=' - ';
	            					foreach ($attributes as $attribute){
	            						$product_details[0].=$attribute.', ';
	            					}
	            					$product_details[0] = substr($product_details[0], 0, -2);
	            				}
	            			}
	            			
	            			
	            		}
	            		?>
	            		<div class="b2bking_myaccount_individual_offer_element_line">
	            			<div class="b2bking_myaccount_individual_offer_element_line_item"><?php echo esc_html($product_details[0]); ?>
	            				<?php 
	            				// if image is enabled in settings, and product is product_id
	            				if ($isproductid[0] === 'product' && intval(get_option('b2bking_offers_product_image_setting', 0)) === 1){
	            					// show image
	            					?>
	            					<img class="b2bking_offer_image" src="<?php echo wp_get_attachment_url( $newproduct->get_image_id() ); ?>">
	            					<?php
	            				}
	            				?>
	            			</div>
	            			<div class="b2bking_myaccount_individual_offer_element_line_item"><?php echo esc_html($product_details[1]); ?></div>
	            			<div class="b2bking_myaccount_individual_offer_element_line_item"><?php
	            			// adjust Unit price for tax 
	            			$unit_price_display = $product_details[2];
	            			// get offer product
	            			$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
	            			$offer_product = wc_get_product($offer_id);

	            			if (is_a(WC()->customer, 'WC_Customer')){
		            			if( wc_prices_include_tax() && ('incl' !== get_option( 'woocommerce_tax_display_shop') || WC()->customer->is_vat_exempt())) {
		            				// if prices are entered including tax, but display is without tax, remove tax 
		            				// get tax rate for the offer product
		            				$tax_rates = WC_Tax::get_base_tax_rates( $offer_product->get_tax_class( 'unfiltered' ) ); 
		            				$taxes = WC_Tax::calc_tax( $unit_price_display, $tax_rates, true ); 
		            				$unit_price_display = WC_Tax::round( $unit_price_display - array_sum( $taxes ) ); 

		            			} else if ( !wc_prices_include_tax() && ('incl' === get_option( 'woocommerce_tax_display_shop') && !WC()->customer->is_vat_exempt())){
		            				// if prices are entered excluding tax, but display is with tax, add tax
		            				$tax_rates = WC_Tax::get_rates( $offer_product->get_tax_class() );
		            				$taxes     = WC_Tax::calc_tax( $unit_price_display, $tax_rates, false );
		            				$unit_price_display = WC_Tax::round( $unit_price_display + array_sum( $taxes ) );
		            			} else {
		            				// no adjustment
		            			}
		            		}
	            			echo wc_price($unit_price_display); 


	            			?></div>
	            			<div class="b2bking_myaccount_individual_offer_element_line_item"><?php echo wc_price($product_details[1]*$unit_price_display); ?></div>

	            		</div>
	            		<?php
	            		$offer_price+=$product_details[1]*$product_details[2];
	            	}

	            	/*
	            	* Adjust for tax with 3 possibilities:
	            	* Option 1: Need to remove tax
	            	* Option 2: Need to add tax
	            	* Option 3: No adjustment
	            	*/ 

	            	// First calculate tax
	            	// get offer product
	            	$offer_id = intval(get_option('b2bking_offer_product_id_setting', 0));
	            	$offer_product = wc_get_product($offer_id);

	            	if (is_a(WC()->customer, 'WC_Customer')){
		            	if( wc_prices_include_tax() && ('incl' !== get_option( 'woocommerce_tax_display_shop') || WC()->customer->is_vat_exempt())) {
		            		// if prices are entered including tax, but display is without tax, remove tax 
		            		// get tax rate for the offer product
		            		$tax_rates = WC_Tax::get_base_tax_rates( $offer_product->get_tax_class( 'unfiltered' ) ); 
		            		$taxes = WC_Tax::calc_tax( $offer_price, $tax_rates, true ); 
		            		$offer_price = WC_Tax::round( $offer_price - array_sum( $taxes ) ); 

		            	} else if ( !wc_prices_include_tax() && ('incl' === get_option( 'woocommerce_tax_display_shop') && !WC()->customer->is_vat_exempt())){
		            		// if prices are entered excluding tax, but display is with tax, add tax
		            		$tax_rates = WC_Tax::get_rates( $offer_product->get_tax_class() );
		            		$taxes     = WC_Tax::calc_tax( $offer_price, $tax_rates, false );
		            		$offer_price = WC_Tax::round( $offer_price + array_sum( $taxes ) );
		            	} else {
		            		// no adjustment
		            	}
		            }
	            	

	            	?>
	            	<?php
	            	do_action('b2bking_before_offer_add_to_cart_public', $post->ID);
	            	// check if there is any custom text in the offer. Display it
	            	$postidnr = apply_filters( 'wpml_object_id', $post->ID, 'post', true );
	            	$custom_text = get_post_meta($postidnr, 'b2bking_offer_customtext_textarea', true);
	            	if (!empty($custom_text) && $custom_text !== NULL){
	            	?>
		            	<div class="b2bking_myaccount_individual_offer_custom_text"><?php echo esc_textarea($custom_text); ?>
		            	</div>
		            <?php } ?>
	            	<div class="b2bking_myaccount_individual_offer_bottom_line">
	            		<div class="b2bking_myaccount_individual_offer_bottom_line_add">
	            			<button class="b2bking_myaccount_individual_offer_bottom_line_button b2bking_offer_add" value="<?php echo esc_attr($post->ID); ?>" type="button">
	            				<svg class="b2bking_myaccount_individual_offer_bottom_line_button_icon" xmlns="http://www.w3.org/2000/svg" width="21" height="19" fill="none" viewBox="0 0 21 19">
	            				  <path fill="#fff" d="M18.401 11.875H7.714l.238 1.188h9.786c.562 0 .978.53.854 1.087l-.202.901a2.082 2.082 0 011.152 1.87c0 1.159-.93 2.096-2.072 2.079-1.087-.016-1.981-.914-2.01-2.02a2.091 2.091 0 01.612-1.543H8.428c.379.378.614.903.614 1.485 0 1.18-.967 2.131-2.14 2.076-1.04-.05-1.886-.905-1.94-1.964a2.085 2.085 0 011.022-1.914L3.423 2.375H.875A.883.883 0 010 1.485V.89C0 .399.392 0 .875 0h3.738c.416 0 .774.298.857.712l.334 1.663h14.32c.562 0 .978.53.854 1.088l-1.724 7.719a.878.878 0 01-.853.693zm-3.526-5.64h-1.75V4.75a.589.589 0 00-.583-.594h-.584a.589.589 0 00-.583.594v1.484h-1.75a.589.589 0 00-.583.594v.594c0 .328.26.594.583.594h1.75V9.5c0 .328.261.594.583.594h.584a.589.589 0 00.583-.594V8.016h1.75a.589.589 0 00.583-.594v-.594a.589.589 0 00-.583-.594z"/>
	            				</svg>
	            			<?php esc_html_e('Add to Cart','b2bking'); ?></button>
	            		</div>
	            		<div class="b2bking_myaccount_individual_offer_bottom_line_total">
	            			<?php esc_html_e('Total: ','b2bking'); ?><strong><?php echo wc_price($offer_price);?></strong>
	            		</div>
	            	</div>
	            </div>

	            <?php

	        }
	    } else {
	    	wc_print_notice(esc_html__('No offers available yet.', 'b2bking'), 'notice');
	    }
	    // Reset postdata
	    wp_reset_postdata();

	    // Custom query loop pagination
	    ?>
	    <div class="b2bking_myaccount_offers_pagination_container">
		    <div class="b2bking_myaccount_conversations_pagination_button b2bking_newer_offers_button">
		    	<?php previous_posts_link( esc_html__('←   Newer offers','b2bking') ); ?>
		    </div>
		    <div class="b2bking_myaccount_conversations_pagination_button b2bking_older_offers_button">
		    	<?php next_posts_link( esc_html__('Older offers   →','b2bking'), $custom_query->max_num_pages ); ?>
		    </div>
		</div>
		<?php

	    // Reset main query object
	    $wp_query = NULL;
	    $wp_query = $temp_query;
		
		echo '</div>';

		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	function b2bking_purchaselists_shortcode(){
		add_shortcode('b2bking_purchaselists', array($this, 'b2bking_purchaselists_shortcode_content'));

	}
	function b2bking_purchaselists_shortcode_content(){
		ob_start();
		$bulk_order_endpoint_url = wc_get_endpoint_url('bulkorder');
		?>
		<div class="b2bking_purchase_list_top_container">
			<div class="b2bking_purchase_lists_top_title">
				<?php esc_html_e('Purchase lists', 'b2bking'); ?>
			</div>
			<?php
			// if bulk order form is disabled, remove the "new list" button
			if (intval(get_option('b2bking_enable_bulk_order_form_setting', 1)) === 1){ 
			?>
				<a href="<?php echo esc_attr($bulk_order_endpoint_url); ?>" class="b2bking_purchase_list_new_link">
					<button type="button" id="b2bking_purchase_list_new_button">
						<svg class="b2bking_purchase_list_new_button_icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
						  <path fill="#fff" d="M9.778 4.889h7.333v2.444H9.778V4.89zm0 4.889h7.333v2.444H9.778V9.778zm0 4.889h7.333v2.444H9.778v-2.444zm-4.89-9.778h2.445v2.444H4.89V4.89zm0 4.889h2.445v2.444H4.89V9.778zm0 4.889h2.445v2.444H4.89v-2.444zM20.9 0H1.1C.489 0 0 .489 0 1.1v19.8c0 .489.489 1.1 1.1 1.1h19.8c.489 0 1.1-.611 1.1-1.1V1.1c0-.611-.611-1.1-1.1-1.1zm-1.344 19.556H2.444V2.444h17.112v17.112z"></path>
						</svg>
						<?php esc_html_e('New List','b2bking'); ?>
					</button>
				</a>
			<?php } ?>
		</div>
		<table id="b2bking_purchase_lists_table">
		        <thead>
		            <tr>
		                <th><?php esc_html_e('List name','b2bking'); ?></th>
		                <th><?php esc_html_e('Number of items','b2bking'); ?></th>
		                <th><?php esc_html_e('User','b2bking'); ?></th>
		                <th><?php esc_html_e('Actions','b2bking'); ?></th>

		            </tr>
		        </thead>
		        <tbody>
		        	<?php
		        	// get all lists of the user and his subaccounts
		        	$current_user = get_current_user_id();
		        	$subaccounts_list = explode(',', get_user_meta($current_user, 'b2bking_subaccounts_list', true));
		        	$subaccounts_list = array_filter($subaccounts_list); // filter blank, null, etc.
		        	// add current user to subaccounts to form a complete accounts list
		        	array_push($subaccounts_list, $current_user);

		        	// if current account is subaccount AND has permission to view all account purchase lists, add parent account+all subaccounts lists
		        	$account_type = get_user_meta($current_user, 'b2bking_account_type', true);
		        	if ($account_type === 'subaccount'){
		        		$permission_view_all_lists = filter_var(get_user_meta($current_user, 'b2bking_account_permission_view_lists', true),FILTER_VALIDATE_BOOLEAN);
		        		if ($permission_view_all_lists === true){
		        			// has permission
		        			$parent_account = get_user_meta($current_user, 'b2bking_account_parent', true);
		        			$parent_subaccounts_list = explode(',', get_user_meta($parent_account, 'b2bking_subaccounts_list', true));
		        			$parent_subaccounts_list = array_filter($parent_subaccounts_list); // filter blank, null, etc.
		        			array_push($parent_subaccounts_list, $parent_account); // add parent itself to form complete parent accounts list

		        			$subaccounts_list = array_merge($subaccounts_list, $parent_subaccounts_list);
		        		}
		        	}

		        	$purchase_lists = get_posts([
			    		'post_type' => 'b2bking_list',
			    	  	'post_status' => 'publish',
			    	  	'numberposts' => -1,
			    	  	'author__in' => $subaccounts_list,
			    	]);

			    	$endpointurl = wc_get_endpoint_url('purchase-list');

			    	foreach ($purchase_lists as $list){
			    		$list_details = get_post_meta($list->ID, 'b2bking_purchase_list_details', true);
			    		$list_items_array = explode('|', $list_details);
			    		$list_items_array = array_filter($list_items_array);
			    		$items_number = count($list_items_array);
			    		$list_author_id = get_post_field( 'post_author', $list->ID );
			    		$list_author_username = get_user_by('id', $list_author_id)->user_login;
			    		?>
			    		<tr>
			    		    <td><?php echo esc_html($list->post_title); ?></td>
			    		    <td>
			    		    	<?php 
			    		    	echo esc_html($items_number); 
			    		    	if ($items_number === 1){
			    		    		esc_html_e(' item', 'b2bking'); 	
			    		    	} else {
			    		    		esc_html_e(' items', 'b2bking'); 
								}
			    		    	?>
			    		    	
			    		    </td>
			    		    <td><?php echo esc_html($list_author_username); ?></td>
			    		    <td>
			    		    	<a class="b2bking_purchase_list_button_href" href="<?php echo esc_url(add_query_arg('id',$list->ID, $endpointurl)); ?>">
			    		    		<button type="button" class="b2bking_purchase_lists_view_list"><?php esc_html_e('View list','b2bking'); ?></button>
			    		    	</a>
			    		    	<a class="b2bking_download_list_button b2bking_purchase_list_button_href id_<?php echo esc_attr($list->ID);?>" href="#">
			    		    		<button type="button" class="b2bking_purchase_lists_view_list"><?php esc_html_e('Download','b2bking'); ?></button>
			    		    	</a>
			    		    </td>
			    		</tr>

			    		<?php
			    	}		        	

		        	?>
		        			           
		        </tbody>
		        <tfoot>
		            <tr>
		                <th><?php esc_html_e('List name','b2bking'); ?></th>
		                <th><?php esc_html_e('Number of items','b2bking'); ?></th>
		                <th><?php esc_html_e('User','b2bking'); ?></th>
		                <th><?php esc_html_e('Actions','b2bking'); ?></th>
		            </tr>
		        </tfoot>
		    </table>
		    <?php
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	// Enables bulk order shortcode
	function b2bking_bulkorder_shortcode(){
		add_shortcode('b2bking_bulkorder', array($this, 'b2bking_bulkorder_shortcode_content'));
	}

	// Bulk order shortcode content
	function b2bking_bulkorder_shortcode_content(){
		ob_start();
		?>
		<div class="b2bking_bulkorder_form_container">
			<div class="b2bking_bulkorder_form_container_top">
				<?php esc_html_e('Bulk Order Form', 'b2bking'); ?>
			</div>
			<div class="b2bking_bulkorder_form_container_content">
				<div class="b2bking_bulkorder_form_container_content_header">
					<div class="b2bking_bulkorder_form_container_content_header_product">
						<?php
						if (intval(get_option( 'b2bking_search_by_sku_setting', 1 )) === 1){
							esc_html_e('Search by', 'b2bking');
						?>
							<select id="b2bking_bulkorder_searchby_select">
								<option value="productname"><?php esc_html_e('Product Name', 'b2bking'); ?></option>
								<option value="sku"><?php esc_html_e('SKU', 'b2bking'); ?></option>
							</select>
						<?php 
						} else {
							esc_html_e('Product name', 'b2bking');
						}
						?>
            		</div>
            		<div class="b2bking_bulkorder_form_container_content_header_qty">
            			<?php esc_html_e('Qty', 'b2bking'); ?>
            		</div>
            		<div class="b2bking_bulkorder_form_container_content_header_subtotal">
            			<?php esc_html_e('Subtotal', 'b2bking'); ?>
            		</div>
				</div>

            	<div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product" placeholder="<?php esc_attr_e('Search for a product...','b2bking'); ?>"><input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty"><div class="b2bking_bulkorder_form_container_content_line_subtotal"><?php echo get_woocommerce_currency_symbol().'0'; ?></div><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div>

            	<div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product" ><input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty"><div class="b2bking_bulkorder_form_container_content_line_subtotal"><?php echo get_woocommerce_currency_symbol().'0'; ?></div><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div>

            	<div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product" ><input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty"><div class="b2bking_bulkorder_form_container_content_line_subtotal"><?php echo get_woocommerce_currency_symbol().'0'; ?></div><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div>

            	<div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product" ><input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty"><div class="b2bking_bulkorder_form_container_content_line_subtotal"><?php echo get_woocommerce_currency_symbol().'0'; ?></div><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div>

            	<div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product"><input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty"><div class="b2bking_bulkorder_form_container_content_line_subtotal"><?php echo get_woocommerce_currency_symbol().'0'; ?></div><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div>

            	<!-- new line button -->
            	<div class="b2bking_bulkorder_form_container_newline_container">
            		<button class="b2bking_bulkorder_form_container_newline_button">
            			<svg class="b2bking_bulkorder_form_container_newline_button_icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
            			  <path fill="#fff" d="M11 1.375c-5.315 0-9.625 4.31-9.625 9.625s4.31 9.625 9.625 9.625 9.625-4.31 9.625-9.625S16.315 1.375 11 1.375zm4.125 10.14a.172.172 0 01-.172.172h-3.265v3.266a.172.172 0 01-.172.172h-1.032a.172.172 0 01-.171-.172v-3.265H7.046a.172.172 0 01-.172-.172v-1.032c0-.094.077-.171.172-.171h3.266V7.046c0-.095.077-.172.171-.172h1.032c.094 0 .171.077.171.172v3.266h3.266c.095 0 .172.077.172.171v1.032z"/>
            			</svg>
            			<?php esc_html_e('new line','b2bking'); ?>
            		</button>
            	</div>

            	<!-- add to cart button -->
            	<div class="b2bking_bulkorder_form_container_bottom">
            		<!-- initialize hidden loader to get it to load instantly -->
            		<img class="b2bking_loader_hidden" src="<?php echo plugins_url('../includes/assets/images/loader.svg', __FILE__); ?>">
            		<div class="b2bking_bulkorder_form_container_bottom_add">
            			<button class="b2bking_bulkorder_form_container_bottom_add_button" type="button">
            				<svg class="b2bking_bulkorder_form_container_bottom_add_button_icon" xmlns="http://www.w3.org/2000/svg" width="21" height="19" fill="none" viewBox="0 0 21 19">
            				  <path fill="#fff" d="M18.401 11.875H7.714l.238 1.188h9.786c.562 0 .978.53.854 1.087l-.202.901a2.082 2.082 0 011.152 1.87c0 1.159-.93 2.096-2.072 2.079-1.087-.016-1.981-.914-2.01-2.02a2.091 2.091 0 01.612-1.543H8.428c.379.378.614.903.614 1.485 0 1.18-.967 2.131-2.14 2.076-1.04-.05-1.886-.905-1.94-1.964a2.085 2.085 0 011.022-1.914L3.423 2.375H.875A.883.883 0 010 1.485V.89C0 .399.392 0 .875 0h3.738c.416 0 .774.298.857.712l.334 1.663h14.32c.562 0 .978.53.854 1.088l-1.724 7.719a.878.878 0 01-.853.693zm-3.526-5.64h-1.75V4.75a.589.589 0 00-.583-.594h-.584a.589.589 0 00-.583.594v1.484h-1.75a.589.589 0 00-.583.594v.594c0 .328.26.594.583.594h1.75V9.5c0 .328.261.594.583.594h.584a.589.589 0 00.583-.594V8.016h1.75a.589.589 0 00.583-.594v-.594a.589.589 0 00-.583-.594z"/>
            				</svg>
            			<?php esc_html_e('Add to Cart','b2bking'); ?>
            			</button>
            			<button class="b2bking_bulkorder_form_container_bottom_save_button" type="button">
            				<svg class="b2bking_bulkorder_form_container_bottom_save_button_icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
            				  <path fill="#fff" d="M9.778 4.889h7.333v2.444H9.778V4.89zm0 4.889h7.333v2.444H9.778V9.778zm0 4.889h7.333v2.444H9.778v-2.444zm-4.89-9.778h2.445v2.444H4.89V4.89zm0 4.889h2.445v2.444H4.89V9.778zm0 4.889h2.445v2.444H4.89v-2.444zM20.9 0H1.1C.489 0 0 .489 0 1.1v19.8c0 .489.489 1.1 1.1 1.1h19.8c.489 0 1.1-.611 1.1-1.1V1.1c0-.611-.611-1.1-1.1-1.1zm-1.344 19.556H2.444V2.444h17.112v17.112z"/>
            				</svg>
            			<?php esc_html_e('Save list','b2bking'); ?>
            			</button>
            		</div>
            		<div class="b2bking_bulkorder_form_container_bottom_total">
            			<?php esc_html_e('Total: ','b2bking'); ?><strong><?php echo wc_price(0);?></strong>
            		</div>
            	</div>


            </div>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	// Subaccounts Endpoint Content
	function b2bking_subaccounts_endpoint_content(){
		$account_type = get_user_meta(get_current_user_id(), 'b2bking_account_type', true);
		?>
		<div class="b2bking_subaccounts_container">
			<div class="b2bking_subaccounts_container_top">
				<div class="b2bking_subaccounts_container_top_title">
					<?php esc_html_e('Subaccounts','b2bking'); ?>
				</div>
				<?php
				// only available if current account is not itself a subaccount
				if ($account_type !== 'subaccount'){
					?>
					<button class="b2bking_subaccounts_container_top_button" type="button">
						<svg class="b2bking_subaccounts_container_top_button_icon" xmlns="http://www.w3.org/2000/svg" width="34" height="34" fill="none" viewBox="0 0 34 34">
						  <path fill="#fff" d="M6.375 12.115c0 2.827 2.132 4.959 4.958 4.959 2.827 0 4.959-2.132 4.959-4.959 0-2.826-2.132-4.958-4.959-4.958-2.826 0-4.958 2.132-4.958 4.958zm20.542-.782h-2.834v4.25h-4.25v2.834h4.25v4.25h2.834v-4.25h4.25v-2.834h-4.25v-4.25zM5.667 26.917h14.166V25.5a7.091 7.091 0 00-7.083-7.083H9.917A7.091 7.091 0 002.833 25.5v1.417h2.834z"/>
						</svg>
						<?php esc_html_e('New subaccount','b2bking'); ?>
					</button>
					<?php
				}
				?>
			</div>

			<!-- Hidden New Subaccount Container -->
			<?php
			// only available if current account is not itself a subaccount
			if ($account_type !== 'subaccount'){
				?>
				<div class="b2bking_subaccounts_new_account_container">
					<div class="b2bking_subaccounts_new_account_container_top">
						<div class="b2bking_subaccounts_new_account_container_top_title">
							<?php esc_html_e('New Subaccount', 'b2bking'); ?>
						</div>
						<div class="b2bking_subaccounts_new_account_container_top_close">
							<?php esc_html_e('Close X', 'b2bking'); ?>
						</div>
					</div>
					<div class="b2bking_subaccounts_new_account_container_content">
						<div class="b2bking_subaccounts_new_account_container_content_large_title">
							<svg class="b2bking_subaccounts_new_account_container_content_large_title_icon" xmlns="http://www.w3.org/2000/svg" width="35" height="25" fill="none" viewBox="0 0 35 25">
							  <path fill="#4E4E4E" d="M22.75 10.5H35V14H22.75v-3.5zm1.75 7H35V21H24.5v-3.5zM21 3.5h14V7H21V3.5zm-17.5 21H21v-1.75c0-4.825-3.925-8.75-8.75-8.75h-3.5C3.925 14 0 17.925 0 22.75v1.75h3.5zm7-12.25c3.491 0 6.125-2.634 6.125-6.125S13.991 0 10.5 0 4.375 2.634 4.375 6.125 7.009 12.25 10.5 12.25z"/>
							</svg>
							<span class="b2bking_span_title_text_subaccount"><?php esc_html_e('Login Details', 'b2bking'); ?></span>
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element">
							<div class="b2bking_subaccounts_new_account_container_content_element_label">
								<?php esc_html_e('Username','b2bking'); ?>
							</div>
							<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_username" placeholder="<?php esc_attr_e('Enter the subaccount username here...','b2bking'); ?>" >
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element">
							<div class="b2bking_subaccounts_new_account_container_content_element_label">
								<?php esc_html_e('Email Address','b2bking'); ?>
							</div>
							<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_email_address" placeholder="<?php esc_attr_e('Enter the subaccount email here...','b2bking'); ?>">
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element b2bking_subaccount_horizontal_line">
							<div class="b2bking_subaccounts_new_account_container_content_element_label">
								<?php esc_html_e('Password','b2bking'); ?>
							</div>
							<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_password" placeholder="<?php esc_attr_e('Enter the subaccount password here...','b2bking'); ?>" >
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_large_title b2bking_subaccount_top_margin">
							<svg class="b2bking_subaccounts_new_account_container_content_large_title_icon" xmlns="http://www.w3.org/2000/svg" width="35" height="29" fill="none" viewBox="0 0 35 29">
							  <path fill="#4E4E4E" d="M12.25 14.063c3.867 0 7-3.148 7-7.031 0-3.884-3.133-7.032-7-7.032-3.866 0-7 3.148-7 7.032 0 3.883 3.134 7.031 7 7.031zm4.9 1.758h-.913a9.494 9.494 0 01-3.986.879 9.512 9.512 0 01-3.987-.88H7.35C3.292 15.82 0 19.129 0 23.205v2.285a2.632 2.632 0 002.625 2.637H17.66a2.648 2.648 0 01-.142-1.17l.372-3.346.066-.61.432-.433 4.227-4.247c-1.34-1.521-3.281-2.5-5.463-2.5zm2.478 7.982l-.372 3.35a.873.873 0 00.963.968l3.33-.374 7.542-7.575-3.921-3.94-7.542 7.57zm14.99-9.031l-2.072-2.082a1.306 1.306 0 00-1.849 0l-2.067 2.076-.224.225 3.927 3.94 2.285-2.297a1.327 1.327 0 000-1.862z"/>
							</svg>
							<span class="b2bking_span_title_text_subaccount"><?php esc_html_e('Personal Details', 'b2bking'); ?></span>
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element">
							<div class="b2bking_subaccounts_new_account_container_content_element_label">
								<?php esc_html_e('Name','b2bking'); ?>
							</div>
							<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_name" placeholder="<?php esc_attr_e('Enter the account holder\'s name here...','b2bking'); ?>">
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element">
							<div class="b2bking_subaccounts_new_account_container_content_element_label">
								<?php esc_html_e('Job Title','b2bking'); ?>
							</div>
							<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_job_title" placeholder="<?php esc_attr_e('Enter the account holder\'s title here...','b2bking'); ?>">
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element b2bking_subaccount_horizontal_line">
							<div class="b2bking_subaccounts_new_account_container_content_element_label">
								<?php esc_html_e('Phone Number','b2bking'); ?>
							</div>
							<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_phone_number" placeholder="<?php esc_attr_e('Enter the account holder\'s phone here...','b2bking'); ?>">
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_large_title b2bking_subaccount_top_margin">
							<svg class="b2bking_subaccounts_new_account_container_content_large_title_icon" xmlns="http://www.w3.org/2000/svg" width="35" height="24" fill="none" viewBox="0 0 35 24">
							  <path fill="#575757" d="M16.042 8.75v2.917h-1.459v2.916h-2.916v-2.916H8.502a4.36 4.36 0 01-4.127 2.916 4.375 4.375 0 110-8.75A4.36 4.36 0 018.502 8.75h7.54zm-11.667 0a1.458 1.458 0 100 2.917 1.458 1.458 0 000-2.917zm18.958 5.833c3.894 0 11.667 1.955 11.667 5.834v2.916H11.667v-2.916c0-3.88 7.773-5.834 11.666-5.834zm0-2.916a5.833 5.833 0 110-11.667 5.833 5.833 0 010 11.667z"/>
							</svg>
							<span class="b2bking_span_title_text_subaccount"><?php esc_html_e('Permissions', 'b2bking'); ?></span>
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
								<?php esc_html_e('Place an Order','b2bking'); ?>
							</div>
							<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_buy">
						</div>
						<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
								<?php esc_html_e('View all account orders','b2bking'); ?>
							</div>
							<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_orders">
						</div>
						<?php if (intval(get_option('b2bking_enable_offers_setting', 1)) === 1){ ?>
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
								<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
									<?php esc_html_e('View all account offers','b2bking'); ?>
								</div>
								<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_offers">
							</div>
						<?php } ?>
						<?php if (intval(get_option('b2bking_enable_conversations_setting', 1)) === 1){ ?>
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
								<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
									<?php esc_html_e('View all account conversations','b2bking'); ?>
								</div>
								<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_conversations">
							</div>
						<?php } ?>
						<?php if (intval(get_option('b2bking_enable_purchase_lists_setting', 1)) === 1){ ?>
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
								<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
									<?php esc_html_e('View all account purchase lists','b2bking'); ?>
								</div>
								<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_lists">
							</div>
						<?php } ?>
						<div class="b2bking_subaccounts_new_account_container_content_bottom">
							<div class="b2bking_subaccounts_new_account_container_content_bottom_validation_errors">
							</div>
							<button class="b2bking_subaccounts_new_account_container_content_bottom_button" type="button">
								<svg class="b2bking_subaccounts_new_account_container_content_bottom_button_icon" xmlns="http://www.w3.org/2000/svg" width="30" height="20" fill="none" viewBox="0 0 30 20">
								  <path fill="#fff" d="M4.375 5.115c0 2.827 2.132 4.959 4.958 4.959 2.827 0 4.959-2.132 4.959-4.959 0-2.826-2.132-4.958-4.959-4.958-2.826 0-4.958 2.132-4.958 4.958zm20.542-.782h-2.834v4.25h-4.25v2.834h4.25v4.25h2.834v-4.25h4.25V8.583h-4.25v-4.25zM3.667 19.917h14.166V18.5a7.091 7.091 0 00-7.083-7.083H7.917A7.091 7.091 0 00.833 18.5v1.417h2.834z"/>
								</svg>
								<?php esc_html_e('Create Subaccount', 'b2bking'); ?>
							</button>
						</div>
					</div>
				</div>
				<?php
			}

			// Get all subaccounts and display them;
			$user_id = get_current_user_id();
			$user_subaccounts_list = get_user_meta($user_id, 'b2bking_subaccounts_list', true);
			$subaccounts_array = explode(',', $user_subaccounts_list);
			$subaccounts_array = array_filter($subaccounts_array); // removing blank, null, false, 0 (zero) values
			$subaccounts_array = array_reverse($subaccounts_array); // show newest first 

			if(empty($subaccounts_array)){
				wc_print_notice(esc_html__('No subaccounts exist.', 'b2bking'), 'notice');
			}
			foreach($subaccounts_array as $subaccount){
				// display subaccount
				$user = get_user_by('ID', $subaccount);
				$username = $user->user_login;
				$name = get_user_meta($subaccount, 'b2bking_account_name', true);
				$job_title = get_user_meta($subaccount, 'b2bking_account_job_title', true);
				$phone = get_user_meta($subaccount, 'b2bking_account_phone', true);
				$email = $user->user_email;
				// Get Subaccount Endpoint URL
		   		$endpointurl = wc_get_endpoint_url('subaccount');
				?>
				<div class="b2bking_subaccounts_account_container">
					<div class="b2bking_subaccounts_account_top">
						<svg class="b2bking_subaccounts_account_top_icon" xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="none" viewBox="0 0 26 26">
						  <path fill="#fff" d="M8.125 7.042A4.881 4.881 0 0013 11.917a4.88 4.88 0 004.875-4.875A4.88 4.88 0 0013 2.167a4.881 4.881 0 00-4.875 4.875zM21.667 22.75h1.083v-1.083c0-4.18-3.403-7.584-7.583-7.584h-4.334c-4.181 0-7.583 3.403-7.583 7.584v1.083h18.417z"/>
						</svg>
						<?php echo esc_html($username); ?>
					</div>
					<div class="b2bking_subaccounts_account_line">
						<div class="b2bking_subaccounts_account_name_title">
							<div class="b2bking_subaccounts_account_name">
								<?php echo esc_html($name); ?>
							</div>
							<div class="b2bking_subaccounts_account_title">
								<?php echo esc_html($job_title); ?>
							</div>
						</div>
						<a href="<?php echo esc_url(add_query_arg('id',$subaccount,$endpointurl)); ?>">
							<button class="b2bking_subaccounts_account_button" type="button">
								<svg class="b2bking_subaccounts_account_button_icon" xmlns="http://www.w3.org/2000/svg" width="24" height="23" fill="none" viewBox="0 0 24 23">
								  <path fill="#fff" d="M20.016 11.236a5.529 5.529 0 01-2.79 1.432 5.672 5.672 0 01-3.15-.294l-6.492 7.498a3.129 3.129 0 01-4.296 0c-1.188-1.139-1.188-2.979 0-4.105l7.824-6.233c-.816-1.898-.42-4.152 1.188-5.693 1.536-1.472 3.744-1.863 5.664-1.219l-3.468 3.324 3.384 3.242 3.432-3.3a5.048 5.048 0 01-1.296 5.348zM4.572 18.64c.48.449 1.248.449 1.716 0 .48-.46.48-1.195 0-1.644a1.24 1.24 0 00-1.716 0c-.225.22-.351.515-.351.822 0 .308.126.603.351.823z"/>
								</svg>
								<?php esc_html_e('Edit account','b2bking'); ?>
							</button>
						</a>
					</div>
					<div class="b2bking_subaccounts_account_line">
						<div class="b2bking_subaccounts_account_phone_email">
							<div class="b2bking_subaccounts_account_phone_email_text">
								<?php echo esc_html($phone); ?>
							</div>
							<div class="b2bking_subaccounts_account_phone_email_text">
								<?php echo esc_html($email); ?>
							</div>
						</div>
					</div>
				</div>
			<?php	
			}
			?>
			</div>	
		<?php
	}

	// Individual subaccount endpoint content
	function b2bking_subaccount_endpoint_content(){
		// get subaccount
		$subaccount_id = sanitize_text_field( $_GET['id'] );
		// check if current user has permission to access this subaccount
		$current_user = get_current_user_id();
		$current_user_subaccounts = get_user_meta($current_user, 'b2bking_subaccounts_list', true);
		$current_user_subaccounts = array_filter(explode(',',$current_user_subaccounts));
		if (in_array ( $subaccount_id, $current_user_subaccounts)){
			// has permission
			// get subaccount meta
			$name = get_user_meta($subaccount_id, 'b2bking_account_name', true);
			$job_title = get_user_meta($subaccount_id, 'b2bking_account_job_title', true);
			$phone = get_user_meta($subaccount_id, 'b2bking_account_phone', true);
			$permission_buy = filter_var(get_user_meta($subaccount_id, 'b2bking_account_permission_buy', true), FILTER_VALIDATE_BOOLEAN); 
			$permission_view_orders = filter_var(get_user_meta($subaccount_id, 'b2bking_account_permission_view_orders', true), FILTER_VALIDATE_BOOLEAN);
			$permission_view_offers = filter_var(get_user_meta($subaccount_id, 'b2bking_account_permission_view_offers', true), FILTER_VALIDATE_BOOLEAN); 
			$permission_view_conversations = filter_var(get_user_meta($subaccount_id, 'b2bking_account_permission_view_conversations', true), FILTER_VALIDATE_BOOLEAN); 
			$permission_view_lists = filter_var(get_user_meta($subaccount_id, 'b2bking_account_permission_view_lists', true), FILTER_VALIDATE_BOOLEAN);   
			?>

			<div class="b2bking_subaccounts_edit_account_container">
				<div class="b2bking_subaccounts_new_account_container_top">
					<div class="b2bking_subaccounts_new_account_container_top_title">
						<?php esc_html_e('Edit Subaccount', 'b2bking'); ?>
					</div>
					<div class="b2bking_subaccounts_edit_account_container_top_close">
						<?php esc_html_e('Close X', 'b2bking'); ?>
					</div>
				</div>
				<div class="b2bking_subaccounts_new_account_container_content">
					<div class="b2bking_subaccounts_new_account_container_content_large_title b2bking_subaccount_top_margin">
						<svg class="b2bking_subaccounts_new_account_container_content_large_title_icon" xmlns="http://www.w3.org/2000/svg" width="35" height="29" fill="none" viewBox="0 0 35 29">
						  <path fill="#4E4E4E" d="M12.25 14.063c3.867 0 7-3.148 7-7.031 0-3.884-3.133-7.032-7-7.032-3.866 0-7 3.148-7 7.032 0 3.883 3.134 7.031 7 7.031zm4.9 1.758h-.913a9.494 9.494 0 01-3.986.879 9.512 9.512 0 01-3.987-.88H7.35C3.292 15.82 0 19.129 0 23.205v2.285a2.632 2.632 0 002.625 2.637H17.66a2.648 2.648 0 01-.142-1.17l.372-3.346.066-.61.432-.433 4.227-4.247c-1.34-1.521-3.281-2.5-5.463-2.5zm2.478 7.982l-.372 3.35a.873.873 0 00.963.968l3.33-.374 7.542-7.575-3.921-3.94-7.542 7.57zm14.99-9.031l-2.072-2.082a1.306 1.306 0 00-1.849 0l-2.067 2.076-.224.225 3.927 3.94 2.285-2.297a1.327 1.327 0 000-1.862z"/>
						</svg>
						<?php esc_html_e('Personal Details', 'b2bking'); ?>
					</div>
					<div class="b2bking_subaccounts_new_account_container_content_element">
						<div class="b2bking_subaccounts_new_account_container_content_element_label">
							<?php esc_html_e('Name','b2bking'); ?>
						</div>
						<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_name" placeholder="<?php esc_attr_e('Enter the account holder\'s name here...','b2bking'); ?>" value="<?php echo esc_attr($name);?>">
					</div>
					<div class="b2bking_subaccounts_new_account_container_content_element">
						<div class="b2bking_subaccounts_new_account_container_content_element_label">
							<?php esc_html_e('Job Title','b2bking'); ?>
						</div>
						<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_job_title" placeholder="<?php esc_attr_e('Enter the account holder\'s title here...','b2bking'); ?>" value="<?php echo esc_attr($job_title);?>">
					</div>
					<div class="b2bking_subaccounts_new_account_container_content_element b2bking_subaccount_horizontal_line">
						<div class="b2bking_subaccounts_new_account_container_content_element_label">
							<?php esc_html_e('Phone Number','b2bking'); ?>
						</div>
						<input type="text" class="b2bking_subaccounts_new_account_container_content_element_text" name="b2bking_subaccounts_new_account_phone_number" placeholder="<?php esc_attr_e('Enter the account holder\'s phone here...','b2bking'); ?>" value="<?php echo esc_attr($phone);?>">
					</div>
					<div class="b2bking_subaccounts_new_account_container_content_large_title b2bking_subaccount_top_margin">
						<svg class="b2bking_subaccounts_new_account_container_content_large_title_icon" xmlns="http://www.w3.org/2000/svg" width="35" height="24" fill="none" viewBox="0 0 35 24">
						  <path fill="#575757" d="M16.042 8.75v2.917h-1.459v2.916h-2.916v-2.916H8.502a4.36 4.36 0 01-4.127 2.916 4.375 4.375 0 110-8.75A4.36 4.36 0 018.502 8.75h7.54zm-11.667 0a1.458 1.458 0 100 2.917 1.458 1.458 0 000-2.917zm18.958 5.833c3.894 0 11.667 1.955 11.667 5.834v2.916H11.667v-2.916c0-3.88 7.773-5.834 11.666-5.834zm0-2.916a5.833 5.833 0 110-11.667 5.833 5.833 0 010 11.667z"/>
						</svg>
						<?php esc_html_e('Permissions', 'b2bking'); ?>
					</div>
					<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
						<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
							<?php esc_html_e('Checkout (place order)','b2bking'); ?>
						</div>
						<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_buy" <?php checked(true, $permission_buy, true); ?>>
					</div>
					<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
						<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
							<?php esc_html_e('View all account orders','b2bking'); ?>
						</div>
						<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_orders" <?php checked(true, $permission_view_orders, true); ?>>
					</div>
					<?php if (intval(get_option('b2bking_enable_offers_setting', 1)) === 1){ ?>
						<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
								<?php esc_html_e('View all account offers','b2bking'); ?>
							</div>
							<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_offers" <?php checked(true, $permission_view_offers, true); ?>>
						</div>
					<?php } ?>
					<?php if (intval(get_option('b2bking_enable_conversations_setting', 1)) === 1){ ?>
						<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
								<?php esc_html_e('View all account conversations','b2bking'); ?>
							</div>
							<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_conversations" <?php checked(true, $permission_view_conversations, true); ?>>
						</div>
					<?php } ?>
					<?php if (intval(get_option('b2bking_enable_purchase_lists_setting', 1)) === 1){ ?>
						<div class="b2bking_subaccounts_new_account_container_content_element_checkbox">
							<div class="b2bking_subaccounts_new_account_container_content_element_checkbox_name">
								<?php esc_html_e('View all account purchase lists','b2bking'); ?>
							</div>
							<input type="checkbox" value="1" class="b2bking_subaccounts_new_account_container_content_element_checkbox_input" name="b2bking_subaccounts_new_account_container_content_element_checkbox_view_lists" <?php checked(true, $permission_view_lists, true); ?>>
						</div>
					<?php } ?>
					<div class="b2bking_subaccounts_new_account_container_content_bottom">
						<button class="b2bking_subaccounts_edit_account_container_content_bottom_button_delete" type="button" value="<?php echo esc_attr($subaccount_id); ?>">
							<svg class="b2bking_subaccounts_new_account_container_content_bottom_button_icon" xmlns="http://www.w3.org/2000/svg" width="32" height="33" fill="none" viewBox="0 0 32 33">
							  <path fill="#fff" d="M11 16.572c2.743 0 4.813-2.07 4.813-4.813S13.742 6.946 11 6.946s-4.813 2.07-4.813 4.813 2.07 4.813 4.813 4.813zm1.375 1.303h-2.75A6.883 6.883 0 002.75 24.75v1.375h16.5V24.75a6.883 6.883 0 00-6.875-6.875zm15.528-6.472l-3.153 3.153-3.153-3.153-1.944 1.944 3.151 3.152-3.152 3.152 1.944 1.945 3.153-3.153 3.154 3.154 1.944-1.944-3.153-3.153 3.153-3.153-1.944-1.944z"/>
							</svg>
							<?php esc_html_e('Delete subaccount', 'b2bking'); ?>
						</button>
						<button class="b2bking_subaccounts_edit_account_container_content_bottom_button" type="button" value="<?php echo esc_attr($subaccount_id); ?>">
							<svg class="b2bking_subaccounts_new_account_container_content_bottom_button_icon" xmlns="http://www.w3.org/2000/svg" width="29" height="21" fill="none" viewBox="0 0 29 21">
							  <path fill="#fff" d="M8.626 10.063c2.868 0 5.032-2.163 5.032-5.031S11.494 0 8.626 0 3.594 2.164 3.594 5.032s2.164 5.031 5.032 5.031zm1.437 1.363H7.188C3.225 11.426 0 14.651 0 18.614v1.438h17.252v-1.438c0-3.963-3.225-7.188-7.189-7.188zM26.3 4.658l-6.182 6.17-1.857-1.857-2.033 2.033 3.89 3.887 8.212-8.197-2.03-2.036z"/>
							</svg>
							<?php esc_html_e('Update subaccount', 'b2bking'); ?>
						</button>
					</div>
				</div>
			</div>


			<?php

		} else {
			// no permission
			esc_html_e('Subaccount does not exist!','b2bking');
		}

	}

	function b2bking_purchase_lists_endpoint_content(){

		echo do_shortcode('[b2bking_purchaselists]');

	}

	// Content of individual purchase list in my account (based on bulk order form content)
	function b2bking_purchase_list_endpoint_content(){
		// get list name
		$purchase_list_id = sanitize_text_field( $_GET['id'] );
		$list_author_id = get_post_field( 'post_author', $purchase_list_id );

		// check permissions
		$current_user = get_current_user_id();
		$subaccounts_list = explode(',', get_user_meta($current_user, 'b2bking_subaccounts_list', true));
		$subaccounts_list = array_filter($subaccounts_list); // filter blank, null, etc.
		array_push($subaccounts_list, $current_user);

		// if current account is subaccount AND has permission to view all account purchase lists, add parent account + all subaccounts 
		$account_type = get_user_meta($current_user, 'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			$permission_view_all_lists = filter_var(get_user_meta($current_user, 'b2bking_account_permission_view_lists', true),FILTER_VALIDATE_BOOLEAN);
			if ($permission_view_all_lists === true){

				// has permission, add all account orders (parent+parent subaccount list orders)
				$parent_account = get_user_meta($current_user, 'b2bking_account_parent', true);
				$parent_subaccounts_list = explode(',', get_user_meta($parent_account, 'b2bking_subaccounts_list', true));
				$parent_subaccounts_list = array_filter($parent_subaccounts_list); // filter blank, null, etc.
				array_push($parent_subaccounts_list, $parent_account); // add parent itself to form complete parent accounts list

				$subaccounts_list = array_merge($subaccounts_list, $parent_subaccounts_list);
			}
		}

		if (in_array($list_author_id, $subaccounts_list )){
			// has permission to view purchase list
			$list_title = get_the_title($purchase_list_id);
			$list_details = get_post_meta($purchase_list_id, 'b2bking_purchase_list_details', true);
			$list_items = explode('|', $list_details);
			$list_items = array_filter($list_items);
			?>
			<div class="b2bking_bulkorder_form_container">
				<div class="b2bking_bulkorder_form_container_top">
					<?php echo esc_html($list_title); ?>
				</div>
				<div class="b2bking_bulkorder_form_container_content">
					<div class="b2bking_bulkorder_form_container_content_header">
						<div class="b2bking_bulkorder_form_container_content_header_product">
							<?php esc_html_e('Product', 'b2bking'); ?>
	            		</div>
	            		<div class="b2bking_bulkorder_form_container_content_header_qty">
	            			<?php esc_html_e('Qty', 'b2bking'); ?>
	            		</div>
	            		<div class="b2bking_bulkorder_form_container_content_header_subtotal">
	            			<?php esc_html_e('Subtotal', 'b2bking'); ?>
	            		</div>
					</div>

					<?php 
						$total = 0;

						require_once ( B2BKING_DIR . 'public/class-b2bking-helper.php' );
						$helper = new B2bking_Helper();

						foreach ($list_items as $list_item){
							$item = explode(':', $list_item);
							$product_id = $item[0];
							$product_qty = $item[1];
							$productobj = wc_get_product($product_id);

							$product_title = $productobj->get_name();
							//if product is a variation with 3 or more attributes, need to change display because get_name doesnt 
							// show items correctly
							if (is_a($productobj,'WC_Product_Variation')){
								$attributes = $productobj->get_variation_attributes();
								$number_of_attributes = count($attributes);
								if ($number_of_attributes > 2){
									$product_title.=' - ';
									foreach ($attributes as $attribute){
										$product_title.=$attribute.', ';
									}
									$product_title = substr($product_title, 0, -2);
								}
							}

							if( $productobj->is_on_sale() ) {
							    $product_price = $productobj -> get_sale_price();
							} else {
								$product_price = $productobj -> get_price();
							}

							$product_price = round(floatval($helper->b2bking_wc_get_price_to_display( $productobj, array( 'price' => $product_price))),2);
							
							$subtotal = $product_qty * $product_price;
							$total += $subtotal;
							?>
							<input type="hidden" id="b2bking_purchase_list_page" value="1"><div class="b2bking_bulkorder_form_container_content_line"><input type="text" class="b2bking_bulkorder_form_container_content_line_product b2bking_selected_product_id_<?php echo esc_attr($product_id); ?>" placeholder="<?php esc_attr_e('Search for a product...','b2bking'); ?>" value="<?php echo esc_attr($product_title); ?>" disabled><button class="b2bking_bulkorder_clear"><?php esc_html_e('Clear X','b2bking'); ?></button><input type="number" min="0" class="b2bking_bulkorder_form_container_content_line_qty" value="<?php echo esc_attr($product_qty); ?>"><div class="b2bking_bulkorder_form_container_content_line_subtotal"><?php echo get_woocommerce_currency_symbol().esc_html($subtotal); ?></div><div class="b2bking_bulkorder_form_container_content_line_livesearch"></div></div>
							<?php
						}

					?>

	            	<!-- new line button -->
	            	<div class="b2bking_bulkorder_form_container_newline_container">
	            		<button class="b2bking_bulkorder_form_container_newline_button">
	            			<svg class="b2bking_bulkorder_form_container_newline_button_icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
	            			  <path fill="#fff" d="M11 1.375c-5.315 0-9.625 4.31-9.625 9.625s4.31 9.625 9.625 9.625 9.625-4.31 9.625-9.625S16.315 1.375 11 1.375zm4.125 10.14a.172.172 0 01-.172.172h-3.265v3.266a.172.172 0 01-.172.172h-1.032a.172.172 0 01-.171-.172v-3.265H7.046a.172.172 0 01-.172-.172v-1.032c0-.094.077-.171.172-.171h3.266V7.046c0-.095.077-.172.171-.172h1.032c.094 0 .171.077.171.172v3.266h3.266c.095 0 .172.077.172.171v1.032z"/>
	            			</svg>
	            			<?php esc_html_e('new line', 'b2bking'); ?>
	            		</button>
	            	</div>

	            	<!-- add to cart button -->
	            	<div class="b2bking_bulkorder_form_container_bottom">
	            		<div class="b2bking_bulkorder_form_container_bottom_add">
	            			<button class="b2bking_bulkorder_form_container_bottom_add_button" type="button">
	            				<svg class="b2bking_bulkorder_form_container_bottom_add_button_icon" xmlns="http://www.w3.org/2000/svg" width="21" height="19" fill="none" viewBox="0 0 21 19">
	            				  <path fill="#fff" d="M18.401 11.875H7.714l.238 1.188h9.786c.562 0 .978.53.854 1.087l-.202.901a2.082 2.082 0 011.152 1.87c0 1.159-.93 2.096-2.072 2.079-1.087-.016-1.981-.914-2.01-2.02a2.091 2.091 0 01.612-1.543H8.428c.379.378.614.903.614 1.485 0 1.18-.967 2.131-2.14 2.076-1.04-.05-1.886-.905-1.94-1.964a2.085 2.085 0 011.022-1.914L3.423 2.375H.875A.883.883 0 010 1.485V.89C0 .399.392 0 .875 0h3.738c.416 0 .774.298.857.712l.334 1.663h14.32c.562 0 .978.53.854 1.088l-1.724 7.719a.878.878 0 01-.853.693zm-3.526-5.64h-1.75V4.75a.589.589 0 00-.583-.594h-.584a.589.589 0 00-.583.594v1.484h-1.75a.589.589 0 00-.583.594v.594c0 .328.26.594.583.594h1.75V9.5c0 .328.261.594.583.594h.584a.589.589 0 00.583-.594V8.016h1.75a.589.589 0 00.583-.594v-.594a.589.589 0 00-.583-.594z"/>
	            				</svg>
	            			<?php esc_html_e('Add to Cart','b2bking'); ?>
	            			</button>
	            			<button class="b2bking_bulkorder_form_container_bottom_update_button" type="button" value="<?php echo esc_attr($purchase_list_id); ?>">
	            				<svg class="b2bking_bulkorder_form_container_bottom_update_button_icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 22 22">
	            				  <path fill="#fff" d="M9.778 4.889h7.333v2.444H9.778V4.89zm0 4.889h7.333v2.444H9.778V9.778zm0 4.889h7.333v2.444H9.778v-2.444zm-4.89-9.778h2.445v2.444H4.89V4.89zm0 4.889h2.445v2.444H4.89V9.778zm0 4.889h2.445v2.444H4.89v-2.444zM20.9 0H1.1C.489 0 0 .489 0 1.1v19.8c0 .489.489 1.1 1.1 1.1h19.8c.489 0 1.1-.611 1.1-1.1V1.1c0-.611-.611-1.1-1.1-1.1zm-1.344 19.556H2.444V2.444h17.112v17.112z"/>
	            				</svg>
	            			<?php esc_html_e('Update list','b2bking'); ?>
	            			</button>
	            			<button class="b2bking_bulkorder_form_container_bottom_delete_button" type="button" value="<?php echo esc_attr($purchase_list_id); ?>">
	            				<svg class="b2bking_bulkorder_form_container_bottom_delete_button_icon" xmlns="http://www.w3.org/2000/svg" width="29" height="29" fill="none" viewBox="0 0 29 29">
	            				  <path fill="#fff" d="M17.4 5.8h4.35c.87 0 1.45.58 1.45 1.45V8.7H4.35V7.25c0-.87.725-1.45 1.45-1.45h4.35c.29-1.595 1.885-2.9 3.625-2.9S17.11 4.205 17.4 5.8zm-5.8 0h4.35c-.29-.87-1.305-1.45-2.175-1.45-.87 0-1.885.58-2.175 1.45zm-5.8 4.35h15.95l-1.305 14.645c0 .725-.725 1.305-1.45 1.305H8.555c-.725 0-1.305-.58-1.45-1.305L5.8 10.15z"/>
	            				</svg>
	            			<?php esc_html_e('Trash','b2bking'); ?>
	            			</button>
	            		</div>
	            		<div class="b2bking_bulkorder_form_container_bottom_total">
	            			<?php esc_html_e('Total: ','b2bking'); ?><strong><?php echo wc_price($total);?></strong>
	            		</div>
	            	</div>


	            </div>
			</div>
			<?php
		} else {
			esc_html_e('Purchase list does not exist!', 'b2bking');
		}

	}

	// Add "Save as Purchase List" button to cart
	function b2bking_purchase_list_cart_button(){
		// should never appear to a guest user + check setting
		if (is_user_logged_in() && (intval(get_option('b2bking_enable_purchase_lists_setting', 1)) === 1)){
			// should not appear if user has a dynamic rule replace prices with quote
			$user_id = get_current_user_id();
	    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
	    	if ($account_type === 'subaccount'){
	    		// for all intents and purposes set current user as the subaccount parent
	    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
	    		$user_id = $parent_user_id;
	    	}
	    	if (get_transient('b2bking_replace_prices_quote_user_'.$user_id) !== 'yes'){
			?>
				<button type="button" class="b2bking_add_cart_to_purchase_list_button button">
					<?php esc_html_e( 'Save as purchase list', 'b2bking' ); ?>
				</button>
			<?php
			}
		}
	}

	// Add "Placed by" column to orders
	function b2bking_orders_placed_by_column( $columns ) {

	    $new_columns = array();
	    foreach ( $columns as $key => $name ) {
	        $new_columns[ $key ] = $name;
	        // add ship-to after order status column
	        if ( 'order-number' === $key ) {
	            $new_columns['order-placed-by'] = esc_html__( 'Placed by', 'b2bking' );
	        }
	    }
	    return $new_columns;
	}

	// Add content to the "Placed by" column
	function b2bking_orders_placed_by_column_content( $order ) {
	    $customer_id = $order->get_customer_id();
	    $username = get_user_by('id', $customer_id)->user_login;
	    echo esc_html($username);
	}

	// Show user subaccount orders as well
	function b2bking_add_subaccounts_orders_to_main_query( $q ) {
		// Set customer orders to Current User + Subaccounts
		$current_user = get_current_user_id();
		$subaccounts_list = explode(',', get_user_meta($current_user, 'b2bking_subaccounts_list', true));
		$subaccounts_list = array_filter($subaccounts_list); // filter blank, null, etc.
		// add current user to subaccounts to form a complete accounts list
		array_push($subaccounts_list, $current_user);

		// if current account is subaccount AND has permission to view all account orders, add parent account+all subaccounts orders
		$account_type = get_user_meta($current_user, 'b2bking_account_type', true);
		if ($account_type === 'subaccount'){
			$permission_view_all_orders = filter_var(get_user_meta($current_user, 'b2bking_account_permission_view_orders', true),FILTER_VALIDATE_BOOLEAN);
			if ($permission_view_all_orders === true){

				// has permission, add all account orders (parent+parent subaccount list orders)
				$parent_account = get_user_meta($current_user, 'b2bking_account_parent', true);
				$parent_subaccounts_list = explode(',', get_user_meta($parent_account, 'b2bking_subaccounts_list', true));
				$parent_subaccounts_list = array_filter($parent_subaccounts_list); // filter blank, null, etc.
				array_push($parent_subaccounts_list, $parent_account); // add parent itself to form complete parent accounts list

				$subaccounts_list = array_merge($subaccounts_list, $parent_subaccounts_list);
			}
		}

	    $q['customer'] = $subaccounts_list; 
	    return $q;
	}

	// Give user permission to access subaccount orders
	function b2bking_give_main_account_view_subaccount_orders_permission( $allcaps, $cap, $args ) {
		if (isset($cap[0])){
		    if ( $cap[0] === 'view_order' ) {
		    	// build list of current user and subaccounts
		    	$current_user = get_current_user_id();
		    	$subaccounts_list = explode(',', get_user_meta($current_user, 'b2bking_subaccounts_list', true));
		    	$subaccounts_list = array_filter($subaccounts_list); // filter blank, null, etc.
		    	array_push($subaccounts_list, $current_user);

		    	// if current account is subaccount AND has permission to view all account orders, add parent account + all subaccounts orders
		    	$account_type = get_user_meta($current_user, 'b2bking_account_type', true);
		    	if ($account_type === 'subaccount'){
		    		$permission_view_all_orders = filter_var(get_user_meta($current_user, 'b2bking_account_permission_view_orders', true),FILTER_VALIDATE_BOOLEAN);
		    		if ($permission_view_all_orders === true){

		    			// has permission, add all account orders (parent+parent subaccount list orders)
		    			$parent_account = get_user_meta($current_user, 'b2bking_account_parent', true);
		    			$parent_subaccounts_list = explode(',', get_user_meta($parent_account, 'b2bking_subaccounts_list', true));
		    			$parent_subaccounts_list = array_filter($parent_subaccounts_list); // filter blank, null, etc.
		    			array_push($parent_subaccounts_list, $parent_account); // add parent itself to form complete parent accounts list

		    			$subaccounts_list = array_merge($subaccounts_list, $parent_subaccounts_list);
		    		}
		    	}

		    	// check if the current order is part of the list
		    	$order_placed_by = wc_get_order( $args[2] )->get_customer_id();
		    	if (in_array($order_placed_by, $subaccounts_list)){
		    		// give permission
		    		$allcaps[ $cap[0] ] = true;
		    	}
		    }
		}
	    return ( $allcaps );
	}

	// Give permissions to order again
	function b2bking_subaccounts_orderagain_cap( $allcaps, $cap, $args ) {
		if (isset($cap[0])){
		    if ( $cap[0] === 'order_again' ) {
		    	// build list of current user and subaccounts
		    	$current_user = get_current_user_id();
		    	$subaccounts_list = explode(',', get_user_meta($current_user, 'b2bking_subaccounts_list', true));
		    	$subaccounts_list = array_filter($subaccounts_list); // filter blank, null, etc.
		    	array_push($subaccounts_list, $current_user);

		    	// if current account is subaccount AND has permission to view all account orders, add parent account + all subaccounts orders
		    	$account_type = get_user_meta($current_user, 'b2bking_account_type', true);
		    	if ($account_type === 'subaccount'){
		    		$permission_view_all_orders = filter_var(get_user_meta($current_user, 'b2bking_account_permission_view_orders', true),FILTER_VALIDATE_BOOLEAN);
		    		if ($permission_view_all_orders === true){

		    			// has permission, add all account orders (parent+parent subaccount list orders)
		    			$parent_account = get_user_meta($current_user, 'b2bking_account_parent', true);
		    			$parent_subaccounts_list = explode(',', get_user_meta($parent_account, 'b2bking_subaccounts_list', true));
		    			$parent_subaccounts_list = array_filter($parent_subaccounts_list); // filter blank, null, etc.
		    			array_push($parent_subaccounts_list, $parent_account); // add parent itself to form complete parent accounts list

		    			$subaccounts_list = array_merge($subaccounts_list, $parent_subaccounts_list);
		    		}
		    	}

		    	// check if the current order is part of the list
		    	$order_placed_by = wc_get_order( $args[2] )->get_customer_id();
		    	if (in_array($order_placed_by, $subaccounts_list)){
		    		// give permission
		    		$allcaps[ $cap[0] ] = true;
		    	}
		    }
		}
	    return ( $allcaps );
	}


	// If multisite, restrict B2C access to my account on main B2B site
	function b2bking_multisite_logout_user_myaccount(){
		if (is_user_logged_in() && (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser',true ) !== 'yes')){
			if (is_account_page()){
				wp_logout();
			}
		}
	}
	// Hide prices to guest users
	function b2bking_hide_prices_guest_users( $price, $product ) {
		// if user is guest, OR multisite B2B/B2C separation is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){
			return get_option('b2bking_hide_prices_guests_text_setting', esc_html__('Login to view prices','b2bking'));
		} else {
			return $price;
		}
	}

	function b2bking_hide_prices_request_quote( $price, $product ) {
		return '';
	}

	function b2bking_disable_purchasable_guest_users($purchasable){
		// if user is guest, or multisite b2b/b2b separation is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){
			return false;
		} else {
			return $purchasable;
		}
	}

	function b2bking_replace_add_to_cart_text() {
		return esc_html__('Add to Quote Request', 'b2bking');
	}

	function b2bking_hide_prices_cart( $price ) {
		return esc_html__('Quote','b2bking');
	}

	function b2bking_checkout_redirect_to_cart(){
		// only for checkout
	    if ( ! is_checkout() ) return; 
	    	wp_redirect( get_permalink( wc_get_page_id( 'cart' ) ) ); // redirect to cart.
	    exit();
	}

	/* Hide Website completely to guest users */
	function b2bking_hide_products( $q ) {
		// User is guest, or multisite option is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){		
		    $tax_query = (array) $q->get( 'tax_query' );
		    $tax_query[] = array(
		           'taxonomy' => 'product_cat',
		           'field' => 'slug',
		           'terms' => array( 'j2kh87ds5gjsfd3dfsZn21bd89d' ), // don't show any products
		           'operator' => 'IN'
		    );
		    $q->set( 'tax_query', $tax_query );
		}
	}
	function b2bking_hide_products_shortcode( $query_args ) {
		// User is guest, or multisite option is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){		
		    $query_args['post__in'] = array('j2kh87ds5gjsfd3dfsZn21bd89d');
		}
		return $query_args;
	}

	function b2bking_show_login() {
		// User is guest, or multisite option is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){	
			remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );

			$message = get_option('b2bking_hide_b2b_site_text_setting', esc_html__('Please login to access the B2B Portal.','b2bking'));
			echo '<p class="woocommerce-info">' . esc_html($message) .'</p>';

			echo do_shortcode( '[woocommerce_my_account]' );
		}
	} 
	function b2bking_product_redirection_to_account() {
		// User is guest, or multisite option is enabled and user should be treated as guest
		if (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')){	
		    if ( ! is_product() ) return; // Only for single product pages.
		    	wp_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) ); // redirect home.
		    exit();
		}
	}

	function b2bking_member_only_site() {
	    if ( !is_user_logged_in() && (get_current_user_id() === 0) ) {
	        auth_redirect();
	    }
	}

	// if user accesses product that he doesn't have access to, redirect to my account
	function b2bking_invisible_product_redirection_to_account() {
	    if ( ! is_product() ){
	    	return; // Only for single product pages.
	    }
	    if ( is_preview() ){
	    	return;
	    }
		$has_access = true;
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

				// Get all categories
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
						$visible = 'no';
						foreach ($userarray as $user){
							if (trim($user) === $currentuserlogin){
								array_push($visiblecategories, $term);
								$visible = 'yes';
								break;
							}
						}
						if ($visible === 'no'){
							array_push($hiddencategories, $term);
						}
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


			    // if caching is enabled
			    if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){

			        // cache query results
			    	if (!get_transient('b2bking_user_'.get_current_user_id().'_visibility')){
			        	$queryA = new WP_Query($queryAparams);
			        	$queryB = new WP_Query($queryBparams);
			       	 	// Merge the 2 queries in an IDs array
			       		$allTheIDs = array_merge($queryA->posts,$queryB->posts);
			       		set_transient('b2bking_user_'.get_current_user_id().'_visibility', $allTheIDs);
			       	} else {
			       		$allTheIDs = get_transient('b2bking_user_'.get_current_user_id().'_visibility');
			       	}

			    } else {

		    	 	$queryA = new WP_Query($queryAparams);
			    	$queryB = new WP_Query($queryBparams);
			     	// Merge the 2 queries in an IDs array
			    	$allTheIDs = array_merge($queryA->posts,$queryB->posts);

			    }

			    if (in_array(get_the_ID(), $allTheIDs)){
			    	$has_access = true;
			    } else {
			    	$has_access = false;
			    }
			}
		}
		
	    if ( ! $has_access ){
		   wp_redirect( get_permalink( wc_get_page_id( 'myaccount' ) ) ); // redirect home.
	    } else {
	    	return;
	    }
		exit();
	}

	function b2bking_init_set_excluded_categories(){

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

				// Build Visible Categories 
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
						$visible = 'no';
						foreach ($userarray as $user){
							if (trim($user) === $currentuserlogin){
								array_push($visiblecategories, $term);
								$visible = 'yes';
								break;
							}
						}
						if ($visible === 'no'){
							array_push($hiddencategories, $term);
						}
					}
				}
				if (!get_transient('b2bking_user_exclude_categories_id_'.get_current_user_id())){
					set_transient('b2bking_user_exclude_categories_id_'.get_current_user_id(), $hiddencategories);
				}
			} else{
				delete_transient('b2bking_user_exclude_categories_id_'.get_current_user_id());
			}
		} else {
			delete_transient('b2bking_user_exclude_categories_id_'.get_current_user_id());
		}
	}

	function b2bking_categories_restrict( $args, $taxonomies ) {
		if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){

			if ( get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){

				if ( is_admin() && 'category' !== $taxonomies[0] ){
				    return $args;
				}

				if (get_transient('b2bking_user_exclude_categories_id_'.get_current_user_id())){
					$args['exclude'] = get_transient('b2bking_user_exclude_categories_id_'.get_current_user_id()); // Array of cat ids to exclude
				}
				return $args;
			}
		}
		return $args;
	}

	// Filter category count
	function b2bking_category_count_filter( $terms, $taxonomy ) {
		// if caching is enabled
		if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){

			if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){

				if ( get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){

					if (get_option('woocommerce_shop_page_display') === 'subcategories' || get_option('woocommerce_category_archive_display') === 'subcategories'){

					    // cache query results
						if (get_transient('b2bking_user_'.get_current_user_id().'_visibility')){
					   		$allTheIDs = get_transient('b2bking_user_'.get_current_user_id().'_visibility');
					   	
						   	if ( in_array( 'product_cat', $taxonomy ) ) {
						   	    foreach ( $terms as $i => $term ) {
						   	        if ( is_a( $term, 'WP_Term' ) ) {
						   	        	// calculate how many IDs there are in this category
					   	        		$products_in_category = new WP_Query(array(
					   	        	        'posts_per_page' => -1,
					   	        	        'fields' => 'ids',
					   	        	        'post_type' => 'product',
					   	        	        'tax_query' => array(
				   	        	                array(
				   	        	                    'taxonomy'  => 'product_cat',
				   	        	                    'field'     => 'id', 
				   	        	                    'terms'     => $term->term_id,
				   	        	                )
				   	        	            ),
					   	        	        'post__in' => $allTheIDs,
					   	        	    ));

					   	        		$number = count($products_in_category->posts);
						   	        	
						   	            $terms[$i]->count = $number;
						   	        }
						   	    }
						   	}
					   	}
					}
				}
			}
		}
	    
	    return $terms;
	}


	// If user is logged in, set up product/category/user/user group visibility rules
	function b2bking_product_categories_visibility_rules( $q ){

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
						$visible = 'no';
						foreach ($userarray as $user){
							if (trim($user) === $currentuserlogin){
								array_push($visiblecategories, $term);
								$visible = 'yes';
								break;
							}
						}
						if ($visible === 'no'){
							array_push($hiddencategories, $term);
						}
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


			    // if caching is enabled
			    if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){

				    // cache query results
					if (!get_transient('b2bking_user_'.get_current_user_id().'_visibility')){
				    	$queryA = new WP_Query($queryAparams);
				    	$queryB = new WP_Query($queryBparams);
				   	 	// Merge the 2 queries in an IDs array
				   		$allTheIDs = array_merge($queryA->posts,$queryB->posts);
				   		set_transient('b2bking_user_'.get_current_user_id().'_visibility', $allTheIDs);
				   	} else {
				   		$allTheIDs = get_transient('b2bking_user_'.get_current_user_id().'_visibility');
				   	}

				} else {
				 	$queryA = new WP_Query($queryAparams);
				 	$queryB = new WP_Query($queryBparams);
					 // Merge the 2 queries in an IDs array
					$allTheIDs = array_merge($queryA->posts,$queryB->posts);
				}

			    if(!empty($allTheIDs)){
			    	$q->set('post__in',$allTheIDs);
				} else {
					// If the array is empty, WooCommerce shows all products. To fix this, we pass an invalid IDs array in that case.
					$q->set('post__in',array('invalidid'));
				}
			}
		}
	}


	// copied from above 
	function b2bking_product_categories_visibility_rules_shortcode( $query_args ){

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
						$visible = 'no';
						foreach ($userarray as $user){
							if (trim($user) === $currentuserlogin){
								array_push($visiblecategories, $term);
								$visible = 'yes';
								break;
							}
						}
						if ($visible === 'no'){
							array_push($hiddencategories, $term);
						}
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


			    // if caching is enabled
			    if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){

				    // cache query results
					if (!get_transient('b2bking_user_'.get_current_user_id().'_visibility')){
				    	$queryA = new WP_Query($queryAparams);
				    	$queryB = new WP_Query($queryBparams);
				   	 	// Merge the 2 queries in an IDs array
				   		$allTheIDs = array_merge($queryA->posts,$queryB->posts);
				   		set_transient('b2bking_user_'.get_current_user_id().'_visibility', $allTheIDs);
				   	} else {
				   		$allTheIDs = get_transient('b2bking_user_'.get_current_user_id().'_visibility');
				   	}

				} else {
				 	$queryA = new WP_Query($queryAparams);
				 	$queryB = new WP_Query($queryBparams);
					 // Merge the 2 queries in an IDs array
					$allTheIDs = array_merge($queryA->posts,$queryB->posts);
				}

			    if(!empty($allTheIDs)){
			    	// check if it is already set to something
			    	// in the widget it can be set to products that are on sale
			    	if (empty($query_args['post__in'])){
			    		$query_args['post__in'] = $allTheIDs;
			    	} else {
			    		// intersect array
			    		$intersection = array_intersect($query_args['post__in'], $allTheIDs);
			    		$query_args['post__in'] = $intersection;
			    	}
				} else {
					// If the array is empty, WooCommerce shows all products. To fix this, we pass an invalid IDs array in that case.
			    	$query_args['post__in'] = array('invalidid');
				}
				return $query_args;
			}
		}
	}

	/* Functions that handle Reordering	*/
	// Add reorder button in account orders (overview)
	function b2bking_add_reorder_button_overview( $actions, $order ) {

	if ( ! $order || ! is_user_logged_in() ) {
		return $actions;
	}

	// check if order is completed
	if ( $order->has_status( apply_filters( 'woocommerce_valid_order_statuses_for_order_again', array( 'completed' ) ) ) ) { 
		$actions['order-again'] = array(
		'url'  => wp_nonce_url( add_query_arg( 'order_again', $order->get_id() ) , 'woocommerce-order_again' ),
		'name' => esc_html__( 'Order again', 'b2bking' )
		);
	}
	return $actions;

	}

	function b2bking_reorder_save_old_order_id( $order_id ) {
		WC()->session->set( 'b2bking_reorder_from_orderid', $order_id );
	}

	function b2bking_reorder_create_order_note_reference( $order_id ) {
		$reorder_id = WC()->session->get( 'b2bking_reorder_from_orderid');
		if ($reorder_id != '' ) {
            add_post_meta( $order_id, '_reorder_from_id', $reorder_id, true );
            $order = wc_get_order( $order_id );
            $url = get_edit_post_link( $reorder_id );
            $note = esc_html__('This is a reorder of order ','b2bking').'<a href="'.esc_url($url).'">'.esc_html($reorder_id).'</a>'.esc_html__('. Please note, however, that customers may have changed the items/quantity ordered — Note by B2BKing.','b2bking');
            $order->add_order_note( apply_filters( 'b2bking_reorder_order_note', $note, $reorder_id, $order_id ) );
		}
		WC()->session->set( 'b2bking_reorder_from_orderid' , null );
	}

	function b2bking_subaccount_order_note( $order_id ) {
        $order = wc_get_order( $order_id );
        $customer_id = $order->get_customer_id();
        $account_type = get_user_meta($customer_id, 'b2bking_account_type', true);
        if ($account_type === 'subaccount'){
        	$parent_id = intval(get_user_meta($customer_id,'b2bking_account_parent', true));
        	$parent_user = new WP_User($parent_id);
        	$parent_login = $parent_user->user_login;

	        $note = esc_html__('This is an order placed by a subaccount of the user ','b2bking').'<a href="'.esc_attr(get_edit_user_link($parent_id)).'">'.esc_html($parent_login).'</a>';
	        $order->add_order_note( $note);
    	}
	}


	// Add "Request a Quote" button
	function b2bking_add_request_quote_button(){

		// If Conversations are enabled in settings
		if (intval(get_option('b2bking_enable_conversations_setting', 1)) === 1){
			if ((get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'replace_prices_quote') && (!is_user_logged_in() || (intval(get_option( 'b2bking_multisite_separate_b2bb2c_setting', 0 )) === 1 && get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes')) ){
				// continue
			} else {
				$quote_button_setting = get_option('b2bking_quote_button_cart_setting', 'enableb2b');
				if ($quote_button_setting === 'disabled'){
					return;
				} else if ($quote_button_setting === 'enableb2b'){
					// return if user is not b2b
					if (get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes'){
						return;
					}
				} else if ($quote_button_setting === 'enableb2c'){
					// return if user is b2b
					if (get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) === 'yes'){
						return;
					}
				} else if ($quote_button_setting === 'enableall'){
					// continue
				}
			}
			// If user is a guest or B2C and this is a Quote Request initiated by a guest, add "Name" and "Email address"
			if (!is_user_logged_in() || get_user_meta(get_current_user_id(), 'b2bking_b2buser', true) !== 'yes'){
				?>
				<span class="b2bking_request_custom_quote_text_label"><?php esc_html_e('Your name:','b2bking'); ?></span>
				<input type="text" id="b2bking_request_custom_quote_name" name="b2bking_request_custom_quote_name">
				<span class="b2bking_request_custom_quote_text_label"><?php esc_html_e('Your email address:','b2bking'); ?></span>
				<input type="text" id="b2bking_request_custom_quote_email" name="b2bking_request_custom_quote_email">
				<?php
			}
			?>
			<span id="b2bking_request_custom_quote_textarea_abovetext"><?php esc_html_e('Your message:','b2bking'); ?></span>
			<textarea id="b2bking_request_custom_quote_textarea"></textarea>
			<button type="button" id="b2bking_request_custom_quote_button" class="button">
				<?php esc_html_e('Request custom quote','b2bking'); ?>
			</button>
		<?php
		}
	}

	// clear user tax cache when checkout is rendered
	function b2bking_clear_tax_cache_checkout(){
		delete_option('_transient_b2bking_tax_exemption_user_'.get_current_user_id());
	}

	/* 
	* Replaces price with quote requests (dynamic rule)
	* returns 'yes' or 'no' string
	*/
	function dynamic_replace_prices_with_quotes(){

		// Get current user
		$user_id = get_current_user_id();

    	$account_type = get_user_meta($user_id,'b2bking_account_type', true);
    	if ($account_type === 'subaccount'){
    		// for all intents and purposes set current user as the subaccount parent
    		$parent_user_id = get_user_meta($user_id, 'b2bking_account_parent', true);
    		$user_id = $parent_user_id;
    	}

		$currentusergroupidnr = get_user_meta( $user_id, 'b2bking_customergroup', true );
		if (empty($currentusergroupidnr)){
			$currentusergroupidnr = 'nothave'; // necessary to avoid issues for guests
		}

		$array_who_multiple = array(
	                'relation' => 'OR',
	                array(
	                    'key' => 'b2bking_rule_who_multiple_options',
	                    'value' => 'group_'.$currentusergroupidnr,
	                	'compare' => 'LIKE'
	                ),
	                array(
	                    'key' => 'b2bking_rule_who_multiple_options',
	                    'value' => 'user_'.$user_id,
	                    'compare' => 'LIKE'
	                ),
	            );

		if ($user_id !== 0){
			array_push($array_who_multiple, array(
                'key' => 'b2bking_rule_who_multiple_options',
                'value' => 'everyone_registered',
                'compare' => 'LIKE'
            ));

			// add rules that apply to all registered b2b/b2c users
			$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
			if ($user_is_b2b === 'yes'){
				array_push($array_who_multiple, array(
                    'key' => 'b2bking_rule_who_multiple_options',
                    'value' => 'everyone_registered_b2b',
                    'compare' => 'LIKE'
                ));
			} else if ($user_is_b2b === 'no'){
				array_push($array_who_multiple, array(
                    'key' => 'b2bking_rule_who_multiple_options',
                    'value' => 'everyone_registered_b2c',
                    'compare' => 'LIKE'
                ));
			}
		}

		$array_who = array(
            'relation' => 'OR',
            array(
                'key' => 'b2bking_rule_who',
                'value' => 'group_'.$currentusergroupidnr
            ),
            array(
                'key' => 'b2bking_rule_who',
                'value' => 'user_'.$user_id
            ),
            array(
                'relation' => 'AND',
                array(
                    'key' => 'b2bking_rule_who',
                    'value' => 'multiple_options'
                ),
                $array_who_multiple
            ),
        );
		// if user is registered, also select rules that apply to all registered users
		if ($user_id !== 0){
			array_push($array_who, array(
	                        'key' => 'b2bking_rule_who',
	                        'value' => 'everyone_registered'
	                    ));

			// add rules that apply to all registered b2b/b2c users
			$user_is_b2b = get_user_meta($user_id, 'b2bking_b2buser', true);
			if ($user_is_b2b === 'yes'){
				array_push($array_who, array(
	                        'key' => 'b2bking_rule_who',
	                        'value' => 'everyone_registered_b2b'
	                    ));
			} else if ($user_is_b2b === 'no'){
				array_push($array_who, array(
	                        'key' => 'b2bking_rule_who',
	                        'value' => 'everyone_registered_b2c'
	                    ));
			}
		}

		$quote_request_rules = get_posts([
    		'post_type' => 'b2bking_rule',
    	  	'post_status' => 'publish',
    	  	'fields'        => 'ids', // Only get post IDs
    	  	'numberposts' => -1,
    	  	'meta_query'=> array(
                'relation' => 'AND',
                array(
                    'key' => 'b2bking_rule_what',
                    'value' => 'replace_prices_quote'
                ),
                $array_who,
            )
    	]);

		if (empty($quote_request_rules)){
			return 'no';
		} else {
			return 'yes';
		}

	}

	// check coupon validity based on role
	function b2bking_filter_woocommerce_coupon_is_valid( $is_valid, $coupon, $discount ) {
	    // Get meta
	    $b2bking_customer_user_role = $coupon->get_meta('b2bking_customer_user_role');

	    // if there is a restriction
	    if( ! empty( $b2bking_customer_user_role ) ) {

	        // Convert string to array
	        $allowed_roles_array = explode(',', $b2bking_customer_user_role);
	        $allowed_roles_array = array_map('trim', $allowed_roles_array);
	        // Get current user role
	        $user = new WP_User( get_current_user_id() );
	        $roles = ( array ) $user->roles;

	        $user_is_allowed = 'no';
	        // check if there is any allowed role that the user has
	        foreach ($roles as $user_role){
	        	if (in_array($user_role, $allowed_roles_array)){
	        		$user_is_allowed = 'yes';
	        		break;
	        	}
	        }

	        if ($user_is_allowed === 'no'){
		        // enable "loggedout", "b2c", "b2b"

		        // logged out
		        if (!is_user_logged_in()){
		        	if (in_array('loggedout', $allowed_roles_array) || in_array('guest', $allowed_roles_array)){
		        		$user_is_allowed = 'yes';
		        	}
		        } else {
			        // user is b2c
			        if (get_user_meta(get_current_user_id(),'b2bking_b2buser', true) !== 'yes'){
			        	if (in_array('b2c', $allowed_roles_array)){
			        		$user_is_allowed = 'yes';
			        	}
			        } else {
			        // user is b2b
			        	if (in_array('b2b', $allowed_roles_array)){
			        		$user_is_allowed = 'yes';
			        	}
			        }

			    }

		    }

	        if ($user_is_allowed === 'no'){
	        	$is_valid = false; 
	        }

	    }

	    return $is_valid;
	}

	function b2bking_custom_color() {
		$color = get_option( 'b2bking_color_setting', '#3AB1E4' );
		$colorhover = get_option( 'b2bking_colorhover_setting', '#0088c2' );
		?>

		<style type="text/css">
			.b2bking_myaccount_individual_offer_bottom_line_add button.b2bking_myaccount_individual_offer_bottom_line_button, #b2bking_myaccount_conversations_container_top button, button.b2bking_myaccount_start_conversation_button, .b2bking_myaccount_conversation_endpoint_button, button.b2bking_bulkorder_form_container_bottom_add_button, button.b2bking_subaccounts_container_top_button, button.b2bking_subaccounts_new_account_container_content_bottom_button, button.b2bking_subaccounts_edit_account_container_content_bottom_button, button#b2bking_purchase_list_new_button, button.b2bking_purchase_lists_view_list{
				background: <?php echo esc_html( $color ); ?>;
			}

			.b2bking_myaccount_individual_offer_bottom_line_add button:hover.b2bking_myaccount_individual_offer_bottom_line_button, #b2bking_myaccount_conversations_container_top button:hover, button:hover.b2bking_myaccount_start_conversation_button, .b2bking_myaccount_conversation_endpoint_button, button:hover.b2bking_bulkorder_form_container_bottom_add_button, button:hover.b2bking_subaccounts_container_top_button, button:hover.b2bking_subaccounts_new_account_container_content_bottom_button, button:hover.b2bking_subaccounts_edit_account_container_content_bottom_button, button:hover#b2bking_purchase_list_new_button, button:hover.b2bking_purchase_lists_view_list, .b2bking_myaccount_conversation_endpoint_button:hover{
				background: <?php echo esc_html( $colorhover ); ?>;
			}
		</style>
		<?php
	}

	function force_permalinks_rewrite() {
	    // Trigger post types and endpoints functions
	    require_once ( B2BKING_DIR . 'admin/class-b2bking-admin.php' );
	    $adminobj = new B2bking_Admin;

	    $this->b2bking_custom_endpoints();
	    
	    // Flush rewrite rules
	    flush_rewrite_rules();
	}

	function enqueue_public_resources(){

		// scripts and styles already registered by default
		wp_enqueue_script('jquery'); 

		// the following 3 scripts enable WooCommerce Country and State selectors
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );
		wp_enqueue_script( 'wc-country-select' );

		wp_enqueue_script('b2bking_public_script', plugins_url('assets/js/public.js', __FILE__), $deps = array(), $ver = false, $in_footer =true);
		wp_enqueue_style('b2bking_main_style', plugins_url('../includes/assets/css/style.css', __FILE__));

		wp_enqueue_script('dataTables', plugins_url('../includes/assets/lib/dataTables/jquery.dataTables.min.js', __FILE__), $deps = array(), $ver = false, $in_footer =true);
		wp_enqueue_style( 'dataTables', plugins_url('../includes/assets/lib/dataTables/jquery.dataTables.min.css', __FILE__));

		// activate password strength for my account custom pages
		wp_enqueue_script( 'wc-password-strength-meter' );

		// Get number of allowed countries and pass it to registration public.js 
		$countries = new WC_Countries;
		$countries_allowed = $countries->get_allowed_countries();
		$number_of_countries = count($countries_allowed);

		// Send display settings to JS
    	$data_to_be_passed = array(
    		'security'  => wp_create_nonce( 'b2bking_security_nonce' ),
    		'ajaxurl' => admin_url( 'admin-ajax.php' ),
    		'carturl' => wc_get_cart_url(),
    		'currency_symbol' => get_woocommerce_currency_symbol(),
    		'conversationurl' => wc_get_account_endpoint_url('conversation'), // conversation endpoint URL, for start conversation redirect
    		'subaccountsurl' => wc_get_account_endpoint_url('subaccounts'),
    		'purchaselistsurl' => wc_get_account_endpoint_url('purchase-lists'),
    		'newSubaccountUsernameError' => esc_html__('Username must be between 8 and 30 characters. ','b2bking'),
    		'newSubaccountEmailError' => esc_html__('Email is invalid. ','b2bking'),
    		'newSubaccountPasswordError' => esc_html__('Password must have minimum eight characters, at least one letter and one number. ','b2bking'),
    		'newSubaccountAccountError' => esc_html__('Account creation error. Username or Email are already taken. ','b2bking'),
    		'newSubaccountMaximumSubaccountsError' => esc_html__('You have reached the maximum number of subaccounts. ','b2bking'),
    		'are_you_sure_delete' => esc_html__('Are you sure you want to delete this subaccount?', 'b2bking'),
    		'are_you_sure_delete_list' => esc_html__('Are you sure you want to delete this purchase list?','b2bking'),
    		'no_products_found' => esc_html__('No products found...','b2bking'),
    		'save_list_name' => esc_html__('Name for the new purchase list:', 'b2bking'),
    		'list_saved' => esc_html__('List has been saved', 'b2bking'),
    		'quote_request_success' => esc_html__('Your quote request has been received. We will get back to you as soon as possible.', 'b2bking'),
    		'custom_quote_request' => esc_html__('Custom Quote Request', 'b2bking'),
    		'send_quote_request' => esc_html__('Send custom quote request', 'b2bking'),
    		'clearx' => esc_html__('Clear X', 'b2bking'),
    		'number_of_countries' => $number_of_countries,
    		'datatables_folder' => plugins_url('../includes/assets/lib/dataTables/i18n/', __FILE__),
    		'loaderurl' => plugins_url('../includes/assets/images/loader.svg', __FILE__),
    		'purchase_lists_language_option' => get_option('b2bking_purchase_lists_language_setting','english'),
    		'accountingsubtotals' => get_option( 'b2bking_show_accounting_subtotals_setting', 0 ),
    		'validating' => esc_html__('Validating...', 'b2bking'),
    		'vatinvalid' => esc_html__('Invalid VAT. Click to try again', 'b2bking'),
    		'vatvalid' => esc_html__('VAT Validated Successfully', 'b2bking'),
    		'validatevat' => esc_html__('Validate VAT', 'b2bking'),
    		'differentdeliverycountrysetting' => intval(get_option( 'b2bking_vat_exemption_different_country_setting', 0 )),
    		'myaccountloggedin' => (is_account_page() && is_user_logged_in()),
    		'colorsetting' => get_option( 'b2bking_color_setting', '#3AB1E4' ),
    		'ischeckout' => is_checkout(),
    		'quote_request_empty_fields' => esc_html__('Please fill all fields to submit the quote request', 'b2bking'),
    		'quote_request_invalid_email' => esc_html__('The email address you entered is invalid', 'b2bking'),
    		'is_required' => esc_html__('is required', 'b2bking'),
    		'must_select_country' =>  esc_html__('You must select a country', 'b2bking'),
		);

		// set purchase lists language for WPML
	 	if (defined('ICL_LANGUAGE_NAME_EN')){
	 		$data_to_be_passed['purchase_lists_language_option'] = ICL_LANGUAGE_NAME_EN;
	 	}

		wp_localize_script( 'b2bking_public_script', 'b2bking_display_settings', $data_to_be_passed );

		
		
    }
    	
}

