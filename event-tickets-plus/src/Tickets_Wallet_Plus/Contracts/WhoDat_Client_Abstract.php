<?php

namespace TEC\Tickets_Wallet_Plus\Contracts;

use Tribe__Utils__Array as Arr;
use Tribe__Log as Log;

/**
 * Class WhoDat_Client_Abstract
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Contracts
 */
abstract class WhoDat_Client_Abstract {

	/**
	 * Public WhoDat URL, used to authenticate accounts with gateway payment providers
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	protected string $base_url = 'https://whodat.theeventscalendar.com';

	/**
	 * Public WhoDat URL, used to authenticate accounts with gateway payment providers
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	protected string $endpoint;

	/**
	 * Returns the gateway-specific endpoint to use
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	protected function get_endpoint(): string {
		return $this->filter_endpoint( $this->endpoint );
	}

	/**
	 * Filters the endpoint to use for the WhoDat client.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $endpoint The endpoint to use.
	 *
	 * @return string
	 */
	protected function filter_endpoint( string $endpoint ): string {
		/**
		 * Filter the endpoint to use for the WhoDat client.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param string                 $endpoint The endpoint to use.
		 * @param WhoDat_Client_Abstract $client   The WhoDat client.
		 */
		$endpoint = (string) apply_filters( 'tec_tickets_wallet_plus_whodat_client_endpoint', $endpoint, $this );

		return trim( $endpoint, '/' );
	}

	/**
	 * Returns the base WhoDat URL to use.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	protected function get_base_url(): string {
		return $this->filter_base_url( $this->base_url );
	}

	/**
	 * Filters the base url to use for the WhoDat client.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $base_url The base url to use.
	 *
	 * @return string
	 */
	protected function filter_base_url( string $base_url ): string {
		/**
		 * Filter the base url to use for the WhoDat client.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param string                 $endpoint The base url to use.
		 * @param WhoDat_Client_Abstract $client   The WhoDat client.
		 */
		$base_url = (string) apply_filters( 'tec_tickets_wallet_plus_whodat_client_base_url', $base_url, $this );

		return trim( $base_url, '/' );
	}

	/**
	 * Returns the URL to use for the WhoDat client.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string|null $append     The endpoint to append to the URL.
	 * @param array       $query_args The query arguments to use.
	 *
	 * @return string
	 */
	protected function get_url( ?string $append = null, array $query_args = [] ): string {
		$url = rtrim( "{$this->get_base_url()}/{$this->get_endpoint()}", '/' );

		if ( ! empty( $append ) ) {
			$url .= '/' . ltrim( $append, '/' );
		}

		return add_query_arg( $query_args, $url );
	}

	/**
	 * Helper around wp_remote_post to make POST requests to the WhoDat API.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string|null $endpoint          The endpoint to use.
	 * @param array       $query_args        The query arguments to append to the URL.
	 * @param array       $request_arguments The request arguments to use.
	 *
	 * @return array|\WP_Error|string
	 */
	protected function post( ?string $endpoint = null, array $query_args = [], array $request_arguments = [] ) {
		// Get the URL to make the POST request.
		$url = $this->get_url( $endpoint, $query_args );

		// Check if the request wants to accept JSON response.
		$is_json_request = strtolower( (string) Arr::get( $request_arguments, [ 'headers', 'Accepts' ], (string) Arr::get( $request_arguments, [ 'headers', 'accepts' ] ) ) ) === 'application/json';

		$defaults = [
			'headers' => [],
			'body'    => [],
			'timeout' => 10,
		];

		$data = array_merge( $defaults, $request_arguments );

		// Encode the body as JSON for the POST.
		if ( isset( $data['body'] ) ) {
			$data['body'] = json_encode( $data['body'] );
		}

		// Make the POST request.
		$response = wp_remote_post( $url, $data );

		// If there is an error, log it and return.
		if ( is_wp_error( $response ) ) {
			$this->log( $response->get_error_message(), $url );
			$response->add_data( [ 'url' => $url, 'request_args' => $request_arguments ] );

			return $response;
		}

		// Check the HTTP response code.
		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $response_code ) {
			$this->log( "Failed request with status code: {$response_code}", $url );

			return new \WP_Error(
				'tec_tickets_wallet_plus_whodat_error',
				wp_remote_retrieve_body( $response ),
				[
					'response'      => $response,
					'url'           => $url,
					'request_args'  => $request_arguments,
					'response_code' => $response_code,
				]
			);
		}

		// Extract the response body.
		$body = wp_remote_retrieve_body( $response );

		// If the response is expected to be JSON, decode it.
		if ( $is_json_request ) {
			try {
				return json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
			} catch ( \JsonException $error ) {
				$this->log( "Failed json_decode: {$error->getMessage()}", $url );

				return new \WP_Error(
					'tec_tickets_wallet_plus_json_decode_error',
					$error->getMessage(),
					[
						'error_code'   => $error->getCode(),
						'response'     => $response,
						'url'          => $url,
						'request_args' => $request_arguments,
					]
				);
			}
		}

		return $body;
	}

	/**
	 * Logs an error message.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $message The message to log.
	 * @param string $url     The URL that was requested.
	 *
	 * @return void
	 */
	protected function log( string $message, string $url ): void {
		$log = sprintf(
			'[whodat][%s] - %s',
			$url,
			$message
		);
		do_action( 'tribe_log', Log::ERROR, $log );
	}
}
