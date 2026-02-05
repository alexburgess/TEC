<?php
/**
 * TEC Rule Toggle Entity definitions.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation;

use TEC\Common\REST\TEC\V1\Abstracts\Definition;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Boolean;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCaseClass, StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * TEC Rule Toggle Entity definitions.
 */
class RuleToggle_Definition extends Definition {
	/**
	 * Get the type.
	 *
	 * @since 6.9.0
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return 'RuleToggle';
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

		$properties[] = ( new Positive_Integer(
			'rule_id',
			fn() => __( 'The ID of the rule', 'event-tickets-plus' ),
		) )->set_example( 11 );

		$properties[] = ( new Boolean(
			'enabled',
			fn() => __( 'Whether the rule is enabled', 'event-tickets-plus' ),
		) )->set_example( true );

		$documentation = [
			'type'        => 'object',
			'title'       => __( 'RuleToggle', 'event-tickets-plus' ),
			'description' => __( 'A rule toggle object as returned by the REST API', 'event-tickets-plus' ),
			'properties'  => $properties,
		];

		$type = strtolower( $this->get_type() );

		/**
		 * Filters the Swagger documentation generated for an RuleToggle in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array                 $documentation An associative PHP array in the format supported by Swagger.
		 * @param RuleToggle_Definition $this          The RuleToggle_Definition instance.
		 *
		 * @return array
		 */
		$documentation = (array) apply_filters( "tec_rest_swagger_{$type}_definition", $documentation, $this );

		/**
		 * Filters the Swagger documentation generated for a definition in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array                $documentation An associative PHP array in the format supported by Swagger.
		 * @param RuleToggle_Definition $this          The RuleToggle_Definition instance.
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
