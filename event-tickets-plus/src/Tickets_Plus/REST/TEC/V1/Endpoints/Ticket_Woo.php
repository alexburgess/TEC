<?php
/**
 * Single WooCommerce ticket endpoint for the TEC REST API V1.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1\Endpoints
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\REST\TEC\V1\Endpoints;

use TEC\Tickets\REST\TEC\V1\Endpoints\Ticket as ET_Ticket;
use TEC\Tickets_Plus\REST\TEC\V1\Tags\Tickets_Plus_Tag;
use TEC\Tickets_Plus\REST\TEC\V1\Traits\With_Tickets_Woo_ORM;
use TEC\Tickets_Plus\REST\TEC\V1\Traits\With_WC_Provider;
use RuntimeException;

/**
 * Single WooCommerce ticket endpoint for the TEC REST API V1.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1\Endpoints
 */
class Ticket_Woo extends ET_Ticket {
	use With_Tickets_Woo_ORM;
	use With_WC_Provider;

	/**
	 * Returns the base path for the endpoint.
	 *
	 * @since 6.8.0
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return '/tickets/woo/%s';
	}

	/**
	 * Returns the post type of the endpoint.
	 *
	 * @since 6.8.0
	 *
	 * @return string
	 */
	public function get_post_type(): string {
		return 'product';
	}

	/**
	 * Returns the operation ID for the endpoint.
	 *
	 * @since 6.8.0
	 *
	 * @param string $operation The operation to get the ID for.
	 *
	 * @return string
	 *
	 * @throws RuntimeException If the operation is invalid.
	 */
	public function get_operation_id( string $operation ): string {
		switch ( $operation ) {
			case 'read':
				return 'readWooTicket';
			case 'update':
				return 'updateWooTicket';
			case 'delete':
				return 'deleteWooTicket';
		}

		throw new RuntimeException( 'Invalid operation.' );
	}

	/**
	 * Returns the tags for the endpoint.
	 *
	 * @since 6.8.0
	 *
	 * @return array
	 */
	public function get_tags(): array {
		return [ tribe( Tickets_Plus_Tag::class ) ];
	}
}
