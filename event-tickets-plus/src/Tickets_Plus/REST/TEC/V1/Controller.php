<?php
/**
 * REST TEC V1 Controller for Event Tickets Plus.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\REST\TEC\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;
use TEC\Tickets_Plus\Ticket_Presets\Meta;

/**
 * REST TEC V1 Controller for Event Tickets Plus.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\REST\TEC\V1
 */
class Controller extends Controller_Contract {
	/**
	 * Registers the controller.
	 *
	 * @since 6.8.0
	 */
	public function do_register(): void {
		$this->container->register( Endpoints::class );
		add_filter( 'tec_rest_swagger_ticket_request_body_definition', [ $this, 'filter_ticket_request_body_definition' ] );
	}

	/**
	 * Unregisters the controller.
	 *
	 * @since 6.8.0
	 */
	public function unregister(): void {
		$this->container->get( Endpoints::class )->unregister();
		remove_filter( 'tec_rest_swagger_ticket_request_body_definition', [ $this, 'filter_ticket_request_body_definition' ] );
	}

	/**
	 * Filters the ticket request body definition.
	 *
	 * @since 6.8.0
	 *
	 * @param array $documentation An associative PHP array in the format supported by Swagger.
	 *
	 * @return array The filtered documentation.
	 */
	public function filter_ticket_request_body_definition( array $documentation ): array {
		$plus_properties = new PropertiesCollection();

		$plus_properties[] = (
			new Text(
				'attendee_collection',
				fn() => __( 'The attendee collection setting for the ticket', 'event-tickets-plus' ),
				Meta::$none_key,
				[ Meta::$none_key, Meta::$allowed_key, Meta::$required_key ]
			)
		)->set_example( Meta::$none_key );

		foreach ( $documentation['allOf'] as &$value ) {
			unset(
				$value['title'],
				$value['description'],
			);
		}

		$documentation['allOf'][] = [
			'type'        => 'object',
			'title'       => 'Plus: Ticket Request Body',
			'description' => __( 'The request body for the ticket endpoint, with Plus-specific properties', 'event-tickets-plus' ),
			'properties'  => $plus_properties,
		];

		return $documentation;
	}
}
