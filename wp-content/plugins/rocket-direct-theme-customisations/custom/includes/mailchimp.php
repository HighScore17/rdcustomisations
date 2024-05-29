<?php
/**
 * MailChimp connection
 */
function rudr_mailchimp_curl_connect( $url, $request_type, $api_key, $data = array() ) {
	if( $request_type == 'GET' )
		$url .= '?' . http_build_query($data);
 
	$mch = curl_init();
	$headers = array(
		'Content-Type: application/json',
		'Authorization: Basic '.base64_encode( 'user:'. $api_key )
	);
	curl_setopt($mch, CURLOPT_URL, $url );
	curl_setopt($mch, CURLOPT_HTTPHEADER, $headers);
	//curl_setopt($mch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');
	curl_setopt($mch, CURLOPT_RETURNTRANSFER, true); // do not echo the result, write it into variable
	curl_setopt($mch, CURLOPT_CUSTOMREQUEST, $request_type); // according to MailChimp API: POST/GET/PATCH/PUT/DELETE
	curl_setopt($mch, CURLOPT_TIMEOUT, 10);
	curl_setopt($mch, CURLOPT_SSL_VERIFYPEER, false); // certificate verification for TLS/SSL connection
 
	if( $request_type != 'GET' ) {
		curl_setopt($mch, CURLOPT_POST, true);
		curl_setopt($mch, CURLOPT_POSTFIELDS, json_encode($data) ); // send data in json
	}
 
	return curl_exec($mch);
}

/**
 * MailChimp subscription
 */
function rudr_mailchimp_subscribe( $fields, $tags = array( 'contact-form' ), $api_key = '6780f535f3165c1e40fa4277f9631697-us5', $list_id = '39ee981f0d' ) {
  if( !empty( $api_key ) && !empty( $list_id ) && is_array( $fields ) && array_key_exists( "email", $fields ) ) {
    $email = $fields['email'];
    $merge_fields = [];
  
    if( isset( $fields['fname'], $fields['lname'] ) )
    {
      $merge_fields['FNAME'] = $fields['fname'];
      $merge_fields['LNAME'] = $fields['lname'];
    }
   
    /* MailChimp API URL */
    $url = 'https://' . substr($api_key,strpos($api_key,'-')+1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email));
    /* MailChimp POST data */
    
    $data = array_merge( 
      array(
        'email_address' => $email,
        'status'        => 'subscribed', // 'subscribed' and 'unsubscribed'
      ),
      count( $merge_fields ) > 0 ? array('merge_fields' => $merge_fields) : array()
    );
    rudr_mailchimp_curl_connect( $url, 'PUT', $api_key, $data );

    $url2 = 'https://' . substr($api_key,strpos($api_key,'-')+1) . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/' . md5(strtolower($email)) . "/tags";
    rudr_mailchimp_curl_connect( $url2, 'POST', $api_key, array(
      'tags' => $tags
    ) );
    return TRUE;
  }
  return FALSE;
}