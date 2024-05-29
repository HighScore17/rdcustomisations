<?php

	/**
	 * Provide an abandoned cart plugin view.
	 *
	 * This file is used to markup the admin-facing aspects of the plugin.
	 *
	 * @link       https://www.activecampaign.com/
	 * @since      1.3.7
	 *
	 * @package    Activecampaign_For_Woocommerce
	 * @subpackage Activecampaign_For_Woocommerce/admin/partials
	 */

$activecampaign_for_woocommerce_limit = 40;

if ( isset( $_REQUEST['offset'] ) ) {
	$activecampaign_for_woocommerce_offset = $_REQUEST['offset'];
} else {
	$activecampaign_for_woocommerce_offset = 0;
}

$activecampaign_for_woocommerce_abandoned_carts = $this->get_abandoned_carts( $activecampaign_for_woocommerce_offset );
$activecampaign_for_woocommerce_total           = 0;

if ( count( $activecampaign_for_woocommerce_abandoned_carts ) > 0 ) {
	$activecampaign_for_woocommerce_total = $this->get_total_abandoned_carts();
	$activecampaign_for_woocommerce_pages = ceil( $activecampaign_for_woocommerce_total / $activecampaign_for_woocommerce_limit );
}
?>
<style>
	button.button.ac-spinner {
		background-image: url(images/spinner.gif) !important;
		background-size: 20px 20px;
		opacity: .7;
		background-repeat: no-repeat;
		background-position: center;
	}
	button.button.success {
		border-color: #008a20;
		color: #008a20;
	}
	button.button.fail {
		border-color: #8a151f;
		color: #8a151f;
	}
</style>
<?php settings_errors(); ?>
<div id="activecampaign-for-woocommerce-abandoned-cart">
	<?php
		require plugin_dir_path( __FILE__ ) . '../partials/activecampaign-for-woocommerce-header.php';
	?>
	<div>
		<?php
			esc_html_e( 'All abandoned cart entries will appear here. Records for customers who have finished their order will be removed from the list.', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
		?>
	</div>
	<section>
		<div class="card">
			<div>
				<div class="columnbox">
					<button id="activecampaign-run-abandoned-cart" class="button
					<?php
					if ( ! $activecampaign_for_woocommerce_total ) :
						?>
						disabled<?php endif; ?>">Sync Abandoned Carts</button>
					<div id="activecampaign-run-abandoned-cart-status"></div>
				</div>
			</div>
			<div class="clear">
				<h3>
					Total Abandoned Carts
				</h3>
				<p>
					Total Unsynced: <?php echo esc_html( $this->get_total_abandoned_carts_unsynced() ); ?>
				</p>
				<p>
					Total Abandoned Carts: <?php echo esc_html( $activecampaign_for_woocommerce_total ); ?>
				</p>
			</div>
		</div>
	</section>
	<section>
		<div class="col-container">
			<?php if ( $activecampaign_for_woocommerce_total ) : ?>
				<div class="pagination">
					Page:
					<?php for ( $activecampaign_for_woocommerce_c = 1; $activecampaign_for_woocommerce_c <= $activecampaign_for_woocommerce_pages; $activecampaign_for_woocommerce_c++ ) : ?>
						<?php if ( $activecampaign_for_woocommerce_c === $activecampaign_for_woocommerce_offset + 1 ) : ?>
							<?php echo esc_html( $activecampaign_for_woocommerce_c ); ?>
						<?php else : ?>
							<a href="<?php echo esc_html( add_query_arg( 'offset', $activecampaign_for_woocommerce_c - 1, wc_get_current_admin_url() ) ); ?>">
								<?php echo esc_html( $activecampaign_for_woocommerce_c ); ?>
							</a>
						<?php endif; ?>
					<?php endfor; ?>
				</div>
			<?php endif; ?>
			<form method="POST" id="activecampaign-for-woocommerce-form">
				<?php
				wp_nonce_field( 'activecampaign_for_woocommerce_abandoned_form', 'activecampaign_for_woocommerce_settings_nonce_field' );
				?>

				<table class="wc_status_table widefat status_activecampaign" cellspacing="0">
					<thead>
					<tr>
						<td>
							<?php
							esc_html_e( 'Customer ID', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'Customer Email', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'Synced To ActiveCampaign', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'First Name', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'Last Name', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'Last Access Time', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
						<td>
							<?php
							esc_html_e( 'Actions', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
							?>
						</td>
					</tr>
					</thead>
					<tbody>
					<?php if ( $activecampaign_for_woocommerce_total ) : ?>
						<?php foreach ( $activecampaign_for_woocommerce_abandoned_carts as $activecampaign_for_woocommerce_ab_cart ) : ?>
							<?php if ( isset( $activecampaign_for_woocommerce_ab_cart->id ) ) : ?>
								<tr rowid="<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->id ); ?>">
									<td>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_id ); ?>
									</td>
									<td>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_email ); ?>
									</td>
									<td>
										<?php
										if ( $activecampaign_for_woocommerce_ab_cart->synced_to_ac ) {
											esc_html_e( 'Yes', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
										} else {
											esc_html_e( 'No', ACTIVECAMPAIGN_FOR_WOOCOMMERCE_LOCALIZATION_DOMAIN );
										}
										?>
									</td>
									<td>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_first_name ); ?>
									</td>
									<td>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->customer_last_name ); ?>
									</td>
									<td>
										<?php echo esc_html( $activecampaign_for_woocommerce_ab_cart->last_access_time ); ?>
									</td>
									<td>
										<button class="activecampaign-sync-abandoned-cart button">Sync</button>
										<button class="activecampaign-delete-abandoned-cart button">Delete</button>
									</td>
								</tr>
							<?php endif; ?>
						<?php endforeach; ?>
						<?php else : ?>
							<tr>
								<td>
									No abandoned carts recorded.
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</form>
		</div>
	</section>
</div>
