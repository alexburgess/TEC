<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Checkbox_Setting_Abstract;

/**
 * Class Include_Credit_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings
 */
class Include_Credit_Setting extends Checkbox_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_pdf_include_credit';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Footer credit', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-pdf-include-credit';
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
		return esc_html__( 'Include "Powered by Event Tickets" in the footer.', 'event-tickets-plus' );
	}
}
