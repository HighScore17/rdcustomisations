<?php

define('SLACK_CHANNEL_TEST', '#slack-api-test');
define('SLACK_CHANNEL_TICKETS', '#rocketdirect-tickets');
define('SLACK_CHANNEL_CART', '#rocketdirect-tickets-abandonedcarts');


/**
 * 
 * 0.- Base Functions
 * 1.- User registration
 * 2.- Abandonen cart email
 * 3.- New order
 * 
 */


/**
 * 0.- Base functions
 */
function slack_post_message($message, $channel) {
	$ch = curl_init("https://slack.com/api/chat.postMessage");
	$data = http_build_query([
		"token" => "xoxb-221176612244-1404650866580-qNpuq4YeuwiX1cau4oF59MOf",
		"channel" => __is_development_enviroment() ? SLACK_CHANNEL_TEST : $channel, //"#mychannel",
		"text" => $message, //"Hello, Foo-Bar channel message.",
		"username" => "horizontrade-bot",
	]);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}

/**
 * 1.- User Registered
 */
function send_slack_notification_new_user( $user_id )
{
  $user = get_userdata( $user_id );
	if($user->user_email != HORIZON_TEST_EMAIL) {
		slack_post_message( ":adult::skin-tone-2: $user->user_email just registered to rocket.direct :adult::skin-tone-2:", __slack_channel("tickets") );
	}
}
add_action( 'account_activation', 'send_slack_notification_new_user', 10, 1 );


/**
 * 2.- Abandonen cart
 */

function send_notification_abandonend_cart( $to, $template_name )
{
	slack_post_message( ":shopping_trolley: The abandoned cart email \"$template_name\" was sent to $to :shopping_trolley: ", __slack_channel("cart") );
}
add_action( 'wcal_send_email', 'send_notification_abandonend_cart', 10, 2);

/**
 * 3.- New Order
 */

function send_slack_notification_new_order( $order_id )
{
	$order = wc_get_order( $order_id );
	$email = $order->get_billing_email();
	$created_via = $order->get_created_via();
	$order_url = "<". __get_order_admin_url($order) ."|#" . $order->get_order_number() . ">";

	if( $created_via === 'ac_free_sample' || __str_starts_with( $created_via, "active_campaign" ) || $email === HORIZON_TEST_EMAIL ) {
		return;
	}

	wc_horizon_set_order_brand( $order );

	if( $created_via === 'horizon_subscription' ) {
		return slack_post_message( ":package: The order $order_url of $email has been created from a subscription :package:", __slack_channel("tickets") );
	}

	if( !isset($_POST["is_frontend_request"]) || $_POST["is_frontend_request"] === false ) {
		send_slack_notification_checkout_order_created( $order_id );
	}
}
add_action( 'woocommerce_new_order', 'send_slack_notification_new_order' );

/**
 * Order created from checkout
 */
function send_slack_notification_checkout_order_created( $order_id )
{
	$order = wc_get_order( $order_id );
	$email = $order->get_billing_email();
	$created_via = $order->get_created_via();
	$order_url = "<". __get_order_admin_url($order) ."|#" . $order->get_order_number() . ">";
	$message = "\n";
	$order_page = isset( $_POST["checkoutOrderPage"] ) ? $_POST["checkoutOrderPage"] : "unknown";
	$shipping_address_validated = isset( $_POST["shippingValidated"] ) && $_POST["shippingValidated"] === "yes";

	if( $created_via === 'horizon_subscription' || $created_via === 'ac_free_sample' || __str_starts_with( $created_via, "active_campaign" ) || $email === HORIZON_TEST_EMAIL) {
		return;
	}

	wc_horizon_set_order_brand( $order );

	foreach( $order->get_items() as $key => $item ) {
		$message .= "    • " .$item->get_name() . " - " . ucfirst($item->get_meta("pa_presentation") ?? "") . " " . strtoupper( $item->get_meta("pa_size") ?? "" ) . " x" . $item->get_quantity() . "\n";
	}

	$message .= "\n*Total:* $" . $order->get_total();

	if( $order_page ) {
		$message .= "\nPage: " . $order_page;
	}

	if( !$shipping_address_validated ) {
		$message .= "\nUser selected unverified address";
	}

	slack_post_message( ":package: $email placed the order $order_url :package:" . $message, __slack_channel("tickets") );
}

