<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.?
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */

$activecampaign_for_woocommerce_options    = $this->get_options();
$activecampaign_for_woocommerce_configured = false;
if ( isset( $activecampaign_for_woocommerce_options['api_url'], $activecampaign_for_woocommerce_options['api_key'] ) ) {
	$activecampaign_for_woocommerce_configured = true;
}

$activecampaign_for_woocommerce_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );
$activecampaign_for_woocommerce_settings = stripslashes_deep( $activecampaign_for_woocommerce_settings );

$activecampaign_for_woocommerce_api_url = '';
if ( isset( $activecampaign_for_woocommerce_settings['api_url'] ) ) {
	$activecampaign_for_woocommerce_api_url = $activecampaign_for_woocommerce_settings['api_url'];
}

$activecampaign_for_woocommerce_api_url = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_api_url ) );

$activecampaign_for_woocommerce_api_key = '';
if ( isset( $activecampaign_for_woocommerce_settings['api_key'] ) ) {
	$activecampaign_for_woocommerce_api_key = $activecampaign_for_woocommerce_settings['api_key'];
}
$activecampaign_for_woocommerce_api_key = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_api_key ) );

$activecampaign_for_woocommerce_debug = '0';
if ( isset( $activecampaign_for_woocommerce_settings['ac_debug'] ) ) {
	$activecampaign_for_woocommerce_debug = $activecampaign_for_woocommerce_settings['ac_debug'];
}
$activecampaign_for_woocommerce_debug = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_debug ) );

$activecampaign_for_woocommerce_abcart_wait = '1';
if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
	$activecampaign_for_woocommerce_abcart_wait = $activecampaign_for_woocommerce_settings['abcart_wait'];
}
$activecampaign_for_woocommerce_abcart_wait = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_abcart_wait ) );

$activecampaign_for_woocommerce_optin_checkbox_text = esc_html__( 'Keep me up to date on news and exclusive offers', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
if ( isset( $activecampaign_for_woocommerce_settings['optin_checkbox_text'] ) ) {
	$activecampaign_for_woocommerce_optin_checkbox_text = $activecampaign_for_woocommerce_settings['optin_checkbox_text'];
}
$activecampaign_for_woocommerce_optin_checkbox_text = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_optin_checkbox_text ) );

$activecampaign_for_woocommerce_optin_checkbox_display_option = 'visible_checked_by_default';
if ( isset( $activecampaign_for_woocommerce_settings['checkbox_display_option'] ) ) {
	$activecampaign_for_woocommerce_optin_checkbox_display_option = $activecampaign_for_woocommerce_settings['checkbox_display_option'];
}
$activecampaign_for_woocommerce_optin_checkbox_display_option = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_optin_checkbox_display_option ) );

