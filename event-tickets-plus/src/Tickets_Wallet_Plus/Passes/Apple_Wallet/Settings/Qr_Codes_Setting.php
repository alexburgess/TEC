<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Checkbox_Setting_Abstract;

/**
 * Class Qr_Code__Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings
 */

class Qr_Codes_Setting extends Checkbox_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_apple-wallet_include_qr_codes';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'QR codes', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-apple-wallet-include-qr-codes';
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
		return esc_html__( 'Include QR codes in wallet passes (required for Event Tickets Plus App)', 'event-tickets-plus' );
	}
}
