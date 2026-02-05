<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings;

use TEC\Tickets\Emails\Admin\Settings as Email_Settings;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Color_Setting_Abstract;

/**
 * Class Header_Color_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings
 */
class Header_Color_Setting extends Color_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_pdf_header_color';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Header color', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_tooltip(): ?string {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-pdf-header-color';
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): string {
		$default_value = tribe_get_option( Email_Settings::$option_header_bg_color, '#50B078' );
		return $default_value;
	}
}
