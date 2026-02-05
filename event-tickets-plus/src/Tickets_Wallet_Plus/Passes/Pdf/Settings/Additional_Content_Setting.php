<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Wysiwyg_Setting_Abstract;

/**
 * Class Additional_Content_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings
 */
class Additional_Content_Setting extends Wysiwyg_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_pdf_additional_content';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Additional content', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-pdf-additional-content';
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): string {
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function get_tooltip(): ?string {
		return esc_html__( 'Add additional instructions and helpful information to your PDF.', 'event-tickets-plus' );
	}
}
