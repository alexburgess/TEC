<?php
/**
 * Base provider for the Ticket Presets feature.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */

namespace TEC\Tickets_Plus\Ticket_Presets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Posts_And_Ticket_Presets;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;
use TEC\Tickets_Plus\Ticket_Presets\Assets;

/**
 * Class Base.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */
class Base extends Controller {
	/**
	 * Registers hooks and service providers with the container.
	 *
	 * @since 6.6.0
	 */
	public function do_register(): void {
		$this->container->register( Assets::class );
		$this->container->singleton( Ticket_Presets::class, Ticket_Presets::class );
		$this->container->singleton( Posts_And_Ticket_Presets::class, Posts_And_Ticket_Presets::class );
	}

	/**
	 * Unregister hooks.
	 *
	 * @since 6.6.0
	 */
	public function unregister(): void {
		// no op.
	}
}
