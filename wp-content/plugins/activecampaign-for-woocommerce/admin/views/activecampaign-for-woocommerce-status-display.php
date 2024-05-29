<?php

	/**
	 * Status display
	 *
	 * @package    Activecampaign_For_Woocommerce
	 */

	global $wpdb;

	$activecampaign_for_woocommerce_wc_report                       = wc()->api->get_endpoint_data( '/wc/v3/system_status' );
	$activecampaign_for_woocommerce_wc_environment                  = $activecampaign_for_woocommerce_wc_report['environment'];
	$activecampaign_for_woocommerce_wc_database                     = $activecampaign_for_woocommerce_wc_report['database'];
	$activecampaign_for_woocommerce_wc_post_type_counts             = isset( $activecampaign_for_woocommerce_wc_report['post_type_counts'] ) ? $activecampaign_for_woocommerce_wc_report['post_type_counts'] : array();
	$activecampaign_for_woocommerce_wc_settings                     = $activecampaign_for_woocommerce_wc_report['settings'];
	$activecampaign_for_woocommerce_wc_theme                        = $activecampaign_for_woocommerce_wc_report['theme'];
	$activecampaign_for_woocommerce_wc_actionscheduler_status_array = $wpdb->get_results( 'SELECT status, COUNT(*) as "count" FROM ' . $wpdb->prefix . 'actionscheduler_actions GROUP BY status;' );
	$activecampaign_for_woocommerce_wc_webhooks                     = $wpdb->get_results( 'SELECT name, status FROM ' . $wpdb->prefix . 'wc_webhooks;' );
	$activecampaign_for_woocommerce_wc_rest_keys                    = $wpdb->get_results( 'SELECT description, last_access, permissions FROM ' . $wpdb->prefix . 'woocommerce_api_keys;' );
	$activecampaign_for_woocommerce_recent_log_errors               = $this->fetch_recent_log_errors();
