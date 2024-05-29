<?php

/**
 * The file that defines the main ActiveCampaign API Client.
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/api-client
 */

use AcVendor\GuzzleHttp\Client;
use AcVendor\GuzzleHttp\Exception\GuzzleException;
use AcVendor\Psr\Http\Message\ResponseInterface;
use AcVendor\Psr\Log\LoggerInterface;
use Activecampaign_For_Woocommerce_Request_Id_Service as RequestIdService;

/**
 * The main API Client class.
 *
 * This is used to fetch/send data from and to ActiveCampaign.
 *
 * @since      1.0.0
 * @package    Activecampaign_For_Woocommerce
 * @subpackage Activecampaign_For_Woocommerce/includes/api-client
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 *
 * @method self get( string $endpoint, string | int $id = null )
 * @method self put( string $endpoint, string | int $id = null )
 * @method self post( string $endpoint, string | int $id = null )
 */
class Activecampaign_For_Woocommerce_Api_Client {
	/**
	 * The API Uri to make requests against.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $api_uri;

	/**
	 * The API Key to include as a header value in all requests.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $api_key;

	/**
	 * The HTTP Client that will handle making the requests.
	 *
	 * @var Client
	 * @since      1.0.0
	 */
	private $client;

	/**
	 * The method for the request, e.g. 'GET'.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $method;

	/**
	 * The endpoint for the request, e.g. 'ecomOrder/1'
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $endpoint;

	/**
	 * The JSON-formatted string to include as the body of the request.
	 *
	 * @var string
	 * @since      1.0.0
	 */
	private $body;

	/**
	 * The filters to add to the endpoint.
	 *
	 * @var array
	 * @since      1.0.0
	 */
	private $filters = [];

	/**
	 * Whether or not we've already configured the client.
	 *
	 * @var bool
	 * @since      1.0.0
	 */
	private $configured = false;

	/**
	 * A list of methods that can be magic-called.
	 *
	 * @since      1.0.0
	 */
	const ACCEPTED_METHODS = [
		'get',
		'put',
		'post',
	];
	/**
	 * Whether or not to log the response.
	 *
	 * @since 1.2.11
	 * @var   bool
	 */
	private $ac_debug;
	/**
	 * The WC Logger
	 *
	 * @since 1.2.11
	 * @var   LoggerInterface|null
	 */
	private $logger;

