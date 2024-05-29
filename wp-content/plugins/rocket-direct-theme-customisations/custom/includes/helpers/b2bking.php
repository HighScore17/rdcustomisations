<?php

function load_b2bking_on_non_ajax() {
  
  define( 'DOING_AJAX', true );

  if( !class_exists( 'B2bking' ) ) {
    return;
  }
 
  $b2bking = new B2bking();
 
  if (intval(get_option('b2bking_search_product_description_setting', 0)) === 0){
    // if search product description is disabled, search by title only
    add_filter('posts_search', array($b2bking, 'b2bking_search_by_title_only'), 500, 2);
  }

  if ( get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ) {
    /* Groups */
   // Set up product/category user/user group visibility rules
    if (intval(get_option( 'b2bking_all_products_visible_all_users_setting', 1 )) !== 1){
      if (intval(get_option('b2bking_disable_visibility_setting', 0)) === 0){

        // if user is not admin or shop manager
        if (!current_user_can( 'manage_woocommerce' )){
          // if caching is enabled
          if (intval(get_option( 'b2bking_product_visibility_cache_setting', 1 )) === 1){
            add_action( 'pre_get_posts', array($b2bking, 'b2bking_product_categories_visibility_rules') );
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
							if ($b2bking->b2bking_user_is_in_list($list) === 'yes'){
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
							if ($b2bking->b2bking_user_is_in_list($list) === 'yes'){
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
						if ($b2bking->b2bking_user_is_in_list($list) === 'yes'){
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
						if ($b2bking->b2bking_user_is_in_list($list) === 'yes'){
							// add quantity step in product page
							add_filter( 'woocommerce_quantity_input_args', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple_quantity'), 10, 2 );
							add_filter( 'woocommerce_available_variation', array('B2bking_Dynamic_Rules', 'b2bking_dynamic_rule_required_multiple_quantity'), 10, 2 );
							
						}
					}
				}

				if (get_option('b2bking_plugin_status_setting', 'disabled') !== 'disabled' ){

					// Add tiered pricing to AJAX as well
					/* Set Tiered Pricing via Fixed Price Dynamic Rule */
					add_filter('woocommerce_product_get_price', array($b2bking, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter('woocommerce_product_get_regular_price', array($b2bking, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					// Variations 
					add_filter('woocommerce_product_variation_get_regular_price', array($b2bking, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter('woocommerce_product_variation_get_price', array($b2bking, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter( 'woocommerce_variation_prices_price', array($b2bking, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );
					add_filter( 'woocommerce_variation_prices_regular_price', array($b2bking, 'b2bking_tiered_pricing_fixed_price'), 9999, 2 );

					// Pricing and Discounts in the Product Page: Add to AJAX
					/* Set Individual Product Pricing (via product tab) */
					add_filter('woocommerce_product_get_price', array($b2bking, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter('woocommerce_product_get_regular_price', array($b2bking, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					// Variations 
					add_filter('woocommerce_product_variation_get_regular_price', array($b2bking, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter('woocommerce_product_variation_get_price', array($b2bking, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_price', array($b2bking, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_regular_price', array($b2bking, 'b2bking_individual_pricing_fixed_price'), 999, 2 );
					// Set sale price as well
					add_filter( 'woocommerce_product_get_sale_price', array($b2bking, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					add_filter( 'woocommerce_product_variation_get_sale_price', array($b2bking, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_price', array($b2bking, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					add_filter( 'woocommerce_variation_prices_sale_price', array($b2bking, 'b2bking_individual_pricing_discount_sale_price'), 999, 2 );
					// display html
					// Displayed formatted regular price + sale price
					add_filter( 'woocommerce_get_price_html', array($b2bking, 'b2bking_individual_pricing_discount_display_dynamic_price'), 999, 2 );
					// Set sale price in Cart
					add_action( 'woocommerce_before_calculate_totals', array($b2bking, 'b2bking_individual_pricing_discount_display_dynamic_price_in_cart'), 999, 1 );
					// Function to make this work for MiniCart as well
					add_filter('woocommerce_cart_item_price',array($b2bking, 'b2bking_individual_pricing_discount_display_dynamic_price_in_cart_item'),999,3);
				}


				if (!is_user_logged_in()){
					if (get_option('b2bking_guest_access_restriction_setting', 'hide_prices') === 'hide_prices'){	
						add_filter( 'woocommerce_get_price_html', array($b2bking, 'b2bking_hide_prices_guest_users'), 9999, 2 );
						add_filter( 'woocommerce_variation_get_price_html', array($b2bking, 'b2bking_hide_prices_guest_users'), 9999, 2 );
						// Hide add to cart button as well / purchasable capabilities
						add_filter( 'woocommerce_is_purchasable', array($b2bking, 'b2bking_disable_purchasable_guest_users'));
						add_filter( 'woocommerce_variation_is_purchasable', array($b2bking, 'b2bking_disable_purchasable_guest_users'));
					}
				}
}