<?php
/**
 * TEC Rule Entity definitions.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;

/**
 * TEC Rule Entity definitions.
 */
class Rule_Definition extends Definition {
	/**
	 * Get the type.
	 *
	 * @since 6.9.0
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return 'Rule';
	}

	/**
	 * Get the documentation.
	 *
	 * @since 6.9.0
	 *
	 * @return array The documentation.
	 */
	public function get_documentation(): array {

		$rule = new Rule();

		$properties = $rule->get_properties();

		$documentation = [
			'type'        => 'object',
			'title'       => __( 'Rule', 'event-tickets-plus' ),
			'description' => __( 'A rule object as returned by the REST API', 'event-tickets-plus' ),
			'properties'  => $properties,
		];

		$type = strtolower( $this->get_type() );

		/**
		 * Filters the Swagger documentation generated for an Rule in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array           $documentation An associative PHP array in the format supported by Swagger.
		 * @param Rule_Definition $this          The Rule_Definition instance.
		 *
		 * @return array
		 */
		$documentation = (array) apply_filters( "tec_rest_swagger_{$type}_definition", $documentation, $this );

		/**
		 * Filters the Swagger documentation generated for a definition in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array           $documentation An associative PHP array in the format supported by Swagger.
		 * @param Rule_Definition $this          The Rule_Definition instance.
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
		return 20;
	}
}
