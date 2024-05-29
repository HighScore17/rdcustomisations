<?php
/**
 * Functions.php
 *
 * @package  Theme_Customisations
 * @author   WooThemes
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'HORIZON_TEST_EMAIL', 'devs@horizon.com.pr' );

/**
 * functions.php
 * Add PHP snippets here
 */

 /**
	* Load Libs
  */
require_once __DIR__ . '/includes/lib/main.php';
require_once 'includes/lib/mustache/mustache.php';
require_once 'includes/lib/fedex-sdk.php';
require_once 'includes/helpers/loader.php';
require_once __DIR__ . "/includes/webhooks/autoload.php";
require_once __DIR__ . '/includes/graphql/autoloader.php';
require_once __DIR__ . '/Src/Packages/autoload.php';


/**
 * Add required
 */
require_once 'includes/woocommerce/horizon-woocommerce.php';
require_once 'includes/wc-product-data.php';
require_once 'includes/reCaptcha.php';
require_once 'includes/hubspot.php';
require_once 'includes/auth.php';
require_once 'includes/emails.php';
require_once 'includes/slack.php';
require_once 'includes/mailchimp.php';
require_once 'includes/admin-menu.php';
require_once 'includes/abandoned_cart.php';
require_once 'includes/cin7.php';
require_once 'includes/shipping/main.php';
require 'includes/api.php';
require 'includes/email-senders.php';
require 'includes/slack-senders.php';
require 'includes/brands.php';
require  'views/table_customers/table_customers.php';
//require 'includes/path.php';


//require_once 'includes/ups.php';

 /**
	* B2BKing Wholesale groups
  */
define( "WHOLESALE_NO_PAYMENT_TERMS", 935 );
define( "WHOLESALE_DENIED_PAYMENT_TERMS", 1352 );
define( "WHOLESALE_30_DAYS_PAYMENT_TERMS", 37 );
define( "POSTMARK_API_KEY_ACCESS", "c7f0ac3a-a9f4-4946-948d-56d146c4366b" );

 /**
 * CORS
 */
function add_cors_http_header() {
	header('Access-Control-Allow-Origin: *');
	header('Access-Control-Allow-Methods: GET, OPTIONS, POST');
	header('Access-Control-Allow-Headers: origin, x-requested-with, content-type, accept, authorization, X-JWT-Auth, X-JWT-Refresh, woocommerce-session, woocommerce-brand-name');
	header('Access-Control-Allow-Credentials: true');
	if('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
			status_header(200);
			exit();
	}
}
add_action('init', 'add_cors_http_header');


/**
 * Sendgrid API Webhook
 */


/**
 * Checkout
 */
add_filter('graphql_request_data', function($parsed_query_params) {
	// Active campaign need to validad nonce, since graphql uses another authentication type this nonce is only for plugin support
	if($parsed_query_params["operationName"] === "makeOrder") {
		$_REQUEST['woocommerce-process-checkout-nonce'] = wp_create_nonce( 'woocommerce-process_checkout' );
	}
	// To prevent unpaid orders
	if (strpos($parsed_query_params['query'], 'isPaid')) {
		status_header(403);
		exit();
	}
	return $parsed_query_params;
});

// To grab Usio's metadata
add_filter('graphql_request_data', function($parsed_query_params) {
	$variables = $parsed_query_params['variables'];
	if (!empty($parsed_query_params['variables']['card_token'])) {
		$_POST['card_token'] = $parsed_query_params['variables']['card_token'];
	}
	if (!empty($parsed_query_params['variables']['paymentId'])) {
		$_POST['payment_id'] = $parsed_query_params['variables']['paymentId'];
	}
	if (!empty($parsed_query_params['variables']['card_type'])) {
		$_POST['card_type'] = $parsed_query_params['variables']['card_type'];
	}
	if (isset($variables['isCheckout'])) {
		$_POST['is_checkout'] = $variables['isCheckout'];
		$_POST['accessorials'] = $variables['accessorials'];
		$_POST['shipTo'] = array(
			'Name' => $variables["to"],
      'Address' => array(
        'AddressLine' => $variables["address"],
        'City' => $variables["city"],
        'PostalCode' => $variables["postcode"],
        'StateProvinceCode' => $variables["state"],
				'ResidentialAddress' => $variables['isResidential'] == 1 ? true : false
			)
		);
		if( $_POST['is_checkout'] ) {
			$packages = WC()->cart->get_shipping_packages();
			foreach ($packages as $package_key => $package ) {
					WC()->session->set( 'shipping_for_package_' . $package_key, false ); // Or true
			}
		}
	}
	$_POST["graphql_enabled"] = true;
	$_POST["is_frontend_request"] = true;
	return $parsed_query_params;
});

// To grab first name
add_filter('graphql_request_data', function($parsed_query_params) {
	if (!empty($parsed_query_params['variables']['first_name'])) {
		$_POST['first_name'] = $parsed_query_params['variables']['first_name'];
	}
	return $parsed_query_params;
});

// To enable guest checkout
add_action('init', function() {
	update_option('woocommerce_enable_guest_checkout', 'yes');
});

/**
 * Conversations
 */
