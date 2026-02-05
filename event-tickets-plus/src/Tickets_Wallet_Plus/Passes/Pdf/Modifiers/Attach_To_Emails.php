<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers;

use TEC\Tickets\Emails\Email\RSVP as RSVP_Email;
use TEC\Tickets\Emails\Email\Ticket as Ticket_Email;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Emails\Settings\RSVP_Include_Passes_Setting;
use TEC\Tickets_Wallet_Plus\Emails\Settings\Ticket_Include_Passes_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Controller;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Pass;

/**
 * Class Include_To_Rsvp
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers
 */
class Attach_To_Emails extends Modifier_Abstract {

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'pdf/attach_to_emails';
	}

	/**
	 * @inheritDoc
	 */
	protected function add_filters(): void {
		$ticket_slug = tribe( Ticket_Email::class )->slug;
		$rsvp_slug   = tribe( RSVP_Email::class )->slug;
		add_filter( "tec_tickets_emails_dispatcher_{$ticket_slug}_attachments", [ $this, 'add_ticket_email_attachments' ], 10, 2 );
		add_filter( "tec_tickets_emails_dispatcher_{$rsvp_slug}_attachments", [ $this, 'add_rsvp_email_attachments' ], 10, 2 );
	}

	/**
	 * @inheritDoc
	 */
	protected function remove_filters(): void {
		$ticket_slug = tribe( Ticket_Email::class )->slug;
		$rsvp_slug   = tribe( RSVP_Email::class )->slug;
		remove_filter( "tec_tickets_emails_dispatcher_{$ticket_slug}_attachments", [ $this, 'add_ticket_email_attachments' ], 10 );
		remove_filter( "tec_tickets_emails_dispatcher_{$rsvp_slug}_attachments", [ $this, 'add_rsvp_email_attachments' ], 10 );
	}

	/**
	 * Add PDF attachment to Ticket email.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array      $attachments The email attachments.
	 * @param Dispatcher $dispatcher  The email dispatcher.
	 *
	 * @return array The modified email attachments.
	 */
	public function add_ticket_email_attachments( $attachments, $dispatcher ): array {
		// If the setting is not enabled, return the attachments.
		$pdf_pass_slug = tribe( Controller::class )->get_slug();
		if ( ! tribe( Ticket_Include_Passes_Setting::class )->is_pass_included( $pdf_pass_slug ) ) {
			return $attachments;
		}

		return $this->add_attachments( $attachments, $dispatcher );
	}

	/**
	 * Add PDF attachment to RSVP email.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array      $attachments The email attachments.
	 * @param Dispatcher $dispatcher  The email dispatcher.
	 *
	 * @return array The modified email attachments.
	 */
	public function add_rsvp_email_attachments( $attachments, $dispatcher ): array {
		// If the setting is not enabled, return the attachments.
		$pdf_pass_slug = tribe( Controller::class )->get_slug();
		if ( ! tribe( RSVP_Include_Passes_Setting::class )->is_pass_included( $pdf_pass_slug ) ) {
			return $attachments;
		}

		return $this->add_attachments( $attachments, $dispatcher );
	}

	/**
	 * Add PDF attachments to email.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array      $attachments The email attachments.
	 * @param Dispatcher $dispatcher  The email dispatcher.
	 *
	 * @return array The modified email attachments.
	 */
	public function add_attachments( $attachments, $dispatcher ): array {
		// Create the PDF pass for each attendee and add them to the attachments.
		$attendees = $dispatcher->get_email()->get( 'tickets' );
		foreach ( $attendees as $attendee ) {
			$attendee_id = isset( $attendee['ID'] ) ? $attendee['ID'] : $attendee['attendee_id'];
			$pass = tribe( Pass::class )->from_attendee( $attendee_id );
			$pdf  = $pass->get_file_path();
			if ( $pdf ) {
				$attachments[] = $pdf;
			}
		}
		return $attachments;
	}
}
