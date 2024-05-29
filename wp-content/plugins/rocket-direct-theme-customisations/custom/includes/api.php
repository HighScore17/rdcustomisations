<?php
/**
 * Contact
 */

 /**
  * Validate input to not be empty
  * @param array $fields Array of string of the fields names that will be validates
  * @param WP_REST_Request $request WP API Request object
  * @return bool Return true if the input isn't empty
  */
function horizon_api_validate_input( $fields, WP_REST_Request $request ) {
  $valid = TRUE; $i = 0; $length = count($fields);
  while( $valid && $i < $length && $i < 100 ) {
    if( !isset( $request[ $fields[ $i ] ] ) ) {
      $valid = FALSE;
    }
    $i = $i + 1;
  }
  return $valid;
}

/**
 * Trim each line of a message and remove the tabs
 * @param string $str String to be formatted
 * @return string String formatted
 */

function horizon_api_trim_lines( $str ) {
  return implode("\n", array_map('trim', explode("\n", preg_replace('/\t+/', '', $str))));
}

function horizon_api_get_tag_by_context( $context ) {
  if( $context === "Contact Form - Footer" )
    return "contact-form";
  else if ( $context === "Contact Form - Kingfa Medical" )
    return "kingfa-landing";
  else if( $context === "Contact Form - Dentist" )
    return "dentist-landing";
  else if( $context === "Wholesale Account" )
      return "wholesale-account";
  else if( $context === "Price Unlock" )
      return "price-unlock";
  else if( $context === "Quote Request" )
      return "quote-request";
  else
    return "";
}

function horizon_rdirect_on_contact_form(WP_REST_Request $request) {
  if ( horizon_api_validate_input( 
    array( "email", "firstname", "lastname", "phone", "message" ),
    $request
   ) ) {
    $fullname = $request['firstname'] . " " . $request['lastname'];
		$slack_message = "
			A lead has filled the contact form.
			Contact Information is :
      Email: {$request['email']}
      Name: {$fullname} 
      Phone: {$request['phone']}
      Message: {$request['message']}
      Context: {$request['context']}
		";
    $data = array(
      "full_name" => $fullname,
      "message" => $request["message"]
    );
		slack_post_message( horizon_api_trim_lines( $slack_message ), __slack_channel("tickets") );
    postmark_send_email( 'contact', $data, wc_horizon_get_email_sender("contact"), "rocket.direct@horizon.com.pr" );
    postmark_send_email( 'contact', $data, wc_horizon_get_email_sender("contact"), $request["email"] );
    rudr_mailchimp_subscribe( 
      array( "email" => $request["email"], "fname" => $request["firstname"], "lname" => $request["lastname"] ), 
      array( array( "name" => horizon_api_get_tag_by_context( $request["context"] ), "status" => "active" ) ) 
    );
		return array("success" => true);
	} else {
		return array("success" => false);
	}
}


add_action( 'rest_api_init', function () {
  register_rest_route( 'horizon/v1', '/rd-contact', array(
    'methods' => ['GET', 'POST'],
    'callback' => 'horizon_rdirect_on_contact_form',
    'permission_callback' => '__return_true'

  ) );
} );

function horizon_rdirect_on_contact_form_ac(WP_REST_Request $request) {
  if ( horizon_api_validate_input( 
    array( "email", "firstname", "lastname", "message" ),
    $request
   ) ) {
    $data = array(
      "full_name" => $request['firstname'] . " " . $request['lastname'],
      "message" => $request["message"]
    );
    postmark_send_email( 'contact', $data, wc_horizon_get_email_sender("contact"), "rocket.direct@horizon.com.pr" );
    postmark_send_email( 'contact', $data, wc_horizon_get_email_sender("contact"), $request["email"] );
		return array("success" => true);
	} else {
		return array("success" => false);
	}
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'horizon/v1', '/rd-contact/ac', array(
    'methods' => ['GET', 'POST'],
    'callback' => 'horizon_rdirect_on_contact_form_ac',
    'permission_callback' => '__return_true'

  ) );
} );

function horizon_rdirect_on_discount_5_off(WP_REST_Request $request) {
  if ( horizon_api_validate_input( array( "email" ), $request ) ) {
    rudr_mailchimp_subscribe( 
      array( "email" => $request["email"] ),
      array( array( "name" => "discount", "status" => "active" ) )  
    );
    return array( "success" => true );
  }
  return array( "success" => false, "message" => "Invalid email" );
}
add_action( 'rest_api_init', function () {
  register_rest_route( 'horizon/v1', '/rd-discount-5-off', array(
    'methods' => ['POST'],
    'callback' => 'horizon_rdirect_on_discount_5_off',
    'permission_callback' => '__return_true'

  ) );
} );

