<?php
/**
 * Controller for the Events Pro REST API.
 *
 * @since 7.7.0
 *
 * @package TEC\Events_Pro\REST
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Events_Pro\REST\TEC\V1\Controller as V1_Controller;

/**
 * Controller for the Events Pro REST API.
 *
 * @since 7.7.0
 *
 * @package TEC\Events_Pro\REST
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 7.7.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->container->register( V1_Controller::class );
	}

	/**
	 * Unregisters the filters and actions hooks added by the controller.
	 *
	 * @since 7.7.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( V1_Controller::class )->unregister();
	}
}
