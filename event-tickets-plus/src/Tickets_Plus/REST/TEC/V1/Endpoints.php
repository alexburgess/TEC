<?php
/**
 * Endpoints Controller class.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\REST\TEC\V1;

use TEC\Common\REST\TEC\V1\Contracts\Definition_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Endpoint_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Tag_Interface;
use TEC\Common\REST\TEC\V1\Abstracts\Endpoints_Controller;
use TEC\Tickets_Plus\REST\TEC\V1\Endpoints\Tickets_Woo;
use TEC\Tickets_Plus\REST\TEC\V1\Endpoints\Ticket_Woo;
use TEC\Tickets_Plus\REST\TEC\V1\Tags\Tickets_Plus_Tag;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Definition;
use TEC\Tickets\REST\TEC\V1\Documentation\Ticket_Request_Body_Definition;

/**
 * Endpoints Controller class.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1
 */
class Endpoints extends Endpoints_Controller {
	/**
	 * Returns the endpoints to register.
	 *
	 * @since 6.8.0
	 *
	 * @return Endpoint_Interface[]
	 */
	public function get_endpoints(): array {
		$endpoints = [];

		if ( function_exists( 'WC' ) ) {
			$endpoints[] = Tickets_Woo::class;
			$endpoints[] = Ticket_Woo::class;
		}

		return $endpoints;
	}

	/**
	 * Returns the tags to register.
	 *
	 * @since 6.8.0
	 *
	 * @return Tag_Interface[]
	 */
	public function get_tags(): array {
		return [
			Tickets_Plus_Tag::class,
		];
	}

	/**
	 * Returns the definitions to register.
	 *
	 * @since 6.8.0
	 *
	 * @return Definition_Interface[]
	 */
	public function get_definitions(): array {
		/**
		 * Why are we registering definitions that are already registered in the Tickets plugin?
		 *
		 * Because they might not be registered if TC is inactive. So we make sure they are registered if
		 * WC is being used as a provider. Registering a definition twice does not hurt.
		 */
		return [
			Ticket_Definition::class,
			Ticket_Request_Body_Definition::class,
		];
	}
}
