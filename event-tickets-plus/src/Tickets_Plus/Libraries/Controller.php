<?php
/**
 * The Libraries controller
 *
 * @since 6.1.0
 *
 * @package TEC\Tickets_Plus\Libraries;
 */

namespace TEC\Tickets_Plus\Libraries;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Controller for setting up libraries.
 *
 * @since 6.1.0
 *
 * @package TEC\TicketsPlus\Libraries
 */
class Controller extends Controller_Contract {
	/**
	 * Register the controller.
	 *
	 * @since 6.1.0
	 */
	public function do_register(): void {
		$this->container->register( Uplink_Controller::class );
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Uplink_Controller::class )->unregister();
	}
}
