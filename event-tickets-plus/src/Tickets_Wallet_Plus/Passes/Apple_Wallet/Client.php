<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use TEC\Tickets_Wallet_Plus\Contracts\WhoDat_Client_Abstract;

/**
 * Class Client.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Client extends WhoDat_Client_Abstract {

	/**
	 * Endpoint for the Apple Wallet API.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string $endpoint
	 */
	protected string $endpoint = 'wallet/v1/apple/';

	/**
	 * Sends a POST request to the WhoDat API, this request will have the params encoded on the body as json.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param Package $pass The pass object.
	 * @param bool    $json Whether to return the response as json or not.
	 *
	 * @return array|\WP_Error Will return Array when successful any other case will return a WP_Error.
	 */
	public function get_pass_package( Package $pass, bool $json = true ) {
		if ( ! $pass->validate()->is_valid() ) {
			return $pass->get_error();
		}

		$request_arguments = [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body'    => $pass->as_array(),
		];
		$query_args = [];

		if ( $json ) {
			$request_arguments['headers']['Accepts'] = 'application/json';
			$query_args['format'] = 'json';
		}

		$pass_package = $this->post( 'pass', $query_args, $request_arguments );

		return $pass_package;
	}
}
