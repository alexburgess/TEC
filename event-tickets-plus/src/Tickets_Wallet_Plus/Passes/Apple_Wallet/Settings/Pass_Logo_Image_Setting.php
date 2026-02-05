<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings;

use TEC\Tickets\Emails\Admin\Settings as Email_Settings;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Image_Setting_Abstract;

/**
 * Class Pass_Logo_Image_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings
 */

class Pass_Logo_Image_Setting extends Image_Setting_Abstract {
    /**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_apple_icon';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Logo', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-apple-icon';
	}

	/**
	 * @inheritDoc
	 */
	public function get_tooltip(): string {
		return esc_html__( 'Image size should be 300 x 160 pixels maximum. If no image is set, the site title will display.', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): string {
		// Get the email image URL.
		$image_url = tribe_get_option( Email_Settings::$option_header_image_url );
		if ( empty( $image_url ) ) {
			return '';
		}

		// Get and return the attachment ID of the image.
		global $wpdb;
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s' LIMIT 1;", $image_url ) );
		if ( empty( $attachment ) ) {
			return '';
		}

		return (string) $attachment[0];
	}
}