	/**
	 * Activecampaign_For_Woocommerce_Api_Client constructor.
	 *
	 * @param string|null          $api_uri The API Uri for the client to use.
	 * @param string|null          $api_key The API Key for the client to use.
	 * @param LoggerInterface|null $logger  The logger.
	 * @param null                 $debug   Whether debugging is turned on.
	 *
	 * @since      1.0.0
	 */
	public function __construct( $api_uri = null, $api_key = null, LoggerInterface $logger = null, $debug = null ) {
		$this->api_uri = $api_uri;
		$this->api_key = $api_key;

		$this->logger = $logger;

		if ( null === $debug ) {
			$settings       = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );
			$this->ac_debug = isset( $settings['ac_debug'] ) ? '1' === $settings['ac_debug'] : false;
		} else {
			$this->ac_debug = $debug;
		}
	}

	/**
	 * Configure the HTTP Client that will make the requests.
	 *
	 * If an instantiated Guzzle Client class is passed in as an argument,
	 * it will be used. Otherwise, a new one will be instantiated and given
	 * default arguments.
	 *
	 * @param Client|null $client The HTTP Client that will be used.
	 *
	 * @since      1.0.0
	 */
	public function configure_client( Client $client = null ) {
		if ( $client ) {
			$this->client = $client;

			$this->configured = true;

			return;
		}

		/**
		 * Prevent mal-configuring this object in the event the DB values are not yet set.
		 */
		if (
			! $this->api_uri ||
			! $this->api_key
		) {
			return;
		}

		$this->client = new Client(
			[
				'base_uri' => $this->get_api_uri_with_v3_path(),
				'timeout'  => 20,
				'headers'  => [
					'Api-Token'    => $this->get_api_key(),
					'X-Request-Id' => RequestIdService::get_request_id(),
				],
			]
		);

		$this->configured = true;
	}

	/**
	 * Returns the api uri with the api v3 path appended.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_api_uri_with_v3_path() {
		return $this->api_uri . '/api/3/';
	}

	/**
	 * Returns the api uri.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_api_uri() {
		return $this->api_uri;
	}

	/**
	 * Sets the api uri.
	 *
	 * @param string $api_uri The API Uri to use.
	 *
	 * @since      1.0.0
	 */
	public function set_api_uri( $api_uri ) {
		$this->api_uri = $api_uri;
	}

	/**
	 * Returns the api key.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function get_api_key() {
		return $this->api_key;
	}

	/**
	 * Sets the api key.
	 *
	 * @param string $api_key The API Key to use.
	 *
	 * @since      1.0.0
	 */
	public function set_api_key( $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Returns the instantiated Guzzle client.
	 *
	 * @return Client
	 * @since      1.0.0
	 */
	public function get_client() {
		return $this->client;
	}

	/**
	 * This magic-method allows us to call get(), put(), and post() on the client without individually
	 * defining them.
	 *
	 * @param string $name      The name of the method called, eg, get() => 'get'.
	 * @param array  $arguments An array containing all arguments passed in the method call.
	 *
	 * @return self
	 * @throws InvalidArgumentException When the method called is not in the list of accepted methods.
	 * @since      1.0.0
	 */
	public function __call( $name, $arguments ) {
		if ( ! in_array( $name, self::ACCEPTED_METHODS, true ) ) {
			throw new InvalidArgumentException( "The method $name is not an acceptable request method." );
		}

		/**
		 * The first time the API key and url are saved to the DB, we run into an issue where this object
		 * is constructed before the values are saved. This leads to fatal exceptions due to a malformed
		 * url. Here, we refresh the values from the DB and now configure the client if we had to return
		 * early in the initial configuration due to the missing values.
		 */
		if ( ! $this->configured ) {
			$this->refresh_api_values();

			$this->configure_client();
		}

		$this->method = $this->format_request_method( $name );

		$this->endpoint = $this->format_endpoint( $arguments );

		return $this;
	}

	/**
	 * Returns the endpoint for this request.
	 *
	 * @return string The endpoint.
	 * @since      1.0.0
	 */
	public function get_endpoint() {
		return $this->endpoint;
	}

	/**
	 * Returns the method for this request.
	 *
	 * @return string The method.
	 * @since      1.0.0
	 */
	public function get_method() {
		return $this->method;
	}

	/**
	 * Sets a JSON-formatted string as the body for the request.
	 *
	 * If called multiple times, the original body will be overwritten.
	 *
	 * @param string $body The JSON-formatted string to include in the request.
	 *
	 * @return self
	 * @since      1.0.0
	 */
	public function with_body( $body ) {
		$this->body = $body;

		return $this;
	}

	/**
	 * Returns the JSON-formatted body for this request.
	 *
	 * @return string The JSON-formatted body.
	 * @since      1.0.0
	 */
	public function get_body() {
		return $this->body;
	}

	/**
	 * Pushes a new key/value pair of a filter to the filters array.
	 *
	 * E.g.:
	 *
	 * $filter_name = 'email', $filter_value = 'example@example.com'
	 *
	 * Becomes
	 *
	 * ["email" => "example@example.com"]
	 *
	 * @param string $filter_name  The name of the filter to add.
	 * @param string $filter_value The value of the filter to add.
	 *
	 * @return self
	 * @since      1.0.0
	 */
	public function with_filter( $filter_name, $filter_value ) {
		$this->filters[ $filter_name ] = rawurlencode( $filter_value );

		return $this;
	}

	/**
	 * Returns the current array of filters.
	 *
	 * @return array The filters array.
	 * @since      1.0.0
	 */
	public function get_filters() {
		return $this->filters;
	}

	/**
	 * Sets the filters.
	 *
	 * @param array $filters The filters array.
	 * @return void
	 */
	public function set_filters( $filters ) {
		$this->filters = $filters;
	}

	/**
	 * Executes the request.
	 *
	 * First creates a filtered endpoint, and then passes that endpoint to the Guzzle
	 * Client. This client then handles making the actual request.
	 *
	 * @return ResponseInterface
	 * @throws GuzzleException Thrown when a non-200/300 response is received.
	 * @since      1.0.0
	 */
	public function execute() {
		$endpoint = $this->construct_endpoint_with_filters();
		if ( ! $this->configured ) {
			$this->refresh_api_values();

			$this->configure_client();
		}

		try {
			if ( $this->body ) {
				$response = $this->client->request(
					$this->method,
					$endpoint,
					[
						'body' => $this->body,
					]
				);
			} else {
				$response = $this->client->request( $this->method, $endpoint );
			}

			if ( $response instanceof ResponseInterface && $this->ac_debug && $this->logger ) {
				$this->logger->debug(
					'Received response',
					[
						'response_status_code' => $response->getStatusCode(),
						'response_headers'     => $response->getHeaders(),
						'response_body'        => $response->getBody()->getContents(),
					]
				);
			}
		} catch ( GuzzleException $e ) {
			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );

			if ( isset( $e ) && 422 === $e->getCode() ) {
				$this->logger->debug( $message, [ 'stack trace' => $stack_trace ] );
			} else {
				$this->logger->error( $message, [ 'stack trace' => $stack_trace ] );
			}

			return null;
		} catch ( \Exception $e ) {
			$message     = $e->getMessage();
			$stack_trace = $this->logger->clean_trace( $e->getTrace() );
			if ( isset( $e ) && 422 === $e->getCode() ) {
				$this->logger->debug( $message, [ 'stack trace' => $stack_trace ] );
			} else {
				$this->logger->error( $message, [ 'stack trace' => $stack_trace ] );
			}

			return null;
		} catch ( Throwable $t ) {
			$message     = $t->getMessage();
			$stack_trace = $this->logger->clean_trace( $t->getTrace() );
			if ( isset( $t ) && 422 === $t->getCode() ) {
				$this->logger->debug( $message, [ 'stack trace' => $stack_trace ] );
			} else {
				$this->logger->error( $message, [ 'stack trace' => $stack_trace ] );
			}

			return null;
		}

		return $response;
	}

	/**
	 * Creates an endpoint string with the filters appended.
	 *
	 * E.g.:
	 *
	 * ["email" => "example@example.com"]
	 *
	 * Becomes
	 *
	 * ?filters[email]=example@example.com
	 *
	 * @return string
	 * @since      1.0.0
	 */
	public function construct_endpoint_with_filters() {
		$endpoint = $this->endpoint;

		if ( $this->filters && count( $this->filters ) > 0 ) {
			$endpoint .= '?';

			foreach ( $this->filters as $filter => $value ) {
				$endpoint .= "filters[$filter]=$value&";
			}
		}

		/**
		 * If the last character of the string is '&', then set $endpoint
		 * to be $endpoint minus the last character
		 */
		if ( substr( $endpoint, - 1 ) === '&' ) {
			$endpoint = substr( $endpoint, 0, strlen( $endpoint ) - 1 );
		}

		return $endpoint;
	}

	/**
	 * Refreshes the values from the DB.
	 *
	 * This method is called typically only the first time the API settings are saved.
	 * The first time they're saved, this object is instantiated prior to the values being
	 * saved to the DB, so the Container constructs the object improperly. By refreshing the
	 * values, we fix this issue.
	 *
	 * @since      1.0.0
	 */
	private function refresh_api_values() {
		$settings = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );

		$this->api_uri = $settings['api_url'];
		$this->api_key = $settings['api_key'];
	}

	/**
	 * Formats the request method called into a usable HTTP Request type.
	 *
	 * Eg, get() => 'get' => 'GET'.
	 *
	 * @param string $method The method name called.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	private function format_request_method( $method ) {
		return strtoupper( $method );
	}

	/**
	 * Formats the endpoint to ensure the client works appropriately.
	 *
	 * We set a default Uri in the Guzzle Client. If and endpoint were
	 * passed in with a preceding slash, the Client would not use the
	 * default Uri and instead ONLY use the endpoint. This method removes the
	 * preceding slash to prevent this from happening.
	 *
	 * @param array $args The endpoint to format.
	 *
	 * @return string
	 * @since      1.0.0
	 */
	private function format_endpoint( $args ) {
		$endpoint = $args[0];

		$id = count( $args ) > 1 ? (string) $args[1] : null;

		$endpoint = str_replace( '/', '', $endpoint );

		if ( $id ) {
			$endpoint .= "/$id";
		}

		return $endpoint;
	}
}
