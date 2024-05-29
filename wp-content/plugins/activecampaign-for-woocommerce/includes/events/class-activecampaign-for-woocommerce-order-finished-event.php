<?php

/**
 * The file that defines the Cart_Emptied Event Class.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 */

use Activecampaign_For_Woocommerce_Admin as Admin;
use Activecampaign_For_Woocommerce_AC_Contact as AC_Contact;
use Activecampaign_For_Woocommerce_Ecom_Customer as Ecom_Customer;
use Activecampaign_For_Woocommerce_Ecom_Customer_Repository as Ecom_Customer_Repository;
use Activecampaign_For_Woocommerce_AC_Contact_Repository as AC_Contact_Repository;
use Activecampaign_For_Woocommerce_Ecom_Order as Ecom_Order;
use Activecampaign_For_Woocommerce_Ecom_Order_Factory as Ecom_Order_Factory;
use Activecampaign_For_Woocommerce_Ecom_Order_Repository as Ecom_Order_Repository;
use Activecampaign_For_Woocommerce_Logger as Logger;
use Activecampaign_For_Woocommerce_Ecom_Product_Factory as Ecom_Product_Factory;
use Activecampaign_For_Woocommerce_User_Meta_Service as User_Meta_Service;
use AcVendor\GuzzleHttp\Exception\GuzzleException;
use Brick\Money\Money;

