<?php

/**
 * Provides interface to logging
 *
 * @link       https://www.activecampaign.com/
 * @since      1.0.0
 *
 * @package    Activecampaign_For_Woocommerce_Logger
 * @subpackage Activecampaign_For_Woocommerce/includes
 */

use AcVendor\Psr\Log\LoggerInterface;
use Activecampaign_For_Woocommerce_Request_Id_Service as RequestIdService;

/**
 * Logger object
 *
 * @package    Activecampaign_For_Woocommerce_Logger
 * @subpackage Activecampaign_For_Woocommerce/includes
 * @author     acteamintegrations <team-integrations@activecampaign.com>
 */
class Activecampaign_For_Woocommerce_Logger extends WC_Log_Handler_DB implements LoggerInterface {

	/**
	 * Instance of the WooCommerce logger.
	 *
	 * @var WC_Logger
	 */
	private $logger;

	/**
	 * The logger context parameter.
	 *
	 * @var array
	 */
	private $context;

	/**
	 * The relative path to the log directory.
	 *
	 * @var string
	 */
	private $path_to_log_directory;

	/**
	 * Whether or not to send debug statements over the INFO level
	 *
	 * @var bool
	 */
	private $ac_debug;



	/**
	 * Logger constructor.
	 *
	 * @param WC_Logger_Interface|null $logger      optional logger parameter used for testing.
	 * @param string                   $plugin_name Stylized name of our plugin.
	 * @param string|null              $ac_debug    Whether or not to enable debug logging via the INFO level.
	 */
	public function __construct(
		WC_Logger_Interface $logger = null,
		$plugin_name = ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB,
		$ac_debug = null
	) {
		$this->path_to_log_directory = wp_upload_dir()['basedir'] . '/wc-logs';
		$this->context               = array( 'source' => $plugin_name );
		if ( ! $this->logDirectoryExists() ) {
			$this->createLogDirectory();
		}
		$this->logger = null !== $logger ? $logger : wc_get_logger();

		if ( is_string( $ac_debug ) ) {
			$this->ac_debug = '1' === $ac_debug;
		} else {
			$settings       = get_option( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_DB_OPTION_NAME );
			$this->ac_debug = isset( $settings['ac_debug'] ) ? '1' === $settings['ac_debug'] : false;
		}
	}

	// phpcs:disable

