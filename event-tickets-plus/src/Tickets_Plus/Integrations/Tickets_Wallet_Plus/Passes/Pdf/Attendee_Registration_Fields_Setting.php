<?php

namespace TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Pdf;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Checkbox_Setting_Abstract;

/**
 * Class Attendee_Registration_Fields_Setting.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Pdf
 */
class Attendee_Registration_Fields_Setting extends Checkbox_Setting_Abstract {

	/**
	 * Get the setting slug.
	 *
	 * @since 5.8.0
	 *
	 * @return string The setting slug.
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_pdf_include_attendee_registration_fields';
	}

	/**
	 * Get the setting label.
	 *
	 * @since 5.8.0
	 *
	 * @return string The setting label.
	 */
	public function get_label(): string {
		return esc_html__( 'Attendee registration fields', 'event-tickets-plus' );
	}

	/**
	 * Get the setting key.
	 *
	 * @since 5.8.0
	 *
	 * @return string The setting key.
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-pdf-include-attendee-registration-fields';
	}

	/**
	 * Get the setting default.
	 *
	 * @since 5.8.0
	 *
	 * @return bool The setting default.
	 */
	public function get_default(): bool {
		return true;
	}

	/**
	 * Get the setting tooltip.
	 *
	 * @since 5.8.0
	 *
	 * @return string|null The setting tooltip.
	 */
	public function get_tooltip(): ?string {
		return esc_html__( 'Include attendee registration fields in PDF tickets.', 'event-tickets-plus' );
	}
}