function add_conversations_graphql_support() {

	$labels = array(
		'name'                  => esc_html__( 'Conversations', 'b2bking' ),
		'singular_name'         => esc_html__( 'Conversation', 'b2bking' ),
		'all_items'             => esc_html__( 'Conversations', 'b2bking' ),
		'menu_name'             => esc_html__( 'Conversations', 'b2bking' ),
		'add_new'               => esc_html__( 'Start Conversation', 'b2bking' ),
		'add_new_item'          => esc_html__( 'Start new conversation', 'b2bking' ),
		'edit'                  => esc_html__( 'Edit', 'b2bking' ),
		'edit_item'             => esc_html__( 'Edit conversation', 'b2bking' ),
		'new_item'              => esc_html__( 'New conversation', 'b2bking' ),
		'view_item'             => esc_html__( 'View conversation', 'b2bking' ),
		'view_items'            => esc_html__( 'View conversations', 'b2bking' ),
		'search_items'          => esc_html__( 'Search conversations', 'b2bking' ),
		'not_found'             => esc_html__( 'No conversations found', 'b2bking' ),
		'not_found_in_trash'    => esc_html__( 'No conversations found in trash', 'b2bking' ),
		'parent'                => esc_html__( 'Parent conversation', 'b2bking' ),
		'featured_image'        => esc_html__( 'Conversation image', 'b2bking' ),
		'set_featured_image'    => esc_html__( 'Set conversation image', 'b2bking' ),
		'remove_featured_image' => esc_html__( 'Remove conversation image', 'b2bking' ),
		'use_featured_image'    => esc_html__( 'Use as conversation image', 'b2bking' ),
		'insert_into_item'      => esc_html__( 'Insert into conversation', 'b2bking' ),
		'uploaded_to_this_item' => esc_html__( 'Uploaded to this conversation', 'b2bking' ),
		'filter_items_list'     => esc_html__( 'Filter conversations', 'b2bking' ),
		'items_list_navigation' => esc_html__( 'Conversations navigation', 'b2bking' ),
		'items_list'            => esc_html__( 'Conversations list', 'b2bking' )
	);

	$args = array(
		'label'                 => esc_html__( 'Conversation', 'b2bking' ),
		'description'           => esc_html__( 'This is where you can create new conversations', 'b2bking' ),
		'labels'                => $labels,
		'supports'              => array('title'),
		'hierarchical'          => false,
		'public'                => false,
		'publicly_queryable' 	=> false,
		'show_ui'               => true,
		'show_in_menu'          => 'b2bking',
		'menu_position'         => 100,
		'show_in_admin_bar'     => true,
		'can_export'            => true,
		'has_archive'           => false,
		'exclude_from_search'   =>  true,
		'capability_type'       => 'product',
		'show_in_graphql'		=> true,
		'graphql_single_name'	=> 'conversation',
		'graphql_plural_name' 	=> 'conversations'
	);

	register_post_type('b2bking_conversation', $args);

}

add_action('init', 'add_conversations_graphql_support');

add_action('graphql_register_types', function() {

	register_graphql_field('Conversation', 'type', [
	   'type' => 'String',
	   'description' => __('Conversation type', 'wp-graphql'),
	   'resolve' => function($post) {
		 $b2bking_conversation_type = get_post_meta($post->ID, 'b2bking_conversation_type', true);
		 return !empty( $b2bking_conversation_type ) ? $b2bking_conversation_type : null;
	   }
	]);

	register_graphql_object_type(
		'ConversationMessage',
		[
			'fields' => [
				'id' => [ 'type' => 'Int' ],
				'author' => [ 'type' => 'String' ],
				'message' => [ 'type' => 'String' ],
				'date'  => [ 'type' => 'String' ],
			  ]
		]
	);

	register_graphql_connection([
		'fromType'         => 'Conversation',
		'toType'           => 'ConversationMessage',
		'fromFieldName'    => 'messages',
		'resolve'          => function ($post) {
			$nodes = [];
			$meta = get_post_meta($post->ID);
			foreach ($meta as $meta_key => $meta_value) {
				$meta_value = $meta_value[0];
				switch ($meta_key) {
					case 'b2bking_conversation_status':
					case 'b2bking_conversation_user':
					case 'b2bking_conversation_type':
					case 'b2bking_conversation_messages_number':
					case '_edit_lock':
						break;
					default:
						$meta_key = explode('_', $meta_key);
						$nodes[$meta_key[3]-1]['id'] = $meta_key[3];
						if (!empty($meta_key[4])) {
							switch ($meta_key[4]) {
								case 'time':
									$key = 'date';
									$meta_value = date_i18n('Y-m-d H:i:s', $meta_value);
									break;
								case 'author':
									$key = $meta_key[4];
									$meta_value = $meta_value;
									break;
							}
						} else {
							$key = 'message';
						}
						$nodes[$meta_key[3]-1][$key] = $meta_value;
						break;
				}
			}
			return  [
				'nodes' => $nodes
			];
		}
	  ]);

});

/**
 * Emails
 */

 // To request wholesale_account
 function send_samples_request() {

	$message = [];
	foreach ($_POST as $key => $value) {
		switch($key){
			case 'fname':
				$message[] = '<b>First name:</b> '.$value;
				break;
			case 'lname':
				$message[] = '<b>Last name:</b> '.$value;
				break;
			case 'company':
				$message[] = '<b>Company:</b> '.$value;
				break;
			case 'email':
				$message[] = '<b>Email:</b> '.$value;
				break;
		}
	}

	if (count($message) == 4) {
		$message = implode('<br>', $message);
		$sent = wp_mail('samples@bulkmasks.direct', 'New samples request', $message);
		if ($sent) {
			wp_send_json_success();
			wp_die();
		}
	}

	wp_send_json_error();
	wp_die();

}

add_action('wp_ajax_request_samples', 'send_samples_request');
add_action('wp_ajax_nopriv_request_samples', 'send_samples_request');


// Order processing
function is_first_bought( $email, $require_zero = TRUE ) {
	$orders_qty = wc_get_orders( array(
		'email' => $email,
		'limit' => 2
	) );
	if( $require_zero )
		return count( $orders_qty ) === 0;
	else
		return count( $orders_qty ) <= 1 ? true : false;
}

