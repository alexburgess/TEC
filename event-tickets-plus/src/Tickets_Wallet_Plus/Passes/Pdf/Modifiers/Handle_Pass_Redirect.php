<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Pass;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Sample;
use WP_Error;

class Handle_Pass_Redirect extends Modifier_Abstract {

	/**
	 * Get key for pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public static $url_get_key_pass   = 'tec-tickets-wallet-plus-pdf';

	/**
	 * Get key for sample pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public static $url_get_key_sample = 'tec-tickets-wallet-plus-pdf-sample';

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'pdf/handle_pass_redirect';
	}

	/**
	 * @inheritDoc
	 */
	public function add_actions(): void {
		add_action( 'template_redirect', [ $this, 'redirect' ] );
		add_action( 'template_redirect', [ $this, 'redirect_sample' ] );
	}

	/**
	 * @inheritDoc
	 */
	public function remove_actions(): void {
		remove_action( 'template_redirect', [ $this, 'redirect' ] );
		remove_action( 'template_redirect', [ $this, 'redirect_sample' ] );
	}

	/**
	 * Manage the redirect to generate the PDF Pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return true|WP_Error
	 */
	public function redirect() {
		if ( ! tribe_get_request_var( static::$url_get_key_pass ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-pdf-pass-base-param-missing', 'The `' . static::$url_get_key_pass . '` parameter is empty.' );
		}

		$attendee_id   = (int) tribe_get_request_var( 'attendee_id' );
		$security_code = (string) tribe_get_request_var( 'security_code' );

		if ( empty( $attendee_id ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-pdf-pass-ticket-id-missing', 'The `attendee_id` parameter is empty.' );
		}

		if ( empty( $security_code ) ) {
			new WP_Error( 'tec-tickets-wallet-plus-apple-wallet-pass-security-code', 'The `security_code` parameter is empty.' );
		}

		/** @var \Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		$service_provider = $data_api->get_ticket_provider( $attendee_id );
		if (
			empty( $service_provider->security_code )
			|| get_post_meta( $attendee_id, $service_provider->security_code, true ) !== $security_code
		) {
			return new WP_Error( 'tec-tickets-wallet-plus-apple-wallet-pass-security-code-not-valid', 'The `security_code` parameter is not valid.' );
		}

		// Add check for attendee data.
		$attendee = $service_provider->get_attendees_by_id( $attendee_id );
		$attendee = reset( $attendee );
		if ( ! is_array( $attendee ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-apple-wallet-pass-attendee-not-found', 'Attendee not found.' );
		}

		Pass::from_attendee( $attendee_id )->create();

		return true;
	}

	/**
	 * Manage the redirect to generate the Apple Pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return true|WP_Error
	 */
	public function redirect_sample() {
		if ( ! tribe_get_request_var( self::$url_get_key_sample ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-pdf-pass-base-param-missing', 'The `' . self::$url_get_key_sample . '` parameter is empty.' );
		}

		// Check nonce.
		if ( ! check_admin_referer( self::$url_get_key_sample ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-pdf-pass-nonce-fail', 'User failed nonce check.' );
		}

		tribe( Sample::class )->create();

		return true;
	}
}
