<?php
/**
 * Purchase Rules Template class.
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules;

use Tribe__Template as Base_Template;
use Tribe__Tickets_Plus__Main as Tickets_Plus;

/**
 * Purchase Rules Template class.
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules
 */
class Template extends Base_Template {
	/**
	 * Template constructor.
	 *
	 * @since 6.9.0
	 */
	public function __construct() {
		$this->set_template_origin( tribe( Tickets_Plus::instance() ) );
		$this->set_template_folder( 'src/views/purchase-rules' );

		// Setup to look for theme files.
		$this->set_template_folder_lookup();

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}