function has_bought( $value = 0 ) {
    global $wpdb;

    // Based on user ID (registered users)
    if ( is_numeric( $value) ) {
        $meta_key   = '_customer_user';
        $meta_value = $value == 0 ? (int) get_current_user_id() : (int) $value;
    }
    // Based on billing email (Guest users)
    else {
        $meta_key   = '_billing_email';
        $meta_value = sanitize_email( $value );
		}

    $paid_order_statuses = array_map( 'esc_sql', wc_get_is_paid_statuses() );

    $count = $wpdb->get_var( $wpdb->prepare("
        SELECT COUNT(p.ID) FROM {$wpdb->prefix}posts AS p
        INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
        WHERE p.post_status IN ( 'wc-" . implode( "','wc-", $paid_order_statuses ) . "' )
        AND p.post_type LIKE 'shop_order'
        AND pm.meta_key = '%s'
        AND pm.meta_value = %s
        LIMIT 1
    ", $meta_key, $meta_value ) );
		
    // Return a boolean value based on orders count
    return $count > 0 ? true : false;
}

/**
 * 
 * 
 * Start user register hooks
 * 
 * 
 */
function new_user_account_created( $user_id )
{
	$user = get_user_by('id', $user_id);
	if( isset( $_POST['disable_new_user_notifications'] ) && $_POST['disable_new_user_notifications'] === "on" ) {
		return;
	}

	if (!empty($_POST['b2bking_customergroup']) && $_POST['b2bking_customergroup'] != 'b2cuser'){
		send_email_account_approved( $user_id );
	}else{
		if( isset( $_POST["accountGroup"] ) ) {
			$group =  get_page_by_title( $_POST["accountGroup"] , OBJECT, 'b2bking_group' );
			if( $group && $group->ID && $group->post_type === "b2bking_group" ) {
				update_user_meta( $user_id, 'b2bking_customergroup', $group->ID);
				update_user_meta( $user_id, 'b2bking_b2buser', 'yes');
			}
		}
		if( isset($_POST["accountStatus"]) && $_POST["accountStatus"] === "activated" ) {
			update_user_meta($user_id, 'active_email_account', '1');
			do_action('account_activation', $user_id);
		} else {
			update_user_meta($user_id, 'active_email_account', '0');
		}
		$activation_code = uniqid();
		update_user_meta($user_id, 'active_email_code', $activation_code);
		send_email_new_user( $user, $activation_code );
	}
	if( isset( $_POST["company"], $_POST["duns"] ) ){
		add_user_meta( $user_id, "wholesale_company", $_POST["company"] );
		add_user_meta( $user_id, "wholesale_duns", $_POST["duns"] );
	}
}

function send_email_new_user( $user, $code )
{
	$context = array(
		'first_name' => $_POST['first_name'],
		'user_email' => $user->user_email,
		'user_code' => $code
	);
	postmark_send_email('welcome', $context, wc_horizon_get_email_sender("account"), $user->user_email, []);
}

function send_email_account_approved($user_id) {
	global $wpdb;
	if( !empty($_POST['b2bking_customergroup']) && $_POST['b2bking_customergroup'] != 'b2cuser' );
	{
		$context['user_name'] = $_POST['email'];
		$context['first_name'] = $_POST['first_name'];
		$context['password'] = $_POST['pass1'] ? $_POST['pass1'] : "Your actual password";
		$context['company'] = $_POST["company"];
		$context['duns_number'] = $_POST["duns"];
		if (!empty($_POST['company'])) {
			$context['company'] = $_POST['company'];
		}
		if (!empty($_POST['duns'])) {
			$context['duns_number'] = $_POST['duns'];
		}
		$query = "SELECT
			meta_key
		FROM wp_postmeta
		WHERE
			post_id = ".$_POST['b2bking_customergroup']."
			AND meta_key LIKE 'b2bking#_group_payment#_method#_%' ESCAPE '#'
			AND (meta_key LIKE '%#_cod' ESCAPE '#' OR meta_key LIKE '%#_net#_%' ESCAPE '#')
			AND meta_value = 1";
		$allowed_payment_gateways = $wpdb->get_results($query);
		$wc_gateways = new WC_Payment_Gateways();
		$payment_gateways = $wc_gateways->get_available_payment_gateways();
		$context['payment_terms'] = array();
		foreach ($allowed_payment_gateways as $allowed_payment_gateway) {
			foreach ($payment_gateways as $id => $payment_gateway) {
				if (strpos($allowed_payment_gateway->meta_key, $id)) {
					$payment_term = explode(' - ', $payment_gateway->get_title());
					$context['payment_terms'][] = $payment_term[0];
					break;
				}
			}
		}
		if (empty($context['payment_terms'])) {
			unset($context['payment_terms']);
		} else {
			$context['payment_terms'] = implode(', ', $context['payment_terms']);
		}
		if( $_POST['b2bking_customergroup'] == WHOLESALE_NO_PAYMENT_TERMS || $_POST['b2bking_customergroup'] == WHOLESALE_30_DAYS_PAYMENT_TERMS ){
			postmark_send_email('account_approved', $context,  wc_horizon_get_email_sender("account"), $_POST['email']);
		}
		else if( $_POST['b2bking_customergroup'] == WHOLESALE_DENIED_PAYMENT_TERMS ){
			postmark_send_email('account_approved_terms_denied', $context,  wc_horizon_get_email_sender("account"), $_POST['email']);
		}
	}
}

add_action( 'user_register', 'new_user_account_created', 10, 1 );

/**
 * 
 * 
 * End user register hooka
 * 
 * 
 */

// Grab user password
function random_pass_save($pass){
    $_POST['pass'] = $pass;
    return $pass;
}

add_filter('random_password', 'random_pass_save');


add_filter( 'wp_new_user_notification_email', 'custom_wp_new_user_notification_email', 10, 3 );

// Reset password
function mapp_custom_password_reset($message, $key, $user_login, $user_data) {
	$front_url = wc_horizon_get_brand_url() . "/forgot-password/";
	$source = isset($_POST["forgotSource"]) ? $_POST["forgotSource"] : "";
	
	postmark_send_email( "password_reset", array(
		"first_name" => get_user_meta($user_data->ID, 'first_name', true),
		"email" => $user_data->user_email,
		"reset_password_link" => $front_url."reset?key=$key&login=".rawurlencode($user_login) . "&source=".rawurlencode($source)
	), wc_horizon_get_email_sender("account"), $user_data->user_email );
	
	return "";
}

add_filter("retrieve_password_message", "mapp_custom_password_reset", 99, 4);

/**
 * 
 * Remove sitename from emails subject
 * 
 */
function email_subject_remove_sitename($email) {
  $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
  $email['subject'] = str_replace("[".$blogname."] - ", "", $email['subject']);    
  $email['subject'] = str_replace("[".$blogname."]", "", $email['subject']);
  return $email;
}
add_filter('wp_mail', 'email_subject_remove_sitename');

// Inquiries
function send_inquiry() {
	$variables = array('name', 'last_name', 'email', 'phone', 'position', 'company', 'country', 'inquire_type', 'msg');
	foreach ($variables as $variable) {
		if (array_key_exists($variable, $_POST)) {
			$values[$variable] = $_POST[$variable];
		}
	}
	$context = [];
	foreach ($values as $field => $value) {
		switch ($field) {
			case 'name':
				$context['first_name'] = $value;
				$context['full_name'] = $value;
				break;
			case 'last_name':
				$context['full_name'] .= ' '.$value;
				break;
			case 'msg':
				$context['message'] = $value;
				break;
			case 'inquire_type':
				switch ($value) {
					case 'frontline':
						$context['inquiry_type'] = 'Frontline Urgent Needs';
						break;
					case 'general':
						$context['inquiry_type'] = 'General Inquiries';
						break;
					case 'media':
						$context['inquiry_type'] = 'Media Inquiries';
						break;
					case 'over_9000_3ply':
						$context['inquiry_type'] = 'Orders over 100 million (3-ply)';
						break;
					case 'over_9000_kn95':
						$context['inquiry_type'] = 'Orders over 100 million (KN95)';
						break;
					case 'recurring':
						$context['inquiry_type'] = 'Recurring Supply Contracts';
						break;
					case 'wholesale':
						$context['inquiry_type'] = 'Request a Wholesale account';
						break;
				}
				break;
			default:
				$context[$field] = $value;
				break;
		}
	}
	$from = 'inquiry@rocket.direct';
	$to = $_POST['email'];
	postmark_send_email('contact-form', $context, $from, $to);
	postmark_send_email('contact-form', $context, $from, 'abigail@horizon.com.pr');
	postmark_send_email('contact-form-admin', $context, $from, $from);
	wp_send_json_success();
}

add_action('wp_ajax_contact_form', 'send_inquiry');
add_action('wp_ajax_nopriv_contact_form', 'send_inquiry');




// Verify company requirement for B2B users whie updating
function verify_custom_fields_update($errors) {
	if (empty($_POST['first_name'])) {
		$errors->add( 'first_name', __( '<strong>Error</strong>: Please enter a first name.' ) );
	}
	if (empty($_POST['last_name'])) {
		$errors->add( 'last_name', __( '<strong>Error</strong>: Please enter a last name.' ) );
	}
}

add_action( 'user_profile_update_errors', 'verify_custom_fields_update', 10, 1 );

// Verify company presence
function verify_company_presence($meta_id, $object_id, $meta_key, $_meta_value) {
	if ($meta_key == 'b2bking_customergroup' && empty($_POST['company'])) {
		wp_die( __('<strong>Error</strong>: Please enter a company for the B2B user.') );
	}
}

add_action( 'update_user_meta', 'verify_company_presence', 10, 4 );

// Skip Confirmation Email
add_action( 'user_new_form', 'skip_confirmation_email' );

function skip_confirmation_email() { ?>
	<script type="text/javascript">
		document.getElementById("noconfirmation").checked = true;
	</script>
<?php }

// Email as username
add_filter('wpmu_validate_user_signup','custom_register_with_email');

function custom_register_with_email($result) {

	if ( $result['user_name'] != '' && is_email( $result['user_name'] ) ) {

	   unset( $result['errors']->errors['user_name'] );

	}

	return $result;
}

// Verify company requirement for B2B users whie creating
add_filter('wpmu_validate_user_signup','verify_custom_fields_create');

function verify_custom_fields_create($result) {

	if (!empty($_POST['b2bking_customergroup'])) {
		switch ($_POST['b2bking_customergroup']) {
			case 'b2cuser':
				break;
			default:
				if (empty($_POST['company'])) {
					$result['errors']->errors['b2b'][0] = 'Please enter a company for the B2B user.';
				}
				break;
		}
	}

	if (empty($_POST['first_name'])) {
		$result['errors']->errors['first_name'][] = 'Please enter a first name.';
	}

	if (empty($_POST['last_name'])) {
		$result['errors']->errors['last_name'][] = 'Please enter a last name.';
	}

	return $result;
}


// Update regular price with Public Pricing group
function update_regular_price($meta_id, $object_id, $meta_key, $_meta_value) {
	global $wpdb;
	if ($meta_key == 'b2bking_regular_product_price_group_368' && !empty($_meta_value)) {
		update_post_meta($object_id, '_regular_price', $_meta_value);
	}
}

add_action( 'update_post_meta', 'update_regular_price', 10, 4 );

// Hide Add Existing User
function hide_add_existing_user() { ?>
	<style>
		#add-existing-user, #add-existing-user + p, #adduser {
			display: none;
		}
	</style>
<?php }

add_action( 'user_new_form', 'hide_add_existing_user' );

// Hide Skip Confirmation Email
function hide_skip_confirmation_email() { ?>
	<script type="text/javascript">
		document.querySelector('#noconfirmation').closest('tr').style.display = 'none';
	</script>
<?php }

add_action( 'user_new_form', 'hide_skip_confirmation_email' );

// Register new status
function register_in_transit_order_status() {
    register_post_status( 'wc-in-transit', array(
        'label'                     => 'In transit',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'In transit (%s)', 'In transit (%s)' )
    ) );
		register_post_status( 'wc-in-transit-backordered', array(
			'label'                     => 'In transit / Backordered',
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'In transit (%s)', 'In transit (%s)' )
	) );
	register_post_status( 'wc-subs-48hrs', array(
		'label'                     => 'Subs 48hrs',
		'public'                    => true,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'In transit (%s)', 'In transit (%s)' )
) );
}
add_action( 'init', 'register_in_transit_order_status' );