	/**
	 * {@inheritdoc}
	 */
	public function emergency( $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->emergency( $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function alert( $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->alert( $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function critical( $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->critical( $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function error( $message, array $context = array() ) {
		$this->add_wc_log_entry($message, $context, 'error');
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->error( $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function warning( $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->warning( $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function notice( $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->notice( $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function info( $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->info( $message, $context );
	}

	/**
	 * {@inheritdoc}
	 */
	public function debug( $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		if ( $this->ac_debug ) {
			// If debug logging is turned on in the AC plugin settings, send all debug logs via the INFO level. Bypasses PHP settings.
			$this->logger->info( "[ActiveCampaign Debug] $message", $context );
		}
		// DO NOT record debug messages if debug is off. It's a waste of log space and makes a mess.
	}

	/**
	 * {@inheritdoc}
	 */
	public function log( $level, $message, array $context = array() ) {
		$context = $this->resolveContext( $context );
		$message = $this->formatMessageWithContext( $message, $context );
		$this->logger->log( $level, $message, $context );
	}

	/**
	 * Format the logger message so it has the context concatenated to it.
	 *
	 * @param string $message The logger message.
	 * @param array $context Additional information for log handlers.
	 *
	 * @return string
	 */
	public function formatMessage( $message, $context = array() ) {
		$formatted_message = $message . ' ' . json_encode( $context );

		return $formatted_message;
	}
	// phpcs:enable

	/**
	 * Return the default context of the log.
	 *
	 * @return array The default context for the log.
	 */
	public function getDefaultContext() {
		return $this->context;
	}

	/**
	 * Merge the default error context into the provided context.
	 *
	 * @param array $context The error context.
	 *
	 * @return array
	 */
	private function resolveContext( array $context ) {
		$context = array_merge( $context, $this->context );

		return $context;
	}

	/**
	 * Check if the log directory exists.
	 *
	 * @return bool
	 */
	private function logDirectoryExists() {
		return file_exists( $this->path_to_log_directory );
	}

	/**
	 * Create the log directory.
	 *
	 * @return bool
	 */
	private function createLogDirectory() {
		return mkdir( $this->path_to_log_directory );
	}

	/**
	 * Format the message with the given context array.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 *
	 * @return string
	 */
	private function formatMessageWithContext( $message, array &$context ) {
		// Add the request ID to the log entry
		$context['request_id'] = RequestIdService::get_request_id();

		// Make absolutely sure we sanitize sensitive data
		$formatted = $message . "\nContext: " . wp_json_encode( $context, JSON_PRETTY_PRINT );
		return preg_replace( '/(user_pass).*[,}]|(password).*[,}]|(cardnumber).*[,}]|(exp.date).*[,}]|(cvc).*[,}]/i', '$1\":\"*REMOVED*\"', $formatted );

		// The logger doesn't seem to actually use the context array, so we'll merge it in with the message
		// return $message . "\nContext: " . wp_json_encode( $context, JSON_PRETTY_PRINT );
	}


	/**
	 * Replaces the trace array with a clean version that only contains specific details.
	 *
	 * @param array $trace The stack trace.
	 *
	 * @return array The replacement stack trace.
	 */
	public function clean_trace( $trace ) {
		$result = [];

		// Only retrieve class, function, and line for the first 3 trace results
		if ( isset( $trace[0]['class'] ) ) {
			$result[0]['class'] = $trace[0]['class'];
		}
		if ( isset( $trace[0]['function'] ) ) {
			$result[0]['function'] = $trace[0]['function'];
		}

		if ( isset( $trace[0]['line'] ) ) {
			$result[0]['line'] = $trace[0]['line'];
		}

		if ( isset( $trace[1]['class'] ) ) {
			$result[1]['class'] = $trace[1]['class'];
		}

		if ( isset( $trace[1]['function'] ) ) {
			$result[1]['function'] = $trace[1]['function'];
		}

		if ( isset( $trace[1]['line'] ) ) {
			$result[1]['line'] = $trace[1]['line'];
		}

		if ( isset( $trace[2]['class'] ) ) {
			$result[2]['class'] = $trace[2]['class'];
		}

		if ( isset( $trace[2]['function'] ) ) {
			$result[2]['function'] = $trace[2]['function'];
		}

		if ( isset( $trace[2]['line'] ) ) {
			$result[2]['line'] = $trace[2]['line'];
		}

		return $result;
	}

	/**
	 * Clears the WooCommerce error log of our errors.
	 */
	public function clear_wc_error_log() {
		return WC_Log_Handler_DB::clear( ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB );
	}

	/**
	 * Adds a log entry to the WooCommerce log.
	 *
	 * @param string $message The message to be saved.
	 * @param array  $context The context from the event.
	 * @param string $level The log level.
	 *
	 * @throws Throwable The error thrown.
	 */
	private function add_wc_log_entry( $message, $context, $level ) {
		$date = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		try {
			// Post this error to the woocommerce log
			WC_Log_Handler_DB::add( $date->getTimestamp(), $level, $message, ACTIVECAMPAIGN_FOR_WOOCOMMERCE_PLUGIN_NAME_KEBAB, $context );
		} catch ( Throwable $t ) {
			$this->info(
				'There was an issue adding an entry to the WooCommerce log',
				[
					'message' => $t->getMessage(),
					'trace'   => $this->clean_trace( $t->getTrace() ),
				]
			);
		}

	}

}
