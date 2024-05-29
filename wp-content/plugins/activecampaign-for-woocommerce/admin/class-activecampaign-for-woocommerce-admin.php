<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 */

use Activecampaign_For_Woocommerce_Admin_Settings_Updated_Event as Admin_Settings_Updated;
use Activecampaign_For_Woocommerce_Admin_Settings_Validator as Validator;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * The response array that will be returned.
	 *
	 * @var array The array.
	 */
	private $response = [];

	/**
	 * The class that handles validating options changes.
	 *
	 * @var Validator The validator class.
	 */
	private $validator;

	/**
	 * The event class to be triggered after a successful options update.
	 *
	 * @var Activecampaign_For_Woocommerce_Admin_Settings_Updated_Event The event class.
	 */
	private $event;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param     string                 $plugin_name     The name of this plugin.
	 * @param     string                 $version     The version of this plugin.
	 * @param     Validator              $validator     The validator for the admin options.
	 * @param     Admin_Settings_Updated $event     The admin settings updated event class.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version, Validator $validator, Admin_Settings_Updated $event ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->validator   = $validator;
		$this->event       = $event;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/activecampaign-for-woocommerce-admin.css',
			array(),
			$this->version,
			'all'
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_register_script(
			$this->plugin_name . 'settings-page',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-settings-page.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'status-page',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-status-page.js',
			array( 'jquery' ),
			$this->version,
			true
		);

		wp_register_script(
			$this->plugin_name . 'abandoned-cart',
			plugin_dir_url( __FILE__ ) . 'scripts/activecampaign-for-woocommerce-abandoned-cart.js',
			array( 'jquery' ),
			$this->version,
			true
		);

	}

	/**
	 * Register the page for the admin section, adds to the WooCommerce menu parent
	 *
	 * @since    1.0.0
	 */
	public function add_admin_page() {
		$ac_icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMjBweCIgaGVpZ2h0PSIyMHB4IiB2aWV3Qm94PSIwIDAgMjAgMjAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8dGl0bGU+R3JvdXA8L3RpdGxlPgogICAgPGcgaWQ9IlBhZ2UtMSIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPGcgaWQ9Ikdyb3VwIj4KICAgICAgICAgICAgPHJlY3QgaWQ9IlJlY3RhbmdsZSIgb3BhY2l0eT0iMCIgeD0iMCIgeT0iMCIgd2lkdGg9IjIwIiBoZWlnaHQ9IjIwIj48L3JlY3Q+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik00LjIxNzMxNjUsMS4xMDg2NTE1IEM0LjY4ODE0LDEuMzk4MzkgMTUuMDEwMDUsOC42MDU2NSAxNS4yNjM2LDguODIyOTUgQzE1LjY5ODIsOS4xMTI3IDE1LjkxNTUsOS40NzQ4NSAxNS45MTU1LDkuODM3IEwxNS45MTU1LDEwLjA1NDM1IEMxNS45MTU1LDEwLjM0NDA1IDE1LjgwNjg1LDEwLjgxNDkgMTUuMjk5OCwxMS4xNzcwNSBDMTUuMDgyNSwxMS4zNTgxNSA0LjEwODY2OSwxOSA0LjEwODY2OSwxOSBMNC4xMDg2NjksMTcuMjk3OCBDNC4xMDg2NjksMTYuNzkwNzUgNC4xNDQ4Njg1LDE2LjUzNzIgNC43MjQzNDUsMTYuMTc1MDUgQzUuMTk1MTcsMTUuODg1MyAxMi42NTU5NSwxMC43MDYyNSAxMy42MzM4LDEwLjAxODEgQzEzLjE2Nzk1LDkuNjkwMyAxMS4yNTUzNSw4LjM1OTYgOS4zMjU0LDcuMDE2ODUgQzcuMjA0MzIsNS41NDExMiA1LjA2MjI3LDQuMDUwOCA0Ljc5Njc4NSwzLjg2MTE3IEw0LjcyNDM0NSwzLjgyNDk1IEM0LjY5Njg2NSwzLjgwMjk2NSA0LjY2OTgwNSwzLjc4MTYxIDQuNjQzMjU1LDMuNzYwNjU1IEM0LjMxOTg3NCwzLjUwNTQ0IDQuMDcyNDM4NSwzLjMxMDE2IDQuMDcyNDM4NSwyLjc3NDY1IEw0LjA3MjQzODUsMSBMNC4yMTczMTY1LDEuMTA4NjUxNSBaIE05LjY4NjEsMTAuNDg4OSBDOS4zOTY0LDEwLjcwNjI1IDkuMTA2NjUsMTAuODE0OSA4LjgxNjkyLDEwLjgxNDkgQzguNTYzNCwxMC44MTQ5IDguMzA5ODc1LDEwLjc0MjQ1IDguMDIwMTM1LDEwLjU2MTM1IEM3LjM2ODIyNSwxMC4xMjY3NSA0LjA3MjQ0OCw3Ljg0NTA1IDQuMDM2MjMwNTUsNy44MDg4NSBMNCw3Ljc3MjY1IEw0LDYuNjQ5OSBDNCw2LjM2MDE1IDQuMTQ0ODc4LDYuMTc5MDUgNC4zMjU5NjQ1LDYuMDcwNCBDNC41MDcwNSw1Ljk2MTc2IDQuNzk2OCw1Ljk5Nzk4IDUuMDE0MSw2LjE3OTA1IEM1LjUyMTE0NSw2LjUwNSAxMC4zMzgwNSw5LjgzNyAxMC4zNzQyNSw5Ljg3MzI1IEwxMC40ODI5LDkuOTQ1NjUgTDEwLjM3NDI1LDEwLjAxODEgQzEwLjM3NDI1LDEwLjAxODEgMTAuMDQ4MywxMC4yMzU0IDkuNjg2MSwxMC40ODg5IFoiIGlkPSJTaGFwZSIgZmlsbD0iIzAwNENGRiI+PC9wYXRoPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+';

		add_menu_page(
			'ActiveCampaign for WooCommerce',
			'ActiveCampaign',
			'manage_options',
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
			array( $this, 'fetch_admin_page' ),
			$ac_icon,
			'55.8'
		);

		add_submenu_page(
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
			'ActiveCampaign for WooCommerce Settings',
			'WooCommerce Settings',
			'manage_options',
			ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
			array( $this, 'fetch_admin_page' )
		);

		$activecampaign_for_woocommerce_options = $this->get_options();
		if ( isset( $activecampaign_for_woocommerce_options['api_url'], $activecampaign_for_woocommerce_options['api_key'] ) ) {
			add_submenu_page(
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
				'ActiveCampaign for WooCommerce Abandoned Carts',
				'Abandoned Carts',
				'manage_options',
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_abandoned_carts',
				array( $this, 'fetch_abandoned_cart_page' )
			);

			add_submenu_page(
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE,
				'ActiveCampaign for WooCommerce Status',
				'Status',
				'manage_options',
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE . '_status',
				array( $this, 'fetch_status_page' )
			);
		}
	}

	/**
	 * This function adds to our plugin listing on the plugin page a link to our settings page.
	 *
	 * @param     array $links     The existing links being passed in.
	 *
	 * @return array
	 */
	public function add_plugin_settings_link( $links ) {
		$html_raw = '<a href="%s" aria-label="%s">%s</a>';

		$html = sprintf(
			$html_raw,
			admin_url( 'admin.php?page=' . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_SNAKE ),
			esc_attr__(
				'View ActiveCampaign settings',
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
			),
			esc_html__( 'Settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN )
		);

		$action_links = [
			$html,
		];

		return array_merge( $action_links, $links );
	}

	/**
	 * Fetch the PHP template file that is used for the admin page
	 *
	 * @since    1.0.0
	 */
	public function fetch_admin_page() {
		wp_enqueue_script( $this->plugin_name . 'settings-page' );
		require_once plugin_dir_path( __FILE__ )
					 . 'views/activecampaign-for-woocommerce-admin-display.php';
	}

	/**
	 * Fetch the PHP template file that is used for the admin page
	 *
	 * @param     array $array array for hook.
	 *
	 * @since    1.?
	 */
	public function please_configure_plugin_notice( $array ) {
		global $pagenow;
		global $plugin_page;

		// Verify we're on an admin section
		if (
			'activecampaign_for_woocommerce' !== $plugin_page &&
			current_user_can( 'administrator' ) &&
			(
				'admin.php' === $pagenow
				|| 'plugins.php' === $pagenow
				|| get_current_screen()->in_admin()
			)
		) {
			require_once plugin_dir_path( __FILE__ ) . 'views/activecampaign-for-woocommerce-please-configure-plugin-notice.php';
		}
	}

	/**
	 * Populates an admin notice dismiss in db.
	 */
	public function update_dismiss_plugin_notice_option() {
		$setting                          = json_decode( get_option( 'activecampaign_for_woocommerce_dismiss_plugin_notice' ), 'array' );
		$setting[ get_current_user_id() ] = 1;
		update_option( 'activecampaign_for_woocommerce_dismiss_plugin_notice', wp_json_encode( $setting ) );
	}

	/**
	 * Populates an admin notice.
	 */
	public function error_admin_notice() {
		global $pagenow;

		// Verify we're on an admin section
		if ( 'admin.php' === $pagenow ) {
			global $wpdb;

			$err_count = $wpdb->get_var(
				'SELECT COUNT(log_id) 
							FROM ' . $wpdb->prefix . 'woocommerce_log
							WHERE source = "activecampaign-for-woocommerce"
							AND level = "500"
						'
			);

			if ( $err_count ) {
				if ( function_exists( 'wc_admin_url' ) ) {
					$admin_log_url = wc_admin_url(
						'status',
						array(
							'page' => 'wc-status',
							'tab'  => 'logs',
						)
					);
				} else {
					$admin_log_url = admin_url( 'admin.php?page=wc-status&tab=logs' );
				}

				echo '<div id="activecampaign-for-woocommerce-notice-error" class="notice notice-error is-dismissible activecampaign-for-woocommerce-error"><p>' .
					esc_html(
						'The ActiveCampaign for WooCommerce plugin has recorded ' . $err_count . ' ' .
							translate_nooped_plural(
								[
									'singular' => 'error',
									'plural'   => 'errors',
									'domain'   => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN,
									'context'  => null,
								],
								$err_count
							) .
							   '.'
					) .
					 '<br/><a href="' . esc_url( $admin_log_url ) . '">' . esc_html( 'Please check the ActiveCampaign logs for issues.' ) .
					 '</a></p></div>
						<script type="text/javascript">
						jQuery(document).ready(function($) {
						    $("#activecampaign-for-woocommerce-notice-error").click(function(){
								jQuery.ajax({
						            url: ajaxurl,
							        data: {
												action: "activecampaign_for_woocommerce_dismiss_error_notice"
							        }
						        });
							});
						});
					</script>';
			}
		}
	}

	/**
	 * Updates the dismiss error notice option in the database
	 */
	public function update_dismiss_error_notice_option() {
		$setting                          = json_decode( get_option( 'activecampaign_for_woocommerce_dismiss_error_notice' ), 'array' );
		$setting[ get_current_user_id() ] = 1;
		update_option( 'activecampaign_for_woocommerce_dismiss_error_notice', wp_json_encode( $setting ) );
	}

	/**
	 * Clears the error log history.
	 */
	public function clear_error_logs() {
		$logger = new Logger();
		$count  = $logger->clear_wc_error_log();
		delete_option( 'activecampaign_for_woocommerce_dismiss_error_notice' );
		wp_send_json_success(
			$count . ' ' .
			translate_nooped_plural(
				[
					'singular' => 'record',
					'plural'   => 'records',
					'domain'   => ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN,
					'context'  => null,
				],
				$count
			) . ' removed from the database.'
		);
	}

	/**
	 * Gets the most recent 10 error log entries saved
	 *
	 * @return array|object|null
	 */
	private function fetch_recent_log_errors() {
		global $wpdb;
		$results = $wpdb->get_results(
			'SELECT message, context 
							FROM ' . $wpdb->prefix . 'woocommerce_log
							WHERE source = "activecampaign-for-woocommerce"
							AND level = "500" 
							ORDER BY timestamp DESC
							LIMIT 10
						'
		);

		return $results;
	}

	/**
	 * Fetch the PHP template file that is used for the admin abandoned cart page.
	 *
	 * @since    1.3.7
	 */
	public function fetch_abandoned_cart_page() {
		wp_enqueue_script( $this->plugin_name . 'abandoned-cart' );
		require_once plugin_dir_path( __FILE__ )
					 . 'views/activecampaign-for-woocommerce-abandoned-cart-display.php';
	}

	/**
	 * Fetch the PHP template file that is used for the admin status page.
	 *
	 * @since    1.?
	 */
	public function fetch_status_page() {
		wp_enqueue_script( $this->plugin_name . 'status-page' );
		require_once plugin_dir_path( __FILE__ )
					 . 'views/activecampaign-for-woocommerce-status-display.php';
	}

	/**
	 * Gets the abandoned carts from our table.
	 *
	 * @param     int $page The page number.
	 *
	 * @return array|object|null
	 */
	public function get_abandoned_carts( $page = 0 ) {
		global $wpdb;
		$limit  = 40;
		$offset = $page * $limit;
		return $wpdb->get_results(
			// phpcs:disable
			$wpdb->prepare(
				'SELECT id, synced_to_ac, customer_id, customer_email, customer_first_name, customer_last_name, last_access_time
        		FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME . '` LIMIT %d,%d',
				[ $offset, $limit ]
			), OBJECT
			// phpcs:enable
		);
	}

	/**
	 * Get the abandoned carts total.
	 *
	 * @return string|null
	 */
	public function get_total_abandoned_carts() {
		global $wpdb;
		// phpcs:disable
		return $wpdb->get_var( 'SELECT COUNT(id) FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME . '`' );
		// phpcs:enable
	}

	/**
	 * Get the unsynced abandoned cart total.
	 *
	 * @return string|null
	 */
	public function get_total_abandoned_carts_unsynced() {
		global $wpdb;
		// phpcs:disable
		return $wpdb->get_var( 'SELECT COUNT(id) FROM `' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME . '` WHERE synced_to_ac = 0' );
		// phpcs:enable
	}

	/**
	 * Triggers abandoned cart sync action
	 */
	public function handle_abandon_cart_sync() {
		do_action( 'activecampaign_for_woocommerce_run_manual_abandonment_sync' );
	}

	/**
	 * Handles the abandoned cart delete function and triggers the manual delete
	 */
	public function handle_abandon_cart_delete() {
		if ( ! wp_verify_nonce( $_REQUEST['activecampaign_for_woocommerce_settings_nonce_field'], 'activecampaign_for_woocommerce_abandoned_form' ) ) {
			$this->logger->warning( 'Invalid nonce from the delete abandoned cart row call:', [ 'request' => $_REQUEST ] );
			wp_send_json_error( 'The nonce appears to be invalid.' );
			return false;
		}

		if ( isset( $_REQUEST['rowId'] ) ) {
			do_action( 'activecampaign_for_woocommerce_run_manual_abandonment_delete', $_REQUEST['rowId'] );
		} else {
			$this->logger->warning( 'Invalid request, rowId missing from the delete abandoned cart call:', [ 'request' => $_REQUEST ] );
			wp_send_json_error( 'No row ID defined.' );
		}
	}

	/**
	 * Handles the abandoned cart sync function and triggers the manual forced sync
	 */
	public function handle_abandon_cart_force_row_sync() {
		if ( ! wp_verify_nonce( $_REQUEST['activecampaign_for_woocommerce_settings_nonce_field'], 'activecampaign_for_woocommerce_abandoned_form' ) ) {
			$this->logger->warning( 'Invalid nonce from the force row sync call:', [ 'request' => $_REQUEST ] );
			wp_send_json_error( 'The nonce appears to be invalid.' );
			return false;
		}

		if ( isset( $_REQUEST['rowId'] ) ) {
			do_action( 'activecampaign_for_woocommerce_run_force_row_abandonment_sync', $_REQUEST['rowId'] );
		} else {
			$this->logger->warning( 'Invalid request, rowId missing from the force row sync call:', [ 'request' => $_REQUEST ] );
			wp_send_json_error( 'The request appears to be invalid. The rowId is missing from the request.' );
		}
	}

	/**
	 * Returns an encoded array of existing notices to be displayed on page-load.
	 *
	 * Once displayed, these notifications are then removed so they don't constantly build up in the
	 * UI.
	 *
	 * @return string
	 */
	public function get_admin_notices() {
		$storage = $this->get_storage();

		$notifications = isset( $storage['notifications'] ) ? $storage['notifications'] : [];

		$this->update_storage(
			[
				'notifications' => [],
			]
		);

		return wp_json_encode( $notifications );
	}

	/**
	 * Handles the API Test request from the settings page,
	 * then redirects back to the plugin page
	 */
	public function handle_api_test() {
		if ( ! $this->validate_request_nonce() ) {
			wp_send_json_error( $this->get_response(), 403 );
		}

		$new_data     = $this->extract_post_data();
		$current_data = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );

		$errors = $this->validator->validate( $new_data, $current_data, true );

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				$this->push_response_error(
					$this->format_response_message(
						$error,
						'error'
					)
				);
			}
		}

		if ( $this->response_has_errors() ) {
			wp_send_json_error( $this->get_response(), 422 );
		}

		$this->push_response_notice(
			$this->format_response_message( 'API tested successfully!', 'success' )
		);

		wp_send_json_success( $this->get_response() );
	}


	/**
	 * Handles the form submission for the settings page,
	 * then redirects back to the plugin page.
	 */
	public function handle_settings_post() {
		if ( ! $this->validate_request_nonce() ) {
			wp_send_json_error( $this->get_response(), 403 );
		}

		$post_data = $this->extract_post_data();

		$this->update_options( $post_data );

		if ( $this->response_has_errors() ) {
			wp_send_json_error( $this->get_response(), 422 );
		}

		$this->push_response_notice(
			$this->format_response_message( 'Settings successfully updated!', 'success' )
		);

		wp_send_json_success( $this->get_response() );
	}

	/**
	 * Returns the options values in the DB.
	 *
	 * @return array
	 */
	public function get_options() {
		if ( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME ) {
			return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );
		} else {
			return get_option( 'activecampaign_for_woocommerce_settings' );
		}
	}

	/**
	 * Updates the settings options in the DB.
	 *
	 * @param     array $data     An array of data that will be serialized into the DB.
	 *
	 * @return array
	 * @throws Exception When the container is missing definitions.
	 */
	public function update_options( $data ) {
		$current_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );

		$this->validate_options_update( $data, $current_settings );

		if ( $this->response_has_errors() ) {
			return $this->response;
		}

		$api_url_changing = $this->api_url_is_changing( $data, $current_settings );

		if ( $current_settings ) {
			$data = array_merge( $current_settings, $data );
		}

		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME, $data );

		$this->event->trigger(
			[
				'api_url_changed' => $api_url_changing,
			]
		);
		return $this->get_options();
	}

	/**
	 * Returns the storage values in the DB.
	 *
	 * @return array
	 */
	public function get_storage() {
		return get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME );
	}

	/**
	 * Updates the storage values in the DB.
	 *
	 * @param     array $data     An array of data that will be serialized into the DB.
	 *
	 * @return bool
	 */
	public function update_storage( $data ) {
		$current_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME );

		if ( $current_settings ) {
			$data = array_merge( $current_settings, $data );
		}

		update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME, $data );

		return true;
	}

	/**
	 * Allows an event listener/async process to store a notification to be displayed
	 * on the next settings page load.
	 *
	 * @param     string $message     The message to be translated and escaped for display.
	 * @param     string $level     The level of severity of the message.
	 */
	public function add_async_processing_notification( $message, $level = 'info' ) {
		$current_storage = $this->get_storage();

		if ( ! isset( $current_storage['notifications'] ) ) {
			$current_storage['notifications'] = [];
		}

		$notifications = $current_storage['notifications'];

		$notifications[] = $this->format_response_message( $message, $level );

		$this->update_storage(
			[
				'notifications' => $notifications,
			]
		);
	}

	/**
	 * Validates the request nonce for the settings form to ensure valid requests.
	 *
	 * @return bool
	 */
	private function validate_request_nonce() {
		/**
		 * Validate the nonce created for this specific form action.
		 * The nonce input is generated in the template using the wp_nonce_field().
		 */
		$valid = wp_verify_nonce(
			$_POST['activecampaign_for_woocommerce_settings_nonce_field'],
			'activecampaign_for_woocommerce_settings_form'
		);

		if ( ! $valid ) {
			$this->push_response_error(
				$this->format_response_message( 'Form nonce is invalid.', 'error' )
			);
		}

		return $valid;
	}

	/**
	 * Extracts from the $_POST superglobal an array of sanitized data.
	 *
	 * Before sanitizing the data, certain key/value pairs from the array are
	 * removed. This is because CSRF values are currently in the POST body
	 * and we do not want to persist them to the DB.
	 *
	 * @return array
	 */
	private function extract_post_data() {
		$post_data = $_POST;

		/**
		 * Unset all the form fields that don't need to be persisted in the DB.
		 */
		unset( $post_data['action'] );
		unset( $post_data['activecampaign_for_woocommerce_settings_nonce_field'] );
		unset( $post_data['_wp_http_referer'] );

		/**
		 * Map through all values sent in and sanitize them.
		 */
		$post_data = array_map(
			function ( $entry ) {
				return sanitize_text_field( $entry );
			},
			$post_data
		);

		return $post_data;
	}

	/**
	 * Translates and sanitizes error/notice messages into an associative array.
	 *
	 * This will be returned as part of a response to be displayed as a notice in the
	 * admin section of the site.
	 *
	 * @param     string $message     The message that will be translated and returned.
	 * @param     string $level     The notice level (e.g. info, success...).
	 *
	 * @return array
	 */
	private function format_response_message( $message, $level = 'info' ) {
		// phpcs:disable
		return [
			'level'   => sanitize_text_field( $level ),
			'message' => esc_html__(
				$message,
				ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN
			),
		];
		// phpcs:enable
	}

	/**
	 * Adds to the array of response errors a new error.
	 *
	 * @param     array $error     The error associative array containing the error message and level.
	 */
	private function push_response_error( $error ) {
		if ( ! isset( $this->response['errors'] ) ) {
			$this->response['errors'] = [];
		}

		$this->response['errors'][] = $error;
	}

	/**
	 * Adds to the array of response notices a new notice.
	 *
	 * @param     array $notice     The notice associative array containing the notice message and level.
	 */
	private function push_response_notice( $notice ) {
		if ( ! isset( $this->response['notices'] ) ) {
			$this->response['notices'] = [];
		}

		$this->response['notices'][] = $notice;
	}

	/**
	 * Returns an array of the response array with notices and errors
	 * merged with the current state of the options array.
	 *
	 * @return array
	 */
	private function get_response() {
		return array_merge( $this->response, $this->get_options() ?: [] );
	}

	/**
	 * Checks whether or not the current response contains errors.
	 *
	 * @return bool
	 */
	private function response_has_errors() {
		return isset( $this->response['errors'] ) &&
			   count( $this->response['errors'] ) > 0;
	}

	/**
	 * Validates the new data for the options table.
	 *
	 * @param     array $new_data     The array of data to be updated.
	 * @param     array $current_data     The existing data for the options.
	 */
	private function validate_options_update( $new_data, $current_data ) {
		$errors = $this->validator->validate( $new_data, $current_data );

		if ( ! empty( $errors ) ) {
			foreach ( $errors as $error ) {
				$this->push_response_error(
					$this->format_response_message(
						$error,
						'error'
					)
				);
			}
		}
	}

	/**
	 * Checks if the API Url setting is changing.
	 *
	 * @param     array $new_data     An array of new data to be saved.
	 * @param     array $current_data     An array of data that's already saved.
	 *
	 * @return bool
	 */
	private function api_url_is_changing( $new_data, $current_data ) {
		return ( isset( $new_data['api_url'] ) && isset( $current_data['api_url'] ) ) && // both are set
			   $new_data['api_url'] !== $current_data['api_url'];                        // and changing
	}

	/**
	 * Registers available WooCommerce route.
	 */
	public function active_campaign_register_settings_api() {
		register_rest_route(
			'wc',
			'/v2/active-campaign-for-woocommerce/register-integration',
			[
				'methods'             => 'POST',
				'callback'            => [
					$this,
					'save_active_campaign_settings',
				],
				'permission_callback' => [
					$this,
					'validate_rest_user',
				],
			]
		);
	}

	/**
	 * Saves our integration connection settings.
	 *
	 * @param     WP_REST_Request $request     The request object.
	 *
	 * @return WP_REST_Response The REST response object.
	 */
	public function save_active_campaign_settings( WP_REST_Request $request ) {
		$logger = new Logger();

		if ( $request->has_param( 'api_url' ) && $request->has_param( 'api_key' ) ) {
			$params  = $request->get_params();
			$options = $this->get_options();

			// We need to set the default values so WP doesn't error
			$defaults = [
				'abcart_wait'             => 1,
				'optin_checkbox_text'     => 'Keep me up to date on news and exclusive offers',
				'checkbox_display_option' => 'visible_checked_by_default',
				'custom_email_field'      => 'billing_email',
				'ac_debug'                => '0',
			];

			foreach ( $defaults as $key => $default ) {
				if ( ! isset( $options[ $key ] ) ) {
					$params[ $key ] = $default;
				}
			}

			$logger->info( 'Saving integration settings from ActiveCampaign...' );

			$response = $this->update_options( $params );

			return new WP_REST_Response( 'ActiveCampaign connection settings successfully saved to WordPress.', 201 );
		} else {
			$logger->error( 'Required parameters were missing from the API setup call. Setup may need to be finished manually. Please contact support about this issue.' );

			return new WP_REST_Response( 'Error: Missing required parameters.', 400 );
		}
	}

	/**
	 * Callback function to validate the user can save settings
	 *
	 * @return bool|WP_Error The error or true.
	 */
	public function validate_rest_user() {
		if ( ! is_user_logged_in() ) {
			return new WP_Error( 'Unauthorized', __( 'Unauthorized', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), [ 'status' => 401 ] );
		} elseif ( ! current_user_can( 'administrator' ) ) {
			return new WP_Error( 'Forbidden', __( 'Forbidden', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), [ 'status' => 403 ] );
		} else {
			return true;
		}
	}

	/**
	 * Handles the ajax call for clear plugin settings.
	 */
	public function handle_clear_plugin_settings() {
		if ( ! wp_verify_nonce( $_REQUEST['activecampaign_for_woocommerce_settings_nonce_field'], 'activecampaign_for_woocommerce_settings_form' ) ) {
			$this->logger->warning( 'Invalid nonce from the force row sync call:', [ 'request' => $_REQUEST ] );
			wp_send_json_error( 'The nonce appears to be invalid.' );
			return false;
		}

		if ( $this->clear_plugin_settings() ) {
			$logger = new Logger();
			$logger->info( 'Plugin settings have been manually cleared by the admin. The plugin will not run until new settings are saved.' );
			wp_send_json_success( 'ActiveCampaign for WooCommerce settings have been cleared. NOTICE: The plugin will not run until new settings are saved.' );
		} else {
			wp_send_json_error( 'There was an issue attempting to clear the plugin settings' );
		}
	}

	/**
	 * Attempts to clear the plugin settings.
	 *
	 * @return bool
	 */
	private function clear_plugin_settings() {
		try {
			if ( delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME ) && delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME ) ) {
				return true;
			} else {
				return false;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue trying to reset the connection ID',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
			return false;
		}
	}

	/**
	 * Handles the ajax call for reset connection.
	 *
	 * @send string json_success|json_error AJAX return for success or failure.
	 */
	public function handle_reset_connection_id() {
		if ( ! wp_verify_nonce( $_REQUEST['activecampaign_for_woocommerce_settings_nonce_field'], 'activecampaign_for_woocommerce_settings_form' ) ) {
			$this->logger->warning( 'Invalid nonce from the force row sync call:', [ 'request' => $_REQUEST ] );
			wp_send_json_error( 'The nonce appears to be invalid.' );
		}

		if ( $this->reset_connection_id() ) {
			$logger = new Logger();
			$logger->info(
				'The connection ID has been manually reset. These are the stored options.',
				[
					'storage_values' => $this->get_storage(),
					'option_values'  => get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME ),
				]
			);
			wp_send_json_success( 'ActiveCampaign connection ID has been updated/repaired.' );
		} else {
			wp_send_json_error( 'There was an issue attempting to reset the connection ID' );
		}
	}

	/**
	 * Clears then attampts to grab the connection ids from Hosted.
	 *
	 * @return bool
	 */
	private function reset_connection_id() {
		try {
			if ( delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME ) ) {
				do_action( 'activecampaign_for_woocommerce_admin_settings_updated' );
				$update_vals = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME );
				if ( empty( $update_vals['connection_id'] ) ) {
					return false;
				}
				return true;
			} else {
				return false;
			}
		} catch ( Throwable $t ) {
			$logger = new Logger();
			$logger->warning(
				'There was an issue trying to reset the connection ID. Please check the logs for details.',
				[
					'message' => $t->getMessage(),
					'trace'   => $logger->clean_trace( $t->getTrace() ),
				]
			);
			return false;
		}
		// a:3:{s:13:"notifications";a:0:{}s:13:"connection_id";s:1:"7";s:20:"connection_option_id";s:1:"5";}
	}

}
