<?php
/**
 * Trait to provide WooCommerce tickets ORM access.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\REST\TEC\V1\Traits;

use TEC\Common\Contracts\Repository_Interface;

/**
 * Trait With_Tickets_Woo_ORM.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1\Traits
 */
trait With_Tickets_Woo_ORM {
	/**
	 * Returns a repository instance.
	 *
	 * @since 6.8.0
	 *
	 * @return Repository_Interface The repository instance.
	 */
	public function get_orm(): Repository_Interface {
		return tribe_tickets( 'woo' );
	}
}
