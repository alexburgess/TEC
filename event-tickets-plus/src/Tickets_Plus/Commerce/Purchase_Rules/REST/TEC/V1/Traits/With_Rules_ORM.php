<?php
/**
 * Trait to provide purchase rules ORM access.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Traits;

use TEC\Tickets_Plus\Commerce\Purchase_Rules\Repository\Rules_Repository;
use TEC\Common\Contracts\Repository_Interface;

/**
 * Trait With_Rules_ORM.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Traits
 */
trait With_Rules_ORM {
	/**
	 * Returns a repository instance.
	 *
	 * @since 6.9.0
	 *
	 * @return Repository_Interface The repository instance.
	 */
	public function get_orm(): Repository_Interface {
		return tribe( Rules_Repository::class );
	}
}
