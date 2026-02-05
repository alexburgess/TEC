<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;
use WP_Error;

class Handle_Pass_Redirect extends Modifier_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'apple-wallet/handle_pass_redirect';
	}

	/**
	 * Adds the actions required by the PDF passes.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function add_actions(): void {
		add_action( 'template_redirect', [ $this, 'redirect' ] );
	}

	/**
	 * Removes the actions required by the PDF passes.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function remove_actions(): void {
		remove_action( 'template_redirect', [ $this, 'redirect' ] );
	}

	/**
	 * Manage the redirect to generate the Apple Pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return true|WP_Error
	 */
	public function redirect() {
		if ( empty( tribe_get_request_var( 'apple-wallet-pass' ) ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-apple-wallet-empty-param', 'The `apple-wallet-pass` parameter is empty.' );
		}

		$attendee_id   = (int) tribe_get_request_var( 'attendee_id' );
		$security_code = (string) tribe_get_request_var( 'security_code' );

		if ( empty( $attendee_id ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-apple-wallet-pass-ticket-id', 'The `attendee_id` parameter is empty.' );
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
}
