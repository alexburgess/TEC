<?php
/**
 * Waitlist Template class.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use Tribe__Template as Base_Template;
use Tribe__Tickets_Plus__Main as Tickets_Plus;

/**
 * Waitlist Template class.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Template extends Base_Template {
	/**
	 * Template constructor.
	 *
	 * @since 6.2.0
	 */
	public function __construct() {
		$this->set_template_origin( tribe( Tickets_Plus::instance() ) );
		$this->set_template_folder( 'src/views/waitlist' );

		// Setup to look for theme files.
		$this->set_template_folder_lookup( true );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}
