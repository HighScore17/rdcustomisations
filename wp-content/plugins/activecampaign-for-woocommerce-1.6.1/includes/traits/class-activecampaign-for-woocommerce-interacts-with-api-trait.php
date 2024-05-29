<?php
/**
 * The file for the trait Activecampaign_For_Woocommerce_Interacts_With_Api.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/traits
 */

use AcVendor\GuzzleHttp\Exception\GuzzleException;
use AcVendor\Psr\Http\Message\StreamInterface;
use Activecampaign_For_Woocommerce_Logger as Logger;

/**
 * Trait Activecampaign_For_Woocommerce_Interacts_With_Api
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/traits
 */
trait Activecampaign_For_Woocommerce_Interacts_With_Api {
	/**
	 * Retrieves a resource via the API and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $id The id to find.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_id(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$id,
		callable $response_massager = null
	) {
		$logger = new Logger();

		try {
			$result = $client
				->get( self::ENDPOINT_NAME_PLURAL, (string) $id )
				->execute();
		} catch ( AcVendor\GuzzleHttp\Exception\ClientException $e ) {
			$logger->debug(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was not found in Hosted. This is likely not an error.',
				[
					'resource_name' => self::RESOURCE_NAME,
					'endpoint_name' => self::ENDPOINT_NAME,
					'found_by'      => 'id',
					'value'         => $id,
					'status_code'   => $e->getResponse() ? $e->getResponse()->getStatusCode() : '',
				]
			);
		}
		if ( isset( $result ) ) {
			try {
				$resource_array = \AcVendor\GuzzleHttp\json_decode( $result->getBody(), true );

				if ( $response_massager ) {
					$resource_array = $response_massager( $resource_array );
				}

				$resource = $resource_array[ self::RESOURCE_NAME ];
				$model->set_properties_from_serialized_array( $resource );
			} catch ( Throwable $t ) {
				$logger->error(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource thrown error.',
					[
						'result' => $result,
					]
				);
			}
		}
	}

	/**
	 * Retrieves a resource via the API by an email address and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Api client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $email The email address.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_email(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$email,
		callable $response_massager = null
	) {
		$this->get_and_set_model_properties_from_api_by_filter(
			$client,
			$model,
			'email',
			$email,
			$response_massager
		);
	}

	/**
	 * Retrieves a resource via the API by an external id and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Api client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $externalid The externalid.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_externalid(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$externalid,
		callable $response_massager = null
	) {
		$this->get_and_set_model_properties_from_api_by_filter(
			$client,
			$model,
			'externalid',
			$externalid,
			$response_massager
		);
	}

	/**
	 * Retrieves a resource via the API with a filter and uses the value to update a model.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The Api client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param string                                              $filter_name The name of the filter.
	 * @param string                                              $filter_value The value of the filter.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function get_and_set_model_properties_from_api_by_filter(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		$filter_name,
		$filter_value,
		callable $response_massager = null
	) {
		$resource = $this->get_result_set_from_api_by_filter( $client, $filter_name, $filter_value, $response_massager );

		if ( ! isset( $resource[0] ) ) {
			$logger = new Logger();
			$logger->debug(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource not found.',
				[
					'endpoint'     => $client->get_endpoint(),
					'resource'     => $resource,
					'filter_name'  => $filter_name,
					'filter_value' => $filter_value,
				]
			);
			$model->set_properties_from_serialized_array( $resource );
		} else {
			$model->set_properties_from_serialized_array( $resource[0] );
		}
	}

	/**
	 * Retrieves a resource via the API with a filter. May return multiple rows.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client $client The Api client class.
	 * @param string                                    $filter_name The name of the filter.
	 * @param string                                    $filter_value The value of the filter.
	 * @param callable|null                             $response_massager A callable to alter the response body.
	 *
	 * @return array
	 */
	private function get_result_set_from_api_by_filter(
		Activecampaign_For_Woocommerce_Api_Client $client,
		$filter_name,
		$filter_value,
		callable $response_massager = null
	) {
		$client->set_filters( [] );
		$client->with_body( '' );
		$logger = new Logger();
		$result = $client
			->get( self::ENDPOINT_NAME_PLURAL )
			->with_filter( $filter_name, $filter_value )
			->execute();

		if ( $result ) {
			try {
				$resources_array = AcVendor\GuzzleHttp\json_decode( $result->getBody(), true );

				if ( count( $resources_array[ self::RESOURCE_NAME_PLURAL ] ) < 1 ) {
					$logger->debug(
						'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was not found. This is likely not an error.',
						[
							'resource_name' => self::RESOURCE_NAME,
							'endpoint_name' => self::ENDPOINT_NAME,
							'found_by'      => $filter_name,
							'value'         => $filter_value,
							'response'      => $result->getBody() instanceof StreamInterface
								? $result->getBody()->getContents()
								: null,
							'code'          => 404,
						]
					);
				}

				if ( $response_massager ) {
					$resources_array = $response_massager( $resources_array );
				}

				return $resources_array[ self::RESOURCE_NAME_PLURAL ];
			} catch ( Throwable $t ) {
				$logger->debug(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource thrown error.',
					[
						'result' => $result,
					]
				);
			}
		}
	}

