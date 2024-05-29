<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Plugin_Upgrade_Command as Plugin_Upgrade_Command;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Activator {
	/**
	 * Used for querying the DB.
	 *
	 * @var Activecampaign_For_Woocommerce_Admin The admin instance.
	 */
	private $admin;

	/**
	 * Activecampaign_For_Woocommerce_Activator constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Admin $admin     The admin instance.
	 */
	public function __construct( Activecampaign_For_Woocommerce_Admin $admin ) {
		$this->admin = $admin;
	}

	/**
	 * Sets default values for settings in the DB.
	 *
	 * @since    1.0.0
	 */
	public function activate() {
		$this->logger = new Logger();
		$this->logger->info( 'Activation running...' );
		$current_options = $this->admin->get_options();

		$options_to_be_saved = [];

		if ( ! isset( $current_options['abcart_wait'] ) ) {
			$options_to_be_saved['abcart_wait'] = '1';
		}

		if ( ! isset( $current_options['optin_checkbox_text'] ) ) {
			$options_to_be_saved['optin_checkbox_text'] = 'Keep me up to date on news and exclusive offers';
		}

		if ( ! isset( $current_options['checkbox_display_option'] ) ) {
			$options_to_be_saved['checkbox_display_option'] = 'visible_checked_by_default';
		}

		if ( ! isset( $current_options['ac_debug'] ) ) {
			$options_to_be_saved['ac_debug'] = '0';
		}

		$this->admin->update_storage( $options_to_be_saved );

		// Perform the table steps
		$upgrade = new Plugin_Upgrade_Command();
		$upgrade->execute();

		$this->logger->info( 'Finished ActiveCampaign for WooCommerce plugin activation.' );
	}
}