// Add to list of WC Order statuses
function add_in_transit_to_order_statuses( $order_statuses ) {
	$order_statuses['wc-in-transit-backordered'] = 'In transit / Backordered';
	$order_statuses['wc-in-transit'] = 'In transit';
	$order_statuses['wc-subs-48hrs'] = 'Subs 48hrs';
	return $order_statuses;
}
add_filter( 'wc_order_statuses', 'add_in_transit_to_order_statuses' );

add_action('admin_head', 'styling_admin_order_list' );
function styling_admin_order_list() {
    global $pagenow, $post;
		
		if(!$post) {
			return;
		}

    if( $pagenow != 'edit.php') return; // Exit
    if( get_post_type($post->ID) != 'shop_order' ) return; // Exit

    // HERE we set your custom status
    $order_status = 'in-transit'; // <==== HERE
    ?>
    <style>
        .order-status.status-<?php echo sanitize_title( $order_status ); ?> {
            background: #fed8b1;
            color: #000000;
        }
    </style>
    <?php
}

add_filter( 'wc_order_statuses', 'wc_renaming_order_status' );
function wc_renaming_order_status( $order_statuses ) {
	foreach ( $order_statuses as $key => $status ) {
		if ( 'wc-completed' === $key ) 
				$order_statuses['wc-completed'] = _x( 'Delivered', 'Order status', 'woocommerce' );
	}
	return $order_statuses;
}

//Email
add_action( 'woocommerce_order_status_processing', 'send_email_order_processing', 10, 1);
add_action( 'woocommerce_order_status_on-hold', 'send_email_order_processing', 10, 1);
add_action( 'woocommerce_order_status_completed', 'send_email_order_complete', 10, 1);
add_action( 'woocommerce_new_customer_note', 'send_email_order_note' );
add_action( 'woocommerce_order_status_processing', 'send_email_new_order', 10, 1 );

function send_email_order_note( $args )
{
  $order = wc_get_order( $args['order_id'] );

	wc_horizon_set_order_brand( $order );

  $context['order_number'] = $order->get_order_number();
  $context['note'] = $args['customer_note'];
	postmark_send_email('order_note', $context, wc_horizon_get_email_sender("order"), $order->get_billing_email(), []);
}

