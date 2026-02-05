<?php
/**
 * Include the passes in the Attendee Modal.
 */

namespace TEC\Tickets_Wallet_Plus\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Manager;

/**
 * Class Include_To_Attendee_Modal
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Modifiers
 */
class Include_To_Attendee_Modal {
	use Generic_Template;

	/**
	 * Stores the template folder that will be used by the Generic Template Trait.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The template folder.
	 */
	protected string $template_folder = 'src/admin-views/tickets-wallet-plus';

	/**
	 * Add to the 'Attendee Modal' within the admin.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string           $file     Which file we are loading.
	 * @param string           $name     The name of the file.
	 * @param \Tribe__Template $template Which Template object is being used.
	 *
	 * @return void
	 */
	public function include_pass_container_attendee_modal( $file, $name, $template ): void {
		// Only show the passes that are enabled.
		$enabled_passes = tribe( Manager::class )->get_enabled_controllers();
		if ( empty( $enabled_passes ) ) {
			return;
		}

		$template_vars = $template->get_local_values();

		if ( empty( $template_vars['attendee_id'] ) ) {
			return;
		}

		$this->get_template()->template( 'attendees/modal/passes', $template_vars );
	}
}
