<?php
/**
 * Handles the integration with Event Automator.
 *
 * @since 6.0.0 Migrated to ET+ from Event Automator
 *
 * @package Tribe\Tickets\Plus\Integrations\Event_Automator
 */

namespace Tribe\Tickets\Plus\Integrations\Event_Automator;

use TEC\Common\Integrations\Plugin_Merge_Provider_Abstract;
use Tribe__Tickets_Plus__Main;

/**
 * Class Service_Provider
 *
 * @since 6.0.0 Migrated to ET+ from Event Automator
 *
 * @package Tribe\Tickets\Plus\Integrations\Event_Automator
 */
class Service_Provider extends Plugin_Merge_Provider_Abstract {

	/**
	 * Load the Event Automator framework.
	 *
	 * @since 6.0.0
	 *
	 * @return void
	 */
	public function init_merged_plugin(): void {
		if ( ! class_exists( \TEC\Event_Automator\Plugin::class, true ) ) {
			do_action( 'tribe_log', 'error', __CLASS__, [ 'error' => 'The Event Automator `Plugin` class does not exist.' ] );

			return;
		}

		// If the ECP plugin is active, we don't need to load the Event Automator integration again.
		if ( ! is_plugin_active( 'events-calendar-pro/events-calendar-pro.php' ) ) {
			tribe_register_provider( \TEC\Event_Automator\Plugin::class );
		}

		// Register ET+ specific services.
		$this->container->register( Power_Automate_Provider::class );
		$this->container->register( Zapier_Provider::class );
	}

	/**
	 * @inheritDoc
	 */
	public function get_plugin_updated_name(): string {
		return sprintf(
			/* Translators: %1$s is the new version number. */
			_x(
				'Event Tickets Plus to %1$s',
				'Plugin name upgraded to version number.',
				'event-tickets-plus'
			),
			Tribe__Tickets_Plus__Main::VERSION
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_merge_notice_slug(): string {
		return 'event-tickets-plus-event-automator-merge';
	}

	/**
	 * @inheritDoc
	 */
	public function get_merged_version(): string {
		return '6.0.0-dev';
	}

	/**
	 * @inheritDoc
	 */
	public function get_plugin_file_key(): string {
		return 'event-automator/event-automator.php';
	}

	/**
	 * @inheritDoc
	 */
	public function get_last_version_option_key(): string {
		return 'event-tickets-plus-schema-version';
	}

	/**
	 * @inheritDoc
	 */
	public function get_child_plugin_text_domain(): string {
		return 'event-automator';
	}

	/**
	 * @inheritDoc
	 */
	public function get_updated_merge_notice_message(): string {
		return sprintf(
			/* Translators: %1$s is the plugin that was deactivated, %2$s is the plugin name, %3$s is the opening anchor tag, %4$s is the closing anchor tag. */
			_x(
				'%1$s has been deactivated as it\'s now bundled into %2$s. %3$sLearn More%4$s.',
				'Notice message for the forced deactivation of the Event Automator plugin after updating Event Tickets Plus to the merged version.',
				'event-tickets-plus'
			),
			'Event Automator',
			'Event Tickets Plus',
			'<a target="_blank" href="https://evnt.is/1bdy">',
			'</a>'
		);
	}

	/**
	 * @inheritDoc
	 */
	public function get_activating_merge_notice_message(): string {
		return sprintf(
			/* Translators: %1$s: Event Automator, %2$s: Event Automator, %3$s: Event Tickets Plus, %4$s: Open anchor tag to the learn more page, %5$s: Closing tag. */
			_x(
				'%1$s could not be activated. The %1$s functionality has been merged into %2$s. %3$sLearn More%4$s.',
				'Notice message for the forced deactivation of the Event Automator plugin after attempting to activate, and the plugin was merged to Event Tickets Plus.',
				'event-tickets-plus'
			),
			'Event Automator',
			'Event Tickets Plus',
			'<a target="_blank" href="https://evnt.is/1bdy">',
			'</a>'
		);
	}
}
