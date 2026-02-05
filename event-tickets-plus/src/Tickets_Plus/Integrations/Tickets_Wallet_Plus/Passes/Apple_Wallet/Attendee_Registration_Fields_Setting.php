<?php

namespace TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Checkbox_Setting_Abstract;

/**
 * Class Attendee_Registration_Fields_Setting
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Attendee_Registration_Fields_Setting extends Checkbox_Setting_Abstract {

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_apple_wallet_include_attendee_registration_fields';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Attendee registration fields', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-apple-wallet-include-attendee-registration-fields';
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function get_tooltip(): ?string {
		return esc_html__( 'Include attendee registration fields in wallet passes.', 'event-tickets-plus' );
	}
}