$activecampaign_for_woocommerce_custom_email_field = esc_html__( 'billing_email', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
if ( isset( $activecampaign_for_woocommerce_settings['custom_email_field'] ) ) {
	$activecampaign_for_woocommerce_custom_email_field = $activecampaign_for_woocommerce_settings['custom_email_field'];
}
$activecampaign_for_woocommerce_custom_email_field = esc_html( sanitize_text_field( $activecampaign_for_woocommerce_custom_email_field ) );

$activecampaign_for_woocommerce_ab_cart_options = [
	'1'  => esc_html__( '1 hour (recommended)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'6'  => esc_html__( '6 hours', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'10' => esc_html__( '10 hours', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'24' => esc_html__( '24 hours', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
];

$activecampaign_for_woocommerce_ac_debug_options = [
	// value  // label
	'1' => esc_html__( 'On', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
	'0' => esc_html__( 'Off', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
];

$activecampaign_for_woocommerce_checkbox_display_options = [
	// value                          // label
	'visible_checked_by_default'   => esc_html__(
		'Visible, checked by default',
		ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
	),
	'visible_unchecked_by_default' => esc_html__(
		'Visible, unchecked by default',
		ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
	),
	'not_visible'                  => esc_html__(
		'Not visible',
		ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
	),
];

?>
<?php settings_errors(); ?>
<div id="activecampaign-for-woocommerce-app">
	<?php
	require plugin_dir_path( __FILE__ ) . '../partials/activecampaign-for-woocommerce-header.php';
	?>
	<?php if ( ! $activecampaign_for_woocommerce_configured ) : ?>
		<section class="no-connection">
			<h2><?php esc_html_e( 'After a few easy steps, you can automate your entire customer lifecycle.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			<p><?php esc_html_e( 'You need to log in to ActiveCampaign and connect the WooCommerce integration within settings to complete your setup.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
			<div class="no-connection-content">
				<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="128" height="128" fill="none"
					 xmlns:v="https://vecta.io/nano">
					<style>
						<![CDATA[.B {fill-rule: evenodd}.C {stroke: #356ae6}  .D{stroke-width: 3}.E {fill: #c1d1f7}]]>
					</style>
					<g fill="#fff" stroke-width="2" class="C">
						<rect x="17" y="10" width="94" height="117" rx="3"/>
						<rect x="23" y="14" width="82" height="107" rx="3"/>
					</g>
					<mask id="A" fill="#fff">
						<path d="M71.826 5H93a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H35a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h21.174c.677-2.867 3.252-5 6.326-5h3c3.074 0 5.649 2.133 6.326 5z"
							  class="B"/>
					</mask>
					<path d="M71.826 5H93a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H35a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h21.174c.677-2.867 3.252-5 6.326-5h3c3.074 0 5.649 2.133 6.326 5z"
						  fill="#e3ebfc" class="B"/>
					<g fill="#356ae6">
						<path d="M71.826 5l-1.947.46.364 1.54h1.583V5zM56.174 5v2h1.583l.364-1.54L56.174 5zm15.652 2H93V3H71.826v4zM93 7h4a4 4 0 0 0-4-4v4zm0 0v11h4V7h-4zm0 11v4a4 4 0 0 0 4-4h-4zm0 0H35v4h58v-4zm-58 0h0-4a4 4 0 0 0 4 4v-4zm0 0V7h-4v11h4zm0-11h0V3a4 4 0 0 0-4 4h4zm0 0h21.174V3H35v4zm23.12-1.54C58.589 3.475 60.375 2 62.5 2v-4c-4.022 0-7.387 2.791-8.272 6.54l3.893.92zM62.5 2h3v-4h-3v4zm3 0c2.125 0 3.911 1.475 4.38 3.46l3.893-.92C72.887.791 69.522-2 65.5-2v4z"
							  mask="url(#A)"/>
						<circle cx="64" cy="7" r="2"/>
					</g>
					<path d="M34.333 54l4.407 4.333 10.593-10.667" class="C D"/>
					<path d="M58 50h30v2H58v-2zm0 5h17v2H58v-2zm36 0H77v2h17v-2z" class="B E"/>
					<path d="M34.333 78l4.407 4.333 10.593-10.667" class="C D"/>
					<path d="M72 74H58v2h14v-2zm22 0H74v2h20v-2zm-36 5h22v2H58v-2zm33 0h-9v2h9v-2z" class="B E"/>
					<path d="M34.333 102l4.407 4.333 10.593-10.666" class="C D"/>
					<path d="M58 98h20v2H58v-2zm22 0h5v2h-5v-2zm9 5H58v2h31v-2zm-8-74H47v3h34v-3zm-7 6H54v2h20v-2z"
						  class="B E"/>
				</svg>
				<div>
					<ul class="circle-numbered-checklist">
						<li>
							<span>1</span><?php esc_html_e( 'Connect the WooCommerce integration in ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</li>
						<li>
							<span>2</span><?php esc_html_e( 'Activate your abandoned cart', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</li>
						<li>
							<span>3</span><?php esc_html_e( 'Activate a cross-sell automation', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</li>
					</ul>
					<a href="https://www.activecampaign.com/apps/woocommerce" target="_blank" rel="noopener noreferrer"
					   class="activecampaign-for-woocommerce button"><span><?php esc_html_e( 'Complete setup in ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></span>
						<svg class="is-styled css-ws9hmn" height="16px" width="16px" role="img" viewBox="0 0 16 16"
							 xmlns="http://www.w3.org/2000/svg">
							<path clip-rule="evenodd"
								  d="M5 0H0V16H16V11H14V14H2V2H5V0ZM8.99995 2H12.5857L6.29285 8.29289L7.70706 9.70711L14 3.41421V7H16V0H8.99995V2Z"
								  fill-rule="evenodd"></path>
						</svg>
					</a>
					<div>
					<div id="activecampaign-manual-mode-container">
						or, <span id="activecampaign-manual-mode">manually configure the API</span>
					</div>
					</div>
				</div>
			</div>
			<section id="manualsetup" style="display:none">
				<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
					  id="activecampaign-for-woocommerce-options-form">
					<input type="hidden" name="action" value="activecampaign_for_woocommerce_settings">
					<?php
					wp_nonce_field( 'activecampaign_for_woocommerce_settings_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
					?>
					<h2>
						<?php esc_html_e( 'API Credentials', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</h2>
					<p>
						<?php esc_html_e( 'To find your ActiveCampaign API URL and API Key, log into your account and navigate to Settings &gt; Developer &gt; API Access.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</p>
					<div>
						<label for="api_url">
							<?php esc_html_e( 'API URL:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<input type="text" name="api_url" id="api_url"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_url ); ?>">
					</div>
					<div>
						<label for="api_key"><?php esc_html_e( 'API key:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></label>
						<input type="text" name="api_key" id="api_key"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_key ); ?>">
					</div>
					<input type="hidden" name="optin_checkbox_text" value="<?php echo esc_html( $activecampaign_for_woocommerce_optin_checkbox_text ); ?>">
					<input type="hidden" name="checkbox_display_option" value="<?php echo esc_html( key( $activecampaign_for_woocommerce_checkbox_display_options ) ); ?>">
					<input type="hidden" name="abcart_wait" value="<?php echo esc_html( key( $activecampaign_for_woocommerce_ab_cart_options ) ); ?>">
					<input type="hidden" id="ac_debug" name="ac_debug" value="0">
					<input type="hidden" name="custom_email_field" id="custom_email_field" value="billing_email">
					<section class="mt-0">
						<hr/>
						<button class="activecampaign-for-woocommerce button button-primary">
							<?php esc_html_e( 'Update settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</button>
					</section>
				</form>
			</section>
			<p>
				<?php
				printf(
				/* translators: link in text */
					esc_html__( '%1$s %2$s to learn more about how ecommerce stores are earning revenue with ActiveCampaign.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
					esc_html__( 'Visit our', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
					sprintf(
						'<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
						esc_url( 'https://www.activecampaign.com/learn' ),
						esc_html__( 'education center', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN )
					)
				);
				?>
			</p>
		</section>
	<?php else : ?>
		<form method="POST" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			  id="activecampaign-for-woocommerce-options-form">
			<input type="hidden" name="action" value="activecampaign_for_woocommerce_settings">
			<?php
			wp_nonce_field( 'activecampaign_for_woocommerce_settings_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
			?>
			<section>
				<div id="activecampaign_store">
					<h2>
						<?php esc_html_e( 'Abandoned Cart', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</h2>
					<p>
						<?php esc_html_e( 'How long should ActiveCampaign wait after a contact abandons a cart before triggering abandoned cart automations?', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</p>
					<label>
						<?php esc_html_e( 'Select wait time:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</label>
					<?php foreach ( $activecampaign_for_woocommerce_ab_cart_options as $activecampaign_for_woocommerce_ab_cart_options_value => $activecampaign_for_woocommerce_ab_cart_options_option ) : ?>
						<label class="radio">
							<input type="radio"
								   id="abcart_wait<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart_options_value ); ?>"
								   name="abcart_wait"
								   value="<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart_options_value ); ?>"
								<?php
								if ( (string) $activecampaign_for_woocommerce_ab_cart_options_value === $activecampaign_for_woocommerce_abcart_wait ) {
									echo 'checked';
								}
								?>
							>
							<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart_options_option ); ?>
						</label>
					<?php endforeach; ?>
					<hr/>
					<h2>
						<?php esc_html_e( 'Opt-in Checkbox', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</h2>
					<p>
						<?php esc_html_e( 'Configure what text should appear next to the opt-in checkbox, and whether that checkbox should be visible and checked by default.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</p>
					<div>
						<label for="optin_checkbox_text">
							<?php esc_html_e( 'Checkbox text:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<input type="text" name="optin_checkbox_text" id="optin_checkbox_text"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_optin_checkbox_text ); ?>">
					</div>
					<h3>
						<?php esc_html_e( 'Checkbox display options:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</h3>
					<?php foreach ( $activecampaign_for_woocommerce_checkbox_display_options as $activecampaign_for_woocommerce_checkbox_display_options_value => $activecampaign_for_woocommerce_checkbox_display_options_option ) : ?>
						<label class="radio"
							   for="checkbox_display_option_<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_value ); ?>">
							<input type="radio"
								   id="checkbox_display_option_<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_value ); ?>"
								   name="checkbox_display_option"
								   value="<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_value ); ?>"
								<?php
								if ( $activecampaign_for_woocommerce_checkbox_display_options_value === $activecampaign_for_woocommerce_optin_checkbox_display_option ) {
									echo esc_html( 'checked' );
								}
								?>
							>
							<?php echo esc_html( $activecampaign_for_woocommerce_checkbox_display_options_option ); ?>
						</label>
					<?php endforeach; ?>
				</div>
			</section>
			<section id="activecampaign_connection" class="advanced"
					 label="<?php esc_html_e( 'Connection', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>">
				<hr/>
				<input type="checkbox" id="advanced" class="hidden-accordion"/>
				<label for="advanced"
					   class="accordion-title"><?php esc_html_e( 'Advanced Settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></label>
				<div class="accordion-content">
					<h2>
						<?php esc_html_e( 'API Credentials', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</h2>
					<p>
						<?php esc_html_e( 'To find your ActiveCampaign API URL and API Key, log into your account and navigate to Settings &gt; Developer &gt; API Access.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</p>
					<div>
						<label for="api_url">
							<?php esc_html_e( 'API URL:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<input type="text" name="api_url" id="api_url"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_url ); ?>">
					</div>
					<div>
						<label for="api_key"><?php esc_html_e( 'API key:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></label>
						<input type="text" name="api_key" id="api_key"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_api_key ); ?>">
					</div>
					<a href="#" id="activecampaign-update-api-button"
					   data-value="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>"
					   class="activecampaign-for-woocommerce button secondary"><span><?php esc_html_e( 'Test connection', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></span></a>
					<hr/>
					<div>
						<label>
							<?php esc_html_e( 'Activate debugging:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</label>
						<label class="radio">
							<input type="radio" id="ac_debug0" name="ac_debug" value="0"
								<?php
								if ( '0' === $activecampaign_for_woocommerce_debug ) {
									echo 'checked';
								}
								?>
							> Off
						</label>
						<label class="radio">
							<input type="radio" id="ac_debug1" name="ac_debug" value="1"
								<?php
								if ( '1' === $activecampaign_for_woocommerce_debug ) {
									echo 'checked';
								}
								?>
							> On
						</label>
					</div>
					<div>
						<label for="custom_email_field"><?php esc_html_e( 'Custom email field:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></label>
						<input type="text" name="custom_email_field" id="custom_email_field"
							   value="<?php echo esc_html( $activecampaign_for_woocommerce_custom_email_field ); ?>"
							   placeholder="billing_email">
						<p><?php esc_html_e( 'Default: billing_email (expects ID as input, do not include #)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
						<p><?php esc_html_e( 'Warning: Advanced users only. Do not set this unless you are having issues with the abandoned cart not triggering on your email field.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
						<p><?php esc_html_e( 'If you have a forced registration or a custom theme for checkout you can change which field we bind on here.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></p>
					</div>
					<div class="col-1">
						<div class="card">
							<h2>
								<?php esc_html_e( 'Reset Plugin Configuration', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</h2>
							<p>
								<?php esc_html_e( 'If you would like to clear all configurations stored for the ActiveCampaign for WooCommerce but retain data you can use this reset method. Please reach out to support before trying this option.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</p>
							<p>
								<i>Resets the plugin without erasing abandoned carts, logs, or tables.</i>
							</p>
							<div class="columnbox">
								<div id="activecampaign-run-clear-plugin-settings" class="button">
									Clear All Settings
								</div>
								<div id="activecampaign-run-clear-plugin-settings-status"></div>
							</div>
						</div>
						<div class="card">
							<h2>
								<?php esc_html_e( 'Repair Connection ID', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</h2>
							<p>
								<?php esc_html_e( 'This button should only be used if you are facing issues with orders not properly sending to ActiveCampaign. Please reach out to support before trying this option.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</p>
							<div class="columnbox">
								<div id="activecampaign-run-fix-connection" class="button">
									Repair Connection IDs
								</div>
								<div id="activecampaign-run-fix-connection-status"></div>
							</div>
						</div>
					</div>
				</div>
			</section>
			<section class="mt-0">
				<hr/>
				<button class="activecampaign-for-woocommerce button button-primary">
					<?php esc_html_e( 'Update settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				</button>
			</section>
		</form>
	<?php endif; ?>
</div>
