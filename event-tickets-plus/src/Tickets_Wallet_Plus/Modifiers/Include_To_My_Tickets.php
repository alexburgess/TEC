<?php

namespace TEC\Tickets_Wallet_Plus\Modifiers;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Manager;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Pass;
use TEC\Tickets_Wallet_Plus\Plugin;

/**
 * Class Include_To_My_Tickets
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Modifiers
 */
class Include_To_My_Tickets {
	use Generic_Template;

	/**
	 * Stores the template folder that will be used by the Generic Template Trait.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The template folder.
	 */
	protected string $template_folder = 'src/views/tickets-wallet-plus';

	/**
	 * Add passes to the my tickets page.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $attendee The attendee.
	 *
	 * @return void
	 */
	public function include_pass_container_my_tickets( $attendee ): void {
		// Bail if there are no passes enabled.
		$enabled_passes = tribe( Manager::class )->get_enabled_controllers();
		if ( empty( $enabled_passes ) ) {
			return;
		}

		$template_vars = [
			'attendee' => $attendee,
		];

		$this->get_template()->template( 'my-tickets/passes', $template_vars );
	}
}
