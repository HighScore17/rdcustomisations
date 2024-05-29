<?php
/*eslint no-unused-vars: ["error", { "varsIgnorePattern": "[^...]" }]*/

/**
 * The file that defines the Plugin_Upgrade_Command Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 */

use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The Uninstall_Plugin_Command Class.
 *
 * This command is called when uninstalling the plugin and handled erasing all plugin-specific data.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     Joshua Bartlett <jbartlett@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Plugin_Upgrade_Command implements Executable {

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The expected db version
	 *
	 * @var string
	 */
	private $db_version;

	// phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter
	/**
	 * Executes the command.
	 *
	 * Checks for any upgrades that need to happen when the plugin is updated
	 *
	 * @param     mixed ...$args     An array of arguments that may be passed in from the action/filter called.
	 *
	 * @since 1.0.0
	 */
	public function execute( ...$args ) {
		$this->logger      = new Logger();
		$installed_version = get_option( 'activecampaign_for_woocommerce_db_version' );

		$this->logger->debug(
			'Plugin Upgrade Check...',
			[
				'Your DB Version'   => $installed_version,
				'Plugin DB Version' => $this->get_plugin_db_version(),
			]
		);

		if ( ! $installed_version ) {
			$this->logger->notice( 'Plugin Upgrade Command: We need to add the ActiveCampaign table.' );
			$this->install_table();
		} elseif ( $installed_version !== $this->get_plugin_db_version() ) {
			$this->logger->notice( 'Plugin Upgrade Command: It looks like your installed version needs a db upgrade.' );
			$this->upgrade_table();
		} elseif ( $installed_version === $this->get_plugin_db_version() ) {
			$this->logger->debug( 'Plugin Upgrade Command: Plugin db is up to date.' );
		} else {
			$this->logger->notice( 'Plugin Upgrade Command: Plugin is unsure what to do with the upgrade.' );
		}
	}
	// phpcs:enable

	/**
	 * Validates the table is installed.
	 */
	public function verify_table() {
		$this->logger = new Logger();
		$table_exists = null;
		global $wpdb;
		try {
			$table_name = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME;

			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
				$table_exists = true;
			} else {
				$this->logger->info( 'Plugin Upgrade Command: Could not find the activecampaign_for_woocommerce_abandoned_cart table so try to create it...' );
				$table_exists = false;
				$this->install_table();

				if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name ) {
					$table_exists = true;
				} else {
					$this->logger->error( 'Plugin Upgrade Command: There was an exception in creating the table...' );
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Plugin Upgrade Command: There was an exception in table verification...',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
			$table_exists = false;
		}

		return $table_exists;
	}

	/**
	 * Adds our table to the WordPress install.
	 */
	private function install_table() {
		$this->logger->info( 'Plugin Upgrade Command: Install the activecampaign_for_woocommerce_abandoned_cart table...' );
		global $wpdb;
		try {
			$table_name      = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME;
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $table_name (
			`id` INT NOT NULL AUTO_INCREMENT,
			`synced_to_ac` TINYINT NOT NULL DEFAULT 0,
			`customer_id` VARCHAR(45) NULL,
			`customer_email` VARCHAR(255),
			`customer_first_name` VARCHAR(255),
			`customer_last_name` VARCHAR(255),
			`last_access_time` DATETIME,
			`user_ref_json` MEDIUMTEXT,
			`customer_ref_json` LONGTEXT,
			`cart_ref_json` LONGTEXT,
			`cart_totals_ref_json` MEDIUMTEXT,
			`removed_cart_contents_ref_json` LONGTEXT,
			`activecampaignfwc_order_external_uuid` VARCHAR(255),
			PRIMARY KEY (`id`),
			INDEX `synced_to_ac_last_access_time` (`last_access_time` ASC, `synced_to_ac` ASC),
			UNIQUE INDEX `customer_id_UNIQUE` (`customer_id` ASC)) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			// Add the db version
			add_option( 'activecampaign_for_woocommerce_db_version', $this->get_plugin_db_version() );
			$this->logger->info( 'Plugin Upgrade Command: Table installation finished!' );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Plugin Upgrade install table: There was an exception creating the abandoned cart table.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Upgrades the table with new changes.
	 */
	private function upgrade_table() {
		$this->logger->debug( 'Plugin Upgrade Command: There is no upgrade currently...' );
		// update_option( "activecampaign_for_woocommerce_db_version", $this->get_plugin_db_version() );
	}

	/**
	 * Gets the current db version of our plugin.
	 *
	 * @return string The expected db version number.
	 */
	private function get_plugin_db_version() {
		if ( ! isset( $this->db_version ) ) {
			$this->db_version = ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_VERSION;
		}

		return $this->db_version;
	}
}
