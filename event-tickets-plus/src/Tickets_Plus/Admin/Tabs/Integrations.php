<?php
/**
 * File: Integrations.php
 *
 * Contains the Integrations tab class for Event Tickets Plus settings.
 *
 * @since 5.6.2
 * @package TEC\Tickets_Plus\Admin\Tabs
 */

namespace TEC\Tickets_Plus\Admin\Tabs;

use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use Tribe__Settings_Tab;

/**
 * Class Integrations
 *
 * Handles the Integrations tab in the Event Tickets Plus settings page.
 *
 * @since 5.6.2
 * @package TEC\Tickets_Plus\Admin\Tabs
 */
class Integrations {

	/**
	 * Slug for the tab.
	 *
	 * @since 5.6.2
	 *
	 * @var string
	 */
	public static $slug = 'integrations';

	/**
	 * Stores the instance of the settings tab.
	 *
	 * @since 6.5.0
	 *
	 * @var Tribe__Settings_Tab
	 */
	protected $settings_tab;

	/**
	 * Register the Tab.
	 *
	 * @since 5.6.2
	 *
	 * @param string $admin_page The ID of the admin page to register the tab on.
	 *
	 * @return void
	 */
	public function register_tab( $admin_page ) {
		if ( ! empty( $admin_page ) && Plugin_Settings::$settings_page_id !== $admin_page ) {
			return;
		}

		$tab_settings = [
			'priority'  => 35,
			'fields'    => $this->get_fields(),
			'show_save' => true,
		];

		/**
		 * Filter the tab settings options.
		 *
		 * @since 5.6.2
		 *
		 * @param array<string, mixed> $tab_settings Key value pairs of setting options.
		 */
		$tab_settings = apply_filters( 'tec_tickets_plus_integrations_tab_settings', $tab_settings );

		$this->settings_tab = new Tribe__Settings_Tab( static::$slug, esc_html__( 'Integrations', 'event-tickets-plus' ), $tab_settings );
	}

	/**
	 * Gets the settings tab.
	 *
	 * @since 6.5.0
	 *
	 * @return Tribe__Settings_Tab
	 */
	public function get_settings_tab() {
		return $this->settings_tab;
	}

	/**
	 * Register tab ID for network mode support.
	 *
	 * @since 5.6.2
	 *
	 * @param array<string> $tabs Array of tabs IDs for the Events settings page.
	 *
	 * @return array<string> Modified array of tab IDs.
	 */
	public function register_tab_id( array $tabs ): array {
		$tabs[] = static::$slug;

		return $tabs;
	}

	/**
	 * Gets the settings fields for the Integrations tab.
	 *
	 * @since 5.6.2
	 *
	 * @return array<string, array{
	 *     type: string,
	 *     html: string
	 * }> Array of field definitions for the settings page.
	 */
	public function get_fields(): array {
		$info_box = [
			'tec-settings-addons-title' => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__header-block tec-settings-form__header-block--horizontal">'
							. '<h2 class="tec-settings-form__section-header">'
							. _x( 'Integrations', 'Integrations tab header', 'event-tickets-plus' )
							. '</h2>'
							. '<p class="tec-settings-form__section-description">'
							. esc_html__(
								'Event Tickets and its add-ons integrate with other online tools and services to bring you additional features. Use the settings below to connect to our mobile app and manage your integrations.',
								'event-tickets-plus'
							)
							. '</p>'
							. '</div>',
			],
		];

		/**
		 * Filter the fields for the Integrations tab.
		 *
		 * @since 5.6.2
		 *
		 * @param array<string, array{
		 *     type: string,
		 *     html: string
		 * }> $fields Array of field definitions.
		 */
		$fields = apply_filters( 'tec_tickets_plus_integrations_tab_fields', [] );

		return array_merge( $info_box, $fields );
	}
}
