<?php

namespace TEC\Tickets_Wallet_Plus\Modifiers;

use TEC\Tickets\Commerce\Module;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Manager;
use TEC\Tickets_Wallet_Plus\Plugin;

/**
 * Class Include_To_Order_Page
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Modifiers
 */
class Include_To_Order_Page {
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
	 * Add to the 'Attendees List' section.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     The name of the file.
	 * @param \Tribe__Template $template Which Template object is being used.
	 *
	 * @return void
	 */
	public function include_pass_container_your_tickets( $file, $name, $template ): void {
		// Only show the passes that are enabled.
		$enabled_passes = tribe( Manager::class )->get_enabled_controllers();
		if ( empty( $enabled_passes ) ) {
			return;
		}
		tribe_asset_enqueue_group( 'tec-tickets-wallet-plus-order-page-assets' );
		$attendee     = $template->get( 'attendee' );

		$template_vars = [
			'attendee' => $attendee,
		];

		$this->get_template()->template( 'attendees-list/passes', $template_vars );
	}
}
