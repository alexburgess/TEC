<?php
/**
 * The Power Automate service provider.
 *
 * @since 7.0.3
 * @package TEC\Event_Automator\Power_Automate
 */

namespace Tribe\Events\Pro\Integrations\Event_Automator;

use TEC\Common\Contracts\Service_Provider;

use TEC\Event_Automator\Power_Automate\REST\V1\Endpoints\Actions\Create_Events;
use TEC\Event_Automator\Power_Automate\REST\V1\Endpoints\Queue\New_Events;
use TEC\Event_Automator\Power_Automate\REST\V1\Endpoints\Queue\Updated_Events;
use TEC\Event_Automator\Power_Automate\REST\V1\Endpoints\Queue\Canceled_Events;
use TEC\Event_Automator\Power_Automate\Settings;
use Tribe__Settings_Tab;

/**
 * Class Power_Automate_Provider
 *
 * @since 7.0.3
 *
 * @package TEC\Event_Automator\Power_Automate
 */
class Power_Automate_Provider extends Service_Provider {
	/**
	 * Stores the instance of the settings tab.
	 *
	 * @since 7.7.6
	 *
	 * @var Tribe__Settings_Tab
	 */
	protected $settings_tab;

	/**
	 * Tab ID for the Power Automate settings.
	 *
	 * @since 7.7.6
	 *
	 * @var string
	 */
	const TAB_ID = 'power-automate';

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 7.0.3
	 */
	public function register() {
		if ( ! self::is_active() ) {
			return;
		}

		// Requires a single instance to use the same API class through the call.
		$this->container->singleton( Create_Events::class );

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Returns whether the event status should register, thus activate, or not.
	 *
	 * @since 7.0.3
	 *
	 * @return bool Whether the event status should register or not.
	 */
	public static function is_active() {
		return \TEC\Event_Automator\Power_Automate\Power_Automate_Provider::is_active();
	}

	/**
	 * Adds the actions required for event status.
	 *
	 * @since 7.0.3
	 * @since 7.7.6 Add hook for `add_settings_fields`.
	 */
	protected function add_actions() {
		add_action( 'tec_settings_tab_addons', [ $this, 'add_settings_fields' ] );

		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );

		// Add endpoints to settings dashboard.
		add_action( 'admin_init', [ $this, 'add_endpoints_to_dashboard' ] );
	}

	/**
	 * Adds the filters required by Power Automate.
	 *
	 * @since 7.0.3
	 * @since 7.7.6 Remove hook for `filter_tec_integrations_tab_fields`.
	 */
	protected function add_filters() {
		add_filter( 'rest_pre_dispatch', [ $this, 'pre_dispatch_verification_for_create_events' ], 10, 3 );
		add_filter( 'rest_request_before_callbacks', [ $this, 'modify_rest_api_params_before_validatio_of_create_events' ], 1, 3 );
	}

	/**
	 * Registers the REST API endpoints for Power Automate.
	 *
	 * @since 7.0.3
	 */
	public function register_endpoints() {
		$this->container->make( Canceled_Events::class )->register();
		$this->container->make( New_Events::class )->register();
		$this->container->make( Updated_Events::class )->register();
		$this->container->make( Create_Events::class )->register();
	}

	/**
	 * Adds the endpoint to the Power Automate endpoint dashboard filter.
	 *
	 * @since 7.0.3
	 */
	public function add_endpoints_to_dashboard() {
		$this->container->make( New_Events::class )->add_to_dashboard();
		$this->container->make( Canceled_Events::class )->add_to_dashboard();
		$this->container->make( Updated_Events::class )->add_to_dashboard();
		$this->container->make( Create_Events::class )->add_to_dashboard();
	}

	/**
	 * Filters the fields in the Events > Settings > Integrations tab to Power Automate settings.
	 *
	 * @since 7.0.3 Migrated from Common to Events Calendar Pro.
	 * @since 7.7.6 Deprecated. Use `add_settings_fields` instead.
	 *
	 * @param array<string,array> $fields The current fields.
	 *
	 * @return array<string,array> The fields, as updated by the settings.
	 */
	public function filter_tec_integrations_tab_fields( $fields ) {
		_deprecated_function( __METHOD__, '7.7.6', 'add_settings_fields' );
	}

	/**
	 * Adds Power Automate settings fields to the settings tab.
	 *
	 * @since 7.7.6
	 *
	 * @param Tribe__Settings_Tab $parent_tab The parent settings tab instance to add fields to.
	 *
	 * @return void
	 */
	public function add_settings_fields( $parent_tab ) {
		$this->settings_tab = new Tribe__Settings_Tab(
			self::TAB_ID,
			esc_html__( 'Power Automate', 'tribe-events-calendar-pro' ),
			[
				'priority'  => 30,
				'fields'    => tribe( Settings::class )->add_fields_tec(
					[
						'power-automate-info' => [
							'type' => 'html',
							'html' => '<div class="tec-settings-form__header-block tec-settings-form__header-block--horizontal">'
								. '<h2 class="tec-settings-form__section-header">'
								. esc_html__( 'Power Automate', 'tribe-events-calendar-pro' )
								. '</h2>'
								. '<p class="tec-settings-form__section-description">'
								. esc_html__(
									'Connect your site to Microsoft Power Automate to automate your event and ticket workflows.',
									'tribe-events-calendar-pro'
								)
								. '</p>'
								. '</div>',
						],
					]
				),
				'show_save' => false,
			]
		);

		$parent_tab->add_child( $this->settings_tab );
	}

	/**
	 * Verify the token and log in the user before dispatching the request.
	 * Done on `rest_pre_dispatch` to be able to set the current user to pass validation capability checks.
	 *
	 * @since 7.0.3 Migrated from Common to Events Calendar Pro.
	 *
	 * @param mixed           $result  Response to replace the requested version with. Can be anything
	 *                                 a normal endpoint can return, or null to not hijack the request.
	 * @param WP_REST_Server  $server  Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 *
	 * @return null With always return null, failure will happen on the can_create permission check.
	 */
	public function pre_dispatch_verification_for_create_events( $result, $server, $request ) {
		return $this->container->make( Create_Events::class )->pre_dispatch_verification( $result, $server, $request );
	}

	/**
	 * Modifies REST API comma-separated parameters before validation.
	 *
	 * @since 6.0.0 Migrated to Common from Event Automator
	 *
	 * @param WP_REST_Response|WP_Error $result   Response to replace the requested version with. Can be anything
	 *                                            a normal endpoint can return, or a WP_Error if replacing the
	 *                                            response with an error.
	 * @param WP_REST_Server            $server   ResponseHandler instance (usually WP_REST_Server).
	 * @param WP_REST_Request           $request  Request used to generate the response.
	 *
	 * @return WP_REST_Response|WP_Error The response.
	 */
	public function modify_rest_api_params_before_validatio_of_create_events( $result, $server, $request ) {
		return $this->container->make( Create_Events::class )->modify_rest_api_params_before_validation( $result, $server, $request );
	}
}
