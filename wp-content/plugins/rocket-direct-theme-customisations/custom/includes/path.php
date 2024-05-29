<?php

define( 'FREE_GLOVES_REQUEST', 'free-gloves-box/thank-you' );

function wc_horizon_get_url_path() {
	if( isset( $_POST["WOOCOMMERCE_URL_PATH"] ) ) {
		return $_POST["WOOCOMMERCE_URL_PATH"];
	} else if( isset( $_SERVER["HTTP_WOOCOMMERCE_URL_PATH"] ) ) {
		return $_SERVER["HTTP_WOOCOMMERCE_URL_PATH"];
	} 
	return "";
}

function wc_horizon_get_path_url() {
	$path = wc_horizon_get_url_path();
	if( $path === FREE_GLOVES_REQUEST ) {
		return "free-gloves-box/thank-you";
	}
	return "checkout";
}