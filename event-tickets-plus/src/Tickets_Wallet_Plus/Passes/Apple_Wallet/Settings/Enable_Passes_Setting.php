<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Checkbox_Setting_Abstract;

/**
 * Class Background_Color_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings
 */
class Enable_Passes_Setting extends Checkbox_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_apple_pass_enabled';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Enable Apple Wallet passes', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-apple-pass-enabled';
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
		$kb_link = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener nofollow">%2$s</a>',
			esc_url( 'https://evnt.is/event-tickets-wallet-plus' ),
			esc_html__( 'Learn more in our Knowledgebase', 'event-tickets-plus' )
		);

		return sprintf(
			// Translators: $1%s is the link to the KB article.
			esc_html__(
				'Offer Apple Wallet passes for users who purchase tickets and respond to RSVPs. Wallet passes are available on ticket and RSVP emails, checkout success page, and the purchaser\'s My Tickets view. %1$s.',
				'event-tickets-wallet-plus'
			),
			$kb_link
		);
	}
}
