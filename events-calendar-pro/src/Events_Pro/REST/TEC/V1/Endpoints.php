<?php
/**
 * Endpoints Controller class.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST\TEC\V1;

use TEC\Common\REST\TEC\V1\Contracts\Definition_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Endpoint_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Tag_Interface;
use TEC\Common\REST\TEC\V1\Abstracts\Endpoints_Controller;
use TEC\Events_Pro\REST\TEC\V1\Tags\Events_Pro_Tag;
use TEC\Events_Pro\REST\TEC\V1\Endpoints\Series;
use TEC\Events_Pro\REST\TEC\V1\Documentation\Series_Definition;

/**
 * Endpoints Controller class.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1
 */
class Endpoints extends Endpoints_Controller {
	/**
	 * Returns the endpoints to register.
	 *
	 * @since 7.7.11
	 *
	 * @return Endpoint_Interface[]
	 */
	public function get_endpoints(): array {
		return [
			Series::class,
		];
	}

	/**
	 * Returns the tags to register.
	 *
	 * @since 7.7.11
	 *
	 * @return Tag_Interface[]
	 */
	public function get_tags(): array {
		return [
			Events_Pro_Tag::class,
		];
	}

	/**
	 * Returns the definitions to register.
	 *
	 * @since 7.7.11
	 *
	 * @return Definition_Interface[]
	 */
	public function get_definitions(): array {
		return [
			Series_Definition::class,
		];
	}
}
