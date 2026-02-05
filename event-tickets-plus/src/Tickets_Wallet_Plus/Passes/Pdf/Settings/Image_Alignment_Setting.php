<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings;

use TEC\Tickets\Emails\Admin\Settings as Email_Settings;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Dropdown_Setting_Abstract;

/**
 * Class Image_Alignment_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings
 */

class Image_Alignment_Setting extends Dropdown_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_pdf_header_image_alignment';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Image alignment', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-pdf-header-image-alignment';
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): string {
		$default_value = tribe_get_option( Email_Settings::$option_header_image_alignment, 'left' );
		return $default_value;
	}

	/**
	 * @inheritDoc
	 */
	public function get_options(): array {
		return [
			'left'   => esc_html__( 'Left', 'event-tickets-plus' ),
			'center' => esc_html__( 'Center', 'event-tickets-plus' ),
			'right'  => esc_html__( 'Right', 'event-tickets-plus' ),
		];
	}
}
