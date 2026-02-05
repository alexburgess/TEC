<?php
/**
 * Series definition for the TEC REST API V1.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Documentation
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;

/**
 * Series definition for the TEC REST API V1.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Documentation
 */
class Series_Definition extends Definition {
	/**
	 * Returns the type of the definition.
	 *
	 * @since 7.7.11
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'Series';
	}

	/**
	 * Returns the properties of the definition.
	 *
	 * @since 7.7.11
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 2;
	}

	/**
	 * Returns an array in the format used by Swagger.
	 *
	 * @since 7.7.11
	 *
	 * @return array An array description of a Swagger supported component.
	 */
	public function get_documentation(): array {
		$documentation = [
			'allOf' => [
				[
					'$ref' => '#/components/schemas/TEC_Post_Entity',
				],
				[
					'type'        => 'object',
					'description' => __( 'A series', 'tribe-events-calendar-pro' ),
					'title'       => 'Series',
				],
			],
		];

		$type = strtolower( $this->get_type() );

		/**
		 * Filters the Swagger documentation generated for a series in the TEC REST API.
		 *
		 * @since 7.7.11
		 *
		 * @param array             $documentation An associative PHP array in the format supported by Swagger.
		 * @param Series_Definition $this          The Series_Definition instance.
		 *
		 * @return array
		 */
		$documentation = (array) apply_filters( "tec_rest_swagger_{$type}_definition", $documentation, $this );

		/**
		 * Filters the Swagger documentation generated for a definition in the TEC REST API.
		 *
		 * @since 7.7.11
		 *
		 * @param array             $documentation An associative PHP array in the format supported by Swagger.
		 * @param Series_Definition $this          The Series_Definition instance.
		 *
		 * @return array
		 */
		return (array) apply_filters( 'tec_rest_swagger_definition', $documentation, $this );
	}
}
