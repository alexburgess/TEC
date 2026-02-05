<?php
namespace TEC\Tickets_Wallet_Plus\Admin;

use \TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets_Wallet_Plus\Admin\Settings\Wallet_Tab;
use TEC\Tickets_Wallet_Plus\Plugin;

/**
 * Class Provider
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Admin
 */
class Controller extends Controller_Contract {
	/**
	 * Register the controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function do_register(): void {
		$this->container->singleton( Plugin_Action_Links::class );
		$this->container->register( Settings\Controller::class );

		// @todo: Admin notifications for required PHP libraries.

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
		$this->remove_filters();
	}

	/**
	 * Add the action hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function add_actions(): void {
		$plugin_dir = trailingslashit( tribe( Plugin::class )->plugin_dir );
		add_action(
			"plugin_action_links_{$plugin_dir}event-tickets-wallet-plus.php",
			[ $this, 'add_links_to_plugin_actions' ]
		);
	}

	/**
	 * Add the filter hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function add_filters(): void {
		add_filter( 'tec_tickets_plus_integrations_tab_fields', [ $this, 'add_qr_settings' ], 15 );
	}

	/**
	 * Remove the action hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function remove_actions(): void {
		$plugin_dir = trailingslashit( tribe( Plugin::class )->plugin_dir );
		remove_action(
			"plugin_action_links_{$plugin_dir}event-tickets-wallet-plus.php",
			[ $this, 'add_links_to_plugin_actions' ]
		);
	}

	/**
	 * Remove the filter hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function remove_filters(): void {
		remove_filter( 'tec_tickets_plus_integrations_tab_fields', [ $this, 'add_qr_settings' ], 15 );
	}

	/**
	 * Add links to plugin actions.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $actions The array with the links on the plugin actions.
	 *
	 * @return array $actions The modified array with the links.
	 */
	public function add_links_to_plugin_actions( $actions ) {
		return $this->container->make( Plugin_Action_Links::class )->add_links_to_plugin_actions( $actions );
	}

	/**
	 * Enqueue assets for the admin settings page.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @deprecated 6.5.0
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		_deprecated_function( __METHOD__, '6.5.0' );
	}

	/**
	 * Determines if the assets should be enqueued.
	 *
	 * This method checks if the current admin page is the Wallet settings page.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool True if the current page is the Wallet settings page, false otherwise.
	 */
	public function should_enqueue_assets(): bool {
		return tribe( Wallet_Tab::class )->is_current_page();
	}

	/**
	 * Add QR settings to the integrations tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $settings_fields The settings fields.
	 *
	 * @return array The modified settings fields.
	 */
	public function add_qr_settings( $settings_fields ) {
		return $this->container->make( Modifiers\Add_QR_Settings::class )->add_settings( $settings_fields );
	}
}
