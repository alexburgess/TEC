<?php
/**
 * Include the button in the Attendee Modal.
 */

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;

/**
 * Class Include_To_Attendee_Modal
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers
 */
class Include_To_Attendee_Modal extends Modifier_Abstract {
	use Generic_Template;

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'apple-wallet/attendees_table_modal';
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
		$attendee_id = $template->get( 'attendee_id' );

		if ( empty( $attendee_id ) ) {
			return;
		}

		$pass                   = Pass::from_attendee( $attendee_id );
		$apple_wallet_image_src = $pass->get_button_image_url();
		$apple_wallet_pass_url  = $pass->get_url();

		$template_vars = [
			'attendee_id'            => $attendee_id,
			'apple_wallet_image_src' => $apple_wallet_image_src,
			'apple_wallet_pass_url'  => $apple_wallet_pass_url,
		];

		$this->get_template()->template( 'components/apple-wallet-button', $template_vars );
	}
}
