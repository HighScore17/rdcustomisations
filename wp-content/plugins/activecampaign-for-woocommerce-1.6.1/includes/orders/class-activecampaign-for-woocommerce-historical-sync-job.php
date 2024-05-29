<?php

/**
 * Controls the historical sync process.
 * This will only be run by admin or cron so make sure all methods are admin only.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.5.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Order_Utilities as Order_Utilities;
use Activecampaign_For_Woocommerce_Customer_Utilities as Customer_Utilities;
use Activecampaign_For_Woocommerce_Executable_Interface as Executable;
use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Order_Repository;
use Activecampaign_For_Woocommerce_Ecom_Customer as Ecom_Customer;
use Activecampaign_For_Woocommerce_AC_Contact as AC_Contact;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as Contact_Repository;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Customer_Repository;
use Activecampaign_For_Woocommerce_Bulksync_Repository as Bulksync_Repository;
use Activecampaign_For_Woocommerce_Ecom_Bulksync as Ecom_Bulksync;
/**
 * The Historical_Sync Event Class.
 *
 * @since      1.5.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Historical_Sync_Job implements Executable {

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	private $order_repository;

	/**
	 * The order utilities functions.
	 *
	 * @var Activecampaign_For_Woocommerce_Order_Utilities
	 */
	private $order_utilities;

	/**
	 * The order utilities functions.
	 *
	 * @var Activecampaign_For_Woocommerce_Customer_Utilities
	 */
	private $customer_utilities;

	/**
	 * The AC contact repository.
	 *
	 * @var object AC_Contact.
	 */
	private $contact_repository;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	private $customer_repository;

	/**
	 * The Ecom Connection ID
	 *
	 * @var int
	 */
	private $connection_id;

	/**
	 * The bulksync repository object.
	 *
	 * @since 1.6.0
	 *
	 * @var Activecampaign_For_Woocommerce_Bulksync_Repository
	 */
	private $bulksync_repository;

	/**
	 * The initializing status array.
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	private $status = [
		'current_page'          => 1,
		'current_record'        => 0, // Only properly tracks with single
		'success_count'         => 0,
		'record_limit'          => 250,
		'start_time'            => null, // WP date time
		'end_time'              => null, // WP date time
		'failed_order_id_array' => [], // Array of failed IDs
		'is_paused'             => false, // if the sync is paused
		'is_running'            => true, // running status
	];

	/**
	 * Activecampaign_For_Woocommerce_Historical_Sync_Job constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Logger|null              $logger     The logger object.
	 * @param     Activecampaign_For_Woocommerce_Order_Utilities          $order_utilities     The order utilities class.
	 * @param     Activecampaign_For_Woocommerce_Customer_Utilities       $customer_utilities     The customer utility class.
	 * @param     Activecampaign_For_Woocommerce_AC_Contact_Repository    $contact_repository     The contact repository object.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer_Repository $customer_repository     The customer repository object.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Repository    $order_repository     The order repository object.
	 * @param     Activecampaign_For_Woocommerce_Bulksync_Repository      $bulksync_repository The bulksync repository object.
	 */
	public function __construct(
		Logger $logger = null,
		Order_Utilities $order_utilities,
		Customer_Utilities $customer_utilities,
		Contact_Repository $contact_repository,
		Customer_Repository $customer_repository,
		Order_Repository $order_repository,
		Bulksync_Repository $bulksync_repository
	) {
		$this->logger              = $logger ?: new Logger();
		$this->order_utilities     = $order_utilities;
		$this->contact_repository  = $contact_repository;
		$this->customer_repository = $customer_repository;
		$this->order_repository    = $order_repository;
		$this->customer_utilities  = $customer_utilities;
		$this->bulksync_repository = $bulksync_repository;

		$admin_storage = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_STORAGE_NAME );
		if ( ! empty( $admin_storage ) && isset( $admin_storage['connection_id'] ) ) {
			$this->connection_id = $admin_storage['connection_id'];
		}
	}

	/**
	 * Execute function.
	 *
	 * @param     mixed ...$args The arg.
	 *
	 * @return mixed|void
	 */
	public function execute( ...$args ) {
		// If from a paused state, use the stored status
		if ( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_RUNNING_STATUS_NAME ) ) {
			$this->status = json_decode( get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_RUNNING_STATUS_NAME ), 'array' );
			$this->logger->info(
				'Historical sync process discovered a previous run that may have errored or been paused. Continuing from this data.',
				[
					'status' => $this->status,
				]
			);
		} else {
			// Set the init sync status
			$this->update_sync_status( $this->status );
		}

		// set the start time
		$this->status['start_time'] = wp_date( 'F d, Y - G:i:s e' );

		// Remove the scheduled status because our process is no longer scheduled & now running
		delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_SCHEDULED_STATUS_NAME );

		if ( isset( $args[0] ) ) {
			// This usually only gets set when starting the initial sync, not from a pause
			if ( ! empty( $args[0]->sync_type ) ) {
				$this->status['sync_type'] = $args[0]->sync_type;
			} elseif ( ! empty( $args[0]['sync_type'] ) ) {
				$this->status['sync_type'] = $args[0]['sync_type'];
			}

			if ( ! empty( $args[0]->record_limit ) ) {
				$this->status['record_limit'] = $args[0]->record_limit;
			} elseif ( ! empty( $args[0]['record_limit'] ) ) {
				$this->status['record_limit'] = $args[0]['record_limit'];
			}
		}

		$this->run_sync_process();

	}

	/**
	 * This runs our sync process after being initialized by the execute command.
	 *
	 * @since 1.6.0
	 */
	private function run_sync_process() {
		// phpcs:disable
		while ( $orders = $this->get_orders_by_page( $this->status['current_page'], $this->status['record_limit'] ) ) {
		// phpcs:enable
			if ( 'bulk' === $this->status['sync_type'] ) {
				$this->bulk_sync_data( $orders );
				$this->status['current_record'] += count( $orders );
			}

			if ( 'single' === $this->status['sync_type'] ) {
				$this->sync_singular_record_data( $orders );
				$this->status['current_record'] += count( $orders );
			}

			$sync_stop_type = $this->check_for_stop();

			if ( $sync_stop_type ) {
				switch ( $sync_stop_type ) {
					case '1':
						$this->status['stop_type_name'] = 'cancelled';
						$this->update_sync_status( 'cancel' );
						break;
					case '2':
						$this->status['stop_type_name'] = 'paused';
						$this->update_sync_status( 'pause' );
						break;
					default:
						$this->logger->error(
							'Historical sync stop status found but did not match a type. There may be a bug. Sync will continue.',
							[
								'status'    => $this->status,
								'stop_type' => $sync_stop_type,
							]
						);
						break;
				}

				break;
			}

			$this->status['current_page'] ++;
			$this->update_sync_status();
		}

		if ( ! isset( $this->status['stop_type_name'] ) ) {
			$this->update_sync_status( 'finished' );
		}
	}


	/**
	 * We absolutely need the WC_Order so we need to make every attempt to get it for a valid order.
	 * The order could be anything so we have to make every attempt to get WC_Order from whatever we get from WC.
	 *
	 * @param object|string|array $order The unknown order item passed back from WC.
	 *
	 * @return bool|WC_Order
	 */
	private function get_wc_order( $order ) {
		// If it's a valid WC_Order just send it back
		if ( $order instanceof WC_Order ) {
			return $order;
		}

		// If it's not a valid order, retrieve the order manually assuming $order is an order object
		if ( ! $order instanceof WC_Order ) {
			if ( is_object( $order ) ) {
				$this->logger->debug(
					'This order record is not a valid WC_Order. Attempting to retrieve order by object.',
					[
						'order_id'     => $order->get_id(),
						'order_number' => $order->get_order_number(),
						'order_email'  => $order->get_billing_email(),
					]
				);

				$order = wc_get_order( $order );

				if ( $order instanceof WC_Order ) {
					return $order;
				}

				// If it's still not a valid order assume that it's an object but we need to retrieve by ID.
				$this->logger->debug(
					'This record is still not a valid WC_Order and could not generate one from the object. Attempt to retrieve through order id specifically.',
					[
						'order_id' => $order->get_id(),
					]
				);

				$order = wc_get_order( $order->get_id() );

				if ( $order instanceof WC_Order ) {
					return $order;
				}
			}

			// Let's assume that what was returned was just the order ID array list
			if ( is_array( $order ) ) {
				$this->logger->debug(
					'This record is still not a valid WC_Order but it is an array. Attempt to retrieve through order id as an array.',
					[
						'order_id' => $order,
					]
				);

				$order = wc_get_order( $order['id'] );

				if ( $order instanceof WC_Order ) {
					return $order;
				}
			}

			// Last attempt, assume order is just the ID number
			$this->logger->debug(
				'This record is still not a valid WC_Order. Assume order is just the ID.',
				[
					'order' => $order,
				]
			);

			$order = wc_get_order( $order );

			if ( $order instanceof WC_Order ) {
				return $order;
			}
		}

		$this->logger->debug(
			'A WC_Order object could not be generated.',
			[
				'order' => $order,
			]
		);

		return false;
	}

	/**
	 * This is the sync process using bulk sync.
	 *
	 * @since 1.6.0
	 *
	 * @param array $orders An array of orders.
	 */
	public function bulk_sync_data( $orders ) {
		$customers       = [];
		$customer_orders = [];

		foreach ( $orders as $order ) {
			if ( ! isset( $order ) ) {
				$this->logger->warning( 'This record is not a valid order', [ $order ] );
				continue;
			}

			$order = $this->get_wc_order( $order );

			if ( $order instanceof WC_Order ) {
				// Get the customer
				try {
					$ecom_customer = new Ecom_Customer();
					$ecom_customer->create_ecom_customer_from_order( $order );

					// Make sure our customer is added to the customers array
					if ( ! isset( $customers[ $ecom_customer->get_email() ] ) ) {
						if ( $ecom_customer->get_accepts_marketing() === null ) {
							$ecom_customer->set_accepts_marketing( $order->get_meta( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME ) );
						}

						$customers[ $ecom_customer->get_email() ] = $ecom_customer->serialize_to_array();
					}
				} catch ( Throwable $t ) {
					$this->logger->error(
						'Historical sync failed to create a customer',
						[
							'message'        => $t->getMessage(),
							'stack_trace'    => $this->logger->clean_trace( $t->getTrace() ),
							'customer_email' => $order->get_email(),
						]
					);

					$this->status['failed_order_id_array'][] = $order->get_order_number();

					break;
				}

				try {
					// Get the order
					$ecom_order_with_products                         = $this->build_ecom_order( $ecom_customer, $order );
					$customer_orders[ $ecom_customer->get_email() ][] = $this->serialize_ecom_order_for_bulksync( $ecom_order_with_products );
					$this->status['success_count'] ++;
				} catch ( Throwable $t ) {
					$this->logger->error(
						'Historical sync failed to create an order',
						[
							'message'      => $t->getMessage(),
							'stack_trace'  => $this->logger->clean_trace( $t->getTrace() ),
							'order_number' => $order->get_order_number(),
						]
					);

					$this->status['failed_order_id_array'][] = $order->get_order_number();

					break;
				}
			} else {
				$this->logger->warning(
					'Could not retrieve a valid WC_Order from WooCommerce. This order cannot be synced at this time.',
					[
						'order_id'     => $order->get_id(),
						'order_number' => $order->get_order_number(),
						'order_email'  => $order->get_billing_email(),
					]
				);
			}
		}

		$serialized_customers = [];
		// Now that we have all of the serialized customers and orders we can put them in the right object
		foreach ( $customers as $customer_email => $customer ) {
			$customer['orders']     = $customer_orders[ $customer_email ];
			$serialized_customers[] = $customer;
		}

		$ecom_bulksync = new Ecom_Bulksync();
		$ecom_bulksync->set_service( 'woocommerce' );
		$ecom_bulksync->set_customers( $serialized_customers );
		$ecom_bulksync->set_externalid( get_site_url() );

		$this->bulksync_repository->create( $ecom_bulksync );
	}

	/**
	 * Runs the historical sync process.
	 *
	 * @since 1.5.0
	 *
	 * @param array $orders The order objects.
	 */
	private function sync_singular_record_data( $orders ) {
		foreach ( $orders as $order ) {
			if ( ! isset( $order ) ) {
				continue;
			}

			$order = $this->get_wc_order( $order );

			if ( $order instanceof WC_Order ) {
				$success = false;
				// Contact is allowed to fail because customer will do most of this anyway
				$ecom_contact = new AC_Contact();

				if ( $ecom_contact->create_ecom_contact_from_order( $order ) ) {
					$ecom_contact->set_connectionid( $this->connection_id );
				}

				try {
					$ecom_customer = new Ecom_Customer();
					$ecom_customer->create_ecom_customer_from_order( $order );
				} catch ( Throwable $t ) {
					$this->logger->error(
						'Activecampaign_For_Woocommerce_Historical_Sync: There was an error with the order build.',
						[
							'message'     => $t->getMessage(),
							'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
						]
					);

					break;
				}

				if ( isset( $order ) && ! empty( $order->get_id() ) ) {
					$ecom_customer->set_connectionid( $this->connection_id );

					if ( $ecom_customer->get_accepts_marketing() === null ) {
						$ecom_customer->set_accepts_marketing( $order->get_meta( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME ) );
					}

					$ecom_order_with_products = $this->build_ecom_order( $ecom_customer, $order );

					if ( $ecom_order_with_products && $this->sync_single_record_to_hosted( $ecom_contact, $ecom_customer, $ecom_order_with_products ) ) {
						$success = true;
						$this->order_utilities->update_last_synced( $order->get_id() );
						$this->status['success_count'] ++;
					}
				}

				if ( ! $success ) {
					$this->status['failed_order_id_array'][] = $order->get_order_number();
					$this->logger->debug(
						'Historical sync failed to sync an order',
						[
							'order_number' => $order->get_order_number(),
						]
					);
				}

				$this->status['current_record'] ++;
			}
		}
	}

	/**
	 * This builds the ecom order object.
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer $ecom_customer The ecom customer object.
	 * @param     WC_Order                                     $order The WC order object.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Order|bool|null
	 */
	private function build_ecom_order( Ecom_Customer $ecom_customer, WC_Order $order ) {
		try {
			$ecom_order = $this->order_utilities->setup_woocommerce_order_from_admin( $order, true );
			$ecom_order = $this->customer_utilities->add_customer_to_order( $order, $ecom_order );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_Historical_Sync: There was an error with the order build.',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			return false;
		}

		try {
			if ( $ecom_order && $ecom_order->get_order_number() && $ecom_order->get_externalid() ) {
				$ecom_order->set_connectionid( $this->connection_id );
				$ecom_order->set_email( $ecom_customer->get_email() );

				// Return the order with products
				return $this->order_utilities->build_products_for_order( $order, $ecom_order );
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Historical sync failed to format an ecommerce order object. Record skipped.',
				[
					'message'      => $t->getMessage(),
					'order_number' => $ecom_order->get_order_number(),
					'order_id'     => $ecom_order->get_externalid(),
				]
			);
		}

		return false;
	}

	/**
	 * Creates or updates the contact, customer, and order objects to Hosted.
	 *
	 * @param     Activecampaign_For_Woocommerce_AC_Contact    $ecom_contact The object to send to AC ecom contact.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Customer $ecom_customer The object to send to AC ecom customer.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order    $ecom_order The object to send to AC ecom order.
	 *
	 * @return bool
	 */
	private function sync_single_record_to_hosted( AC_Contact $ecom_contact, Ecom_Customer $ecom_customer, Ecom_Order $ecom_order ) {
		// Sync the contact
		try {
			// Contact is allowed to fail
			$ac_contact = $this->contact_repository->find_by_email( $ecom_contact->get_email() );
			if ( isset( $ac_contact ) && $ac_contact->get_id() ) {
				$ecom_contact->set_id( $ac_contact->get_id() );
				$this->contact_repository->update( $ecom_contact );
			} else {
				$this->contact_repository->create( $ecom_contact );
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Historical Sync: Could not create contact.',
				[
					'email'   => $ecom_contact->get_email(),
					'message' => $t->getMessage(),
				]
			);
		}

		// Sync the customer
		try {
			$ac_customer = $this->customer_repository->find_by_email_and_connection_id( $ecom_customer->get_email(), $this->connection_id );

			if ( isset( $ac_customer ) && $ac_customer->get_id() ) {
				$ecom_customer->set_id( $ac_customer->get_id() );
				$customer_response = $this->customer_repository->update( $ecom_customer );
			} else {
				$customer_response = $this->customer_repository->create( $ecom_customer );
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Historical Sync: Customer create process received a thrown error.',
				[
					'email'   => $ecom_customer->get_email(),
					'message' => $t->getMessage(),
				]
			);

			return false;
		}

		// Sync the order
		try {
			$ac_order = $this->order_repository->find_by_externalid( $ecom_order->get_externalid() );

			if ( isset( $ac_customer ) && ! empty( $ac_customer->get_id() ) ) {
				$ecom_order->set_customerid( $ac_customer->get_id() );
			} else {
				$ecom_order->set_customerid( $customer_response->get_id() );
			}

			if ( $ecom_order->get_customerid() && $ac_order->get_id() ) {
				$ecom_order->set_id( $ac_order->get_id() );
				$ecom_order->set_source( $ac_order->get_source() );
				$order_response = $this->order_repository->update( $ecom_order );
			} else {
				$ecom_order->set_source( '0' );
				$order_response = $this->order_repository->create( $ecom_order );
			}

			$this->order_utilities->store_ac_id( $ecom_order->get_externalid(), $order_response->get_id() );
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Historical Sync: Could not create order.',
				[
					'order_json'  => $ecom_order->order_to_json(),
					'message'     => $t->getMessage(),
					'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			return false;
		}

		try {
			if ( $customer_response->get_id() && $order_response->get_id() ) {
				return true;
			}

			return false;
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Historical sync: Issue with syncing order',
				[
					'customer' => $ecom_customer->get_id(),
					'contact'  => $ecom_contact->get_id(),
					'order'    => $ecom_order->get_id(),
				]
			);
			return false;
		}
	}

	/**
	 * Gets all orders by page filtered by status.
	 *
	 * @param     int    $page     The page number AKA offset.
	 * @param     string $record_limit The limit of records to pull.
	 *
	 * @return stdClass|WC_Order[]
	 */
	public function get_orders_by_page( $page, $record_limit ) {
		// limits and paged can be added
		$orders = wc_get_orders(
			array(
				'status'  => array( 'wc-processing', 'wc-completed' ),
				'limit'   => $record_limit,
				'page'    => $page,
				'orderby' => 'date',
				'order'   => 'ASC',
			)
		);

		return $orders;
	}

	/**
	 * Checks for a stop condition.
	 * 1 = cancel, 2 = pause
	 */
	private function check_for_stop() {
		global $wpdb;
		$sync_stop_type = $wpdb->get_var( 'SELECT option_value from ' . $wpdb->prefix . 'options WHERE option_name = "activecampaign_for_woocommerce_historical_sync_stop"' );

		if ( ! empty( $sync_stop_type ) ) {
			delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_STOP_CHECK_NAME );
			$this->logger->alert(
				'Historical Sync Stop Request Found: Cancelled by admin.',
				[
					'stop_type' => $sync_stop_type,
				]
			);

			return $sync_stop_type;
		}

		return false;
	}

	/**
	 * Serialize the order in preparation for bulksync. This requires a very specific structure so for now we do this.
	 *
	 * @since 1.6.0
	 *
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order $order The ecom order object.
	 *
	 * @return object
	 */
	private function serialize_ecom_order_for_bulksync( Ecom_Order $order ) {
		return (object) [
			'ecomOrder' => $order->serialize_to_array(),
		];
	}

	/**
	 * Updates the sync statuses in the options for the info readout to use on the frontend.
	 * This is how the admin panel is able to tell where we are in the process and to keep record of the sync.
	 *
	 * @param string $type Indicates the type of update.
	 */
	private function update_sync_status( $type = '' ) {
		switch ( $type ) {
			case 'pause':
				update_option(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_RUNNING_STATUS_NAME,
					wp_json_encode(
						[
							'current_record'        => $this->status['current_record'],
							'success_count'         => $this->status['success_count'],
							'failed_order_id_array' => $this->status['failed_order_id_array'],
							'current_page'          => $this->status['current_page'],
							'is_paused'             => true,
							'is_running'            => false,
						]
					)
				);
				update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_LAST_STATUS_NAME, wp_json_encode( $this->status ) );
				break;
			case 'cancel':
			case 'finished':
				delete_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_RUNNING_STATUS_NAME );

				$this->status['end_time'] = wp_date( 'F d, Y - G:i:s e' );

				update_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_LAST_STATUS_NAME, wp_json_encode( $this->status ) );
				$this->logger->info(
					'Historical Sync Ended',
					[
						'status' => $this->status,
					]
				);
				break;
			default:
				update_option(
					ACTIVECAMPAIGN_FOR_WOOCOMMERCE_SYNC_RUNNING_STATUS_NAME,
					wp_json_encode(
						[
							'current_record'        => $this->status['current_record'],
							'success_count'         => $this->status['success_count'],
							'failed_order_id_array' => $this->status['failed_order_id_array'],
							'current_page'          => $this->status['current_page'],
							'is_running'            => true,
						]
					)
				);
				break;
		}
	}
}
