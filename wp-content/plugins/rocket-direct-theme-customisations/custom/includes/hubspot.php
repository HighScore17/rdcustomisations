<?php
define('HUBSPOT_API_KEY', 'd6e6e4f1-6802-478b-be51-090fb7c5dc11');

function hubspot_curl_connect($url, $request_type, $data = array()) {
  $hubspot = curl_init();
	$headers = array(
		'Content-Type: application/json',
	);
	curl_setopt($hubspot, CURLOPT_URL, $url );
	curl_setopt($hubspot, CURLOPT_HTTPHEADER, $headers);
	//curl_setopt($postmark, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
	curl_setopt($hubspot, CURLOPT_RETURNTRANSFER, true); // do not echo the result, write it into variable
	curl_setopt($hubspot, CURLOPT_CUSTOMREQUEST, $request_type);
	curl_setopt($hubspot, CURLOPT_TIMEOUT, 10);
	curl_setopt($hubspot, CURLOPT_SSL_VERIFYPEER, false); // certificate verification for TLS/SSL connection
 
	if( $request_type != 'GET' ) {
		curl_setopt($hubspot, CURLOPT_POST, true);
		curl_setopt($hubspot, CURLOPT_POSTFIELDS, json_encode($data) ); // send data in json
	}
  return curl_exec($hubspot);
}

function hubspot_create_contact($email, $firstname, $lastname, $website) {
  return hubspot_curl_connect(
    "https://api.hubapi.com/crm/v3/objects/contacts?hapikey=" . HUBSPOT_API_KEY,
    "POST",
    array(
      "properties" => array(
        "email" => $email,
        "firstname" => $firstname,
        "lastname" => $lastname,
        "website" => $website,
      )
    )
  );
}

function hubspot_create_contact_at_new_order( $order_id ) {
  $order = wc_get_order($order_id);
	$email = $order->get_billing_email();
	$firstname = $order->get_billing_first_name();
	$lastname = $order->get_billing_last_name();
  $website = explode("@", $email)[1];
  hubspot_create_contact($email, $firstname, $lastname, $website);
}
add_action( 'woocommerce_checkout_order_processed', 'hubspot_create_contact_at_new_order', 10, 1 );

function hubspot_create_contact_at_new_user( $user_id ) {
	$user = get_user_by('id', $user_id);
  $email = $user->user_email;
  $firstname = $user->first_name;
  $lastname = $user->last_name;
  $website = explode("@", $email)[1];
  hubspot_create_contact($email, $firstname, $lastname, $website);
  rudr_mailchimp_subscribe( array( "email" => $email, "fname" => $firstname, "lname" => $lastname ), 
    array( array( "name" => "account-creation", "status" => "active" ) )
  );
}

add_action( 'account_activation', 'hubspot_create_contact_at_new_user', 10, 1 );

add_action( 'rest_api_init', function () {
  register_rest_route( 'horizon/v1', '/postmark', array(
    'methods' => ['GET', 'POST'],
    'callback' => 'postmark_webhook',
    'permission_callback' => '__return_true'

  ) );
} );
