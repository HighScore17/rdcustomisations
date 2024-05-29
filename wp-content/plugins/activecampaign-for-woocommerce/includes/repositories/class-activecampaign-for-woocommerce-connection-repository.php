<?php

/**
 * The file for the Activecampaign_for_Woocommerce_Connection_Repository class
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 */

use Activecampaign_For_Woocommerce_Api_Client as Api_Client;

use Activecampaign_For_Woocommerce_Connection as Connection;
use Activecampaign_For_Woocommerce_Ecom_Model_Interface as Ecom_Model;
use Activecampaign_For_Woocommerce_Interacts_With_Api as Interacts_With_Api;
use Activecampaign_For_Woocommerce_Repository_Interface as Repository;
use Activecampaign_For_Woocommerce_Resource_Not_Found_Exception as Resource_Not_Found;
use Activecampaign_For_Woocommerce_Resource_Unprocessable_Exception as Unprocessable;

/**
 * The repository class for Connections
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/repositories
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Connection_Repository implements Repository {
	use Interacts_With_Api;

	/**
	 * The singular resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME = 'connection';

	/**
	 * The plural resource name as it maps to the AC API.
	 */
	const RESOURCE_NAME_PLURAL = 'connections';

	/**
	 * The API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Connection Repository constructor.
	 *
	 * @param Api_Client $client The api client.
	 */
	public function __construct( Api_Client $client ) {
		$this->client = $client;

		$this->client->configure_client();
	}

	/**
	 * Finds a resource by its ID and returns an instantiated model with the resource's data.
	 *
	 * @param string|int $id The ID to find the resource by.
	 *
	 * @return Ecom_Model
	 * @throws Resource_Not_Found Thrown when the connection could not be found.
	 */
	public function find_by_id( $id ) {
		/**
		 * A Connection Model.
		 *
		 * @var Connection $connection_model
		 */
		$connection_model = new Connection();

		$this->get_and_set_model_properties_from_api_by_id(
			$this->client,
			$connection_model,
			(string) $id
		);

		return $connection_model;
	}

	/**
	 * Finds a resource by a filtered list response and returns an instantiated model with the resource's data.
	 *
	 * @param string $filter_name The filter name.
	 * @param string $filter_value The filter value.
	 *
	 * @return Ecom_Model
	 * @throws Resource_Not_Found Thrown when the connection could not be found.
	 */
	public function find_by_filter( $filter_name, $filter_value ) {
		/**
		 * A Connection Model.
		 *
		 * @var Connection $connection_model
		 */
		$connection_model = new Connection();

		$this->get_and_set_model_properties_from_api_by_filter(
			$this->client,
			$connection_model,
			$filter_name,
			$filter_value
		);

		return $connection_model;
	}

	/**
	 * Finds a connection for current WooCommerce website.
	 *
	 * @return Ecom_Model
	 * @throws Resource_Not_Found Thrown when the connection could not be found.
	 */
	public function find_current() {
		return $this->find_by_filter( 'externalid', get_home_url() );
	}

	/**
	 * Creates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be created remotely.
	 *
	 * @return Ecom_Model
	 * @throws Unprocessable Thrown when the connection could not be processed due to bad data.
	 */
	public function create( Ecom_Model $model ) {
		$this->create_and_set_model_properties_from_api(
			$this->client,
			$model
		);

		return $model;
	}

	/**
	 * Updates a remote resource and updates the model with the returned data.
	 *
	 * @param Ecom_Model $model The model to be updated remotely.
	 *
	 * @return Ecom_Model
	 * @throws Resource_Not_Found Thrown when the connection could not be found.
	 * @throws Unprocessable Thrown when the connection could not be processed due to bad data.
	 */
	public function update( Ecom_Model $model ) {
		$this->update_and_set_model_properties_from_api(
			$this->client,
			$model
		);

		return $model;
	}
}
