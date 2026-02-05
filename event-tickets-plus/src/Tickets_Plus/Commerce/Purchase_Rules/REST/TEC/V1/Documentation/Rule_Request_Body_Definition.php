<?php
/**
 * Rule Request Body definitions.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use TEC\Common\REST\TEC\V1\Contracts\Parameter;

/**
 * Rule Request Body definitions.
 */
class Rule_Request_Body_Definition extends Definition {
	/**
	 * Get the type.
	 *
	 * @since 6.9.0
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return 'Rule_Request_Body';
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
			'title'       => __( 'Rule Request Body', 'event-tickets-plus' ),
			'description' => __( 'A rule object as expected by the REST API', 'event-tickets-plus' ),
			'type'        => 'object',
			'properties'  => $properties->filter( fn( Parameter $property ) => ! $property->is_read_only() ),
		];

		$type = strtolower( $this->get_type() );

		/**
		 * Filters the Swagger documentation generated for an Rule_Request_Body in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array                        $documentation An associative PHP array in the format supported by Swagger.
		 * @param Rule_Request_Body_Definition $this          The Rule_Request_Body_Definition instance.
		 *
		 * @return array
		 */
		$documentation = (array) apply_filters( "tec_rest_swagger_{$type}_definition", $documentation, $this );

		/**
		 * Filters the Swagger documentation generated for a definition in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array                        $documentation An associative PHP array in the format supported by Swagger.
		 * @param Rule_Request_Body_Definition $this          The Rule_Request_Body_Definition instance.
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
