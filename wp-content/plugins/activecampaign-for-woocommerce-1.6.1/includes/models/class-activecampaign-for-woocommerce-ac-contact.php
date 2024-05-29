<?php

/**
 * The file for the EcomCustomer Model
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 */

use Activecampaign_For_Woocommerce_Api_Serializable as Api_Serializable;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Has_Id as Has_Id;
use Activecampaign_For_Woocommerce_Has_Email as Has_Email;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * The model class for the Ecom Contact
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_AC_Contact implements Ecom_Model, Has_Id, Has_Email {
	use Api_Serializable;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'connectionid' => 'connectionid',
		'externalid'   => 'externalid',
		'email'        => 'email',
		'id'           => 'id',
		'first_name'   => 'firstName',
		'last_name'    => 'lastName',
		'phone'        => 'phone',
	];

	/**
	 * The connection id.
	 *
	 * @var string
	 */
	private $connectionid;

	/**
	 * The external id.
	 *
	 * @var string
	 */
	private $externalid;

	/**
	 * The email address.
	 *
	 * @var string
	 */
	private $email;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The customer's first name.
	 *
	 * @var string
	 */
	private $first_name;

	/**
	 * The customer's last name.
	 *
	 * @var string
	 */
	private $last_name;

	/**
	 * The customer's phone number.
	 *
	 * @var string
	 */
	private $phone;

	/**
	 * Returns a connection id.
	 *
	 * @return string
	 */
	public function get_connectionid() {
		return $this->connectionid;
	}

	/**
	 * Sets the connection id.
	 *
	 * @param     string $connectionid     The connection id.
	 */
	public function set_connectionid( $connectionid ) {
		$this->connectionid = $connectionid;
	}

	/**
	 * Returns the external id.
	 *
	 * @return string
	 */
	public function get_externalid() {
		return $this->externalid;
	}

	/**
	 * Sets the external id.
	 *
	 * @param     string $externalid     The external id.
	 */
	public function set_externalid( $externalid ) {
		$this->externalid = $externalid;
	}

	/**
	 * Returns the email.
	 *
	 * @return string
	 */
	public function get_email() {
		return $this->email;
	}

	/**
	 * Sets the email.
	 *
	 * @param     string $email     The email.
	 */
	public function set_email( $email ) {
		$this->email = $email;
	}

	/**
	 * Returns the id.
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Sets the id.
	 *
	 * @param     string $id     The id.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Returns the first_name.
	 *
	 * @return string
	 */
	public function get_first_name() {
		return $this->first_name;
	}

	/**
	 * Sets the first_name.
	 *
	 * @param     string $first_name     The first_name.
	 */
	public function set_first_name( $first_name ) {
		$this->first_name = $first_name;
	}

	/**
	 * Returns the last_name.
	 *
	 * @return string
	 */
	public function get_last_name() {
		return $this->last_name;
	}

	/**
	 * Sets the last_name.
	 *
	 * @param     string $last_name     The last_name.
	 */
	public function set_last_name( $last_name ) {
		$this->last_name = $last_name;
	}

	/**
	 * Returns the phone number.
	 *
	 * @return string
	 */
	public function get_phone() {
		return $this->phone;
	}

	/**
	 * Sets the phone.
	 *
	 * @param     string $phone     The phone number.
	 */
	public function set_phone( $phone ) {
		$this->phone = $phone;
	}


	/**
	 * Creates the contact model from an order object.
	 *
	 * @param     WC_Order $order The WC Order.
	 *
	 * @return bool
	 */
	public function create_ecom_contact_from_order( WC_Order $order ) {
		$logger = new Logger();
		if ( isset( $order ) && $order->get_id() ) {
			$customer = null;
			if ( $order->get_customer_id() ) {
				try {
					// Use the customer information
					$customer = new WC_Customer( $order->get_customer_id(), false );

					if ( $customer->get_id() ) {
						$this->externalid = $customer->get_id();
					}

					if ( $customer->get_email() ) {
						$this->email = $customer->get_email();
					} elseif ( $customer->get_billing_email() ) {
						$this->email = $customer->get_billing_email();
					}

					if ( $customer->get_first_name() ) {
						$this->first_name = $customer->get_first_name();
					} elseif ( $customer->get_billing_first_name() ) {
						$this->first_name = $customer->get_billing_first_name();
					}

					if ( $customer->get_last_name() ) {
						$this->last_name = $customer->get_last_name();
					} elseif ( $customer->get_billing_last_name() ) {
						$this->last_name = $customer->get_billing_last_name();
					}

					if ( $customer->get_billing_phone() ) {
						$this->phone = $customer->get_billing_phone();
					}

					return true;
				} catch ( Throwable $t ) {
					$logger->error(
						'Activecampaign_For_Woocommerce_Historical_Sync: Could not establish WC_Customer object',
						[
							'message' => $t->getMessage(),
						]
					);
				}
			}

			if ( ! $customer || ! $order->get_customer_id() || ! $customer->get_email() ) {
				try {
					$this->externalid = 0;
					$this->email      = $order->get_billing_email();
					$this->first_name = $order->get_billing_first_name();
					$this->last_name  = $order->get_billing_last_name();
					$this->phone      = $order->get_billing_phone();

					return true;
				} catch ( Throwable $t ) {
					$logger->error(
						'Activecampaign_For_Woocommerce_Historical_Sync: There was a problem preparing data for a record.',
						[
							'customer_email' => $order->get_billing_email(),
							'message'        => $t->getMessage(),
						]
					);

					return false;
				}
			}
		}
	}
}
