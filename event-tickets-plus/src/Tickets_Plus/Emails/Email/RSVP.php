<?php
/**
 * Class RSVP.
 *
 * @since 5.6.10
 *
 * @package TEC\Tickets_Plus\Emails
 */

namespace TEC\Tickets_Plus\Emails\Email;

use TEC\Tickets\Emails\Email\RSVP as RSVP_Email;
use TEC\Tickets\Emails\Email\Ticket as Ticket_Email;
use Tribe__Utils__Array as Arr;

/**
 * Class Ticket.
 *
 * @since 5.6.10
 *
 * @package TEC\Tickets_Plus\Emails
 */
class RSVP extends Components {
	/**
	 * The option key for the QR codes.
	 *
	 * @since 5.6.10
	 *
	 * @var string
	 */
	public static $option_ticket_include_qr_codes = 'tec-tickets-emails-rsvp-include-qr-codes';

	/**
	 * The option key for the attendee registration fields.
	 *
	 * @since 5.6.10
	 *
	 * @var string
	 */
	public static $option_ticket_include_ar_fields = 'tec-tickets-emails-rsvp-include-ar-fields';

	/**
	 * Add settings to Tickets Emails Ticket template settings page.
	 *
	 * @since 5.6.10
	 *
	 * @param array $fields Array of settings fields from Tickets Emails.
	 *
	 * @return array $fields Modified array of settings fields.
	 */
	public function filter_tec_tickets_emails_rsvp_settings( $fields ): array {
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
			'fieldset_attributes' => [
				'data-depends'                  => '#' . tribe( RSVP_Email::class )->get_option_key( 'use-ticket-email' ),
				'data-condition-is-not-checked' => true,
			],
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
			'fieldset_attributes' => [
				'data-depends'                  => '#' . tribe( RSVP_Email::class )->get_option_key( 'use-ticket-email' ),
				'data-condition-is-not-checked' => true,
			],
		];

		return $fields;
	}

	/**
	 * Maybe include Attendee Registration Fields.
	 *
	 * @since 5.6.10
	 *
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 * @return void
	 */
	public function maybe_include_ar_fields( $et_template ) {

		if ( ! $this->is_ar_fields_active( $et_template ) ) {
			return;
		}

		$args = $et_template->get_local_values();

		$this->render_ar_fields_template( $args );
	}

	/**
	 * Determines if Attendee Registrations Fields for Emails is Active.
	 *
	 * @since 5.6.10
	 *
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 *
	 * return boolean
	 */
	public function is_ar_fields_active( $et_template ) {
		$args = $et_template->get_local_values();

		if ( ! $args['email'] instanceof RSVP_Email ) {
			return false;
		}

		// handle live preview.
		$is_preview = Arr::get( $args, 'preview', false );
		if ( $is_preview && isset( $args['add_ar_fields'] ) ) {
			return tribe_is_truthy( $args['add_ar_fields'] );
		}

		$option_key = self::$option_ticket_include_ar_fields;
		if ( tribe( RSVP_Email::class )->is_using_ticket_email_settings() ) {
			$option_key = Ticket::$option_ticket_include_ar_fields;
		}

		return tribe_is_truthy( tribe_get_option( $option_key, true ) );
	}

	/**
	 * Maybe include QR Code template for RSVP Email.
	 *
	 * @since 5.7.0
	 *
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 */
	public function maybe_include_qr_code_template( $et_template ): void {
		$args  = $et_template->get_local_values();

		// Include QR code template.
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

		// bail out if the email is not a RSVP email or if the ticket email settings are being used.
		if ( ! $args['email'] instanceof RSVP_Email ) {
			return false;
		}

		$is_preview = Arr::get( $args, 'preview', false );
		if ( $is_preview && isset( $args['add_qr_codes'] ) ) {
			return tribe_is_truthy( $args['add_qr_codes'] );
		}

		$option_key = self::$option_ticket_include_qr_codes;
		// if using Ticket settings, then include from Ticket class.
		if ( tribe( RSVP_Email::class )->is_using_ticket_email_settings() ) {
			$option_key = Ticket::$option_ticket_include_qr_codes;
		}

		return tribe_is_truthy( tribe_get_option( $option_key, true ) );
	}

	/**
	 * Maybe include the styles for the RSVP email.
	 *
	 * @since 5.7.3
	 *
	 * @param \Tribe__Template $et_template
	 */
	public function maybe_include_styles( $et_template ) {
		$args  = $et_template->get_local_values();
		$email = $args['email'];

		// Bail out if the email is not a RSVP email or if the ticket email settings are being used.
		if ( ! $email instanceof RSVP_Email ) {
			return;
		}

		$this->render_styles( $args );
	}
}