/**
 * The Order_Finished Event Class.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/events
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Order_Finished_Event {

	/**
	 * The WC Customer
	 *
	 * @var WC_Customer
	 */
	public $customer;

	/**
	 * The Ecom Order Factory
	 *
	 * @var Ecom_Order_Factory
	 */
	public $factory;

	/**
	 * The Ecom Order Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order_Repository
	 */
	public $order_repository;

	/**
	 * The Ecom Customer Repo
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Customer_Repository
	 */
	public $customer_repository;

	/**
	 * The Admin object
	 *
	 * @var Activecampaign_For_Woocommerce_Admin
	 */
	private $admin;

	/**
	 * The guest customer email address
	 *
	 * @var string
	 */
	private $customer_email;

	/**
	 * The guest customer first name
	 *
	 * @var string
	 */
	private $customer_first_name;

	/**
	 * The guest customer last name
	 *
	 * @var string
	 */
	private $customer_last_name;

	/**
	 * The WooCommerce customer object
	 *
	 * @var WC_Customer
	 */
	private $customer_woo;

	/**
	 * The resulting existing or newly created AC ecom customer
	 *
	 * @var Ecom_Model
	 */
	private $customer_ac;

	/**
	 * Hash of the WooCommerce session ID plus the guest customer email.
	 * Used to identify an order as being created in a pending state.
	 *
	 * @var string
	 */
	private $external_checkout_id;

	/**
	 * The native ecom order object used to
	 * create or update an order in AC
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Order
	 */
	private $ecom_order;

	/**
	 * The resulting existing or newly created AC ecom order
	 *
	 * @var Activecampaign_For_Woocommerce_Ecom_Model_Interface
	 */
	private $order_ac;

	/**
	 * The custom ActiveCampaign logger
	 *
	 * @var Activecampaign_For_Woocommerce_Logger
	 */
	private $logger;

	/**
	 * The WooCommerce session
	 *
	 * @var WC_Session|null
	 */
	private $wc_session;

	/**
	 * The Ecom Product Factory
	 *
	 * @var Ecom_Product_Factory
	 */
	private $product_factory;

	/**
	 * The Ecom Connection ID
	 *
	 * @var int
	 */
	private $connection_id;

	/**
	 * The contact repository.
	 *
	 * @var AC_Contact_Repository
	 */
	private $contact_repository;

	/**
	 * The customer phone number.
	 *
	 * @var string
	 */
	private $customer_phone;

	/**
	 * The AC contact object.
	 *
	 * @var object AC_Contact.
	 */
	private $contact_ac;

	/**
	 * The accepts marketing checkbox choice
	 *
	 * @var string
	 */
	private $accepts_marketing;

	/**
	 * Activecampaign_For_Woocommerce_Cart_Emptied_Event constructor.
	 *
	 * @param     Activecampaign_For_Woocommerce_Admin|null              $admin     The Admin object.
	 * @param     Activecampaign_For_Woocommerce_Ecom_Order_Factory|null $factory     The ecom order factory.
	 * @param     Ecom_Order_Repository|null                             $order_repository     The Ecom Order Repository.
	 * @param     Ecom_Product_Factory|null                              $product_factory     The Ecom Product Factory.
	 * @param     Ecom_Customer_Repository|null                          $customer_repository     The Ecom Customer Repository.
	 * @param     AC_Contact_Repository|null                             $contact_repository     The AC Contact Repository.
	 * @param     Logger|null                                            $logger     The Logger.
	 */
	public function __construct(
		Admin $admin,
		Ecom_Order_Factory $factory,
		Ecom_Order_Repository $order_repository,
		Ecom_Product_Factory $product_factory,
		Ecom_Customer_Repository $customer_repository,
		AC_Contact_Repository $contact_repository,
		Logger $logger = null
	) {
		$this->admin               = $admin;
		$this->factory             = $factory;
		$this->order_repository    = $order_repository;
		$this->product_factory     = $product_factory;
		$this->customer_repository = $customer_repository;
		$this->contact_repository  = $contact_repository;
		$this->logger              = $logger;
	}

	/**
	 * Called when an order checkout is completed and meta can be added to the final order.
	 * Directly called via hook action.
	 *
	 * @param     order_id $order_id     The order ID.
	 *
	 * @throws Throwable Does not stop.
	 */
	public function checkout_meta( $order_id ) {
		try {
			$this->logger = $this->logger ?: new Logger();

			// get order
			if ( ! empty( $this->admin->get_storage() ) && isset( $this->admin->get_storage()['connection_id'] ) ) {
				$this->connection_id = $this->admin->get_storage()['connection_id'];

				$order = wc_get_order( $order_id );

				if ( isset( $order ) && $order->get_id() && $order->get_billing_email() && $this->verify_order_status( $order->get_status() ) ) {
					// init functions
					$this->customer_email = $order->get_billing_email();

					// Check order for accepts marketing meta info
					if ( is_user_logged_in() ) {
						$this->accepts_marketing = User_Meta_Service::get_current_user_accepts_marketing();
					} else {
						$this->accepts_marketing = $order->get_meta( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ACCEPTS_MARKETING_NAME );
					}

					if ( ! empty( wc()->cart ) ) {
						// if there is a cart the event origin is triggered by customer
						$this->customer = wc()->customer;

						if ( ! empty( wc()->customer->get_billing_phone() ) ) {
							$this->customer_phone = wc()->customer->get_billing_phone();
						} elseif ( ! empty( $order->get_billing_phone() ) ) {
							$this->customer_phone = $order->get_billing_phone();
						}
					} else {
						// Customer is empty
						// This occurs generally as an update by a plugin or admin.
						// Admin has null values for wc() objects
						// we need the customer object
						$user_id = $order->get_user_id();
						$this->build_customer_from_user_meta( $user_id );
						// $this->logger->debug( 'Finished event: built a customer from user meta' );
						if ( User_Meta_Service::get_user_accepts_marketing( $user_id ) !== $this->accepts_marketing ) {
							User_Meta_Service::set_user_accepts_marketing( $user_id, $this->accepts_marketing );
						}
					}

					$this->customer_woo        = $this->customer;
					$this->customer_email      = $order->get_billing_email();
					$this->customer_first_name = $order->get_billing_first_name();
					$this->customer_last_name  = $order->get_billing_last_name();

					if ( empty( $this->customer_phone ) && ! empty( $order->get_billing_phone() ) ) {
						$this->customer_phone = $order->get_billing_phone();
					}

					$this->setup_woocommerce_contact();
					$this->setup_woocommerce_customer();

					// we need the order data
					// see if the order exists in AC already
					$this->get_ac_order( $order );

					$find_or_create_ac_order = $this->setup_woocommerce_order( $order );

					if ( 1 === $find_or_create_ac_order ) {
						// Existing order found in AC, try to update it
						// $this->logger->debug( 'order exists, update it' );
						$this->update_ac_order();
					}

					if ( ! $find_or_create_ac_order ) {
						// 0 was returned, meaning some kind of exception
						$this->logger->error(
							'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not create an order in AC, please review log messages.',
							[
								'order_id'      => $order->get_id(),
								'email'         => $this->customer_email,
								'connection_id' => $this->connection_id,
							]
						);
					} else {
						// Mark the sync time in the order
						$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
						try {
							add_post_meta( $order_id, 'activecampaign_for_woocommerce_last_synced', $date->format( 'Y-m-d H:i:s e' ), true );
						} catch ( Throwable $t ) {
							$this->logger->warning(
								'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not create datetime or save sync metadata to the order',
								[
									'message'  => $t->getMessage(),
									'date'     => $date->format( 'Y-m-d H:i:s e' ),
									'order_id' => $order_id,
									'trace'    => $this->logger->clean_trace( $t->getTrace() ),
								]
							);
						}

						$this->cleanup_activecampaignfwc_order_external_uuid();

						if ( ! $this->remove_abandoned_cart_entry( $order ) ) {
							$this->logger->warning( 'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not reliably delete the abandoned cart entry from table.' );
						}
					}
				}
			} else {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not retrieve or find connection id.',
					[
						'connection_id' => $this->connection_id,
					]
				);
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: There was a fatal error in the checkout AC sync process.',
				[
					'connection_id' => $this->connection_id,
					'message'       => $t->getMessage(),
					'stack_trace'   => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Resets our UUID
	 */
	private function cleanup_activecampaignfwc_order_external_uuid() {
		$logger = new Logger();
		if ( isset( wc()->session ) && wc()->session->get( 'activecampaignfwc_order_external_uuid' ) ) {
			wc()->session->set( 'activecampaignfwc_order_external_uuid', false );
			$logger->debug( 'Reset the activecampaignfwc_order_external_uuid on cart' );
		}
	}

	/**
	 * Removes a record from abandoned cart by passing it the customer ID.
	 * This should only be used for a processed order.
	 *
	 * @param WC_Order $order The order object.
	 *
	 * @return bool
	 */
	private function remove_abandoned_cart_entry( $order ) {
		global $wpdb;
		try {
			$table_name = $wpdb->prefix . ACTIVECAMPAIGN_FOR_WOOCOMMERCE_ABANDONED_CART_NAME;
			// Try to delete by order directly
			$this->delete_from_table( $order->get_customer_id(), $table_name );
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: could not delete the abandoned cart entry.',
				[
					'message'  => $t->getMessage(),
					'order'    => $order->get_data(),
					'session'  => wc()->session->get_session_data(),
					'customer' => wc()->customer->get_data(),
					'trace'    => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}

		// If the previous delete didn't work try the customer method
		if ( isset( wc()->customer ) && wc()->customer->get_id() && $this->delete_from_table( wc()->customer->get_id(), $table_name ) ) {
			return true;
		}

		// If the previous delete didn't work try the session method
		if ( isset( wc()->session ) && wc()->session->get_customer_id() && $this->delete_from_table( wc()->session->get_customer_id(), $table_name ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Deletes entry from abandoned cart table.
	 *
	 * @param string $customer_id The customer id.
	 * @param string $table_name The table name.
	 *
	 * @return bool
	 */
	private function delete_from_table( $customer_id, $table_name ) {
		global $wpdb;
		try {
			if ( ! empty( $customer_id ) ) {
				$wpdb->delete(
					$table_name,
					[
						'customer_id' => $customer_id,
					]
				);

				if ( ! empty( $wpdb->last_error ) ) {
					$this->logger->error(
						'Activecampaign_For_Woocommerce_Order_Finished_Event: There was an error removing the abandoned cart record.',
						[
							'customer_id'     => $customer_id,
							'wpdb_last_error' => $wpdb->last_error,
						]
					);

					return false;
				}

				return true;
			}
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: could not delete the abandoned cart entry.',
				[
					'message'  => $t->getMessage(),
					'session'  => wc()->session->get_session_data(),
					'customer' => wc()->customer->get_data(),
					'trace'    => $this->logger->clean_trace( $t->getTrace() ),
				]
			);
		}
	}

	/**
	 * Builds a customer from the user_id using stored meta
	 *
	 * @param     int $user_id     The user id.
	 *
	 * @return bool
	 */
	private function build_customer_from_user_meta( $user_id ) {
		$this->customer = new Ecom_Customer();
		$this->customer->set_connectionid( $this->connection_id );
		$this->customer->set_email( $this->customer_email );
		$this->customer->set_first_name( get_user_meta( $user_id, 'first_name', true ) );
		$this->customer->set_last_name( get_user_meta( $user_id, 'last_name', true ) );
		$this->customer->set_accepts_marketing( $this->accepts_marketing );

		return true;
	}

	/**
	 * Sets up the order and sends to AC
	 *
	 * @param     WC_Order $order     The order object.
	 *
	 * @return int
	 * @throws Exception Does not stop.
	 */
	private function setup_woocommerce_order( WC_Order $order ) {
		// Setup the woocommerce cart
		try {
			// setup the ecom order
			if ( ! empty( wc()->cart ) && ! empty( $this->customer_woo->get_email() ) ) {
				$this->ecom_order = $this->factory->from_woocommerce( wc()->cart, $this->customer_woo );
			} elseif ( $this->order_ac && ! empty( $this->order_ac->get_email() ) ) {
				// Use the order from ActiveCampaign as the base for our order object
				$this->ecom_order = $this->order_ac;
			} else {
				$this->ecom_order = new Ecom_Order();
			}

			// add the data to the order factory
			if (
				$this->ecom_order && (
					! empty( $this->customer->get_email() ) ||
					! empty( $order->get_billing_email() )
				)
			) {
				if ( $this->customer && $this->customer->get_email() ) {
					$this->ecom_order->set_email( $this->customer->get_email() );
				} elseif ( wc()->customer && wc()->customer->get_email() ) {
					// Set the email address from customer
					$this->ecom_order->set_email( wc()->customer->get_email() );
				} elseif ( get_user_meta( $order->get_user_id(), 'email' ) ) {
					// Set the email address from user
					$this->ecom_order->set_email( get_user_meta( $order->get_user_id(), 'email' ) );
				} elseif ( $order->get_billing_email() ) {
					// Set the email address from order
					$this->ecom_order->set_email( $order->get_billing_email() );
				} else {
					$this->logger->warning(
						'Order_Finished_Event: There was an issue setting the email on this order. This order may not be synced to ActiveCampaign.',
						[
							'order_number' => $order->get_order_number(),
							'order'        => $order,
						]
					);
				}

				$created_date = new DateTime( $order->get_date_created(), new DateTimeZone( 'UTC' ) );
				$this->ecom_order->set_external_created_date( $created_date->format( DATE_ATOM ) );
				$this->ecom_order->set_customerid( $this->customer_ac->get_id() );
				$this->ecom_order->set_currency( get_woocommerce_currency() );
				$this->ecom_order->set_order_number( $order->get_order_number() );
				$this->ecom_order->set_externalid( $order->get_id() );
				$this->ecom_order->set_discount_amount( Money::of( wc_format_decimal( $order->get_total_discount(), 2, 0 ), get_woocommerce_currency() )->getMinorAmount()->toInt() );
				$this->ecom_order->set_shipping_amount( Money::of( wc_format_decimal( $order->get_shipping_total(), 2, 0 ), get_woocommerce_currency() )->getMinorAmount()->toInt() );
				$this->ecom_order->set_shipping_method( $order->get_shipping_method() );
				$this->ecom_order->set_tax_amount( Money::of( wc_format_decimal( $order->get_total_tax(), 2, 0 ), get_woocommerce_currency() )->getMinorAmount()->toInt() );

				if ( ! empty( User_Meta_Service::get_current_cart_ac_id( get_current_user_id() ) ) ) {
					$this->ecom_order->set_id( User_Meta_Service::get_current_cart_ac_id( get_current_user_id() ) );
				}

				// If the event is triggered by customer add these values, otherwise do not update them
				if ( ! empty( wc()->cart ) ) {
					$this->ecom_order->set_total_price( $this->get_cart_total( wc()->cart ) );
				}

				// If we see the total is empty then set total from order
				if ( empty( $this->ecom_order->get_total_price() ) ) {
					$this->ecom_order->set_total_price( Money::of( wc_format_decimal( $order->get_total(), 2, 0 ), get_woocommerce_currency() )->getMinorAmount()->toInt() );
				}

				// If we see the total is still empty then re-calculate then set total from order
				if ( empty( $this->ecom_order->get_total_price() ) ) {
					$order->calculate_totals();
					$this->ecom_order->set_total_price( Money::of( wc_format_decimal( $order->get_total(), 2, 0 ), get_woocommerce_currency() )->getMinorAmount()->toInt() );
				}

				// If it's still empty assume the total is zero
				if ( empty( $this->ecom_order->get_total_price() ) ) {
					$this->ecom_order->set_total_price( 0 );
				}

				$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
				$this->ecom_order->set_order_url( wc_get_cart_url() );
				$this->ecom_order->set_order_date( $date->format( DATE_ATOM ) );
			}

			$this->ecom_order->set_connectionid( $this->connection_id );
			$this->ecom_order->set_source( '1' );

			// Catch any missing data and call it out
			if ( ! $this->ecom_order->validate_model() ) {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Failed to validate a required field in the model. This order will not sync to ActiveCampaign correctly so this order will not be synced.',
					[
						'order_number' => $order->get_order_number(),
					]
				);

				return 0;
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: There was an error creating the order object for AC:',
				[
					'message'              => $t->getMessage(),
					'stack_trace'          => $this->logger->clean_trace( $t->getTrace() ),
					'email'                => $this->customer->get_email(),
					'external_checkout_id' => $this->external_checkout_id,
					'connection_id'        => $this->connection_id,
				]
			);

			return 0;
		}

		try {
			$products = null;

			if ( ! empty( wc()->cart ) ) {
				$products = $this->product_factory->create_products_from_cart_contents( wc()->cart->get_cart_contents() );
			}

			if ( empty( $products ) ) {
				// There is no cart object, build the products and add to the order
				$products = [];
				if ( ! empty( $order->get_items() ) ) {
					// Get and Loop Over Order Items to populate products
					foreach ( $order->get_items() as $item_id => $item ) {
						$products[ $item_id ] = $this->build_ecom_product( $item );
					}
					// Add products to list
					if ( ! empty( $products ) && count( $products ) > 0 ) {
						array_walk( $products, [ $this->ecom_order, 'push_order_product' ] );
					} else {
						$this->logger->warning(
							'Activecampaign_For_Woocommerce_Order_Finished_Event: There was an issue creating the product objects for AC',
							[
								'email'                => $this->customer->get_email(),
								'external_checkout_id' => $this->external_checkout_id,
								'connection_id'        => $this->connection_id,
							]
						);
					}
				} else {
					$this->logger->warning(
						'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not retrieve items from order. Products could not be populated.',
						[
							'email'        => $this->customer->get_email(),
							'order_number' => $order->get_order_number(),
						]
					);
				}
			}
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: There was an error creating the products objects for AC',
				[
					'message'              => $t->getMessage(),
					'stack_trace'          => $this->logger->clean_trace( $t->getTrace() ),
					'email'                => $this->customer->get_email(),
					'external_checkout_id' => $this->external_checkout_id,
					'connection_id'        => $this->connection_id,
					'cart'                 => wc()->cart,
				]
			);
		}

		if ( ! $this->order_ac ) {
			try {
				$this->logger->debug(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Creating order in ActiveCampaign',
					[
						'serialized_order' => \AcVendor\GuzzleHttp\json_encode( $this->ecom_order->serialize_to_array() ),
					]
				);

				// A new order should never have an externalcheckoutid
				$this->ecom_order->set_externalcheckoutid( null );

				// An order was created, remove persistent cart ID
				$order->delete_meta_data( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PERSISTANT_CART_ID_NAME );
				$order->save_meta_data();

				// Try to create the new order in AC
				$this->order_ac = $this->order_repository->create( $this->ecom_order );

				return 2;
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Order finished: Could not send/create order in AC: ',
					[
						'message'              => $t->getMessage(),
						'stack_trace'          => $this->logger->clean_trace( $t->getTrace() ),
						'email'                => $this->customer->get_email(),
						'external_checkout_id' => $this->external_checkout_id,
						'connection_id'        => $this->connection_id,
					]
				);

				return 0;
			}
		} else {
			return 1;
		}
	}

	/**
	 * Get the AC order.
	 *
	 * @param     object $order     The order object.
	 *
	 * @return bool
	 */
	private function get_ac_order( $order ) {
		// Let's try to create the order
		$this->order_ac = null;

		if ( ! empty( wc()->session ) ) {
			$this->wc_session = $this->wc_session ?: wc()->session; // not available for admin

			if ( wc()->customer->get_id() ) {
				$customer_id = wc()->customer->get_id();
			} else {
				$customer_id = wc()->session->get_customer_id();
			}

			$this->external_checkout_id = self::generate_externalcheckoutid(
				$customer_id,
				$this->customer_email
			);
		}

		if ( ! empty( $this->external_checkout_id ) ) {
			try {
				// Try to find the order by it's externalcheckoutid
				$this->order_ac = $this->order_repository->find_by_externalcheckoutid( $this->external_checkout_id );

				if ( $this->order_ac->get_externalcheckoutid() === $this->external_checkout_id ) {
					$this->logger->info(
						'Activecampaign_For_Woocommerce_Order_Finished_Event: Order found in ActiveCampaign by externalcheckoutid',
						[
							'externalcheckoutid' => $this->external_checkout_id,
							'order_ac'           => $this->order_ac->serialize_to_array(),
						]
					);

					return true;
				} else {
					$this->order_ac = null;
					return false;
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: No existing order with this external_checkout_id',
					[
						'external_checkout_id' => $this->external_checkout_id,
						'connection_id'        => $this->connection_id,
						'message'              => $t->getMessage(),
					]
				);
			}
		}

		if ( ! $this->order_ac && ! empty( $order->get_order_number() ) ) {
			try {
				// Try to find the order by it's externalid
				$this->order_ac = $this->order_repository->find_by_externalid( $order->get_order_number() );
				$this->logger->info(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Order found in ActiveCampaign by externalid',
					[
						'order_number' => $order->get_order_number(),
						'order_ac'     => $this->order_ac->serialize_to_array(),
					]
				);

				// Double check that the order we received matches in case of bad responses
				if ( $this->order_ac->get_order_number() === $order->get_order_number() ) {
					return true;
				} else {
					$this->order_ac = null;
					return false;
				}
			} catch ( Throwable $t ) {
				$this->logger->debug(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not find existing order by this externalid',
					[
						'connection_id' => $this->connection_id,
						'order_number'  => $order->get_order_number(),
						'message'       => $t->getMessage(),
					]
				);
			}
		}

		return false;
	}

	/**
	 * Builds an ecom product from the item passed in.
	 *
	 * @param     object $item     The product object.
	 *
	 * @return Activecampaign_For_Woocommerce_Ecom_Product
	 */
	private function build_ecom_product( $item ) {
		$product = [
			'data'         => wc_get_product( $item->get_product_id() ),
			'product_id'   => $item->get_product_id(),
			'variation_id' => $item->get_variation_id(),
			'product'      => $item->get_product(),
			'name'         => $item->get_name(),
			'quantity'     => $item->get_quantity(),
			'subtotal'     => $item->get_subtotal(),
			'total'        => $item->get_total(),
			'tax'          => $item->get_subtotal_tax(),
			'taxclass'     => $item->get_tax_class(),
			'taxstat'      => $item->get_tax_status(),
			'allmeta'      => $item->get_meta_data(),
			'somemeta'     => $item->get_meta( '_whatever', true ),
			'type'         => $item->get_type(),
		];

		return $this->product_factory->product_from_cart_content( $product );
	}

	/**
	 * Creates or updates the contact in ActiveCampaign.
	 *
	 * @return bool
	 * @throws Throwable Activecampaign_For_Woocommerce_Resource_Not_Found_Exception.
	 */
	private function setup_woocommerce_contact() {
		try {
			$this->contact_ac = $this->contact_repository->find_by_email( $this->customer_email );
		} catch ( Throwable $t ) {
			$this->logger->warning(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not find contact by email in AC',
				[
					'customer_email' => $this->customer_email,
					'message'        => $t->getMessage(),
				]
			);
		}

		if ( ! empty( $this->contact_ac ) ) {
			// We have a contact but do we need to update the data?
			$updated_contact = new AC_Contact();

			try {
				$updated_contact->set_id( $this->contact_ac->get_id() );
				$updated_contact->set_email( $this->customer_email );
				$updated_contact->set_first_name( $this->customer_first_name );
				$updated_contact->set_last_name( $this->customer_last_name );
				$updated_contact->set_phone( $this->customer_phone );
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not set data to an updated contact',
					[
						'ac_contact_id'  => $this->contact_ac->get_id(),
						'customer_email' => $this->customer_email,
						'message'        => $t->getMessage(),
					]
				);
			}

			if ( ! empty( array_diff( $this->contact_ac->serialize_to_array(), $updated_contact->serialize_to_array() ) ) ) {
				try {
					$this->logger->debug(
						'Activecampaign_For_Woocommerce_Order_Finished_Event: This contact needs to be updated.',
						[
							'original contact' => [
								'id'         => $this->contact_ac->get_id(),
								'first_name' => $this->contact_ac->get_first_name(),
								'last_name'  => $this->contact_ac->get_last_name(),
								'email'      => $this->contact_ac->get_email(),
								'phone'      => $this->contact_ac->get_phone(),
							],
							'updated contact'  => [
								'id'         => $updated_contact->get_id(),
								'first_name' => $updated_contact->get_first_name(),
								'last_name'  => $updated_contact->get_last_name(),
								'email'      => $updated_contact->get_email(),
								'phone'      => $updated_contact->get_phone(),
							],
							'array_diff'       => array_diff( $this->contact_ac->serialize_to_array(), $updated_contact->serialize_to_array() ),
						]
					);
					$this->contact_ac = $this->contact_repository->update( $updated_contact );

					return true;
				} catch ( Throwable $t ) {
					$this->logger->error(
						'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not update the contact in AC!',
						[
							'original contact' => [
								'id'         => $this->contact_ac->get_id(),
								'first_name' => $this->contact_ac->get_first_name(),
								'last_name'  => $this->contact_ac->get_last_name(),
								'email'      => $this->contact_ac->get_email(),
								'phone'      => $this->contact_ac->get_phone(),
							],
							'updated contact'  => [
								'id'         => $updated_contact->get_id(),
								'first_name' => $updated_contact->get_first_name(),
								'last_name'  => $updated_contact->get_last_name(),
								'email'      => $updated_contact->get_email(),
								'phone'      => $updated_contact->get_phone(),
							],
							'array_diff'       => array_diff( $this->contact_ac->serialize_to_array(), $updated_contact->serialize_to_array() ),
							'message'          => $t->getMessage(),
							'stack_trace'      => $this->logger->clean_trace( $t->getTrace() ),
						]
					);

					return false;
				}
			}
		} else {
			// add new contact
			$new_contact = new AC_Contact();

			try {
				// Try to create the new contact in AC
				$new_contact->set_email( $this->customer_email );
				$new_contact->set_first_name( $this->customer_first_name );
				$new_contact->set_last_name( $this->customer_last_name );
				$new_contact->set_phone( $this->customer_phone );

				$this->logger->info(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Creating new contact in ActiveCampaign ',
					[
						'serialized' => \AcVendor\GuzzleHttp\json_encode( $new_contact->serialize_to_array() ),
					]
				);

				$this->contact_ac = $this->contact_repository->create( $new_contact );

				return true;
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not set data to a new contact object or create customer in AC: ',
					[
						'connection_id' => $this->connection_id,
						'email'         => $this->customer_email,
						'first_name'    => $this->customer_first_name,
						'last_name'     => $this->customer_last_name,
						'phone'         => $this->customer_phone,
						'message'       => $t->getMessage(),
						'stack_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);

				return false;
			}
		}
	}

	/**
	 * Sets up and sends the customer for AC
	 *
	 * @throws GuzzleException Is not fatal.
	 */
	private function setup_woocommerce_customer() {
		// setup_woocommerce_customer
		$this->customer_woo->set_email( $this->customer_email );

		// find_or_create_ac_customer
		$this->customer_ac = null;
		try {
			// Try to find the customer in AC
			$this->customer_ac = $this->customer_repository->find_by_email_and_connection_id( $this->customer_email, $this->connection_id );
		} catch ( Throwable $t ) {
			$this->logger->debug(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not find existing customer ',
				[
					'customer_email' => $this->customer_email,
					'connection_id'  => $this->connection_id,
					'message'        => $t->getMessage(),
				]
			);
		}

		if ( $this->customer_ac ) {
			try {
				// Update the customer accepts marketing value
				if ( $this->accepts_marketing !== $this->customer_ac->get_accepts_marketing() ) {
					$this->customer_ac->set_accepts_marketing( $this->accepts_marketing );
					$this->customer_repository->update( $this->customer_ac );
				}
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Something went wrong with setting the customer update fields.',
					[
						'message'                       => $t->getMessage(),
						'customer_ac id'                => $this->customer_ac->get_id(),
						'customer_ac accepts_marketing' => $this->customer_ac->get_accepts_marketing(),
						'trace'                         => $this->logger->clean_trace( $t->getTrace() ),
					]
				);
			}

			return true;
		} else {
			try {
				$this->logger->debug( 'Activecampaign_For_Woocommerce_Order_Finished_Event: No customer found in AC, making a new one.' );
				// Try to create the new customer in AC
				$new_customer = new Ecom_Customer();
				$new_customer->set_email( $this->customer_email );
				$new_customer->set_connectionid( $this->connection_id );
				$new_customer->set_first_name( $this->customer_first_name );
				$new_customer->set_last_name( $this->customer_last_name );
				$new_customer->set_accepts_marketing( $this->accepts_marketing );

				$this->logger->debug(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Creating customer in ActiveCampaign',
					[
						'customer' => \AcVendor\GuzzleHttp\json_encode( $new_customer->serialize_to_array() ),
					]
				);

				$this->customer_ac = $this->customer_repository->create( $new_customer );
			} catch ( Throwable $t ) {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not create a customer in AC! ',
					[
						'connection_id' => $this->connection_id,
						'email'         => $this->customer_email,
						'message'       => $t->getMessage(),
						'stack_trace'   => $this->logger->clean_trace( $t->getTrace() ),
					]
				);

				return false;
			}

			if ( $this->customer_ac ) {
				// Customer was created successfully
				return true;
			} else {
				$this->logger->error(
					'Activecampaign_For_Woocommerce_Order_Finished_Event: It appears we could not find or create a customer in AC. Please check through the logs for further info.',
					[
						'connection_id'     => $this->connection_id,
						'email'             => $this->customer_email,
						'first_name'        => $this->customer_first_name,
						'last_name'         => $this->customer_last_name,
						'accepts_marketing' => $this->accepts_marketing,
					]
				);

				return false;
			}
		}
	}

	/**
	 * Get the cart's total price in cents,
	 * considering whether the global setting indicates that tax should be included.
	 *
	 * @param     WC_Cart $cart     The WC Cart.
	 *
	 * @return int
	 * @throws Throwable Does not stop.
	 */
	private function get_cart_total( WC_Cart $cart ) {
		$totals = new WC_Cart_Totals( $cart );

		return $totals->get_total( 'total', true );
	}

	/**
	 * Update the existing ecom order in AC
	 *
	 * @return bool Whether or not this job was successful
	 */
	private function update_ac_order() {
		$this->ecom_order->set_id( $this->order_ac->get_id() );

		try {
			if ( ! $this->order_repository->update( $this->ecom_order ) ) {
				$this->logger->error(
					'Could not update the order in AC: ',
					[
						'order_array' => $this->ecom_order->serialize_to_array(),
					]
				);
			}

			return true;
		} catch ( Throwable $t ) {
			$this->logger->error(
				'Activecampaign_For_Woocommerce_Order_Finished_Event: Could not update the order in AC',
				[
					'message'     => $t->getMessage(),
					'stack_trace' => $this->logger->clean_trace( $t->getTrace() ),
				]
			);

			return false;
		}
	}

	/**
	 * Generate the externalcheckoutid hash which
	 * is used to tie together pending and complete
	 * orders in Hosted (so we don't create duplicate orders).
	 * This has been modified to accurately work with woo commerce not independently
	 * tracking cart session vs order session
	 *
	 * @param     string $customer_id     The unique WooCommerce cart session ID.
	 * @param     string $billing_email     The guest customer's email address.
	 *
	 * @return string The hash used as the externalcheckoutid value
	 */
	public static function generate_externalcheckoutid( $customer_id, $billing_email ) {
		// Get the custom session if it exists
		$order_external_uuid = wc()->session->get( 'activecampaignfwc_order_external_uuid' );

		// If custom session is not set, create one on the cart
		if ( ! $order_external_uuid || '' === $order_external_uuid ) {
			$order_external_uuid = uniqid( '', true );
			wc()->session->set( 'activecampaignfwc_order_external_uuid', $order_external_uuid );
		}

		// Generate the hash we'll use
		return md5( $customer_id . $billing_email . $order_external_uuid );
	}

	/**
	 * Verifies the status of the order for sending to AC
	 *
	 * @param     string $status     The order status.
	 *
	 * @return bool Whether or not the order passes.
	 */
	private function verify_order_status( $status ) {
		if ( ! empty( $status ) ) {
			$accepted_statuses = [ 'completed', 'processing' ];

			return in_array( $status, $accepted_statuses, true );
		} else {
			return false;
		}
	}
}