add_action( 'woocommerce_checkout_order_created', 'send_slack_notification_checkout_order_created' );

function send_slack_notification_shipped_order( $order_id )
{
	$order = wc_get_order( $order_id );
	$email = $order->get_billing_email();
	if( $email != HORIZON_TEST_EMAIL ) {
		wc_horizon_set_order_brand( $order );
		slack_post_message( ":truck: The order #" . $order->get_order_number() . " of " . $email . " was shipped.", __slack_channel("logistics") );
	}
}
add_action( 'woocommerce_order_status_in-transit', 'send_slack_notification_shipped_order', 10, 1 );

// Recurring orders 

function send_slack_notification_on_subscription_payment_failed( $recurring_order, $order, $reason ) {
	$subscription_id = $recurring_order->get_id();
	slack_post_message( ":x: Subscription #{$subscription_id} cannot be proccesed. Reason: " . $reason, __slack_channel("subscriptions") );
}

add_action('wc_horizon_recurring_order_payment_failed', 'send_slack_notification_on_subscription_payment_failed', 10, 3);

function wc_hook_on_recurring_order_failed( WC_Horizon_Recurrring_Order $recurring_order, $reason ) {
	$subscription_id = $recurring_order->get_id();
	slack_post_message( ":x: Subscription #{$subscription_id} cannot be processed. Reason: " . $reason, __slack_channel("subscriptions") );
}
add_action( 'wc_horizon_recurring_order_failed', 'wc_hook_on_recurring_order_failed', 10, 2 );


function wc_hook_on_recurring_order_processed_successfully( WC_Horizon_Recurrring_Order $recurring_order, WC_Order $order ) {
	$order_url = "<". __get_order_admin_url($order) ."|#" . $order->get_order_number() . ">";
	slack_post_message( ":moneybag: The subscription order " . $order_url . " was charged successfully", __slack_channel("tickets") );
}
add_action( 'wc_horizon_recurring_order_processed_successfully', 'wc_hook_on_recurring_order_processed_successfully', 10, 2 );

function wc_hook_on_recurring_order_created( WC_Horizon_Recurrring_Order $recurring_order ) {
	$user = WC_Horizon_Email_Notifications_For_Recurring_Orders::get_subs_user_email( $recurring_order );
	slack_post_message( ":alarm_clock: " . $user . " has created the subscription #" . $recurring_order->get_id(), __slack_channel("subscriptions") );
}

add_action( 'wc_horizon_after_create_recurring_order', 'wc_hook_on_recurring_order_created' );

function wc_hook_on_recurring_order_canceled( WC_Horizon_Recurrring_Order $recurring_order, $status ) {
	if( !$recurring_order->is_active() ) {
		$user = WC_Horizon_Email_Notifications_For_Recurring_Orders::get_subs_user_email( $recurring_order );
		slack_post_message( ":alarm_clock: " . $user . " has canceled the subscription #" . $recurring_order->get_id(), __slack_channel("subscriptions") );
	}
}

add_action( "wc_horizon_recurring_order_status_changed", 'wc_hook_on_recurring_order_canceled', 10, 2 );


function wc_hook_on_recurring_order_updated( WC_Horizon_Recurrring_Order $old, WC_Horizon_Recurrring_Order $new ) {
	if( $new->is_active() ) {
		$changes = array_map( 'ucfirst', wc_horizon_recurring_order_detect_changes( $old, $new ) );
		$changes_msg = implode( ", ", $changes );
		$changes_msg = str_replace( "_", " ", $changes_msg );
		$user = WC_Horizon_Email_Notifications_For_Recurring_Orders::get_subs_user_email( $new );
		slack_post_message( ":alarm_clock: " . $user . " has updated the $changes_msg of the subscription #" . $new->get_id(), __slack_channel("subscriptions") );
	}
}

add_action( "wc_horizon_recurring_order_updated", 'wc_hook_on_recurring_order_updated', 10, 2 );