//wholesale notification
function horizon_rdirect_on_request_wholesale(WP_REST_Request $request) {
    if ( horizon_api_validate_input(
        array( "email", "firstname", "lastname", "phone"),
        $request
    ) ) {
        $fullname = $request['firstname'] . " " . $request['lastname'];
        $slack_message = "
			A lead has requested a wholesale account.
			Contact Information is :
      Corporate Email: {$request['email']}
      Name: {$fullname} 
      Phone: {$request['phone']}
      Company: {$request['company']}
      Position: {$request['jobtitle']}
      # of Employees: {$request['employees_number']}
      DUNS: {$request['dunsn']}
      Credit Terms: {$request['payment_terms_prop']}
      Tax Exempt: {$request['tax_exempt_prop']}
		";
        slack_post_message( horizon_api_trim_lines( $slack_message ), __slack_channel("tickets") );
        rudr_mailchimp_subscribe(
            array( "email" => $request["email"], "fname" => $request["firstname"], "lname" => $request["lastname"] ),
            array( array( "name" => horizon_api_get_tag_by_context( "Wholesale Account"), "status" => "active" ) )
        );
        return array("success" => true);
    } else {
        return array("success" => false);
    }
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'horizon/v1', '/rd-wholesale', array(
        'methods' => ['GET', 'POST'],
        'callback' => 'horizon_rdirect_on_request_wholesale',
        'permission_callback' => '__return_true'

    ) );
} );


function horizon_rdirect_on_request_sample(WP_REST_Request $request) {
  if ( horizon_api_validate_input(
      array( "email", "firstname", "lastname", "product_presentation"),
      $request
  ) ) {
      $fullname = $request['firstname'] . " " . $request['lastname'];
      $slack_message = "
        A lead has filled the Sample Request form.
        Contact Information is :
        Email: {$request['email']}
        Name: {$fullname} 
        Phone: {$request['phone']}
        Company: {$request['company']}
        Product: {$request['product_presentation']}
      ";
      $data = array(
        'fname' => $request['firstname'],
        'product' => $request['product_presentation']
      );
      slack_post_message( horizon_api_trim_lines( $slack_message ), __slack_channel("tickets") );
      postmark_send_email( 'request-sample', $data, wc_horizon_get_email_sender("sales"), "sales@rocket.direct" );
      postmark_send_email( 'request-sample', $data, wc_horizon_get_email_sender("sales"), $request['email'] );
      rudr_mailchimp_subscribe(
          array( "email" => $request["email"], "fname" => $request["firstname"], "lname" => $request["lastname"] ),
          array( array( "name" => 'sample-request', "status" => "active" ) )
      );
      return array("success" => true);
  } else {
      return array("success" => false);
  }
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'horizon/v1', '/rd-sample-request', array(
      'methods' => ['GET', 'POST'],
      'callback' => 'horizon_rdirect_on_request_sample',
      'permission_callback' => '__return_true'

  ) );
});
//unlock table prices notification

function horizon_rdirect_on_price_unlock(WP_REST_Request $request) {
    if ( horizon_api_validate_input(
        array( "email"),
        $request
    ) ) {
      $slack_message = "
			A lead has unlock RD - bulk product pricing from rocket.direct price page.
			Contact Information is :
      Email: {$request['email']}
      Page: {$request['url']}
		";
        slack_post_message( horizon_api_trim_lines( $slack_message ), __slack_channel("tickets") );
        rudr_mailchimp_subscribe(
            array( "email" => $request["email"], "fname" => $request["firstname"], "lname" => $request["lastname"] ),
            array( array( "name" => horizon_api_get_tag_by_context( "Price Unlock"), "status" => "active" ) )
        );
        return array("success" => true);
    } else {
        return array("success" => false);
    }
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'horizon/v1', '/rd-price-unlock', array(
        'methods' => ['GET', 'POST'],
        'callback' => 'horizon_rdirect_on_price_unlock',
        'permission_callback' => '__return_true'

    ) );
} );

// Request a quote notification
function horizon_rdirect_on_quote_request(WP_REST_Request $request) {
    if ( horizon_api_validate_input(
        array( "email", "firstname", "lastname", "phone"),
        $request
    ) ) {
        $fullname = $request['firstname'] . " " . $request['lastname'];
        $slack_message = "
			A lead has requested a quote for {$request['form_name']}
			Contact Information is :
      Email: {$request['email']}
      Name: {$fullname} 
      Phone: {$request['phone']}
      Company: {$request['company']}
      Position: {$request['jobtitle']}
      Product Presentation: {$request['product_presentation']}
      Desired Delivery Time: {$request['delivery_time']}
      Desired Quantity: {$request['product_quantity']}
      Message: {$request['message']}
		";
        slack_post_message( horizon_api_trim_lines( $slack_message ), __slack_channel("tickets") );
        rudr_mailchimp_subscribe(
            array( "email" => $request["email"], "fname" => $request["firstname"], "lname" => $request["lastname"] ),
            array( array( "name" => horizon_api_get_tag_by_context( "Quote Request"), "status" => "active" ) )
        );
        return array("success" => true);
    } else {
        return array("success" => false);
    }
}

add_action( 'rest_api_init', function () {
    register_rest_route( 'horizon/v1', '/rd-quote-request', array(
        'methods' => ['GET', 'POST'],
        'callback' => 'horizon_rdirect_on_quote_request',
        'permission_callback' => '__return_true'

    ) );
} );


function horizon_on_free_box_request( WP_REST_Request $request ) {
  $params = $request->get_params();
  postmark_send_email( 'rd-dentist-request-sample', $params, wc_horizon_get_email_sender("account") , $params["email"], []);
  postmark_send_email( 'rd-dentist-request-sample', $params, wc_horizon_get_email_sender("account") , "abigail@horizon.com.pr,grecia@horizon.com.pr,javier@horizon.com.pr,g.nunez@rocket.pr", []);
  return $params;
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'horizon/v1', '/free-box-email', array(
      'methods' => ['GET', 'POST'],
      'callback' => 'horizon_on_free_box_request',
      'permission_callback' => '__return_true'
  ) );
} );
