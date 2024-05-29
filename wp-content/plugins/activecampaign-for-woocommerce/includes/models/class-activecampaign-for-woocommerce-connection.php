<?php

/**
 * The file for the Connection Model
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

/**
 * The model class for the Connection Model
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/models
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Connection implements Ecom_Model, Has_Id {
	use Api_Serializable;

	/**
	 * The API mappings for the API_Serializable trait.
	 *
	 * @var array
	 */
	public $api_mappings = [
		'externalid' => 'externalid',
		'id'         => 'id',
		'name'       => 'name',
		'service'    => 'service',
	];

	/**
	 * The externalid.
	 *
	 * @var string
	 */
	private $externalid;

	/**
	 * The id.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * The service.
	 *
	 * @var string
	 */
	private $service;

	/**
	 * Returns the externalid.
	 *
	 * @return string
	 */
	public function get_externalid() {
		return $this->externalid;
	}

	/**
	 * Sets the externalid.
	 *
	 * @param string $externalid The externalid.
	 */
	public function set_externalid( $externalid ) {
		$this->externalid = $externalid;
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
	 * @param string $id The id.
	 */
	public function set_id( $id ) {
		$this->id = $id;
	}

	/**
	 * Returns the name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Sets the name.
	 *
	 * @param string $name The name.
	 */
	public function set_name( $name ) {
		$this->name = $name;
	}

	/**
	 * Returns the service.
	 *
	 * @return string
	 */
	public function get_service() {
		return $this->service;
	}

	/**
	 * Sets the service.
	 *
	 * @param string $service The service.
	 */
	public function set_service( $service ) {
		$this->service = $service;
	}
}
