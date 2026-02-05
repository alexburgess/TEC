<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Color_Setting_Abstract;

/**
 * Class Pass_Color_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings
 */

class Pass_Color_Setting extends Color_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_apple_pass_color';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Pass color', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-apple-pass-color';
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): string {
		return tribe_get_option( 'tec-tickets-emails-header-bg-color', '#50B078' );
	}
}
