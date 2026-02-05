<?php
/**
 * Include the PDF button in the Attendee Modal.
 */

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Pass;

/**
 * Class Include_To_Attendee_Modal
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers
 */
class Include_To_Attendee_Modal extends Modifier_Abstract {
	use Generic_Template;

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'pdf/attendees_table_modal';
	}

	/**
	 * @inheritDoc
	 */
	protected function add_actions(): void {
		add_action( 'tribe_template_entry_point:tickets-wallet-plus/admin-views/attendees/modal/passes:wallet_plus_attendee_modal', [ $this, 'include_button' ], 15, 3 );
	}

	/**
	 * @inheritDoc
	 */
	protected function remove_actions(): void {
		remove_action( 'tribe_template_entry_point:tickets-wallet-plus/admin-views/attendees/modal/passes:wallet_plus_attendee_modal', [ $this, 'include_button' ], 15, 3 );
	}

	/**
	 * Add a link to each attendee in the Attendee's modal in the admin.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string           $hook        The hook name.
	 * @param string           $entry_point The entry point name.
	 * @param \Tribe__Template $template    The template object.
	 */
	public function include_button( $hook, $entry_point, $template ): void {
		$template_vars = $template->get_local_values();

		if ( empty( $template_vars['attendee_id'] ) ) {
			return;
		}

		$pdf_link = Pass::from_attendee( $template_vars['attendee_id'] )->get_url();

		if ( empty( $pdf_link ) ) {
			return;
		}

		$template_vars = [
			'attendee_id'  => $template_vars['attendee_id'],
			'pdf_pass_url' => $pdf_link,
		];

		$this->get_template()->template( 'components/pdf-button', $template_vars );
	}
}
