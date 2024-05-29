<?php
/*
 * Plugin Name: Net 15 days - Payment Terms gateway
 * Plugin URI: #
 * Description: Net 15 days - Payment Terms gateway
 * Author: Fernando Osuna
 * Author URI: #
 * Version: 1.0.0
 *
 * /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'net_15_days_add_gateway_class' );
function net_15_days_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Net_15_Days_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'net_15_days_init_gateway_class' );
function net_15_days_init_gateway_class() {

	class WC_Net_15_Days_Gateway extends WC_Payment_Gateway {

 		public function __construct() {
			$this->id = 'net_15_days'; // payment gateway plugin ID
			$this->method_title = 'Net 15 days - Payment Terms';
			$this->method_description = ''; // will be displayed on the options page

			// gateways can support subscriptions, refunds, saved payment methods,
			// but in this tutorial we begin with simple payments
			$this->supports = array(
				'products'
			);

			// Method with all the options fields
			$this->init_form_fields();
		
			// Load the settings.
			$this->init_settings();
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' );

			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		
			// We need custom JavaScript to obtain a token
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );

			// You can also register a webhook here
			// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 		}
		/**
 		 * Plugin options
 		 */
 		public function init_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable Net 15 days - Payment Terms gateway',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Net 15 days - Payment Terms',
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => '',
				)
			);
	 	}

		public function payment_fields() {
		}

		public function payment_scripts() {
		}
 
		public function validate_fields() {
		}
 
		public function process_payment( $order_id ) {
			
			global $woocommerce;
 
			// we need it to get any order detailes
			$order = wc_get_order( $order_id );

			// to trigger the email
			do_action( 'woocommerce_order_status_processing', $order_id );
			$order->reduce_order_stock();

			// Empty cart
			$woocommerce->cart->empty_cart();

			// Redirect to the thank you page
			return array(
				'result' => 'success',
				'redirect' => null
			);

	 	}

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
	 	}
 	}
}