<?php
/**
 * REST TEC V1 Controller for Event Tickets Plus Purchase Rules.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;

/**
 * REST TEC V1 Controller for Event Tickets Plus Purchase Rules.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the controller.
	 *
	 * @since 6.9.0
	 */
	public function do_register(): void {
		$this->container->register( Endpoints::class );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since 6.9.0
	 */
	public function unregister(): void {
		$this->container->get( Endpoints::class )->unregister();
	}
}
