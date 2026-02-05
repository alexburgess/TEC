<?php
/**
 * Trait to provide series ORM access.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Traits
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST\TEC\V1\Traits;

use TEC\Events_Pro\Custom_Tables\V1\Repository\Series_Repository;
use TEC\Common\Contracts\Repository_Interface;

/**
 * Trait With_Series_ORM.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Traits
 */
trait With_Series_ORM {
	/**
	 * Returns a repository instance.
	 *
	 * @since 7.7.11
	 *
	 * @return Repository_Interface The repository instance.
	 */
	public function get_orm(): Repository_Interface {
		return tribe( Series_Repository::class );
	}
}
