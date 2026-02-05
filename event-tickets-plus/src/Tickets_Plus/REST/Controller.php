<?php
/**
 * Controller for the Tickets Plus REST API.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\REST;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

use TEC\Tickets_Plus\REST\TEC\V1\Controller as V1_Controller;

/**
 * Controller for the Tickets Plus REST API.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 6.8.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->register( V1_Controller::class );
	}

	/**
	 * Unregisters the filters and actions hooks added by the controller.
	 *
	 * @since 6.8.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( V1_Controller::class )->unregister();
	}
}