function send_email_order_complete( $order_id )
{
	$order = wc_get_order($order_id);
	wc_horizon_set_order_brand( $order );
	
	if(  $order->get_meta('disable_default_notifications', true) === "yes" ) {
		return;
	}

	$shipping_cost = floatval( $order->get_shipping_total() );
	$context = array_merge (
		array(
			"first_name" => (empty($order->get_user_id()) ? $order->get_billing_first_name() : get_user_meta($order->get_user_id(), 'first_name', true)),
			"received_name" =>  $order->get_shipping_first_name().' '.$order->get_shipping_last_name(),
			"received_date" =>	$order->get_date_completed()->format('M jS, Y'),
			"received_hour" => 	$order->get_date_completed()->format('H:i'),
			"items" =>	get_order_products_purchased( $order, FALSE ),
			'subtotal' => '$' . __safe_number_format( ( floatval( $order->get_subtotal() ) - floatval($order->get_discount_total()) ) , 2 ),
			'shipping_cost' => $shipping_cost > 0 ? ('$' . __safe_number_format( $shipping_cost, 2 )) : "FREE SHIPPING",
			'total' => '$'.__safe_number_format($order->get_total(), 2),
		),
		get_order_purchased_shipping_address( $order )
	);
	postmark_send_email('delivery_confirmation', $context, wc_horizon_get_email_sender("tracking"), $order->get_billing_email(), []);
}

function send_email_new_order( $order_id ) {
	$order = wc_get_order($order_id);
	$email = $order->get_billing_email();
	$created_via = $order->get_created_via();

	if( 
		$email != HORIZON_TEST_EMAIL && 
		$order->get_meta('disable_default_notifications', true) !== "yes"  &&
		$created_via !== 'ac_free_sample' && 
		!__str_starts_with( $created_via, "active_campaign" ) &&
		!__is_development_enviroment()
		) {
		send_email_order_status( $order, 'new_order', "sales@rocket.direct,abigail@horizon.com.pr,javier@horizon.com.pr,grecia@horizon.com.pr,a.garcia@rocket.pr,nick@rocketdistributors.com" );
	}
}

function send_email_order_processing($order_id) {
	$order = wc_get_order($order_id);
	$created_via = $order->get_created_via();

	if( 
		$order->get_meta('disable_default_notifications', true) !== "yes" &&
		$created_via !== 'ac_free_sample' && 
		$created_via !== 'horizon_subscription' &&
		!__str_starts_with( $created_via, "active_campaign" )
	) {
		send_email_order_status( $order, 'order_confirmation', $order->get_billing_email(), array(
			'template' => 'pdf-invoice',
			'name' => 'rocket.direct invoice'
		) );
	}
}

function end_email_order_status( $order, $template, $to, $template_pdf = NULL )
{
	$context = get_order_email_context( $order );
	wc_horizon_set_order_brand( $order );
	$attachments = array();

	if( $template_pdf ) {
		$pdf_attachment = postmark_get_email_pdf(
			$template_pdf['template'],
			$template_pdf['name'],
			$context
		);
		$attachments = $pdf_attachment ? [ $pdf_attachment ] : [];
	}

	postmark_send_email($template, $context, wc_horizon_get_email_sender("order"), $to, $attachments);
}

function get_order_email_context( WC_Order $order ) {
	$shipping_cost = floatval(  $order->get_shipping_total() );
	return array_merge( 
		array(
			'order_number' => $order->get_order_number(),
			'first_name' => (empty($order->get_user_id()) ? $order->get_billing_first_name() : get_user_meta($order->get_user_id(), 'first_name', true)),
			'billing_full_name' => $order->get_billing_first_name().' '.$order->get_billing_last_name(),
			'billing_company' => $order->get_billing_company(),
			'billing_address' => $order->get_billing_address_1().' '.$order->get_billing_address_2(),
			'billing_city' => $order->get_billing_city(),
			'billing_state' => $order->get_billing_state(),
			'billing_zip_code' => $order->get_billing_postcode(),
			'billing_country' => $order->get_billing_country(),
			'payment_title' => $order->get_payment_method_title(),
			'items' => get_order_products_purchased( $order ),
			'payment_value' => !empty( $_POST['card_type'] ) ? $_POST['card_type'] : NULL,
			'first_time' => is_first_bought($order->get_billing_email(), FALSE),
			'subtotal' => '$' . __safe_number_format( ( floatval( $order->get_subtotal() ) - floatval($order->get_discount_total()) ) , 2 ),
			'shipping_cost' => $shipping_cost > 0 ? ('$' . __safe_number_format( $shipping_cost, 2)) : "FREE SHIPPING",
			'total' => '$'.__safe_number_format($order->get_total(), 2),
		),
		get_order_purchased_shipping_address( $order )
	);
}

function get_order_products_purchased( $order, $withShipping = TRUE )
{
	$items = array();
	foreach ($order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) ) as $item_id => $item) {
		$product_size = $item->get_meta("pa_size") ?? $item->get_meta("Size");
		$presentation = $item->get_meta( "pa_presentation");
		$current_item = array();
		$current_item['order_number'] = $order->get_order_number();
		$current_item['date'] = $order->get_date_created()->format('M jS, Y');
		$current_item['product_name'] = str_replace("- 28-35 Days", "", $item->get_name()) ;
		$current_item['product_size'] = $product_size && is_string( $product_size ) ? strtoupper( $product_size ) : "";
		$current_item['presentation'] = $presentation ? ucfirst( $presentation ) : "Case";
		$current_item['delivery'] = '3-7 Days';//str_replace("-days", " Days", $item->get_meta("pa_delivery") );
		$current_item['order_quantity'] = __safe_number_format((int)$item->get_quantity());
		$decimals = strlen(substr(strrchr(strval($item->get_total()), "."), 1));
		$current_item['total'] = '$'.__safe_number_format($item->get_subtotal(), $decimals > 0 ? $decimals : 2);
		$current_item['cost_per_product'] = '$'.($item->get_subtotal()/$item->get_quantity());
		$current_item['isFree'] = $item->get_subtotal() == 0;
		if( $withShipping ) {
			$current_item = array_merge( $current_item, get_order_purchased_shipping_address( $order ) );
		}
		array_push( $items, $current_item );
	}
	return $items;
}