	/**
	 * Serializes a model and creates a remote resource via the API.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The API Client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function create_and_set_model_properties_from_api(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		callable $response_massager = null
	) {
		$client->set_filters( [] );
		$logger = new Logger();

		$resource = $model->serialize_to_array();

		$body = [
			self::RESOURCE_NAME => $resource,
		];

		$body_as_string = wp_json_encode( $body );

		try {
			$result = $client
				->post( self::ENDPOINT_NAME_PLURAL )
				->with_body( $body_as_string )
				->execute();
		} catch ( AcVendor\GuzzleHttp\Exception\ClientException $e ) {
			$logger->warning(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was unprocessable.',
				[
					'message'       => $e->getMessage(),
					'resource_name' => self::RESOURCE_NAME,
					'endpoint_name' => self::ENDPOINT_NAME,
					'context'       => $body_as_string,
					'response'      => $e->getResponse()
						? $e->getResponse()->getBody()->getContents()
						: '',
					// Make sure the clean trace ends up in the logs
					'trace'         => $logger->clean_trace( $e->getTrace() ),
					'status_code'   => $e->getResponse() ? $e->getResponse()->getStatusCode() : '',
				]
			);
		}

		if ( isset( $result ) ) {
			try {
				$resource_array = AcVendor\GuzzleHttp\json_decode( $result->getBody(), true );

				if ( $response_massager ) {
					$resource_array = $response_massager( $resource_array );
				}

				if ( isset( $resource_array[ self::RESOURCE_NAME ] ) ) {
					$resource = $resource_array[ self::RESOURCE_NAME ];
					$model->set_properties_from_serialized_array( $resource );
				}
			} catch ( Throwable $t ) {
				$logger = new Logger();
				$logger->error(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Resource thrown error.',
					[
						'result' => $result,
					]
				);
			}
		}
	}

	/**
	 * Serializes a model and updates a remote resource via the API.
	 *
	 * @param Activecampaign_For_Woocommerce_Api_Client           $client The API Client class.
	 * @param Activecampaign_For_Woocommerce_Ecom_Model_Interface $model The model class.
	 * @param callable                                            $response_massager A callable to alter the response body.
	 */
	private function update_and_set_model_properties_from_api(
		Activecampaign_For_Woocommerce_Api_Client $client,
		Activecampaign_For_Woocommerce_Ecom_Model_Interface $model,
		callable $response_massager = null
	) {
		$client->set_filters( [] );

		$resource = $model->serialize_to_array();

		$body = [
			self::RESOURCE_NAME => $resource,
		];

		$body_as_string = AcVendor\GuzzleHttp\json_encode( $body );
		$logger         = new Logger();
		try {
			$result = $client
				->put( self::RESOURCE_NAME_PLURAL, $model->get_id() )
				->with_body( $body_as_string )
				->execute();
		} catch ( AcVendor\GuzzleHttp\Exception\ClientException $e ) {
			if ( $e->getCode() === 404 ) {
				$logger->debug(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was not found. This is likely not an error.',
					[
						'message'       => $e->getMessage(),
						'resource_name' => self::RESOURCE_NAME,
						'endpoint_name' => self::ENDPOINT_NAME,
						'found_by'      => 'id',
						'value'         => $model->get_id(),
						'response'      => $e->getResponse()
							? $e->getResponse()->getBody()->getContents()
							: '',
						// Make sure the trace ends up in the logs
						'trace'         => $logger->clean_trace( $e->getTrace() ),
						'status_code'   => $e->getResponse() ? $e->getResponse()->getStatusCode() : '',
					]
				);
			}

			$logger->warning(
				'Activecampaign_For_Woocommerce_Interacts_With_Api: The resource was unprocessable.',
				[
					'message'       => $e->getMessage(),
					'resource_name' => self::RESOURCE_NAME,
					'endpoint_name' => self::ENDPOINT_NAME,
					'context'       => $body_as_string,
					'response'      => $e->getResponse()
						? $e->getResponse()->getBody()->getContents()
						: '',
					'status_code'   => $e->getResponse() ? $e->getResponse()->getStatusCode() : '',
				]
			);
		}

		if ( isset( $result ) && null !== $result ) {
			try {
				$resource_array = AcVendor\GuzzleHttp\json_decode( $result->getBody(), true );

				if ( $response_massager ) {
					$resource_array = $response_massager( $resource_array );
				}

				$resource = $resource_array[ self::RESOURCE_NAME ];
				$model->set_properties_from_serialized_array( $resource );
			} catch ( Throwable $t ) {
				$logger = new Logger();
				$logger->debug(
					'Activecampaign_For_Woocommerce_Interacts_With_Api: Failed to set properties from serialized array.',
					[
						'result' => $result,
					]
				);
			}
		}
	}
}
