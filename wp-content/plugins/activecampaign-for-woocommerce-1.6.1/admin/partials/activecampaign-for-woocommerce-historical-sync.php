<?php
/**
 * Provide an admin historical sync view for the plugin
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/admin/partials
 */

	$activecampaign_for_woocommerce_total_orders            = $this->get_sync_ready_order_count( 'array' );
	$activecampaign_for_woocommerce_historical_sync_running = false;
	$activecampaign_for_woocommerce_running_sync_status     = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_RUNNING_STATUS_NAME ), 'array' );
	$activecampaign_for_woocommerce_sync_status             = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_LAST_STATUS_NAME ), 'array' );
	$activecampaign_for_woocommerce_schedule_status         = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_SCHEDULED_STATUS_NAME );
	$activecampaign_for_woocommerce_event_status            = wp_get_scheduled_event( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_RUN_SYNC_NAME );
?>
<style>
	#activecampaign-run-historical-sync-status-bar{
		border: 1px solid #0073AA;
		height:24px;
		border-radius: 3px;
		margin: 10px 0;
	}
	#sync-run-section{
		display:none;
	}
	#activecampaign-run-historical-sync-status-progress{
		width: 0%;
		max-width: 100%;
		height:24px;
		background: #0073AA;
	}
	#activecampaign-for-woocommerce-historical-sync .columnbox {
		min-height: 60px;
	}
	#sync-start-section label{
		vertical-align: top;
	}
</style>
<div id="activecampaign-for-woocommerce-historical-sync" class="wrap">
<h1>
	<?php
	esc_html_e( 'ActiveCampaign for WooCommerce Historical Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
	?>
</h1>
<section>
	<div class="card">
		<div>
			<div id="sync-start-section" class="columnbox columns-1">
				<div>
					<button id="activecampaign-run-historical-sync" class="button disabled">
						<?php esc_html_e( 'Start Historical Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</button>
					<div style="float:right;padding:5px;">
						<div>
							<input type="radio" name="activecampaign_for_woocommerce_settings_sync_type" value="bulk" checked />
							<label>Bulk Sync</label>
						</div>
						<div>
							<input type="radio" name="activecampaign_for_woocommerce_settings_sync_type" value="single" />
							<label>Single Record Sync</label>
						</div>
					</div>
				</div>
				<div id="activecampaign-run-historical-sync-status"></div>
			</div>
			<div id="sync-run-section" class="columnbox columns-1">
				<div id="activecampaign-run-historical-sync-running-status">
					<?php if ( $activecampaign_for_woocommerce_schedule_status ) : ?>
						<?php esc_html_e( 'Historical sync will start shortly...', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					<?php endif; ?>
				</div>
				<div id="activecampaign-run-historical-sync-status-bar">
					<div id="activecampaign-run-historical-sync-status-progress"></div>
				</div>
				<div>
					<button id="activecampaign-cancel-historical-sync" class="button">
						<?php esc_html_e( 'Cancel Sync Process', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</button>
					<button id="activecampaign-pause-historical-sync" class="button">
						<?php esc_html_e( 'Pause Historical Sync', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</button>
					<button id="activecampaign-reset-historical-sync" class="button">
						<?php esc_html_e( 'Reset Sync Status', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>
	<div class="card">
		<div class="clear">
			<h3>
				<?php esc_html_e( 'Last Historic Sync Results', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
			</h3>
			<hr />
			<div>
				<?php if ( ! empty( $activecampaign_for_woocommerce_sync_status ) ) : ?>
					<table class="form-table">
						<?php if ( ! empty( $activecampaign_for_woocommerce_sync_status['stop_type_name'] ) ) : ?>
							<tr>
								<td>Notice</td>
								<td>
									<?php esc_html_e( 'Last historic sync process was', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
									<?php echo esc_html( $activecampaign_for_woocommerce_sync_status['stop_type_name'] ); ?>
								</td>
							</tr>
						<?php endif; ?>
						<tr class="alternate">
							<td>
								<?php esc_html_e( 'Start Time:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</td>
							<td>
								<?php echo esc_html( $activecampaign_for_woocommerce_sync_status['start_time'] ); ?>
							</td>
						</tr>
						<tr>
							<td>
								<?php esc_html_e( 'Finished Time:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</td>
							<td>
								<?php if ( isset( $activecampaign_for_woocommerce_sync_status['end_time'] ) ) : ?>
									<?php echo esc_html( $activecampaign_for_woocommerce_sync_status['end_time'] ); ?>
								<?php else : ?>
									<?php esc_html_e( 'The last sync was paused or canceled.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								<?php endif; ?>
							</td>
						</tr>
						<tr class="alternate">
							<td>
								<?php esc_html_e( 'Success Count:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</td>
							<td>
								<?php echo esc_html( $activecampaign_for_woocommerce_sync_status['success_count'] ); ?>
							</td>
						<tr>
						</tr>
							<td>
								<?php esc_html_e( 'Failed Count:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</td>
							<td>
								<?php echo esc_html( count( $activecampaign_for_woocommerce_sync_status['failed_order_id_array'] ) ); ?>
							</td>
						</tr>
						<tr class="alternate">
							<td>
								<?php esc_html_e( 'Failed Order IDs:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</td>
							<td>
								<?php if ( empty( $activecampaign_for_woocommerce_sync_status['failed_order_id_array'] ) ) : ?>
									<?php esc_html_e( 'No failed orders.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
								<?php else : ?>
									<?php echo esc_html( wp_json_encode( $activecampaign_for_woocommerce_sync_status['failed_order_id_array'] ) ); ?>
								<?php endif; ?>
							</td>
						<tr>
						</tr>
							<td>
								<?php esc_html_e( 'Total Records Processed:', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
							</td>
							<td>
								<?php echo esc_html( $activecampaign_for_woocommerce_sync_status['current_record'] ); ?>
							</td>
						</tr>
					</table>
				<?php else : ?>
					<?php esc_html_e( 'No sync results found.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN ); ?>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
	<script>
		import AcRadio from "../src/js/components/AcRadio";
		export default {
			components: {AcRadio}
		}
	</script>
