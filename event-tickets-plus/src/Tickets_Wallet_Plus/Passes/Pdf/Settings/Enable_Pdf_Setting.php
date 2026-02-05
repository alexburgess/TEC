<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Toggle_Setting_Abstract;

/**
 * Class Enable_Pdf_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings
 */

class Enable_Pdf_Setting extends Toggle_Setting_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'tec_tickets_wallet_plus_pdf_enabled';
	}

	/**
	 * @inheritDoc
	 */
	public function get_label(): string {
		return esc_html__( 'Enable PDF tickets', 'event-tickets-plus' );
	}

	/**
	 * @inheritDoc
	 */
	public function get_tooltip(): ?string {
		$kb_link = sprintf(
			// Translators: $1%s is the url to the KB article, $2%s is the text for the link.
			'<a href="%1$s" target="_blank" rel="noopener nofollow">%2$s</a>',
			esc_url( 'https://evnt.is/event-tickets-wallet-plus' ),
			esc_html__( 'Learn more in our Knowledgebase', 'event-tickets-plus' )
		);
		$description = sprintf(
			// Translators: %s is a link to the KB article.
			__(
				'Offer printable, portable PDF tickets for users who purchase tickets and respond to RSVPs. PDF tickets are available on ticket and RSVP emails, checkout success page, and the purchaser\'s My Tickets view. %s',
				'event-tickets-wallet-plus'
			),
			$kb_link
		);
		return $description;
	}

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'tec-tickets-wallet-plus-pdf-enabled';
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): bool {
		return true;
	}
}
