<?php
/**
 * The Zapier service provider.
 *
 * @since 6.0.0
 * @package Tribe\Tickets\Plus\Integrations\Event_Automator
 */

namespace Tribe\Tickets\Plus\Integrations\Event_Automator;

use TEC\Common\Contracts\Service_Provider;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Actions\Find_Attendees;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Actions\Find_Tickets;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Attendees;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Checkin;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Orders;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Refunded_Orders;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Updated_Attendees;
use TEC\Event_Automator\Zapier\Settings;

use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Actions\Create_Events;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Actions\Update_Events;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Actions\Find_Events;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Canceled_Events;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\New_Events;
use TEC\Event_Automator\Zapier\REST\V1\Endpoints\Updated_Events;

/**
 * Class Zapier_Provider
 *
 * @since 6.0.0
 *
 * @package Tribe\Events\Pro\Integrations\Event_Automator
 */
class Zapier_Provider extends Service_Provider {
	/**
	 * Binds and sets up implementations.
	 *
	 * @since 6.0.0
	 */
	public function register() {
		if ( ! self::is_active() ) {
			return;
		}

		// Requires single instance to use the same API class through the call.
		$this->container->singleton( Find_Tickets::class );
		$this->container->singleton( Find_Attendees::class );

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Returns whether the event status should register, thus activate, or not.
	 *
	 * @since 6.0.0
	 *
	 * @return bool Whether the event status should register or not.
	 */
	public static function is_active() {
		return \TEC\Event_Automator\Zapier\Zapier_Provider::is_active();
	}

	/**
	 * Adds the actions required for event status.
	 *
	 * @since 6.0.0
	 */
	protected function add_actions() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );

		// Add endpoints to settings dashboard.
		add_action( 'admin_init', [ $this, 'add_endpoints_to_dashboard' ] );
	}

	/**
	 * Adds the filters required by Zapier.
	 *
	 * @since 6.0.0
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_plus_integrations_tab_fields', [ $this, 'filter_et_integrations_tab_fields' ], 30 );
		add_filter( 'rest_pre_dispatch', [ $this, 'pre_dispatch_verification_for_find_attendees' ], 10, 3 );
		add_filter( 'rest_pre_dispatch', [ $this, 'pre_dispatch_verification_for_find_tickets' ], 10, 3 );
	}

	/**
	 * Registers the REST API endpoints for Zapier
	 *
	 * @since 6.0.0
	 */
	public function register_endpoints() {
		$this->container->make( Attendees::class )->register();
		$this->container->make( Updated_Attendees::class )->register();
		$this->container->make( Orders::class )->register();
		$this->container->make( Refunded_Orders::class )->register();
		$this->container->make( Checkin::class )->register();
		$this->container->make( Find_Attendees::class )->register();
		$this->container->make( Find_Tickets::class )->register();
	}

	/**
	 * Adds the endpoint to the Zapier endpoint dashboard filter.
	 *
	 * @since 6.0.0
	 */
	public function add_endpoints_to_dashboard() {
		$this->container->make( Attendees::class )->add_to_dashboard();
		$this->container->make( Updated_Attendees::class )->add_to_dashboard();
		$this->container->make( Checkin::class )->add_to_dashboard();
		$this->container->make( Orders::class )->add_to_dashboard();
		$this->container->make( Refunded_Orders::class )->add_to_dashboard();
		$this->container->make( Find_Attendees::class )->add_to_dashboard();
		$this->container->make( Find_Tickets::class )->add_to_dashboard();
	}

	/**
	 * Filters the fields in the Tickets > Settings > Integrations tab to Zapier settings.
	 *
	 * @since 6.0.0
	 *
	 * @param array<string,array> $fields The current fields.
	 *
	 * @return array<string,array> The fields, as updated by the settings.
	 */
	public function filter_et_integrations_tab_fields( $fields ) {
		if ( ! is_array( $fields ) ) {
			return $fields;
		}

		return tribe( Settings::class )->add_fields_et( $fields );
	}


	/**
	 * Verify token and login user before dispatching the request.
	 * Done on `rest_pre_dispatch` to be able to set current user to pass validation capability checks.
	 *
	 * @since 6.0.0 Migrated to Common from Event Automator
	 * @since 6.0.1 Migrated from Common to Event Tickets Plus
	 *
	 * @param mixed           $result  Response to replace the requested version with. Can be anything
	 *                                 a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return null Will always return null, failure will happen on the can_create permission check.
	 */
	public function pre_dispatch_verification_for_find_attendees( $result, $server, $request ) {
		return $this->container->make( Find_Attendees::class )->pre_dispatch_verification( $result, $server, $request );
	}

	/**
	 * Verify token and login user before dispatching the request.
	 * Done on `rest_pre_dispatch` to be able to set current user to pass validation capability checks.
	 *
	 * @since 6.0.0 Migrated to Common from Event Automator
	 * @since 6. Migrated from Common to Event Tickets Plus
	 *
	 * @param mixed           $result  Response to replace the requested version with. Can be anything
	 *                                 a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return null Will always return null, failure will happen on the can_create permission check.
	 */
	public function pre_dispatch_verification_for_find_tickets( $result, $server, $request ) {
		return $this->container->make( Find_Tickets::class )->pre_dispatch_verification( $result, $server, $request );
	}
}
