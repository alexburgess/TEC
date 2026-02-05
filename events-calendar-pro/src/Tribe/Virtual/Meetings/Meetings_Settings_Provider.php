<?php
/**
 * The Meetings settings provider.
 *
 * @since 7.7.6
 * @package Tribe\Events\Virtual\Meetings
 */

namespace Tribe\Events\Virtual\Meetings;

use TEC\Common\Contracts\Service_Provider;
use Tribe__Settings_Tab;

/**
 * Class Meetings_Settings_Provider
 *
 * @since 7.7.6
 *
 * @package Tribe\Events\Virtual\Meetings
 */
class Meetings_Settings_Provider extends Service_Provider {

	/**
	 * Stores the instance of the settings tab.
	 *
	 * @since 7.7.6
	 *
	 * @var Tribe__Settings_Tab
	 */
	protected $settings_tab;

	/**
	 * Tab ID for the Meetings settings.
	 *
	 * @since 7.7.6
	 *
	 * @var string
	 */
	const TAB_ID = 'meetings';

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 7.7.6
	 */
	public function register() {
		$this->add_actions();
	}

	/**
	 * Adds the actions required for meetings settings.
	 *
	 * @since 7.7.6
	 */
	protected function add_actions() {
		add_action( 'tec_settings_tab_addons', [ $this, 'add_settings_fields' ] );
	}

	/**
	 * Adds Meetings settings fields to the Integrations settings tab.
	 *
	 * @since 7.7.6
	 *
	 * @param Tribe__Settings_Tab $parent_tab The parent Integrations tab instance to add fields to.
	 *
	 * @return void
	 */
	public function add_settings_fields( $parent_tab ) {
		if ( ! $parent_tab instanceof Tribe__Settings_Tab ) {
			return;
		}

		$this->settings_tab = new Tribe__Settings_Tab(
			self::TAB_ID,
			esc_html__( 'Meetings', 'tribe-events-calendar-pro' ),
			[
				'priority'  => 25,
				'fields'    => $this->get_fields(),
				'show_save' => true,
			]
		);

		$parent_tab->add_child( $this->settings_tab );
	}

	/**
	 * Gets the settings tab instance.
	 *
	 * @since 7.7.6
	 *
	 * @return Tribe__Settings_Tab|null
	 */
	public function get_settings_tab() {
		return $this->settings_tab;
	}

	/**
	 * Gets the settings fields for the Meetings tab.
	 *
	 * @since 7.7.6
	 *
	 * @return array<string, array> Array of field definitions for the settings page.
	 */
	protected function get_fields() {
		$fields = [
			'meetings-info' => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__header-block tec-settings-form__header-block--horizontal">'
					. '<h2 class="tec-settings-form__section-header">'
					. esc_html__( 'Meetings', 'tribe-events-calendar-pro' )
					. '</h2>'
					. '<p class="tec-settings-form__section-description">'
					. esc_html__(
						'Connect your site to various meeting and video conferencing platforms to create and manage virtual events.',
						'tribe-events-calendar-pro'
					)
					. '</p>'
					. '</div>',
			],
		];

		/**
		 * Filter to allow meeting providers to add their settings fields.
		 *
		 * @since 7.7.6
		 *
		 * @param array<string, array> $fields Array of field definitions.
		 */
		$provider_fields = apply_filters( 'tec_events_pro_meetings_tab_fields', [] );

		$fields = array_merge( $fields, $provider_fields );

		/**
		 * Filter the complete fields for the Meetings tab.
		 *
		 * @since 7.7.6
		 *
		 * @param array<string, array> $fields Array of field definitions.
		 */
		return apply_filters( 'tec_events_pro_virtual_meetings_tab_fields', $fields );
	}
}
