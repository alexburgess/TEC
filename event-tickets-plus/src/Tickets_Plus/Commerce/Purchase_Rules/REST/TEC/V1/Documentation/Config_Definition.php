<?php
/**
 * TEC Config Entity definitions.
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
use TEC\Common\REST\TEC\V1\Parameter_Types\Number;
use TEC\Common\REST\TEC\V1\Parameter_Types\Array_Of_Type;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Definition_Parameter;

/**
 * TEC Config Entity definitions.
 */
class Config_Definition extends Definition {
	/**
	 * Get the type.
	 *
	 * @since 6.9.0
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return 'Rule_Config';
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
				'message',
				fn() => __( 'The message of the rule.', 'event-tickets-plus' ),
			)
		)->set_example( 'Buy 4 or more tickets and get $5 off your purchase!' )->set_nullable( true );

		$properties[] = (
			new Text(
				'requirement',
				fn() => __( 'The requirement of the rule.', 'event-tickets-plus' ),
				Rule::QUANTITY_REQUIREMENT,
				Rule::ALL_REQUIREMENTS
			)
		)->set_example( Rule::QUANTITY_REQUIREMENT );

		$properties[] = (
			new Number(
				'requirementValue',
				fn() => __( 'The requirement value of the rule.', 'event-tickets-plus' ),
				null,
				0
			)
		)->set_example( 10 );

		$properties[] = (
			new Text(
				'discountType',
				fn() => __( 'The discount type of the rule.', 'event-tickets-plus' ),
				Rule::PERCENTAGE_DISCOUNT_TYPE,
				Rule::ALL_DISCOUNT_TYPES
			)
		)->set_example( Rule::PERCENTAGE_DISCOUNT_TYPE );

		$properties[] = (
			new Number(
				'discountValue',
				fn() => __( 'The discount value of the rule.', 'event-tickets-plus' ),
				null,
				0
			)
		)->set_example( 10 );

		$properties[] = new Definition_Parameter( new Requirements_Definition(), 'requirements' );

		$properties[] = (
			new Positive_Integer(
				'ticketLimit',
				fn() => __( 'The ticket limit of the rule.', 'event-tickets-plus' ),
				null,
				1
			)
		)->set_example( 10 );

		$properties[] = (
			new Text(
				'limitedTicket',
				fn() => __( 'The limited ticket of the rule.', 'event-tickets-plus' ),
			)
		)->set_example( 'ticket_1' );

		$properties[] = (
			new Positive_Integer(
				'ticketMinimum',
				fn() => __( 'The ticket minimum of the rule.', 'event-tickets-plus' ),
				null,
				1
			)
		)->set_example( 10 );

		$properties[] = (
			new Array_Of_Type(
				'userRoles',
				fn() => __( 'The user roles of the rule.', 'event-tickets-plus' ),
				Text::class,
				array_keys( wp_roles()->get_names() )
			)
		)->set_example(
			[
				'administrator',
			]
		);

		$properties[] = (
			new Text(
				'restrictedTicket',
				fn() => __( 'The restricted ticket of the rule.', 'event-tickets-plus' ),
			)
		)->set_example( 'ticket_1' );

		$properties[] = (
			new Array_Of_Type(
				'requiredTickets',
				fn() => __( 'The required tickets of the rule.', 'event-tickets-plus' ),
				Text::class,
			)
		)->set_example( [ 'ticket_1', 'ticket_2' ] );

		$properties[] = (
			new Text(
				'requiredQuantity',
				fn() => __( 'The required quantity of the rule.', 'event-tickets-plus' ),
				Rule::MATCHED_REQUIRED_QUANTITY,
				Rule::ALL_REQUIRED_QUANTITIES
			)
		)->set_example( 'matched' );

		$properties[] = (
			new Positive_Integer(
				'specificQuantity',
				fn() => __( 'The specific quantity of the rule.', 'event-tickets-plus' ),
				null,
				0
			)
		)->set_example( 10 );

		$documentation = [
			'type'        => 'object',
			'title'       => __( 'Rule_Config', 'event-tickets-plus' ),
			'description' => __( 'A rule config object as returned by the REST API', 'event-tickets-plus' ),
			'properties'  => $properties,
		];

		$type = strtolower( $this->get_type() );

		/**
		 * Filters the Swagger documentation generated for an Rule in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array             $documentation An associative PHP array in the format supported by Swagger.
		 * @param Config_Definition $this          The Config_Definition instance.
		 *
		 * @return array
		 */
		$documentation = (array) apply_filters( "tec_rest_swagger_{$type}_definition", $documentation, $this );

		/**
		 * Filters the Swagger documentation generated for a definition in the TEC REST API.
		 *
		 * @since 6.9.0
		 *
		 * @param array             $documentation An associative PHP array in the format supported by Swagger.
		 * @param Config_Definition $this          The Config_Definition instance.
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