function get_order_purchased_shipping_address( $order )
{
	$current_item = array();
	$current_item['shipping_full_name'] = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
	$current_item['shipping_email'] = $order->get_billing_email();
	$current_item['shipping_company'] = $order->get_shipping_company();
	$current_item['shipping_address'] = $order->get_shipping_address_1().' '.$order->get_shipping_address_2();
	$current_item['shipping_city'] = $order->get_shipping_city();
	$current_item['shipping_state'] = $order->get_shipping_state();
	$current_item['shipping_zip_code'] = $order->get_shipping_postcode();
	$current_item['shipping_country'] = $order->get_shipping_country();
	return $current_item;
}

/**
 * 
 * 
 * Start Peyments terms hooks
 * 
 * 
 */
function update_wholesale_group($meta_id, $object_id, $meta_key, $_meta_value) {
	if( $meta_key == "b2bking_customergroup" && !empty( $_POST['b2bking_customergroup'] ) && $_POST['b2bking_customergroup'] !== "b2cuser" )
	{
		send_email_account_approved( $object_id );
		update_user_meta( $object_id, "wholesale_company", $_POST["company"] );
		update_user_meta( $object_id, "wholesale_duns", $_POST["duns"] );
	}
}


add_action( 'update_user_meta', 'update_wholesale_group', 10, 4 );

function update_create_edit_account_custom_fields( $user_id ) {

	if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'update-user_' . $user_id ) ) {
		return;
	}

	if ( !current_user_can( 'edit_user', $user_id ) ) { 
			return false; 
	}
	update_user_meta( $user_id, 'active_email_account', $_POST['active_email_account'] === "on" ? "1" : "0" );
}
add_action( 'personal_options_update', 'update_create_edit_account_custom_fields' );
add_action( 'edit_user_profile_update', 'update_create_edit_account_custom_fields' );

/**
 * 
 * 
 * End Payment terms hooks
 * 
 * 
 */

 /**
	* 
	* Start Request wholesale account
	*
  */

function send_email_request_wholesale_account()
{
	if(
		isset
		(
			$_POST["name"], $_POST["position"], $_POST["company"], $_POST["dunsn"], $_POST["email"], 
			$_POST["credit_terms"], $_POST["employees"], $_POST["tax_exempt"], $_POST["recaptcha"]
		)
	)
	{
		$validReCaptcha = validateReCaptcha($_POST["recaptcha"]);
		if( $validReCaptcha === "success" )
		{
			$context = array(
				'name' => $_POST['name'],
				'last_name' => $_POST['last_name'],
				'phone' => $_POST['phone'],
				'position' => $_POST['position'],
				'company' => $_POST['company'],
				'dunsn' => $_POST['dunsn'],
				'email' => $_POST['email'],
				'credit_terms' => $_POST['credit_terms'] == 'yes' ? TRUE : FALSE,
				'employees' => $_POST['employees'],
				'tax_exempt' => $_POST['tax_exempt'] == 'yes' ? TRUE : FALSE
			);
			$attachments = array();
			if($context['credit_terms'])
			{
				$payment_terms_file = file_get_contents("https://s3.ca-central-1.amazonaws.com/cdn.rocketdistributors.com/rocket.direct/Credit+Application_Rocket.direct.pdf");
				$payment_terms_file = chunk_split( base64_encode( $payment_terms_file ) );
				$attachments[] = array(
					'Name' => 'Credit Application Rocket.direct.pdf',
					'Content' => $payment_terms_file,
					'ContentType' => 'application/octet-stream'
				);
			}
			postmark_send_email('account_request', $context, wc_horizon_get_email_sender("account"), $_POST["email"], $attachments );
			postmark_send_email('account_request', $context, wc_horizon_get_email_sender("account"), "sales@rocket.direct", [] );
			wp_send_json_success();
		}else{
			wp_send_json_error($validReCaptcha);
		}
	}
	else{
		wp_send_json_error();
	}
	die();
}

add_action('wp_ajax_request_wholesale_account', 'send_email_request_wholesale_account');
add_action('wp_ajax_nopriv_request_wholesale_account', 'send_email_request_wholesale_account');
/**
 * Extra fields
 */
add_action( 'show_user_profile', 'extra_user_profile_fields_update' );
add_action( 'edit_user_profile', 'extra_user_profile_fields_update' );

