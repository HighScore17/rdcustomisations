<?php

/**
 * The file that runs the abandonment synchronization for abandoned carts.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.3.2
 *
 * @package    Activecampaign_For_Woocommerce
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_Ecom_Customer as Ecom_Customer;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities as Abandoned_Cart_Utilities;
use Activecampaign_For_Woocommerce_Order_Utilities as Order_Utilities;
use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Brick\Money\Money;

/**
 * Sync the abandoned carts and their products to ActiveCampaign.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/commands
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Run_Abandonment_Sync_Command {

	/**
	 * The logger interface.
	 *
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	private $order_repository;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	private $customer_repository;

	/**
	 * Abandoned cart utilities class
	 *
	 * @var Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities
	 */
	private $abandoned_cart_util;

	/**
	 * Order utility class.
	 *
	 * @since 1.5.0
	 * @var Order_Utilities The order utility class.
	 */
	private $order_utilities;

	/**
	 * Customer utility class.
	 *
	 * @since 1.5.0
	 * @var Customer_Utilities The customer utility class.
	 */
	private $customer_utilities;

	/**
	 * Activecampaign_For_Woocommerce_Update_Cart_Command constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Admin|null               $admin     The admin object.
	 * @param     Logger                                                  $logger     The logger interface.
	 * @param     Ecom_Customer_Repository|null                           $customer_repository     The Ecom Customer Repo.
	 * @param     Ecom_Order_Repository                                   $order_repository     The Ecom Order Repo.
	 * @param     Activecampaign_For_Woocommerce_Abandoned_Cart_Utilities $abandoned_cart_util The cart utility class.
	 * @param     Activecampaign_For_Woocommerce_Order_Utilities          $order_utilities The order utility class.
	 * @param     Activecampaign_For_Woocommerce_Customer_Utilities       $customer_utilities The customer utility class.
	 */
	public function __construct(
		Admin $admin,
		Logger $logger,
		Ecom_Customer_Repository $customer_repository,
		Ecom_Order_Repository $order_repository,
		Abandoned_Cart_Utilities $abandoned_cart_util,
		Order_Utilities $order_utilities,
	Customer_Utilities $customer_utilities
	) {
		$this->admin               = $admin;
		$this->logger              = $logger;
		$this->customer_repository = $customer_repository;
		$this->order_repository    = $order_repository;
		$this->abandoned_cart_util = $abandoned_cart_util;
		$this->order_utilities     = $order_utilities;
		$this->customer_utilities  = $customer_utilities;

	}

	/**
	 * The hourly task that runs via hook
	 * This initializes via Activecampaign_For_Woocommerce_Save_Abandoned_Cart_Command
	 */
	public function abandoned_cart_hourly_task() {
		$this->logger = $this->logger ?: new Logger();

		// Check for abandoned carts
		$abandoned_carts = $this->get_all_abandoned_carts_from_table();
		if ( ! empty( $abandoned_carts ) ) {
			$this->process_abandoned_carts_per_record( $abandoned_carts );
		} else {
			$this->logger->debug( 'Abandoned cart hourly task: No abandoned carts to process...' );
		}
	}

	/**
	 * The manual run of the hourly task
	 */
	public function abandoned_cart_manual_run() {
		$this->logger = $this->logger ?: new Logger();

		// Check for abandoned carts
		$abandoned_carts = $this->get_all_abandoned_carts_from_table();

		if ( ! empty( $abandoned_carts ) ) {
			$this->process_abandoned_carts_per_record( $abandoned_carts );
			wp_send_json_success( 'Finished sync of abandoned cart. Processed ' . count( $abandoned_carts ) . ' carts.' );
		} else {
			wp_send_json_success( 'No abandoned carts to process.' );
		}
	}

	/**
	 * Performs a manual delete of a row from the abandoned cart table.
	 *
	 * @param string $row_id The row id.
	 */
	public function abandoned_cart_manual_delete( $row_id ) {
		if ( $this->abandoned_cart_util->delete_abandoned_cart_by_filter( 'id', $row_id ) ) {
			wp_send_json_success( 'Row deleted.' );
		} else {
			wp_send_json_error( 'There was an issue deleting the row.' );
		}
	}

	/**
	 * Forces the sync of a specific row
	 *
	 * @param     int $id     The row id.
	 */
	public function force_sync_row( $id ) {
		$abandoned_cart = $this->abandoned_cart_util->get_abandoned_cart_by_row_id( $id );
		if ( ! empty( $abandoned_cart ) ) {
			$this->process_abandoned_carts_per_record( $abandoned_cart );
		} else {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Run_Abandonment_Sync_Command [force_sync_row]: No abandoned carts found by id',
				[
					'id'             => $id,
					'abandoned_cart' => $abandoned_cart,
				]
			);
		}
	}

	/**
	 * Get all active carts.
	 *
	 * @return mixed Whether or not there are abandoned carts.
	 * @throws Throwable Thrown message.
	 */
	private function get_all_abandoned_carts_from_table() {
		global $wpdb;

		// default is 1 hour abandon cart expiration
		$expire_time = 60 * 60;

		// Get the expire time period from the db
		$activecampaign_for_woocommerce_settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );
		$activecampaign_for_woocommerce_settings = stripslashes_deep( $activecampaign_for_woocommerce_settings );
		if ( isset( $activecampaign_for_woocommerce_settings['abcart_wait'] ) ) {
			$activecampaign_for_woocommerce_abcart_wait = $activecampaign_for_woocommerce_settings['abcart_wait'];
			$expire_time                                = 60 * $activecampaign_for_woocommerce_abcart_wait;
		}

		$expire_datetime = new DateTime( 'now -' . $expire_time . ' minutes', new DateTimeZone( 'UTC' ) );

		try {
			// Get the expired carts from our table
			$abandoned_carts = $wpdb->get_results(
			// phpcs:disable
				$wpdb->prepare( 'SELECT id, customer_ref_json, cart_ref_json, cart_totals_ref_json, removed_cart_contents_ref_json, activecampaignfwc_order_external_uuid 
					FROM
						`' . $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME . '`
					WHERE
						last_access_time <= %s
						AND synced_to_ac = 0;',
					$expire_datetime->format( 'Y-m-d H:i:s' )
				)
			// phpcs:enable
			);

			if ( $wpdb->last_error ) {
				$this->logger->error(
					'Abandonment sync: There was an error getting results for abandoned cart records.',
					[
						'wpdb_last_error' => $wpdb->last_error,
					]
				);
			}

			if ( ! empty( $abandoned_carts ) ) {
				// abandoned carts found
				return $abandoned_carts;
			} else {
				// no abandoned carts
				return false;
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Abandonment Sync: There was an error with preparing or getting abandoned cart results.',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Process the abandoned carts per record
	 *
	 * @param     Array $abandoned_carts     Abandoned carts found in the database.
	 *
	 * @throws Throwable Thrown message.
	 */
	private function process_abandoned_carts_per_record( $abandoned_carts ) {
		// set each cart as though it's the existing active cart
		global $wpdb;

		foreach ( $abandoned_carts as $ab_order ) {
			// parse the values
			$synced_to_ac                          = false;
			$customer                              = json_decode( $ab_order->customer_ref_json, false );
			$cart                                  = json_decode( $ab_order->cart_ref_json, false );
			$cart_totals                           = json_decode( $ab_order->cart_totals_ref_json, false );
			$removed_cart_contents                 = json_decode( $ab_order->removed_cart_contents_ref_json, false );
			$activecampaignfwc_order_external_uuid = $ab_order->activecampaignfwc_order_external_uuid;
			$customer->activecampaignfwc_order_external_uuid = $activecampaignfwc_order_external_uuid;

			$item_count_total = 0;
			$products         = [];

			// Get or register our contact
			$customer_ac = $this->find_or_create_ac_customer( $customer );

			// Step 1: Check if we have customer in AC & create or update
			if ( ! isset( $customer_ac ) || empty( $customer_ac ) ) {
				$this->logger->warning(
					'Abandonment sync: Process single abandon cart - Could not find or create customer...',
					[
						'customer id'         => $customer->id,
						'customer first name' => $customer->first_name,
						'customer last name'  => $customer->last_name,
					]
				);

				break;
			}

			// get the products set up
			foreach ( $cart as $product ) {
				try {
					// One of these two methods will get product_id
					$product_id = $product->product_id;
					if ( empty( $product_id ) ) {
						$product_id = $product['product_id'];
					}

					$item_count_total += $product->quantity;
					$wc_product        = wc_get_product( $product_id );
					$product->data     = $wc_product->get_data();

					// Create ecom product
					$ecom_product = new Activecampaign_For_Woocommerce_Ecom_Product();
					$ecom_product->set_externalid( $wc_product->get_id() );
					$ecom_product->set_name( $wc_product->get_name() );
					//$ecom_product->set_price( Money::of( wc_format_decimal( $wc_product->get_price(), 2, 0 ), get_woocommerce_currency() )->getMinorAmount()->toInt() );
					$ecom_product->set_price( Money::of( apply_filters( 'active_campaign_get_abandoned_cart_price', wc_get_product( $product->variation_id ), $product->quantity ), get_woocommerce_currency() )->getMinorAmount() );
					$ecom_product->set_description( $wc_product->get_description() );
					$ecom_product->set_category( $this->order_utilities->get_product_category( $wc_product ) );
					$ecom_product->set_image_url( $this->order_utilities->get_product_image_url( $wc_product ) );
					$ecom_product->set_sku( $wc_product->get_sku() );
					$ecom_product->set_quantity( $product->quantity );

					$products[] = $ecom_product;
				} catch ( Throwable $t ) {
					$this->logger->error(
						'Abandonment Sync: Failed to build the product: ',
						[
							'exception_message' => $t->getMessage(),
							'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
							'product_id'        => $product_id,
						]
					);
				}
			}

			try {
				$wc_cart = new WC_Cart();
				$wc_cart->set_cart_contents( $cart );
				$wc_cart->set_removed_cart_contents( $removed_cart_contents );
				$wc_cart->set_totals( $cart_totals );
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Abandonment Sync: Failed to build the cart: ',
					[
						'exception_message' => $t->getMessage(),
						'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
						'cart'              => $cart,
					]
				);
			}

			try {
				// Check if we have a valid order that may have failed to send.
				$externalcheckout_id = $this->abandoned_cart_util->generate_externalcheckoutid( $customer->id, $customer->email, $activecampaignfwc_order_external_uuid );
				$wc_post_id          = $this->abandoned_cart_util->find_existing_wc_order( $externalcheckout_id );
				$wc_order            = wc_get_order( $wc_post_id );
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Abandonment Sync: There was an error trying to validate if this is an existing order. Do not process.',
					[
						'exception_message' => $t->getMessage(),
						'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);

				continue;
			}

			// We have a valid order, do not send this as abandoned. Create an order instead.
			if ( $wc_order && isset( $wc_post_id ) && ! empty( $wc_post_id ) && $wc_order->get_id() ) {
				$this->logger->debug(
					'Abandonement Sync: Check for valid order',
					[
						'wc_post_id' => $wc_post_id,
						'order id'   => $wc_order->get_id(),
					]
				);
				$ecom_order = $this->order_utilities->setup_woocommerce_order_from_admin( $wc_order );
				$ecom_order = $this->order_utilities->build_products_for_order( $wc_order, $ecom_order );
				$ecom_order = $this->customer_utilities->add_customer_to_order( $wc_order, $ecom_order, true );

				if ( ! $ecom_order->get_customerid() ) {
					$ecom_order->set_customerid( $customer_ac->get_id() );
				}

				if ( isset( $ecom_order ) && $ecom_order->get_externalid() ) {
					$this->logger->debug(
						'Abandonement Sync: Order was found but may not have been sent to ActiveCampaign',
						[
							'order_number' => $ecom_order->get_order_number(),
						]
					);

					$order_ac = $this->order_repository->find_by_externalid( $ecom_order->get_id() );

					if ( isset( $order_ac ) && $order_ac->get_id() ) {
						$this->logger->info(
							'Abandoned cart: Found a valid order in hosted. Ignore this abandoned cart and continue.',
							[
								'externalid'   => $ecom_order->get_externalid(),
								'order_number' => $ecom_order->get_order_number(),
								'hosted_id'    => $order_ac->get_id(),
							]
						);
					} else {
						$this->logger->info(
							'Abandoned cart: This order did not get synced to AC but a valid order was discovered. Invalidate abandoned cart and sync this order to Hosted.',
							[
								'externalid'   => $ecom_order->get_externalid(),
								'order_number' => $ecom_order->get_order_number(),
							]
						);

						$order_ac = $this->order_repository->create( $ecom_order );
					}

					if ( isset( $order_ac ) && $order_ac->get_id() ) {
						$this->order_utilities->update_last_synced( $wc_order->get_id() );
					}

					$this->abandoned_cart_util->delete_abandoned_cart_by_filter( 'id', $ab_order->id );
				}

				// This was a valid order, nothing else to do so skip the rest
				continue;
			}

			// Step 2: Let's make the abandoned order for AC
			$ecom_order = new Ecom_Order();
			try {
				$externalcheckout_id = $this->abandoned_cart_util->generate_externalcheckoutid( $customer->id, $customer->email, $customer->activecampaignfwc_order_external_uuid );

				$ecom_order->set_externalcheckoutid( $externalcheckout_id );
				$ecom_order->set_source( '1' );
				$ecom_order->set_email( $customer->email );
				$ecom_order->set_currency( get_woocommerce_currency() );
				$ecom_order->set_total_price( Money::of( wc_format_decimal( $cart_totals->subtotal, 2, 0 ), get_woocommerce_currency() )->getMinorAmount()->toInt() ); // must be in cents
				$ecom_order->set_connectionid( $this->admin->get_storage()['connection_id'] );
				$ecom_order->set_customerid( $customer_ac->get_id() );
				$ecom_order->set_order_url( wc_get_cart_url() );
				$ecom_order->set_total_products( $item_count_total );

			} catch ( Throwable $t ) {
				$this->logger->error(
					'Abandonment Sync: Failed to build ecom order.',
					[
						'exception_message' => $t->getMessage(),
						'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			try {
				// Step 3: Add the products to the order
				if ( count( $products ) > 0 ) {
					array_walk( $products, [ $ecom_order, 'push_order_product' ] );
				} else {
					$this->logger->warning(
						'Abandonment Sync: Failed to add products to ecom order.',
						[
							'email' => $customer->email,
						]
					);
				}
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Abandonment Sync: Failed to add products to ecom order.',
					[
						'exception_message' => $t->getMessage(),
						'exception_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			try {
				// Try to find the order by it's externalcheckoutid
				$order_ac = $this->order_repository->find_by_externalcheckoutid( $externalcheckout_id );
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Abandonment Sync: Find order in AC exception. ',
					[
						'exception_message'   => $t->getMessage(),
						'connection_id'       => $this->admin->get_storage()['connection_id'],
						'customer_email'      => $customer->email,
						'externalcheckout_id' => $externalcheckout_id,
						'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

				// Let's make absolutely sure this is the same record
			if ( isset( $order_ac ) && ! empty( $order_ac->get_id() ) && $externalcheckout_id === $order_ac->get_externalcheckoutid() ) {
				try {
					$synced_to_ac = true;

					$updated_date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
					$ecom_order->set_external_updated_date( $updated_date->format( DATE_ATOM ) );
					$ecom_order->set_id( $order_ac->get_id() );

					$this->logger->debug(
						'Abandonment Sync: This abandoned cart has already been synced to ActiveCampaign and will be updated.',
						[
							'order'                     => $ecom_order->serialize_to_array(),
							'order connection_id'       => $this->admin->get_storage()['connection_id'],
							'order externalcheckout_id' => $externalcheckout_id,
							'ac externalcheckout_id'    => $order_ac->get_externalcheckoutid(),
							'ac_id'                     => $order_ac->get_id(),
							'customer_email'            => $customer->email,
							'externalcheckout_id'       => $externalcheckout_id,
						]
					);

					$order_ac = $this->order_repository->update( $ecom_order );
				} catch ( Throwable $t ) {
					$this->logger->debug(
						'Abandonment Sync: Order update exception: ',
						[
							'exception_message'   => $t->getMessage(),
							'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
							'connection_id'       => $this->admin->get_storage()['connection_id'],
							'customer_email'      => $customer->email,
							'externalcheckout_id' => $externalcheckout_id,
						]
					);
					$synced_to_ac = false;
				}
			} else {
				try {
					// Order does not exist in AC yet
					// Try to create the new order in AC
					$this->logger->debug(
						'Abandonment Sync: Creating abandoned cart entry in ActiveCampaign: ',
						[
							'order_created' => \AcVendor\GuzzleHttp\json_encode( $ecom_order->serialize_to_array() ),
						]
					);

					$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
					$ecom_order->set_abandoned_date( $date->format( DATE_ATOM ) );
					$ecom_order->set_external_created_date( $date->format( DATE_ATOM ) );

					$order_ac = $this->order_repository->create( $ecom_order );
				} catch ( Throwable $t ) {
					$this->logger->debug(
						'Abandonment Sync: Order creation exception: ',
						[
							'exception_message'   => $t->getMessage(),
							'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
							'connection_id'       => $this->admin->get_storage()['connection_id'],
							'customer_email'      => $customer->email,
							'externalcheckout_id' => $externalcheckout_id,
						]
					);
					$synced_to_ac = false;
				}
				try {
					if ( $order_ac->get_id() ) {
						$synced_to_ac = true;
					}
				} catch ( Throwable $t ) {
					$this->logger->debug(
						'Abandonment Sync: Could not read sync ID, record may not have synced to AC: ',
						[
							'exception_message'   => $t->getMessage(),
							'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
							'connection_id'       => $this->admin->get_storage()['connection_id'],
							'customer_email'      => $customer->email,
							'externalcheckout_id' => $externalcheckout_id,
						]
					);

					$synced_to_ac = false;
				}
			}

			try {
				if ( $synced_to_ac ) {
					// Update the record to show we've synced so we don't sync it again
					$wpdb->update(
						$wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME,
						[
							'synced_to_ac' => 1,
						],
						[
							'id' => $ab_order->id,
						]
					);

					if ( $wpdb->last_error ) {
						$this->logger->error(
							'Abandonement sync: There was an error updating an abandoned cart record as synced.',
							[
								'wpdb_last_error' => $wpdb->last_error,
								'order_id'        => $ab_order->id,
							]
						);
					}
				}
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Abandonment Sync: Issue in updating the abandonment record as synced: ',
					[
						'exception_message'   => $t->getMessage(),
						'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
						'abandoned_order_id'  => $ab_order->id,
						'externalcheckout_id' => $externalcheckout_id,
					]
				);
			}
		}
	}


	/**
	 * Lookup ecom customer record in AC. If it does not exist, create it. This is altered specifically for abandonment.
	 *
	 * @param     WC_Customer $customer     The customer object.
	 *
	 * @return object $customer_ac The customer object from ActiveCampaign.
	 */
	private function find_or_create_ac_customer( $customer ) {
		$customer_ac   = null;
		$connection_id = $this->admin->get_storage()['connection_id'];

		try {
			// Try to find the customer in AC
			$customer_ac = $this->customer_repository->find_by_email_and_connection_id( $customer->email, $connection_id );
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Abandonment sync: Abandon find customer exception.',
				[
					'exception'           => $t->getMessage(),
					'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
					'customer_email'      => $customer->email,
					'customer_first_name' => $customer->first_name,
					'customer_last_name'  => $customer->last_name,
					'connection_id'       => $connection_id,
				]
			);
		}

		if ( ! $customer_ac ) {
			try {
				// Customer does not exist in AC yet
				// Set up AC customer model
				$new_customer = new Ecom_Customer();
				$new_customer->set_connectionid( $connection_id );
				$new_customer->set_email( $customer->email );
				$new_customer->set_first_name( $customer->first_name );
				$new_customer->set_last_name( $customer->last_name );

				// Try to create the new customer in AC
				$this->logger->debug(
					'Abandonment sync: Creating customer in ActiveCampaign: '
					. \AcVendor\GuzzleHttp\json_encode( $new_customer->serialize_to_array() )
				);
				if ( ! empty( $new_customer->get_email() ) ) {
					$customer_ac = $this->customer_repository->create( $new_customer );
				} else {
					$this->logger->warning(
						'Abandonment sync: Email missing, cannot create a customer in AC.',
						[
							'email'    => $new_customer->get_email(),
							'customer' => $customer,
						]
					);
				}
			} catch ( Throwable $t ) {
				$this->logger->warning(
					'Abandonment sync: Abandon customer creation exception.',
					[
						'customer_email'      => $customer->email,
						'customer_first_name' => $customer->first_name,
						'customer_last_name'  => $customer->last_name,
						'connection_id'       => $connection_id,
						'exception_message'   => $t->getMessage(),
						'exception_trace'     => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			if ( ! $customer_ac ) {
				$this->logger->warning(
					'Abandonment sync: Invalid AC customer.',
					[
						'customer_email'      => $customer->email,
						'customer_first_name' => $customer->first_name,
						'customer_last_name'  => $customer->last_name,
						'connection_id'       => $connection_id,
					]
				);
			}
		}

		return $customer_ac;
	}
}
