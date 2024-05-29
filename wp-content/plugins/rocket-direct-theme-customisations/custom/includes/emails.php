<?php
/**
 * 1.- Base functions
 * 2.- Quote Request Email
 */

/**
 * 1.- Base functions
 */
function postmark_curl_connect( $url, $request_type, $api_key, $data = array() ) {
	$postmark = curl_init();
	$headers = array(
		'Content-Type: application/json',
		'X-Postmark-Server-Token: '.$api_key
	);
	curl_setopt($postmark, CURLOPT_URL, $url );
	curl_setopt($postmark, CURLOPT_HTTPHEADER, $headers);
	//curl_setopt($postmark, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
	curl_setopt($postmark, CURLOPT_RETURNTRANSFER, true); // do not echo the result, write it into variable
	curl_setopt($postmark, CURLOPT_CUSTOMREQUEST, $request_type);
	curl_setopt($postmark, CURLOPT_TIMEOUT, 10);
	curl_setopt($postmark, CURLOPT_SSL_VERIFYPEER, false); // certificate verification for TLS/SSL connection
 
	if( $request_type != 'GET' ) {
		curl_setopt($postmark, CURLOPT_POST, true);
		curl_setopt($postmark, CURLOPT_POSTFIELDS, json_encode($data) ); // send data in json
	}
 
    return curl_exec($postmark);

}

function postmark_send_email($alias, $context, $from, $to, $attachments = []) {

	/* Postmark API URL */
	$url = 'https://api.postmarkapp.com/email/withTemplate';

	$data = array(
		'TemplateAlias' => $alias,
		'TemplateModel' => $context,
		'From' => $from,
		'To' => $to,
		'Attachments' => $attachments
	);
  return postmark_curl_connect( $url, 'POST', get_postmark_api_by_brand(), $data );
}

function postmark_get_template( $alias, $withLayout = true ) {
	/* Postmark API URL */
	$url = 'https://api.postmarkapp.com/templates/'.$alias;

	$response = json_decode( postmark_curl_connect( $url, 'GET', get_postmark_api_by_brand() ) );

	if( !$response || !$response->TemplateId ) {
		return false;
	}

	if( !$response->LayoutTemplate || !$withLayout) {
		return $response;
	}

	$layout = postmark_get_template( $response->LayoutTemplate, false );

	if ( !$layout ) {
		return $response;
	}

	$response->HtmlBody = str_replace( "{{{ @content }}}", $response->HtmlBody, $layout->HtmlBody );

	return $response;
}


/**
 * 1.- Quote Request Email
 */

 function send_quote_request_email()
 {
    if( isset( 
      $_POST["firstName"], $_POST["desiredQty"], $_POST["product"], 
      $_POST["desiredDeliveryTime"], $_POST["message"], $_POST["email"] 
    ) )
    {
      $context = array(
        'first_name' => $_POST["firstName"],
        'quantity' => $_POST["desiredQty"],
        'product' => $_POST["product"],
        'desired_time' =>  $_POST["desiredDeliveryTime"],
        'message' => $_POST["message"]
      );
      postmark_send_email( 'quote_request', $context, wc_horizon_get_email_sender("sales"), $_POST["email"] );
      wp_send_json_success();
    }else {
      wp_send_json_error();
    }
 }

  add_action('wp_ajax_send_quote_request_email', 'send_quote_request_email');
  add_action('wp_ajax_nopriv_send_quote_request_email', 'send_quote_request_email');

	function postmark_get_email_pdf( $template, $file_name, $context = array() ) {
		$template = postmark_get_template( $template, false );
		if( $template ) {
			$mustache = new Mustache_Engine;
			$templateRendered = $mustache->render( 
				email_transform_mustachio_to_mustache( $template->HtmlBody ), 
				$context
			);
			return postmark_conver_email_to_snappy_pdf( $templateRendered, $file_name );
		}
		return NULL;
	}

	function email_transform_mustachio_to_mustache( $html ) {
		return str_replace( 
			"{{#each items}}", 
			"{{#items}}", 
			str_replace(
				"{{/each}}",
				"{{/items}}",
				$html
			)
		);
	}

	function postmark_conver_email_to_snappy_pdf( $html, $file_name ) {
		if( !defined("WP_TEMP_DIR") ){
			return NULL;
		}
		$snappy = new \Knp\Snappy\Pdf('/usr/local/bin/wkhtmltopdf');
		$snappy->setOption('footer-html', __DIR__ . "/footer.html");
		$options = [
			'margin-top'    => 0,
			'margin-right'  => 0,
			'margin-bottom' => 20,
			'margin-left'   => 0,
			'page-size' => 'A5'
		];
		$file = WP_TEMP_DIR . "/" . uniqid() .".pdf";
		$snappy->generateFromHtml($html, $file, $options);
		if (file_exists($file)) {
			$content = file_get_contents($file);
			$content = chunk_split(base64_encode($content));
			$order_confirmation_pdf = array('Name' => $file_name . '.pdf', 'Content' => $content, 'ContentType' => 'application/octet-stream');
			unlink($file);
			return $order_confirmation_pdf;
		}
		return NULL;
	}

	/**
	 * Begin Postmark webhook
	 */

	function postmark_webhook(WP_REST_Request $request) {
		try {
			if(isset( $request["From"] )) {
				$email = $request["From"];
				$name = explode(" ", $request["FromName"]);
				hubspot_create_contact($email, $name[0], $name[1], explode("@", $email)[1]);
			}
			return array('success' => true);
		} catch(Exception $e) {
			return array('success' => false);
		}
		
	}
	add_action( 'rest_api_init', function () {
		register_rest_route( 'horizon/v1', '/postmark', array(
			'methods' => ['GET', 'POST'],
			'callback' => 'postmark_webhook',
			'permission_callback' => '__return_true'
		) );
	} );

	function get_postmark_api_by_brand() {
		$brand = wc_horizon_get_brand_name();
		if( $brand === BRAND_AMERISANO ) {
			return "b94ee8a8-ff37-4959-94e4-24bb719ff007";
		} else {
			return "c7f0ac3a-a9f4-4946-948d-56d146c4366b";
		}
	}

	function wcal_filter_abandoned_cart_email( $args ) {
		$session =  key_exists( "session", $args["variables"] ) ? $args["variables"]["session"] : null;

		if( $session && property_exists( $session, "brand" ) ) {
			wc_horizon_set_brand( $session->brand );
		}

		postmark_send_email( "your_order_waiting", array(
			"first_name" =>  $args["variables"] ? $args["variables"]["firstname"] : ""
		), wc_horizon_get_email_sender("sales"), $args["email"]);

		$args["sended"] = true;

		return $args;
	}
	add_filter( 'wacl_get_email_args', 'wcal_filter_abandoned_cart_email' );