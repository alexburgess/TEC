<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Pass;

/**
 * Class Include_To_Your_Tickets
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers
 */
class Include_To_Attendees_List extends Modifier_Abstract {
	use Generic_Template;

	/**
	 * Stores the template folder that will be used by the Generic Template Trait.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The template folder.
	 */
	protected string $template_folder = 'src/views/tickets-wallet-plus/components';

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'pdf/include_to_your_tickets';
	}

	/**
	 * @inheritDoc
	 */
	protected function add_actions(): void {
		add_action( 'tribe_template_entry_point:tickets-plus/tickets-wallet-plus/attendees-list/passes:buttons', [ $this, 'add_pass_button' ], 10, 3 );
	}

	/**
	 * @inheritDoc
	 */
	protected function remove_actions(): void {
		remove_action( 'tribe_template_entry_point:tickets-plus/tickets-wallet-plus/attendees-list/passes:buttons', [ $this, 'add_pass_button' ], 10 );
	}

	/**
	 * Add PDF button to the `Attendees List` section.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string           $hook        The hook name.
	 * @param string           $entry_point The entry point name.
	 * @param \Tribe__Template $template    The template object.
	 *
	 * @return void
	 */
	public function add_pass_button( $hook, $entry_point, $template ): void {
		$attendee = $template->get( 'attendee' );
		if ( empty( $attendee ) ) {
			return;
		}
		$pdf_pass_url = Pass::from_attendee( $attendee['attendee_id'] )->get_url();

		$template_vars = [
			'pdf_pass_url' => $pdf_pass_url,
		];

		$this->get_template()->template( 'pdf-button', $template_vars );
	}
}
