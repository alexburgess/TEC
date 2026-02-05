<?php
/**
 * Class Ticket.
 *
 * @since 5.6.10
 *
 * @package TEC\Tickets_Plus\Emails
 */

namespace TEC\Tickets_Plus\Emails\Email;

use TEC\Tickets\Emails\Email\Purchase_Confirmation_Email_Interface;
use Tribe__Utils__Array as Arr;

/**
 * Class Ticket.
 *
 * @since 5.6.10
 *
 * @package TEC\Tickets_Plus\Emails
 */
class Ticket extends Components {
	/**
	 * The option key for the QR codes.
	 *
	 * @since 5.6.10
	 *
	 * @var string
	 */
	public static $option_ticket_include_qr_codes = 'tec-tickets-emails-ticket-include-qr-codes';

	/**
	 * The option key for the attendee registration fields.
	 *
	 * @since 5.6.10
	 *
	 * @var string
	 */
	public static $option_ticket_include_ar_fields = 'tec-tickets-emails-ticket-include-ar-fields';

	/**
	 * Add settings to Tickets Emails Ticket template settings page.
	 *
	 * @since 5.6.10
	 *
	 * @param array $fields Array of settings fields from Tickets Emails.
	 *
	 * @return array $fields Modified array of settings fields.
	 */
	public function filter_tec_tickets_emails_ticket_settings( $fields ): array {
		$ticket_label_plural_lower                       = esc_html( tribe_get_ticket_label_plural_lowercase( 'check_in_app' ) );
		$fields[ self::$option_ticket_include_qr_codes ] = [
			'type'            => 'checkbox_bool',
			'label'           => esc_html__( 'QR Codes', 'event-tickets-plus' ),
			'tooltip'         => esc_html(
				sprintf(
					// Translators: %1$s: 'tickets' label (plural, lowercase).
					__( 'Include QR codes in %1$s emails (required for Event Tickets Plus App).', 'event-tickets-plus' ),
					$ticket_label_plural_lower
				)
			),
			'default'         => true,
			'validation_type' => 'boolean',
		];

		$fields[ self::$option_ticket_include_ar_fields ] = [
			'type'            => 'checkbox_bool',
			'label'           => esc_html__( 'Attendee Registration Fields', 'event-tickets-plus' ),
			'tooltip'         => esc_html(
				sprintf(
					// Translators: %1$s: 'tickets' label (plural, lowercase).
					__( 'Include Attendee Registration fields in your %1$s emails.', 'event-tickets-plus' ),
					$ticket_label_plural_lower
				)
			),
			'default'         => true,
			'validation_type' => 'boolean',
		];

		return $fields;
	}

	/**
	 * Maybe include Attendee Registration Fields.
	 *
	 * @since 5.6.10
	 *
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 *
	 * @return void
	 */
	public function maybe_include_ar_fields( $et_template ) {
		if ( ! $this->is_ar_fields_active( $et_template ) ) {
			return;
		}

		$args       = $et_template->get_local_values();

		$this->render_ar_fields_template( $args );
	}

	/**
	 * Determines if Attendee Registrations Fields for Emails is Active.
	 *
	 * @since 5.6.10
	 *
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 *
	 * @return boolean
	 */
	public function is_ar_fields_active( $et_template ): bool {
		$args  = $et_template->get_local_values();

		if ( ! $args['email'] instanceof Purchase_Confirmation_Email_Interface ) {
			return false;
		}

		// handle live preview.
		$is_preview  = tribe_is_truthy( Arr::get( $args, 'preview', false ) );
		$preview_arf = tribe_is_truthy( Arr::get( $args, 'add_ar_fields', false ) );
		if ( $is_preview ) {
			return $preview_arf;
		}

		return tribe_is_truthy( tribe_get_option( self::$option_ticket_include_ar_fields, true ) );
	}

	/**
	 * Maybe include QR Code template for Ticket Email.
	 *
	 * @since 5.7.0
	 *
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 */
	public function maybe_include_qr_code_template( $et_template ): void {
		$args  = $et_template->get_local_values();
		$email = $args['email'];

		// bail out if the email is not a RSVP email or if the ticket email settings are being used.
		if ( ! $email instanceof Purchase_Confirmation_Email_Interface ) {
			return;
		}

		$args['include_qr'] = $this->should_show_qr_code( $et_template );
		$this->render_qr_code_template( $args );
	}

	/**
	 * Determines if QR code for Emails is Active.
	 *
	 * @since 5.7.0
	 *
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 *
	 * @return boolean
	 */
	public function should_show_qr_code( $et_template ): bool {
		$args  = $et_template->get_local_values();

		if ( ! $args['email'] instanceof Purchase_Confirmation_Email_Interface ) {
			return false;
		}

		// handle live preview.
		$is_preview = tribe_is_truthy( Arr::get( $args, 'preview', false ) );
		$preview_qr = tribe_is_truthy( Arr::get( $args, 'add_qr_codes', false ) );
		if ( $is_preview ) {
			return $preview_qr;
		}

		return tribe_is_truthy( tribe_get_option( self::$option_ticket_include_qr_codes, true ) );
	}

	/**
	 * Maybe include the styles for the ticket email.
	 *
	 * @since 5.7.3
	 *
	 * @param \Tribe__Template $et_template
	 */
	public function maybe_include_styles( $et_template ): void {
		$args  = $et_template->get_local_values();
		$email = $args['email'];

		// Bail out if the email is not a Ticket email or if the ticket email settings are being used.
		if ( ! $email instanceof Purchase_Confirmation_Email_Interface ) {
			return;
		}

		$this->render_styles( $args );
	}
}
