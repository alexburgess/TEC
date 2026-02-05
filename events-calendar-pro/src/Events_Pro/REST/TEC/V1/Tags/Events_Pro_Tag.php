<?php
/**
 * Events Pro tag for the TEC REST API V1.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Tags
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST\TEC\V1\Tags;

use TEC\Common\REST\TEC\V1\Abstracts\Tag;

/**
 * Events Pro tag for the TEC REST API V1.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Tags
 */
class Events_Pro_Tag extends Tag {
	/**
	 * Returns the tag name.
	 *
	 * @since 7.7.11
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'Events Pro';
	}

	/**
	 * Returns the tag.
	 *
	 * @since 7.7.11
	 *
	 * @return array
	 */
	public function get(): array {
		return [
			'name'        => $this->get_name(),
			'description' => __( 'These operations are introduced by Events Pro.', 'tribe-events-calendar-pro' ),
		];
	}

	/**
	 * Returns the priority of the tag.
	 *
	 * @since 7.7.11
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 15;
	}
}
