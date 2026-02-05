<?php
/**
 * Tickets Plus tag for the TEC REST API V1.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1\Tags
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\REST\TEC\V1\Tags;

use TEC\Common\REST\TEC\V1\Abstracts\Tag;

/**
 * Tickets Plus tag for the TEC REST API V1.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1\Tags
 */
class Tickets_Plus_Tag extends Tag {
	/**
	 * Returns the tag name.
	 *
	 * @since 6.8.0
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Tickets Plus';
	}

	/**
	 * Returns the tag.
	 *
	 * @since 6.8.0
	 *
	 * @return array
	 */
	public function get(): array {
		return [
			'name'        => $this->get_name(),
			'description' => __( 'These operations are introduced by Event Tickets Plus.', 'event-tickets-plus' ),
		];
	}

	/**
	 * Returns the priority of the tag.
	 *
	 * @since 6.8.0
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 20;
	}
}
