<?php

use TEC\Tickets\QR\Settings;
use TEC\Tickets\QR\Connector;

/**
 * Class Tribe__Tickets_Plus__QR
 */
class Tribe__Tickets_Plus__QR {

	public function __construct() {
		add_action( 'tribe_tickets_ticket_email_ticket_bottom', [ $this, 'inject_qr' ] );
	}

	/**
	 * Generates the QR image, stores is locally and injects it into the tickets email
	 *
	 * @since 5.8.0 uses the code from the new Tickets QR connector.
	 *
	 * @param array $ticket The ticket array.
	 *
	 * @return string
	 */
	public function inject_qr( $ticket ) {
		if ( ! tribe( Settings::class )->is_enabled( $ticket ) ) {
			return null;
		}

		$connector = tribe( Connector::class );

		$link = $connector->get_checkin_url( $ticket['qr_ticket_id'], $ticket['event_id'], $ticket['security_code'] );
		$qr   = $connector->get_image_url_for_link( $link );

		if ( ! $qr ) {
			return;
		}

		// echo QR template for email
		tribe_tickets_get_template_part( 'tickets-plus/email-qr', null, [ 'qr' => $qr ], true );
	}

	/**
	 * Processes the links coming from QR codes and decides what to do:
	 *   - If the user is logged in and has proper permissions, it will redirect
	 *     to the attendees screen for the event, and will automatically check in the user.
	 *
	 *   - If the user is not logged in and/or does not have proper permissions, it will
	 *     redirect to the homepage of the event (front end single event view).
	 */
	public function handle_redirects() {
		_deprecated_function( __METHOD__, '5.8.0', 'TEC\Tickets\QR\Observer::handle_checkin_redirect' );
	}

	/**
	 * Check if user is authorized to Checkin Ticket
	 *
	 * @since 4.8.1
	 *
	 * @deprecated 5.8.0
	 *
	 * @param int    $event_id      The event post ID.
	 * @param int    $ticket_id     The ticket post ID.
	 * @param string $security_code The ticket security code.
	 *
	 * @return array
	 */
	public function authorized_checkin( $event_id, $ticket_id, $security_code ) {
		_deprecated_function( __METHOD__, '5.8.0', 'TEC\Tickets\QR\Observer::authorized_check_in' );
	}

	/**
	 * Show a notice so the user knows the ticket was checked in.
	 */
	public function admin_notice() {
		_deprecated_function( __METHOD__, '5.8.0', 'TEC\Tickets\QR\Observer::legacy_handle_admin_notice' );
	}

	/**
	 * Get QR Code URL from ticket.
	 *
	 * @since 5.6.10
	 *
	 * @param array $ticket
	 *
	 * @return string|null
	 */
	public function get_qr_url( $ticket ) {
		_deprecated_function( __METHOD__, '5.8.0', 'TEC\Tickets\QR\Connector::get_image_url_from_ticket_data' );
	}
}
