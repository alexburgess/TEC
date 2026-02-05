<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;
use TEC\Tickets_Wallet_Plus\Plugin;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;

/**
 * Class Email_Link
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers
 */
class Email_Link extends Modifier_Abstract {
	use Generic_Template;

	/**
	 * Stores the template folder that will be used by the Generic Template Trait.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The template folder.
	 */
	protected string $template_folder = 'src/views/tickets-wallet-plus/apple-wallet';

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'apple-wallet/email_link';
	}

	/**
	 * @inheritDoc
	 */
	protected function add_actions(): void {
		add_action( 'tribe_tickets_ticket_email_ticket_bottom', [ $this, 'include_link' ], 10, 2 );
	}

	/**
	 * @inheritDoc
	 */
	protected function remove_actions(): void {
		remove_action( 'tribe_tickets_ticket_email_ticket_bottom', [ $this, 'include_link' ], 10 );
	}

	/**
	 * Include the Apple Wallet pass link in the email.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $ticket The ticket data.
	 */
	public function include_link( $ticket ): void {

		$pass = Pass::from_attendee( $ticket['attendee_id'] );
		$apple_wallet_image_src = $pass->get_button_image_url();
		$apple_wallet_pass_url  = $pass->get_url();

		$template_vars = [
			'apple_wallet_image_src' => $apple_wallet_image_src,
			'apple_wallet_pass_url'  => $apple_wallet_pass_url,
		];

		$this->get_template()->template( 'email', $template_vars );
	}

}
