<?php
/*
 * Plugin Name: Usio payments for WooCommerce
 * Plugin URI: #
 * Description: Take credit card payments on your store with Usio
 * Author: Luis Angel Jimenez
 * Author URI: #
 * Version: 2.0.2
 *
 * /*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */

require_once __DIR__ . "/usio-sdk.php";

add_filter( 'woocommerce_payment_gateways', 'usio_add_gateway_class' );
function usio_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Usio_Gateway'; // your class name is here
	return $gateways;
}
 
/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'usio_init_gateway_class' );
function usio_init_gateway_class() {
 
	class WC_Usio_Gateway extends WC_Payment_Gateway {
 
 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {
			$this->id = 'usio'; // payment gateway plugin ID
			$this->icon = 'https://api.securepds.com/2.0/documentation/logo.png'; // URL of the icon that will be displayed on checkout page near your gateway name
			$this->has_fields = true; // in case you need a custom credit card form
			$this->method_title = 'Usio Gateway';
			$this->method_description = 'Description of Usio payment gateway'; // will be displayed on the options page
		
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
			$this->testmode = 'yes' === $this->get_option( 'testmode' );
			$this->test_error = 'yes' === $this->get_option( 'test_error' ) && current_user_can('administrator');
			$this->allow_logs = 'yes' === $this->get_option("allow_logs");
			$this->test_error_message = $this->get_option( 'test_error_message' );
			$this->merchant_id = $this->testmode ? "0000000001" : $this->get_option( 'merchant_id' );
			$this->api_key = $this->testmode ? "AEAE82F9-5A34-47C3-A61E-1E8EE37BE3AD" : $this->get_option( 'api_key' );
			$this->username = $this->testmode ? "API0000000001" : $this->get_option( 'username' );
			$this->password = $this->testmode ? "Temp1234!" : $this->get_option( 'password' );

			// This action hook saves the settings
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		
			// We need custom JavaScript to obtain a token
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		
			// You can also register a webhook here
			// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 		}
		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){
			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Enable/Disable',
					'label'       => 'Enable Usio Gateway',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'Credit Card',
					'desc_tip'    => true,
				),
				'description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => '',
				),
				'testmode' => array(
					'title'       => 'Test mode',
					'label'       => 'Enable Test Mode',
					'type'        => 'checkbox',
					'description' => 'Place the payment gateway in test mode using test API keys.',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'test_error' => array(
					'title'       => 'Test Error',
					'label'       => 'Test Error for admins',
					'type'        => 'checkbox',
					'description' => 'Test a error message that will be throwed instead request USIO payment (Admin user is needed)',
					'default'     => 'yes',
					'desc_tip'    => true,
				),
				'test_error_message' => array(
					'title'       => 'Test Error Message',
					'type'        => 'textarea',
					'description' => 'The error message that will be showed.',
					'default' => 'Invalid Credit Card'
				),
				'allow_logs' => array(
					'title'       => 'Logs',
					'label'       => 'Enable Logs',
					'type'        => 'checkbox',
					'description' => 'If enable then error logs will be saved in woocommerce status logs',
					'default'     => 'no',
					'desc_tip'    => true,
				),
				'merchant_id' => array(
					'title'       => 'Merchant ID',
					'type'        => 'text'
				),
				'api_key' => array(
					'title'       => 'API Key',
					'type'        => 'text'
				),
				'username' => array(
					'title'       => 'Username',
					'type'        => 'text',
				),
				'password' => array(
					'title'       => 'Password',
					'type'        => 'password'
				)
			);
	 	}
 
		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
			if ( $this->description ) {
				// you can instructions for test mode, I mean test card numbers etc.
				if ( $this->testmode ) {
					$this->description .= '<br/> TEST MODE ENABLED. In test mode, you can use the card number <br/> 4111 1111 1111 1111.';
					$this->description  = trim( $this->description );
				}
				// display the description with <p> tags etc.
				echo wpautop( wp_kses_post( $this->description ) );
			}

			echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
				// Add this action hook if you want your custom payment gateway to support it
			do_action( 'woocommerce_credit_card_form_start', $this->id );


			echo '<div class="form-row form-row-wide"><label for="usio_ccNo">Card Number <span class="required">*</span></label>
				<input id="usio_ccNo" type="text" autocomplete="off" onkeyup="cardFormat(this)" onblur="validateForm()">
				<p class="alert_usio" id="alert_usio_ccNo">Invalid Card Number</p>
				</div>
				<div class="form-row form-row-first">
					<label for="usio_expdate">Expiry Date <span class="required">*</span></label>
					<input id="usio_expdate" type="text" autocomplete="off" placeholder="MM / YY" onkeyup="dateFormat(this)" onblur="validateForm()">
					<p class="alert_usio" id="alert_usio_expdate">Card Expired</p>
				</div>
				<div class="form-row form-row-last">
					<label for="usio_cvv">Card Code (CVC) <span class="required">*</span></label>
					<input id="usio_cvv" type="password" maxlength="4" autocomplete="off" placeholder="CVC" onkeyup="validateForm()">
					</div>
					<div class="clear"></div>
					<input id="card_token" name="card_token" type="hidden">';
					
			do_action( 'woocommerce_credit_card_form_end', $this->id );

			echo '<div class="clear"></div></fieldset>';
 
		}

      public function payment_scripts() {
 
        // we need JavaScript to process a token only on cart/checkout pages, right?
        if ( ! is_cart() && ! is_checkout() && ! isset( $_GET['pay_for_order'] ) ) {
          return;
        }
       
        // if our payment gateway is disabled, we do not have to enqueue JS too
        if ( 'no' === $this->enabled ) {
          return;
        }
       
        // no reason to enqueue JavaScript if API keys are not set
        if (  empty( $this->merchant_id ) ) {
          return;
        }
       
        // do not work with card detailes without SSL unless your website is in a test mode
        if ( ! $this->testmode && ! is_ssl() ) {
          return;
        }
       
        // let's suppose it is our payment processor JavaScript that allows to obtain a token
        // wp_enqueue_script( 'usio_js', 'https://checkout.securepds.com/checkout/checkout.svc/JSON/GenerateToken' );
        
        // load jQuery from cdn
        wp_enqueue_script( 'jquery_js', 'https://code.jquery.com/jquery-3.2.1.min.js' );
       
        // load creditcard.s validatior
        wp_enqueue_script( 'creditcard', plugins_url( 'creditcard.min.js', __FILE__ ),  );
        // and this is our custom JS in your plugin directory that works with token.js
        wp_register_script( 'usio', plugins_url( 'usio.js', __FILE__ ),[] , "1.0.1", true  );
       
        // in most payment processors you have to use PUBLIC KEY to obtain a token
        wp_localize_script( 'usio', 'usio_params', array(
          'api_key' => $this->api_key
        ) );
       
        wp_enqueue_script( 'usio' );

 
	 	}
 
		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {
			if( empty( $_POST[ 'card_token' ]) && empty( $_POST[ 'payment_id' ]) ) {
				wc_add_notice(  'We need a token! Did you capture your Card Info?', 'error' );
				return false;
			}
			return true;
		}
 
		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {
			global $woocommerce;

			// Check if decryptation method isn't enabled
			if( !class_exists( 'System_Data_Encryptation' ) ) {
				wc_add_notice( "Credit card payments are disabled at this moment.", 'error' );
				return array(
					'result'   => 'failed',
				);
			}

			$merchant_id = $this->testmode ? $this->merchant_id : System_Data_Encryptation::decrypt_encoded( $this->merchant_id );
			$api_username = $this->testmode ? $this->username : System_Data_Encryptation::decrypt_encoded( $this->username );
			$api_password = $this->testmode ? $this->password : System_Data_Encryptation::decrypt_encoded( $this->password );

			if( !$merchant_id || !$api_username || !$api_password ) {
				wc_add_notice( "Credit card payments are disabled at this moment due to a bad configuration.", 'error' );
				return array(
					'result'   => 'failed',
				);
			}
			
			// we need it to get any order detailes
			$order = wc_get_order( $order_id );
			$usio = new WC_Horizon_USIO_Payment_SDK( $merchant_id, $api_username, $api_password, $this->api_key, $this->allow_logs );
			$result = new WP_Error("usio_gateway", "Error Processing Payment");
			
			do_action('woocommerce_usio_before_process_payment', $order, $_POST["card_token"], $_POST["payment_id"]);

			if( !$this->test_error ) {
				if( ! empty( $_POST[ 'card_token' ] ) ) {
					// Singles payment
					$result = $usio->make_single_charge( $_POST[ 'card_token' ], $order->get_total(), array(
						"FirstName" => $order->billing_first_name,
						"LastName" => $order->billing_last_name,
						"Address1" => $order->billing_address_1,
						"Address2" => $order->billing_address_1,
						"City" => $order->billing_city,
						"State" => $order->billing_state,
						"Zip" => $order->billing_postcode,	
					) );
	
				} else if( ! empty( $_POST[ 'payment_id' ] ) && function_exists('wc_horizon_get_credit_card_token') ) {
					// Payment with a saved credit card
					$token = wc_horizon_get_credit_card_token( 
						$_POST[ 'payment_id' ], 
						key_exists( "payment_user_id", $_POST  ) && defined( 'WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS' ) && WC_INTERNAL_CHECKOUT_PAYMENT_PROCCESS === "yes" ? 
						$_POST["payment_user_id"] : 0 
					);
					if( !is_wp_error( $token ) ) {
						$result = $usio->make_charge_by_confirmation( $token, $order->get_total() );
					} else {
						$result = $token;
					}
				}
			} else {
				$result = new WP_Error('testmode-error',  esc_html($this->test_error_message) );
			}

			if( is_wp_error( $result ) ) {
				do_action('woocommerce_usio_payment_declined', $order_id, $result, $_POST["card_token"], $_POST["payment_id"]);
				
				$result = apply_filters('woocommerce_usio_payment_error', $result);

				do_action( 'woocommerce_usio_before_return_failed_order', $result );
				
				wc_add_notice( $result->get_error_message(), 'error' );

				return array(
					'result'   => 'failed',
					'redirect' => isset($_POST["error_redirect"]) ? '/checkout?reason=' . rawurlencode($result->get_error_message()) : null,
				);
			}

			do_action('woocommerce_usio_after_process_payment', $order, $_POST["card_token"], $_POST["payment_id"], $result);

			$order->update_meta_data( 'confirmation_id', $result);
			$order->set_transaction_id( $result );
			
			// we received the payment
			$order->payment_complete();
			
			// reduce_order_stock is deprecated
			//$order->reduce_order_stock();
			wc_reduce_stock_levels( $order->get_id() );

			// Empty cart
			$woocommerce->cart->empty_cart();

			// Redirect to the thank you page
			return array(
				'result' => 'success',
				'redirect' => $this->get_return_url( $order )
			);
	 	}
 
		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {
  
	 	}
 	}
}