?>
<div id="activecampaign_status" label="
	<?php
		use Automattic\WooCommerce\Blocks\Package as Block_Package;
		esc_html_e( 'Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
	?>
	">
	<?php
		require plugin_dir_path( __FILE__ ) . '../partials/activecampaign-for-woocommerce-header.php';
	?>
	<div id="activecampaign_status_copy_button">
		<span id="copyStatus"></span>
		<button id="copyButton" class="activecampaign-for-woocommerce button secondary">Copy to clipboard</button>
	</div>
	<table class="wc_status_table widefat status_activecampaign" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Theme">
				<h2><?php esc_html_e( 'ActiveCampaign Quick Check', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td>
				<?php
				esc_html_e( 'ActiveCampaign connection ID: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
				?>
			</td>
			<td>
				<?php if ( empty( $this->get_storage() ) || ! $this->get_storage() || ! isset( $this->get_storage()['connection_id'] ) ) : ?>
					<?php esc_html_e( 'Error: No connection ID found in settings! ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				<?php else : ?>
					<?php echo esc_html( $this->get_storage()['connection_id'] ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php
				esc_html_e( 'ActiveCampaign connection option ID: ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
				?>
			</td>
			<td>
				<?php if ( empty( $this->get_storage() ) || ! $this->get_storage() || ! isset( $this->get_storage()['connection_option_id'] ) ) : ?>
					<?php esc_html_e( 'Error: No connection option ID found in settings! ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				<?php else : ?>
					<?php echo esc_html( $this->get_storage()['connection_option_id'] ); ?>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td>
				<?php esc_html_e( 'Legacy API Enabled:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
			</td>
			<td>
				<?php
				$activecampaign_for_woocommerce_legacy_api = get_option( 'woocommerce_api_enabled' );
				if ( 'yes' === $activecampaign_for_woocommerce_legacy_api && ! is_null( $activecampaign_for_woocommerce_legacy_api ) ) :
					?>
					<mark class="yes">
						<span class="dashicons dashicons-yes"></span>
						<?php esc_html_e( 'Yes', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</mark>
				<?php else : ?>
					<mark class="error">
						<span class="dashicons dashicons-warning"></span>
						<a href="<?php esc_html( get_admin_url() ); ?>admin.php?page=wc-settings&tab=advanced&section=legacy_api">
							<?php esc_html__( 'Disabled (Please make sure this is enabled)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						</a>
					</mark>
				<?php endif; ?>
				<?php esc_html( get_option( 'woocommerce_api_enabled' ) ); ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Cron"><?php esc_html_e( 'WordPress cron', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php if ( $activecampaign_for_woocommerce_wc_environment['wp_cron'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Webhooks:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php foreach ( $activecampaign_for_woocommerce_wc_webhooks as $activecampaign_for_woocommerce_hook ) : ?>
					<?php if ( strpos( $activecampaign_for_woocommerce_hook->name, 'ActiveCampaign' ) >= 0 ) : ?>
						<?php
						echo esc_html( $activecampaign_for_woocommerce_hook->name );
						?>
						<br/>
						<?php
						esc_html_e( 'Status:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
						echo esc_html( $activecampaign_for_woocommerce_hook->status );
						?>
						<br/><br/>
					<?php endif; ?>
				<?php endforeach; ?>
			</td>
		</tr>
		<tr>
			<td><?php esc_html_e( 'Rest APIs:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php foreach ( $activecampaign_for_woocommerce_wc_rest_keys as $activecampaign_for_woocommerce_key ) : ?>
					<?php if ( strpos( $activecampaign_for_woocommerce_key->description, 'ActiveCampaign' ) >= 0 ) : ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_key->description ); ?>
						<br/>
						<?php
						esc_html_e( 'Last Access:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
						echo esc_html( $activecampaign_for_woocommerce_key->last_access );
						esc_html_e( '| Permission:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
						echo esc_html( $activecampaign_for_woocommerce_key->permissions );
						?>
						<br/><br/>
					<?php endif; ?>
				<?php endforeach; ?>
			</td>
		</tr>
		</tbody>
	</table>
	<hr />
	<table class="wc_status_table widefat status_activecampaign_errors" cellspacing="0">
		<div class="status-split">
			<a href="
			<?php
			if ( function_exists( 'wc_admin_url' ) ) {
				echo esc_url(
					wc_admin_url(
						'status',
						array(
							'page' => 'wc-status',
							'tab'  => 'logs',
						)
					)
				);
			} else {
				echo esc_url(
					admin_url( 'admin.php?page=wc-status&tab=logs' )
				);
			}
			?>
			">See the ActiveCampaign for WooCommerce logs for more info</a>

			<span id="activecampaign-for-woocommerce-clear-error-log">
				<?php if ( $activecampaign_for_woocommerce_recent_log_errors ) : ?>
					<span class="button-secondary" href="#" title="Clear Log Errors">Clear Log Errors</span>
				<?php else : ?>
					<span class="button-secondary button-disabled" href="#" title="Clear Log Errors">Clear Log Errors</span>
				<?php endif; ?>
			</span>
		</div>
		<thead>
		<tr>
			<td>
				WooCommerce Logs: ActiveCampaign for WooCommerce error messages
			</td>
			<td>
				Context
			</td>
		</tr>
		</thead>
		<tbody>
		<?php if ( $activecampaign_for_woocommerce_recent_log_errors ) : ?>
			<?php foreach ( $activecampaign_for_woocommerce_recent_log_errors as $activecampaign_for_woocommerce_err ) : ?>
				<tr>
					<td style="width: 60%;">
						<div class="td-container">
							<?php echo esc_html( $activecampaign_for_woocommerce_err->message ); ?>
						</div>
					</td>
					<td>
						<?php if ( is_null( $activecampaign_for_woocommerce_err->context ) ) : ?>
							<div class="td-container no-context">
								<?php echo esc_html( 'No context available' ); ?>
							</div>
						<?php else : ?>
							<div class="td-container">
								<?php echo esc_html( wp_json_encode( maybe_unserialize( $activecampaign_for_woocommerce_err->context ) ) ); ?>
							</div>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td>
					There are no errors at this time.
				</td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
	<table class="wc_status_table widefat status_wordpress_env" cellspacing="0" id="status">
		<thead>
		<tr>
			<th colspan="3" data-export-label="WordPress Environment">
				<h2><?php esc_html_e( 'WordPress environment', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td data-export-label="WordPress address (URL)"><?php esc_html_e( 'WordPress address (URL)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['site_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Site address (URL)"><?php esc_html_e( 'Site address (URL)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['home_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Version"><?php esc_html_e( 'WooCommerce version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['version'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="REST API Version"><?php esc_html_e( 'WooCommerce REST API package', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				$activecampaign_for_woocommerce_version = wc()->api->get_rest_api_package_version();

				if ( ! is_null( $activecampaign_for_woocommerce_version ) ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( wc()->api->get_rest_api_package_path() ) . '</code></mark> ';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Unable to detect the REST API package.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WC Blocks Version"><?php esc_html_e( 'WooCommerce Blocks package', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( class_exists( 'Block_Package' ) ) {
					$activecampaign_for_woocommerce_version = Block_Package::get_version();
					$activecampaign_for_woocommerce_path    = Block_Package::get_path(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} else {
					$activecampaign_for_woocommerce_version = null;
				}

				if ( ! is_null( $activecampaign_for_woocommerce_version ) ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( $activecampaign_for_woocommerce_path ) . '</code></mark> ';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Unable to detect the Blocks package.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Action Scheduler Version"><?php esc_html_e( 'Action Scheduler package', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( class_exists( 'ActionScheduler_Versions' ) && class_exists( 'ActionScheduler' ) ) {
					$activecampaign_for_woocommerce_version = ActionScheduler_Versions::instance()->latest_version();
					$activecampaign_for_woocommerce_path    = ActionScheduler::plugin_path( '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} else {
					$activecampaign_for_woocommerce_version = null;
				}

				if ( ! is_null( $activecampaign_for_woocommerce_version ) ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( $activecampaign_for_woocommerce_path ) . '</code></mark> ';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Unable to detect the Action Scheduler package.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WC Admin Version"><?php esc_html_e( 'WooCommerce Admin package', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				$activecampaign_for_woocommerce_wc_admin_path = null;
				if ( defined( 'WC_ADMIN_VERSION_NUMBER' ) ) {
					// Plugin version of WC Admin.
					$activecampaign_for_woocommerce_version        = WC_ADMIN_VERSION_NUMBER;
					$activecampaign_for_woocommerce_package_active = false;
				} elseif ( class_exists( 'Admin_Package' ) ) {
					if ( WC()->is_wc_admin_active() ) {
						// Fully active package version of WC Admin.
						$activecampaign_for_woocommerce_version        = Admin_Package::get_active_version();
						$activecampaign_for_woocommerce_package_active = Admin_Package::is_package_active();
					} else {
						// with WP version < 5.3, package is present, but inactive.
						$activecampaign_for_woocommerce_version = sprintf(
						/* translators: %s: Version number of wc-admin package */
							__( 'Inactive %s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
							Admin_Package::VERSION
						);
						$activecampaign_for_woocommerce_package_active = false;
					}
					$activecampaign_for_woocommerce_wc_admin_path = Admin_Package::get_path();
				} else {
					$activecampaign_for_woocommerce_version = null;
				}

				if ( ! is_null( $activecampaign_for_woocommerce_version ) ) {
					if ( ! isset( $activecampaign_for_woocommerce_wc_admin_path ) ) {
						if ( defined( 'WC_ADMIN_PLUGIN_FILE' ) ) {
							$activecampaign_for_woocommerce_wc_admin_path = dirname( WC_ADMIN_PLUGIN_FILE );
						} else {
							$activecampaign_for_woocommerce_wc_admin_path = __( 'Active Plugin', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
						}
					}
					if ( WC()->is_wc_admin_active() ) {
						echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( $activecampaign_for_woocommerce_wc_admin_path ) . '</code></mark> ';
					} else {
						echo '<span class="dashicons dashicons-no-alt"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( $activecampaign_for_woocommerce_wc_admin_path ) . '</code> ';
					}
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Unable to detect the WC Admin package.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Log Directory Writable"><?php esc_html_e( 'Log directory writable', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( $activecampaign_for_woocommerce_wc_environment['log_directory_writable'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> <code class="private">' . esc_html( $activecampaign_for_woocommerce_wc_environment['log_directory'] ) . '</code></mark> ';
				} else {
					/* Translators: %1$s: Log directory, %2$s: Log directory constant */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'To allow logging, make %1$s writable or define a custom %2$s.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), '<code>' . esc_html( $activecampaign_for_woocommerce_wc_environment['log_directory'] ) . '</code>', '<code>WC_LOG_DIR</code>' ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Version"><?php esc_html_e( 'WordPress version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				$activecampaign_for_woocommerce_latest_version = get_transient( 'woocommerce_system_status_wp_version_check' );

				if ( false === $activecampaign_for_woocommerce_latest_version ) {
					$activecampaign_for_woocommerce_version_check = wp_remote_get( 'https://api.wordpress.org/core/version-check/1.7/' );
					$activecampaign_for_woocommerce_api_response  = json_decode( wp_remote_retrieve_body( $activecampaign_for_woocommerce_version_check ), true );

					if ( $activecampaign_for_woocommerce_api_response && isset( $activecampaign_for_woocommerce_api_response['offers'], $activecampaign_for_woocommerce_api_response['offers'][0], $activecampaign_for_woocommerce_api_response['offers'][0]['version'] ) ) {
						$activecampaign_for_woocommerce_latest_version = $activecampaign_for_woocommerce_api_response['offers'][0]['version'];
					} else {
						$activecampaign_for_woocommerce_latest_version = $activecampaign_for_woocommerce_wc_environment['wp_version'];
					}
					set_transient( 'woocommerce_system_status_wp_version_check', $activecampaign_for_woocommerce_latest_version, DAY_IN_SECONDS );
				}

				if ( version_compare( $activecampaign_for_woocommerce_wc_environment['wp_version'], $activecampaign_for_woocommerce_latest_version, '<' ) ) {
					/* Translators: %1$s: Current version, %2$s: New version */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - There is a newer version of WordPress available (%2$s)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_wc_environment['wp_version'] ), esc_html( $activecampaign_for_woocommerce_latest_version ) ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( $activecampaign_for_woocommerce_wc_environment['wp_version'] ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Multisite"><?php esc_html_e( 'WordPress multisite', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo ( $activecampaign_for_woocommerce_wc_environment['wp_multisite'] ) ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
		</tr>
		<tr>
			<td data-export-label="WP Memory Limit"><?php esc_html_e( 'WordPress memory limit', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( $activecampaign_for_woocommerce_wc_environment['wp_memory_limit'] < 67108864 ) {
					/* Translators: %1$s: Memory limit, %2$s: Docs link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend setting memory to at least 64MB. See: %2$s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( size_format( $activecampaign_for_woocommerce_wc_environment['wp_memory_limit'] ) ), '<a href="https://wordpress.org/support/article/editing-wp-config-php/#increasing-memory-allocated-to-php" target="_blank">' . esc_html__( 'Increasing memory allocated to PHP', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( size_format( $activecampaign_for_woocommerce_wc_environment['wp_memory_limit'] ) ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Debug Mode"><?php esc_html_e( 'WordPress debug mode', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php if ( $activecampaign_for_woocommerce_wc_environment['wp_debug_mode'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="WP Cron"><?php esc_html_e( 'WordPress cron', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php if ( $activecampaign_for_woocommerce_wc_environment['wp_cron'] ) : ?>
					<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>
				<?php else : ?>
					<mark class="no">&ndash;</mark>
				<?php endif; ?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Language"><?php esc_html_e( 'Language', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['language'] ); ?></td>
		</tr>
		</tbody>
	</table>
	<table class="wc_status_table widefat status_server" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Server Environment">
				<h2><?php esc_html_e( 'Server environment', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td data-export-label="Server Info"><?php esc_html_e( 'Server info', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['server_info'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="PHP Version"><?php esc_html_e( 'PHP version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( version_compare( $activecampaign_for_woocommerce_wc_environment['php_version'], '7.2', '>=' ) ) {
					echo '<mark class="yes">' . esc_html( $activecampaign_for_woocommerce_wc_environment['php_version'] ) . '</mark>';
				} else {
					$activecampaign_for_woocommerce_update_link = ' <a href="https://docs.woocommerce.com/document/how-to-update-your-php-version/" target="_blank">' . esc_html__( 'How to update your PHP version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>';
					$activecampaign_for_woocommerce_class       = 'error';

					if ( version_compare( $activecampaign_for_woocommerce_wc_environment['php_version'], '5.4', '<' ) ) {
						$activecampaign_for_woocommerce_notice = '<span class="dashicons dashicons-warning"></span> ' . __( 'WooCommerce will run under this version of PHP, however, some features such as geolocation are not compatible. Support for this version will be dropped in the next major release. We recommend using PHP version 7.2 or above for greater performance and security.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . $activecampaign_for_woocommerce_update_link;
					} elseif ( version_compare( $activecampaign_for_woocommerce_wc_environment['php_version'], '5.6', '<' ) ) {
						$activecampaign_for_woocommerce_notice = '<span class="dashicons dashicons-warning"></span> ' . __( 'WooCommerce will run under this version of PHP, however, it has reached end of life. We recommend using PHP version 7.2 or above for greater performance and security.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . $activecampaign_for_woocommerce_update_link;
					} elseif ( version_compare( $activecampaign_for_woocommerce_wc_environment['php_version'], '7.2', '<' ) ) {
						$activecampaign_for_woocommerce_notice = __( 'We recommend using PHP version 7.2 or above for greater performance and security.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . $activecampaign_for_woocommerce_update_link;
						$activecampaign_for_woocommerce_class  = 'recommendation';
					}

					echo '<mark class="' . esc_attr( $activecampaign_for_woocommerce_class ) . '">' . esc_html( $activecampaign_for_woocommerce_wc_environment['php_version'] ) . ' - ' . wp_kses_post( $activecampaign_for_woocommerce_notice ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<?php if ( function_exists( 'ini_get' ) ) : ?>
			<tr>
				<td data-export-label="PHP Post Max Size"><?php esc_html_e( 'PHP post max size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( size_format( $activecampaign_for_woocommerce_wc_environment['php_post_max_size'] ) ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Time Limit"><?php esc_html_e( 'PHP time limit', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['php_max_execution_time'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="PHP Max Input Vars"><?php esc_html_e( 'PHP max input vars', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['php_max_input_vars'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="cURL Version"><?php esc_html_e( 'cURL version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_environment['curl_version'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="SUHOSIN Installed"><?php esc_html_e( 'SUHOSIN installed', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo $activecampaign_for_woocommerce_wc_environment['suhosin_installed'] ? '<span class="dashicons dashicons-yes"></span>' : '&ndash;'; ?></td>
			</tr>
		<?php endif; ?>

		<?php

		if ( $activecampaign_for_woocommerce_wc_environment['mysql_version'] ) :
			?>
			<tr>
				<td data-export-label="MySQL Version"><?php esc_html_e( 'MySQL version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td>
					<?php
					if ( version_compare( $activecampaign_for_woocommerce_wc_environment['mysql_version'], '5.6', '<' ) && ! strstr( $activecampaign_for_woocommerce_wc_environment['mysql_version_string'], 'MariaDB' ) ) {
						/* Translators: %1$s: MySQL version, %2$s: Recommended MySQL version. */
						echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend a minimum MySQL version of 5.6. See: %2$s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_wc_environment['mysql_version_string'] ), '<a href="https://wordpress.org/about/requirements/" target="_blank">' . esc_html__( 'WordPress requirements', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>' ) . '</mark>';
					} else {
						echo '<mark class="yes">' . esc_html( $activecampaign_for_woocommerce_wc_environment['mysql_version_string'] ) . '</mark>';
					}
					?>
				</td>
			</tr>
		<?php endif; ?>
		<tr>
			<td data-export-label="Default Timezone is UTC"><?php esc_html_e( 'Default timezone is UTC', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( 'UTC' !== $activecampaign_for_woocommerce_wc_environment['default_timezone'] ) {
					/* Translators: %s: default timezone.. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( 'Default timezone is %s - it should be UTC', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_wc_environment['default_timezone'] ) ) . '</mark>';
				} else {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="fsockopen/cURL"><?php esc_html_e( 'fsockopen/cURL', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( $activecampaign_for_woocommerce_wc_environment['fsockopen_or_curl_enabled'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Your server does not have fsockopen or cURL enabled - PayPal IPN and other scripts which communicate with other servers will not work. Contact your hosting provider.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<?php
		$activecampaign_for_woocommerce_env_rows = apply_filters( 'activecampaign_for_woocommerce_system_status_environment_rows', array() );
		foreach ( $activecampaign_for_woocommerce_env_rows as $activecampaign_for_woocommerce_row ) {
			if ( ! empty( $activecampaign_for_woocommerce_row['success'] ) ) {
				$activecampaign_for_woocommerce_css_class = 'yes';
				$activecampaign_for_woocommerce_icon      = '<span class="dashicons dashicons-yes"></span>';
			} else {
				$activecampaign_for_woocommerce_css_class = 'error';
				$activecampaign_for_woocommerce_icon      = '<span class="dashicons dashicons-no-alt"></span>';
			}
			?>
			<tr>
				<td data-export-label="<?php echo esc_attr( $activecampaign_for_woocommerce_row['name'] ); ?>"><?php echo esc_html( $activecampaign_for_woocommerce_row['name'] ); ?>
					:
				</td>
				<td>
					<mark class="<?php echo esc_attr( $activecampaign_for_woocommerce_css_class ); ?>">
						<?php echo wp_kses_post( $activecampaign_for_woocommerce_icon ); ?><?php echo wp_kses_data( ! empty( $activecampaign_for_woocommerce_row['note'] ) ? $activecampaign_for_woocommerce_row['note'] : '' ); ?>
					</mark>
				</td>
			</tr>
			<?php
		}
		?>
		</tbody>
	</table>
	<table id="status-database" class="wc_status_table widefat status_database" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Database">
				<h2>
					<?php
					esc_html_e( 'Database', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
					?>
				</h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td data-export-label="WC Database Version"><?php esc_html_e( 'WooCommerce database version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_database['wc_database_version'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Database Prefix"><?php esc_html_e( 'Database prefix', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td>
				<?php
				if ( strlen( $activecampaign_for_woocommerce_wc_database['database_prefix'] ) > 20 ) {
					/* Translators: %1$s: Database prefix, %2$s: Docs link. */
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . sprintf( esc_html__( '%1$s - We recommend using a prefix with less than 20 characters. See: %2$s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_wc_database['database_prefix'] ), '<a href="https://docs.woocommerce.com/document/completed-order-email-doesnt-contain-download-links/#section-2" target="_blank">' . esc_html__( 'How to update your database table prefix', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</a>' ) . '</mark>';
				} else {
					echo '<mark class="yes">' . esc_html( $activecampaign_for_woocommerce_wc_database['database_prefix'] ) . '</mark>';
				}
				?>
			</td>
		</tr>

		<?php if ( ! empty( $activecampaign_for_woocommerce_wc_database['database_size'] ) && ! empty( $activecampaign_for_woocommerce_wc_database['database_tables'] ) ) : ?>
			<tr>
				<td><?php esc_html_e( 'Total Database Size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
				<td><?php printf( '%.2fMB', esc_html( $activecampaign_for_woocommerce_wc_database['database_size']['data'] + $activecampaign_for_woocommerce_wc_database['database_size']['index'] ) ); ?></td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Database Data Size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
				<td><?php printf( '%.2fMB', esc_html( $activecampaign_for_woocommerce_wc_database['database_size']['data'] ) ); ?></td>
			</tr>

			<tr>
				<td><?php esc_html_e( 'Database Index Size', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
				<td><?php printf( '%.2fMB', esc_html( $activecampaign_for_woocommerce_wc_database['database_size']['index'] ) ); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
	<table class="wc_status_table widefat status_woocommerce_settings" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Settings">
				<h2><?php esc_html_e( 'WooCommerce Settings', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td data-export-label="API Enabled"><?php esc_html_e( 'API enabled', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo $activecampaign_for_woocommerce_wc_settings['api_enabled'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Force SSL"><?php esc_html_e( 'Force SSL', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo $activecampaign_for_woocommerce_wc_settings['force_ssl'] ? '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>' : '<mark class="no">&ndash;</mark>'; ?></td>
		</tr>
		<tr>
			<td data-export-label="Currency"><?php esc_html_e( 'Currency', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_settings['currency'] ); ?>
				(<?php echo esc_html( $activecampaign_for_woocommerce_wc_settings['currency_symbol'] ); ?>)
			</td>
		</tr>
		<tr>
			<td data-export-label="Currency Position"><?php esc_html_e( 'Currency position', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_settings['currency_position'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Thousand Separator"><?php esc_html_e( 'Thousand separator', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_settings['thousand_separator'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Decimal Separator"><?php esc_html_e( 'Decimal separator', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_settings['decimal_separator'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Number of Decimals"><?php esc_html_e( 'Number of decimals', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_settings['number_of_decimals'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="WC Admin Version"><?php esc_html_e( 'WooCommerce Admin package', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				$activecampaign_for_woocommerce_wc_admin_path = null;
				if ( defined( 'WC_ADMIN_VERSION_NUMBER' ) ) {
					// Plugin version of WC Admin.
					$activecampaign_for_woocommerce_version        = WC_ADMIN_VERSION_NUMBER;
					$activecampaign_for_woocommerce_package_active = false;
				} elseif ( class_exists( 'Admin_Package' ) ) {
					if ( WC()->is_wc_admin_active() ) {
						// Fully active package version of WC Admin.
						$activecampaign_for_woocommerce_version        = Admin_Package::get_active_version();
						$activecampaign_for_woocommerce_package_active = Admin_Package::is_package_active();
					} else {
						// with WP version < 5.3, package is present, but inactive.
						$activecampaign_for_woocommerce_version = sprintf(
						/* translators: %s: Version number of wc-admin package */
							__( 'Inactive %s', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ),
							Admin_Package::VERSION
						);
						$activecampaign_for_woocommerce_package_active = false;
					}
					$activecampaign_for_woocommerce_wc_admin_path = Admin_Package::get_path();
				} else {
					$activecampaign_for_woocommerce_version = null;
				}

				if ( ! is_null( $activecampaign_for_woocommerce_version ) ) {
					if ( ! isset( $activecampaign_for_woocommerce_wc_admin_path ) ) {
						if ( defined( 'WC_ADMIN_PLUGIN_FILE' ) ) {
							$activecampaign_for_woocommerce_wc_admin_path = dirname( WC_ADMIN_PLUGIN_FILE );
						} else {
							$activecampaign_for_woocommerce_wc_admin_path = __( 'Active Plugin', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
						}
					}
					if ( WC()->is_wc_admin_active() ) {
						echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( $activecampaign_for_woocommerce_wc_admin_path ) . '</code></mark> ';
					} else {
						echo '<span class="dashicons dashicons-no-alt"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( $activecampaign_for_woocommerce_wc_admin_path ) . '</code> ';
					}
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Unable to detect the WC Admin package.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				}
				?>
			</td>
		</tr>
		</tbody>
	</table>
	<table class="wc_status_table widefat status_theme" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Theme">
				<h2><?php esc_html_e( 'Theme', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td data-export-label="Name"><?php esc_html_e( 'Name', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_theme['name'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Version"><?php esc_html_e( 'Version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( version_compare( $activecampaign_for_woocommerce_wc_theme['version'], $activecampaign_for_woocommerce_wc_theme['version_latest'], '<' ) ) {
					/* translators: 1: current version. 2: latest version */
					echo esc_html( sprintf( __( '%1$s (update to version %2$s is available)', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), $activecampaign_for_woocommerce_wc_theme['version'], $activecampaign_for_woocommerce_wc_theme['version_latest'] ) );
				} else {
					echo esc_html( $activecampaign_for_woocommerce_wc_theme['version'] );
				}
				?>
			</td>
		</tr>
		<tr>
			<td data-export-label="Author URL"><?php esc_html_e( 'Author URL', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_theme['author_url'] ); ?></td>
		</tr>
		<tr>
			<td data-export-label="Child Theme"><?php esc_html_e( 'Child theme', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( $activecampaign_for_woocommerce_wc_theme['is_child_theme'] ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				} else {
					/* Translators: %s docs link. */
					echo '<span class="dashicons dashicons-no-alt"></span> &ndash; ' . wp_kses_post( sprintf( __( 'If you are modifying WooCommerce on a parent theme that you did not build personally we recommend using a child theme. See: <a href="%s" target="_blank">How to create a child theme</a>', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), 'https://developer.wordpress.org/themes/advanced-topics/child-themes/' ) );
				}
				?>
			</td>
		</tr>
		<?php if ( $activecampaign_for_woocommerce_wc_theme['is_child_theme'] ) : ?>
			<tr>
				<td data-export-label="Parent Theme Name"><?php esc_html_e( 'Parent theme name', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_theme['parent_name'] ); ?></td>
			</tr>
			<tr>
				<td data-export-label="Parent Theme Version"><?php esc_html_e( 'Parent theme version', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td>
					<?php
					echo esc_html( $activecampaign_for_woocommerce_wc_theme['parent_version'] );
					if ( version_compare( $activecampaign_for_woocommerce_wc_theme['parent_version'], $activecampaign_for_woocommerce_wc_theme['parent_version_latest'], '<' ) ) {
						/* translators: %s: parent theme latest version */
						echo ' &ndash; <strong style="color:red;">' . sprintf( esc_html__( '%s is available', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ), esc_html( $activecampaign_for_woocommerce_wc_theme['parent_version_latest'] ) ) . '</strong>';
					}
					?>
				</td>
			</tr>
			<tr>
				<td data-export-label="Parent Theme Author URL"><?php esc_html_e( 'Parent theme author URL', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					:
				</td>
				<td><?php echo esc_html( $activecampaign_for_woocommerce_wc_theme['parent_author_url'] ); ?></td>
			</tr>
		<?php endif ?>
		<tr>
			<td data-export-label="WooCommerce Support"><?php esc_html_e( 'WooCommerce support', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( ! $activecampaign_for_woocommerce_wc_theme['has_woocommerce_support'] ) {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Not declared', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				} else {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span></mark>';
				}
				?>
			</td>
		</tr>
		</tbody>
	</table>
	<table class="wc_status_table widefat status_action_scheduler" cellspacing="0">
		<thead>
		<tr>
			<th colspan="3" data-export-label="Theme">
				<h2><?php esc_html_e( 'Action Scheduler', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?></h2>
			</th>
		</tr>
		</thead>
		<tbody>
		<tr>
			<td data-export-label="Action Scheduler Version"><?php esc_html_e( 'Action Scheduler package', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				:
			</td>
			<td>
				<?php
				if ( class_exists( 'ActionScheduler_Versions' ) && class_exists( 'ActionScheduler' ) ) {
					$activecampaign_for_woocommerce_version = ActionScheduler_Versions::instance()->latest_version();
					$activecampaign_for_woocommerce_path    = ActionScheduler::plugin_path( '' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
				} else {
					$activecampaign_for_woocommerce_version = null;
				}

				if ( ! is_null( $activecampaign_for_woocommerce_version ) ) {
					echo '<mark class="yes"><span class="dashicons dashicons-yes"></span> ' . esc_html( $activecampaign_for_woocommerce_version ) . ' <code class="private">' . esc_html( $activecampaign_for_woocommerce_path ) . '</code></mark> ';
				} else {
					echo '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . esc_html__( 'Unable to detect the Action Scheduler package.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ) . '</mark>';
				}
				?>
			</td>
		</tr>
		<?php if ( $activecampaign_for_woocommerce_wc_actionscheduler_status_array ) : ?>
			<tr>
				<?php foreach ( $activecampaign_for_woocommerce_wc_actionscheduler_status_array as $activecampaign_for_woocommerce_status ) : ?>
					<td>
						<?php esc_html_e( 'Status - ', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
						<?php echo esc_html( $activecampaign_for_woocommerce_status->status ); ?>
					</td>
					<td>
						<?php echo esc_html( $activecampaign_for_woocommerce_status->count ); ?>
						<?php esc_html_e( ' actions', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</td>
				<?php endforeach; ?>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
