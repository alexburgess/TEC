<?php
/**
 * Trait to provide WooCommerce provider.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\REST\TEC\V1\Traits;

use Tribe__Tickets_Plus__Commerce__WooCommerce__Main as WC_Provider;
use Tribe__Tickets__Tickets as Ticket_Provider;

/**
 * Trait With_WC_Provider.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets\REST\TEC\V1\Traits
 */
trait With_WC_Provider {
	/**
	 * Returns the WooCommerce provider.
	 *
	 * @since 6.8.0
	 *
	 * @return Ticket_Provider
	 */
	protected function get_provider(): Ticket_Provider {
		return tribe( WC_Provider::class );
	}
}
