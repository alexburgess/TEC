<?php

use Tribe\Tickets\Plus\REST\V1\Endpoints\Bulk_Checkin;

/**
 * Class Tribe__Tickets_Plus__REST__V1__Service_Provider
 *
 * Add Event Tickets Plus REST API
 *
 * @since 4.7.5
 */
class  Tribe__Tickets_Plus__REST__V1__Service_Provider extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 */
	public $namespace;

	/**
	 * Registers the classes and functionality needed fro REST API
	 *
	 * @since 4.7.5
	 */
	public function register() {
		tribe_singleton( 'tickets-plus.rest-v1.main', new Tribe__Tickets_Plus__REST__V1__Main );
		tribe_singleton( 'tickets-plus.rest-v1.repository', new Tribe__Tickets_Plus__REST__V1__Post_Repository );
		tribe_singleton( 'tickets-plus.rest-v1.response', 'Tribe__Tickets_Plus__REST__V1__Response' );

		$this->register_endpoints();
		$this->hook();
	}

	/**
	 * Registers the REST API endpoints for Event Tickets Plus
	 *
	 * @since 6.7.0
	 */
	public function register_endpoints() {
		tribe_singleton(
			'tickets-plus.rest-v1.endpoints.bulk-checkin',
			new Bulk_Checkin( new Tribe__Tickets__REST__V1__Validator__Base() )
		);
	}

	/**
	 * Hooks the actions and filters required for the REST API integration to work.
	 *
	 * @since 4.8
	 */
	protected function hook() {
		add_filter( 'tribe_tickets_rest_api_ticket_data', tribe_callback( 'tickets-plus.rest-v1.response', 'filter_single_ticket_data' ) );
		add_filter( 'tribe_tickets_rest_api_attendee_data', tribe_callback( 'tickets-plus.rest-v1.response', 'filter_single_attendee_data' ), 10, 2 );
		add_filter( 'tribe_tickets_rest_api_update_attendee_data', tribe_callback( 'tickets-plus.rest-v1.response', 'filter_single_attendee_update_data' ), 10, 3 );
		add_filter( 'tribe_tickets_rest_api_post_attendee_data', tribe_callback( 'tickets-plus.rest-v1.response', 'filter_single_attendee_create_data' ), 10, 2 );

		add_action( 'rest_api_init', [ $this, 'register_bulk_checkin_endpoint' ] );
	}


	/**
	 * Register the bulk-checkin endpoint.
	 *
	 * @since 6.7.0
	 */
	public function register_bulk_checkin_endpoint() {
		tribe( 'tickets-plus.rest-v1.endpoints.bulk-checkin' )->register();
	}
}