function extra_user_profile_fields_update( $user ) { ?>
	<h3><?php _e("High profile information", "blank"); ?></h3>

    <table class="form-table">
	<tr>
        <th><label for="company">Company <span class="description">(required if it's B2B)</span></label></th>
        <td>
            <input type="text" name="company" id="company" value="<?php echo $user->get("wholesale_company"); ?>" class="regular-text" />
        </td>
    </tr>
    <tr>
        <th><label for="duns">DUNS</label></th>
        <td>
            <input type="text" name="duns" id="duns" value="<?php echo $user->get("wholesale_duns"); ?>" class="regular-text" placeholder="Data Universal Numbering System"/>
        </td>
    </tr>
		<tr>
        <th><label for="active_email_account">Account Status</label></th>
        <td>
            <input type="checkbox" <?php echo $user->get("active_email_account") === "1" ? "checked" : "" ?> name="active_email_account" id="active_email_account" class="regular-text"/>
						<label for="active_email_account">Account is active</label>
        </td>
    </tr>
    </table>
<?php }

add_action( 'user_new_form', 'extra_user_profile_fields_create' );

function extra_user_profile_fields_create( $user ) { ?>
	<h3><?php _e("High profile information", "blank"); ?></h3>
    <table class="form-table">
			<tr>
					<th><label for="company">Company <span class="description">(required if it's B2B)</span></label></th>
					<td>
							<input type="text" name="company" id="company" value="" class="regular-text" />
					</td>
				</tr>
				<tr>
					<th><label for="duns">DUNS</label></th>
					<td>
							<input type="text" name="duns" id="duns" value="" class="regular-text" placeholder="Data Universal Numbering System"/>
					</td>
				</tr>
    </table>
<?php }

/**
 * Order metabox
 */
add_action( 'cmb2_admin_init', 'register_order_shippment_metabox' );
function register_order_shippment_metabox()
{
	$cmb = new_cmb2_box( array(
		'id' => 'order_shipping_metabox',
		'title' => 'Shipping',
		'context' => 'side',
		'object_types' => array( 'shop_order' )
	) );

	$cmb->add_field( array(
		'name' => 'Tracking number',
		'id'   => 'shipment_tracking_number',
		'type' => 'text',
	));
	$cmb->add_field( array(
		'name' => 'Shipment Provider',
		'id'   => 'shipment_provider',
		'type' => 'select',
		'options' => array(
			'DHL' => __('DHL', 'cmb2'),
			'UPS' => __('UPS', 'cmb2'),
			'FedEx' => __('FedEx', 'cmb2'),
			'USPS' => __('USPS', 'cmb2'),
		)
	) );

	$cmb->add_field( array(
		'name' => 'Estimated shipment date',
		'id'   => 'shipment_date',
		'type' => 'text_date',
	) );

	$cmb = new_cmb2_box( array(
		'id' => 'order_brand_name',
		'title' => 'Site Brands',
		'context' => 'side',
		'object_types' => array( 'shop_order' )
	) );

	$cmb->add_field( array(
		'name' => 'Brand',
		'id'   => 'wcbrand_name',
		'type' => 'select',
		'default' => wc_horizon_get_default_brand(),
		'options' => wc_horizon_get_brands( true )
	) );
}

add_action( 'save_post', 'send_email_shipping_tracking', 10, 1 );

function send_email_shipping_tracking( $post_id )
{
	$post = get_post( $post_id );
	/**
	 * Checkig if the post saved is a order and order tracking info exists
	 */
	if(
		$post->post_type == 'shop_order' &&
		isset($_POST['shipment_tracking_number'], $_POST['shipment_provider'], $_POST['shipment_date']) &&
		$_POST['shipment_tracking_number'] !== "" && $_POST['shipment_provider'] !== "" && $_POST['shipment_date'] !== ""
	)
	{
		if( get_post_meta($post_id, "disable_default_notifications", true) === "yes" ) {
			return;
		}
		$order = wc_get_order( $post_id );
		wc_horizon_set_order_brand( $order );
		$oldShipment = array(
			'shipment_tracking_number' => $order->get_meta('shipment_tracking_number'),
			'shipment_provider' => $order->get_meta('shipment_provider'),
			'shipment_date' => $order->get_meta('shipment_date')
		);
		$newShipment = array(
			'shipment_tracking_number' => $_POST['shipment_tracking_number'],
			'shipment_provider' => $_POST['shipment_provider'],
			'shipment_date' => $_POST['shipment_date']
		);

		// If order tracking info is different
		if(
			$newShipment['shipment_tracking_number'] != $oldShipment['shipment_tracking_number'] ||
			$newShipment['shipment_provider'] != $oldShipment['shipment_provider']
		)
		{
			$date = new WC_DateTime( $newShipment['shipment_date'] );
			$context = array(
				'first_name' => $order->get_billing_first_name(),
				'order_number' => $order->get_order_number(),
				'tracking_number' => $newShipment['shipment_tracking_number'],
				'carrier' => $newShipment['shipment_provider'],
				'expected_time' => $date->format( 'M jS, Y' )
			);
			postmark_send_email('tracking_number', $context, wc_horizon_get_email_sender("tracking"), $order->get_billing_email(), []);
		}
		// If only the expected delivery date change
		else if(
			$newShipment['shipment_date'] != $oldShipment['shipment_date']
		)
		{
			$oldDate = new WC_DateTime( $oldShipment['shipment_date'] );
			$newDate = new WC_DateTime( $newShipment['shipment_date'] );
			$context = array(
				'first_name' => $order->get_billing_first_name(),
				'expected_time' => $oldDate->format( 'M jS, Y' ),
				'new_time' =>  $newDate->format( 'M jS, Y' ),
			);
			postmark_send_email('delivery_update', $context, wc_horizon_get_email_sender("tracking"), $order->get_billing_email(), []);
		}
	}
}

//Abandoned cart
//add_action( 'woocommerce_ac_send_email_action', 'send_notification_abandonend_cart', 11 );



// Registering custom post status
function wpb_custom_post_status(){
	register_post_status('only_read', array(
			'label'                     => _x( 'Only Read', 'post' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Only Read <span class="count">(%s)</span>', 'Only Read <span class="count">(%s)</span>' ),
	) );
}
add_action( 'init', 'wpb_custom_post_status' );




add_filter( 'graphql_woocommerce_new_cart_item_data', 'check_add_to_cart_contact', 100, 1);
function check_add_to_cart_contact( $cart_items_args ) {
		$variation = wc_get_product($cart_items_args[2]);
		if( $variation !== false ) {
			$qty = wc_qty_get_cart_qty($cart_items_args[2]);
			$quantity = $cart_items_args[1];
			$tiers = $variation->get_meta( 'b2bking_product_pricetiers_group_b2c' );
			$tiers = substr($tiers, -1) === ";" ? substr($tiers, 0, -1) : $tiers;
			$tiersArray = explode(";", $tiers);
			$lastTier = $tiersArray[count($tiersArray) - 1];
			$qtyPrice = explode(":", $lastTier);
			if( ($quantity + $qty) >= intval($qtyPrice[0]) && floatval($qtyPrice[1]) == 0 ) {
				wc_add_notice( __( "Your cart exceeds the maximum quantity", "woocommerce" ), "error" );
				$cart_items_args[1] = 0;
			}
		}
    return $cart_items_args;
}

function wc_qty_get_cart_qty( $variation_id ) {
	$running_qty = 0; 
	foreach(WC()->cart->get_cart() as $other_cart_item_keys => $values ) {
		if ( $variation_id == $values['variation_id'] ) {				
			$running_qty += (int) $values['quantity'];
		}
	}
	return $running_qty;
}

add_filter('woocommerce_admin_order_buyer_name', function( $buyer, $order ) {
	return $buyer . ($order->get_created_via() === "ac_free_sample" ?  " (Free Box)" : "");
}, 10, 2);


add_action('woocommerce_checkout_create_order_line_item', function($item, $cart_item_key, $values, $order) {
	if( key_exists("custom_title", $values) && !empty( $values["custom_title"] )  ) {
		$item->set_props( array(
			'name' => $values["custom_title"],
		) );
	}
}, 10, 4);


function __is_admin() {
	return current_user_can('administrator');
}

/**
 * Update manual lineitem in order to get the price from the tiered pricing
 */
add_filter( 'woocommerce_ajax_order_item', function( $item, $item_id, $order, $product) {
	$tiered_price = Horizon_Product_Helper::get_b2c_price( $product, $item->get_quantity() );
	if( $tiered_price ) {
		$total = floatval($tiered_price) * $item->get_quantity();
		$item->set_subtotal($total);
		$item->set_total($total);
		$item->save();
	}
	return $item;
}, 9999, 4 );


add_action('woocommerce_usio_before_return_failed_order', function($error) {
	if( defined( "WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS" ) && WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS === "yes" ) {
		return;
	}
	throw new GraphQL\Error\UserError($error->get_error_message());
});


function wc_notices_to_string( $notices = [] ) {
	array_reduce($notices, function( $acc, $notice ) {
		return $acc . $notice["notice"] . ", ";
	}, "" );
}

function wc_horizon_set_order_brand( $order ) {
	wc_horizon_set_brand( $order->get_meta("wcbrand_name") );
}

function wc_horizon_set_brand( $brand ) {
	$_POST["WOOCOMMERCE_BRAND_NAME"] = $brand;
}


function wc_horizon_add_order_brand( WC_Order $order ) {
	$order->update_meta_data( "wcbrand_name", wc_horizon_get_brand_name() );
}
add_action( 'woocommerce_checkout_create_order', 'wc_horizon_add_order_brand', 20, 2);

function wc_api_products_change_default_per_page( $args, $request ) {
	if( !$args["posts_per_page"] || $args["posts_per_page"] == 10 ) {
		$args["posts_per_page"] = 100;
	}
	return $args;
}
add_filter( 'woocommerce_rest_product_object_query', 'wc_api_products_change_default_per_page', 10, 2 );
add_filter( 'woocommerce_rest_product_variation_object_query', 'wc_api_products_change_default_per_page', 10, 2 );

// Prevent set directly the order status as completed from the api
function wc_api_set_order_status_completed_to_in_transit( $args ) {
	global $wp;
	global $HTTP_RAW_POST_DATA;
	$logger = wc_get_logger();

	if( $_SERVER["REQUEST_METHOD"] !== "POST" && $_SERVER["REQUEST_METHOD"] !== "PUT" ) return $args;
	if( !preg_match("/wp-json\/wc\/v3\/orders\/[0-9]+/", $wp->request) ) return $args;

	$post = $HTTP_RAW_POST_DATA ?? file_get_contents( 'php://input' );
	$post = json_decode( $post, true );

	$logger->info( wc_print_r($post, true), array("source", "cin7_order_updated") );

	if( $post["status"] !== "completed" ) return $args;

	$post["status"] = "in-transit";
	$HTTP_RAW_POST_DATA = json_encode( $post );

	return $args;
}

function wc_legacy_api_set_order_status_completed_to_in_transit( $data, $id, WC_API_Orders $object ) {
	global $wp;
	$logger = wc_get_logger();

	if( !preg_match("/wc-api\/v3\/orders\/[0-9]+/", $wp->request) ) return $data;
	$logger->info( wc_print_r($data, true), array("source", "cin7_order_updated-legacy") );
	if( $data["status"] !== "completed" ) return $data;
	$data["status"] = "in-transit";
	return $data;
}

add_filter( 'rest_allowed_cors_headers', 'wc_api_set_order_status_completed_to_in_transit' );
add_filter( 'woocommerce_api_edit_order_data', 'wc_legacy_api_set_order_status_completed_to_in_transit', 10, 3 );



function set_order_phone_if_not_exists( $order_id, WC_Order &$order ) {
	if( empty($order->get_shipping_phone()) ) {
		$order->set_shipping_phone( $order->get_billing_phone() );
		$order->save();
	}
}
add_action( 'woocommerce_new_order', 'set_order_phone_if_not_exists', 10, 2 );

add_action( 'user_register', 'add_crypto_key_to_user_on_register', 10, 1 );

function add_crypto_key_to_user_on_register( $user_id ) {
	if( class_exists( 'User_Data_Encryptation' ) ) {
		User_Data_Encryptation::generate_user_encriptation_key( $user_id );
	}
}

function add_subscription_brand_after_created( WC_Horizon_Recurrring_Order $recurring_order) {
	$recurring_order->update_meta("__wc_brand", wc_horizon_get_brand_name());
}
add_action( 'wc_horizon_recurring_order_created', 'add_subscription_brand_after_created' );


function add_subscription_order_brand_to_enviroment( WC_Horizon_Recurrring_Order $recurring_order ) {
	wc_horizon_set_brand( $recurring_order->get_meta( '__wc_brand' ) );
}
add_action( 'wc_horizon_recurring_order_before_process_order', 'add_subscription_order_brand_to_enviroment' );


function add_subscription_brand_to_the_order( WC_Horizon_Recurrring_Order $recurring_order, WC_Order $order ) {
	wc_horizon_set_brand( $recurring_order->get_meta( '__wc_brand' ) );
	wc_horizon_add_order_brand( $order );
}
add_action( 'wc_horizon_recurring_order_created_order', 'add_subscription_brand_to_the_order', 10, 2 );


function horizon_send_email_larger_savings(WP_REST_Request $request) {

    if ( horizon_api_validate_input(
        array("firstname", "lastname", "email", "phone", "company", "quantity" ),
        $request
    ) ) {
        $data = array(
            "firstname" => $request['firstname'],
            "lastname" => $request['lastname'],
            "email" => $request["email"],
            "phone" => $request["phone"],
            "company" => $request["company"],
            "quantity" => $request["quantity"]
        );
        postmark_send_email( 'buy-landing', $data, wc_horizon_get_email_sender("contact"), "support@amerisano.com,g.loza@rocket.pr,g.nunez@rocket.pr,a.luna@rocket.pr" );
        postmark_send_email( 'buy-landing', $data, wc_horizon_get_email_sender("contact"), $request["email"] );
        return array("success" => true);
    } else {
        return array("success" => false);
    }
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'horizon/v1', '/buy-landing', array(
        'methods' => ['GET', 'POST'],
        'callback' => 'horizon_send_email_larger_savings',
        'permission_callback' => '__return_true'

    ) );
} );

function horizon_contact_customer_address(WP_REST_Request $request) {

    if ( horizon_api_validate_input(
        array("firstname", "lastname", "email", "phone", "company", "address1", "address2", "phone", "city", "state", "postcode", "product", "quantity" ),
        $request
    )

    ) {

        $data = array(
            "firstname" => $request['firstname'],
            "lastname" => $request['lastname'],
            "email" => $request['email'],
            "company" => $request['company'],
            "address1" => $request['address1'],
            "address2" => $request['address2'],
            "phone" => $request['phone'],
            "city" => $request['city'],
            "state" => $request['state'],
            "postcode" => $request['postcode'],
            "productName" => $request['productName'],
        );
        postmark_send_email( 'customer-contact', $data, wc_horizon_get_email_sender("contact"), "l.jimenez@rocket.pr" );

        return array("success" => true);
    } else {
        return array("success" => false);
    }
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'horizon/v1', '/contact-customer-address', array(
        'methods' => ['GET', 'POST'],
        'callback' => 'horizon_contact_customer_address',
        'permission_callback' => '__return_true'

    ) );
} );
