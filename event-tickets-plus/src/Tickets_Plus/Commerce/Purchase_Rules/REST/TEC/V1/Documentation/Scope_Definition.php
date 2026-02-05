<?php
/**
 * TEC Scope Entity definitions.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;
use TEC\Common\REST\TEC\V1\Parameter_Types\Entity;

/**
 * TEC Scope Entity definitions.
 */
class Scope_Definition extends Definition {
	/**
	 * Get the type.
	 *
	 * @since 6.9.0
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return 'Rule_Scope';
	}

	/**
	 * Get the documentation.
	 *
	 * @since 6.9.0
	 *
	 * @return array The documentation.
	 */
	public function get_documentation(): array {
		$properties = new PropertiesCollection();

		$properties[] = (
			new Text(
				'connector',
				fn() => __( 'The connector of the scope.', 'event-tickets-plus' ),
			)
		)->set_example( 'and' );

		$properties[] = (
			new Entity(
				'criteria',
				fn() => __( 'The criteria of the scope.', 'event-tickets-plus' ),
			)
		)->set_example(
			[
				[
					'term'  => 'post_tag',
					'value' => 1,
				],
				[
					'term'  => 'title',
					'value' => 'Rock',
				],
			]
		);

		$documentation = [
			'type'        => 'object',
			'title'       => __( 'Rule_Scope', 'event-tickets-plus' ),
			'description' => __( 'A rule scope object as returned by the REST API', 'event-tickets-plus' ),
			'properties'  => $properties,
		];

		$type = strtolower( $this->get_type() );

		/**
		 * Filters the Swagger documentation generated for an Rule in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array            $documentation An associative PHP array in the format supported by Swagger.
		 * @param Scope_Definition $this          The Scope_Definition instance.
		 *
		 * @return array
		 */
		$documentation = (array) apply_filters( "tec_rest_swagger_{$type}_definition", $documentation, $this );

		/**
		 * Filters the Swagger documentation generated for a definition in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array            $documentation An associative PHP array in the format supported by Swagger.
		 * @param Scope_Definition $this          The Scope_Definition instance.
		 *
		 * @return array
		 */
		return (array) apply_filters( 'tec_rest_swagger_definition', $documentation, $this );
	}

	/**
	 * Get the priority.
	 *
	 * @since 6.9.0
	 *
	 * @return int The priority.
	 */
	public function get_priority(): int {
		return 30;
	}
}
