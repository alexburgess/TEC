<?php
/**
 * Handles the Flexible Tickets set of features in ET+ context.
 *
 * @since 5.9.0
 *
 * @package TEC\Tickets_Plus\Flexible_Tickets;
 */

namespace TEC\Tickets_Plus\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series;

/**
 * Class Provider.
 *
 * @since 5.9.0
 *
 * @package TEC\Tickets_Plus\Flexible_Tickets;
 */
class Provider extends Controller {

	/**
	 * Registers the bindings, service providers and controllers part of the feature.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$series_are_ticketable = in_array(
			Series::POSTTYPE,
			(array) tribe_get_option( 'ticket-enabled-post-types', [] ),
			true
		);

		if ( $series_are_ticketable ) {
			$this->container->register( Series_Passes::class );
		}

		$this->container->register( WooCommerce::class );
	}

	/**
	 * Unregisters the bindings, service providers and controllers part of the feature.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Series_Passes::class )->unregister();
		$this->container->get( WooCommerce::class )->unregister();
	}
}
