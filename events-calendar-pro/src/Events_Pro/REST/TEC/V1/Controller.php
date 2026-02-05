<?php
/**
 * Controller for the Events Pro V1 REST API.
 *
 * @since 7.7.0
 *
 * @package TEC\Events_Pro\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST\TEC\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * Controller for the Events Pro V1 REST API.
 *
 * @since 7.7.0
 *
 * @package TEC\Events_Pro\REST\TEC\V1
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
		$this->container->register( Pro_Extension::class );
		$this->container->register( Endpoints::class );
	}

	/**
	 * Unregisters the filters and actions hooks added by the controller.
	 *
	 * @since 7.7.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Pro_Extension::class )->unregister();
		$this->container->get( Endpoints::class )->unregister();
	}
}
