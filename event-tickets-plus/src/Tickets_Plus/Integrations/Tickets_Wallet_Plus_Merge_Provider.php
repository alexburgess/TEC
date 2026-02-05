<?php
/**
 * The Events Calendar Pro Integration with merge of Events Virtual plugin into Pro.
 *
 * @since 6.0.0
 * @package TEC\Tickets_Plus\Integrations
 */

namespace TEC\Tickets_Plus\Integrations;

use TEC\Common\Integrations\Plugin_Merge_Provider_Abstract;
use Tribe__Tickets_Plus__Main;

/**
 * Class Tickets_Wallet_Plus_Merge_Provider
 *
 * @since 6.0.0
 *
 * @package TEC\Tickets_Plus\Integrations
 */
class Tickets_Wallet_Plus_Merge_Provider extends Plugin_Merge_Provider_Abstract {
	/**
	 * @inheritDoc
	 */
	public function get_merge_notice_slug(): string {
		return 'event-tickets-plus-tickets-wallet-plus-merge';
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
		return 'event-tickets-wallet-plus/event-tickets-wallet-plus.php';
	}

	/**
	 * @inheritDoc
	 */
	public function get_last_version_option_key(): string {
		return 'latest_event_tickets_plus_version';
	}

	/**
	 * @inheritDoc
	 */
	public function get_child_plugin_text_domain(): string {
		return 'event-tickets-wallet-plus';
	}

	/**
	 * @inheritDoc
	 */
	public function get_plugin_updated_name(): string {
		return sprintf(
			/* Translators: %1$s: The plugin version */
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
	public function get_updated_merge_notice_message(): string {
		return sprintf(
			/* translators: %1$s: Event Tickets Wallet Plus, %2$s: Event Tickets Plus, %3$s: Open anchor tag to the learn more page, %4$s: Closing tag. */
			_x(
				'%1$s has been deactivated as it\'s now bundled into %2$s. %3$sLearn More%4$s.',
				'Notice message for the forced deactivation of the Tickets Wallet Plus plugin after updating Tickets Plus to the merged version.',
				'event-tickets-plus'
			),
			'Event Tickets Wallet Plus',
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
			/* translators: %1$s: Event Tickets Wallet Plus, %2$s: Event Tickets Wallet Plus, %3$s: Event Tickets Plus, %4$s: Open anchor tag to the learn more page, %5$s: Closing tag. */
			_x(
				'%1$s could not be activated. The %2$s functionality has been merged into %3$s. %4$sLearn More%5$s.',
				'Notice message for the forced deactivation of the Tickets Wallet Plus plugin after attempting to activate, and the plugin was merged to Event Tickets Plus.',
				'event-tickets-plus'
			),
			'Event Tickets Wallet Plus',
			'Event Tickets Wallet Plus',
			'Event Tickets Plus',
			'<a target="_blank" href="https://evnt.is/1bdy">',
			'</a>'
		);
	}
}
