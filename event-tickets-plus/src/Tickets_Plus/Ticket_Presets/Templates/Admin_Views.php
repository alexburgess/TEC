<?php
/**
 * Handles the templating for the Flexible Tickets, administration side.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Templates\Ticket_Presets;
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Templates;

use Tribe__Template as Base_Template;
use Tribe__Tickets_Plus__Main as ETP;

/**
 * Class Admin_Templates.
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */
class Admin_Views extends Base_Template {
	/**
	 * Template constructor.
	 *
	 * Sets the correct paths for templates for event status.
	 *
	 * @since 5.8.0
	 */
	public function __construct() {
		$this->set_template_origin( ETP::class );
		$this->set_template_folder( 'src/admin-views/tickets-presets' );

		// We specifically don't want to look up template files here.
		$this->set_template_folder_lookup( false );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}
