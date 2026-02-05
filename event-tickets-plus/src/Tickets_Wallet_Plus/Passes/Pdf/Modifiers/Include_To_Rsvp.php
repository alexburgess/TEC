<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Pass;

/**
 * Class Include_To_Rsvp
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers
 */
class Include_To_Rsvp extends Modifier_Abstract {
	use Generic_Template;

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'pdf/include_to_rsvp';
	}

	/**
	 * @inheritDoc
	 */
	protected function add_actions(): void {
		add_action( 'tribe_template_after_include:tickets/v2/rsvp/attendees/attendee', [ $this, 'include_passes_rsvp_block' ], 10, 3 );
	}

	/**
	 * @inheritDoc
	 */
	protected function remove_actions(): void {
		remove_action( 'tribe_template_after_include:tickets/v2/rsvp/attendees/attendee', [ $this, 'include_passes_rsvp_block' ], 10 );
	}

	/**
	 * Add Pdf button to the `RSVP` block confirmation state.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     The name of the file.
	 * @param \Tribe__Template $template Which Template object is being used.
	 *
	 * @return void
	 */
	public function include_passes_rsvp_block( $file, $name, $template ): void {
		$attendee_id  = $template->get( 'attendee_id' );
		$pdf_pass_url = Pass::from_attendee( $attendee_id )->get_url();

		$template_vars = [
			'attendee_id'  => $attendee_id,
			'pdf_pass_url' => $pdf_pass_url,
		];

		$this->get_template()->template( 'pdf/rsvp/pdf-button', $template_vars );
	}
}
