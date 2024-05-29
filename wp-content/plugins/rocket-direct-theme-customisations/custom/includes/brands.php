<?php

define( 'BRAND_DEFAULT', 'rdirect' );
define( 'BRAND_AMERISANO', 'amerisano' );

function wc_horizon_get_brand_name() {
	if( isset( $_POST["WOOCOMMERCE_BRAND_NAME"] ) ) {
		return $_POST["WOOCOMMERCE_BRAND_NAME"];
	} else if( isset( $_SERVER["HTTP_WOOCOMMERCE_BRAND_NAME"] ) ) {
		return $_SERVER["HTTP_WOOCOMMERCE_BRAND_NAME"];
	} 
	return "";
}

function wc_horizon_get_brand_url() {
	$brand = wc_horizon_get_brand_name();
	if( $brand === BRAND_AMERISANO ) {
		return "https://www.amerisano.com";
	}
	return "https://www.rocket.direct";
}

function wc_horizon_get_default_brand() {
	return BRAND_DEFAULT;
}

function wc_horizon_get_brands( $associative = false ) {
	if( !$associative ) {
		return array(
			BRAND_DEFAULT,BRAND_AMERISANO
		);
	} else {
		return array(
			BRAND_DEFAULT => "Rocket Direct",
			BRAND_AMERISANO => "Amerisano"
		);
	}

}