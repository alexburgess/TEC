<?php

namespace TEC\Tickets_Wallet_Plus\Admin\Settings;

use \TEC\Common\Contracts\Provider\Controller as Controller_Contract;


/**
 * Class Service_Provider
 *
 * @package TEC\Tickets_Wallet_Plus\Admin\Settings
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 */
class Controller extends Controller_Contract {

	/**
	 * Register the controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function do_register(): void {
		// Hook actions and filters.
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Unregister the provider.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function unregister(): void {
		// Remove actions and filters.
		$this->remove_actions();
		$this->remove_filters();
	}

	/**
	 * Add the action hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function add_actions() {
		add_action( 'tribe_settings_do_tabs', [ $this, 'add_tabs' ], 25 );
	}

	/**
	 * Add fhe filter hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function add_filters() {
		add_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'filter_include_wallet_tab_id' ] );
		add_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'filter_include_integrations_tab_id' ] );
		add_filter( 'wp_redirect', [ $this, 'filter_redirect_url' ] );
	}

	/**
	 * Remove the action hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function remove_actions() {
		remove_action( 'tribe_settings_do_tabs', [ $this, 'add_tabs' ], 25 );
	}

	/**
	 * Remove fhe filter hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function remove_filters() {
		remove_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'filter_include_wallet_tab_id' ] );
		remove_filter( 'tec_tickets_settings_tabs_ids', [ $this, 'filter_include_integrations_tab_id' ] );
		remove_filter( 'wp_redirect', [ $this, 'filter_redirect_url' ] );
	}

	/**
	 * Register the admin Settings Tab for this Plugin
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $admin_page Admin page id.
	 *
	 * @return void
	 */
	public function add_tabs( $admin_page ) {
		$this->container->make( Wallet_Tab::class )->register_tab( $admin_page );
	}

	/**
	 * Register the Wallet tab id.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $tabs Array of tabs IDs for the Events settings page.
	 *
	 * @return array
	 */
	public function filter_include_wallet_tab_id( array $tabs ): array {
		return $this->container->make( Wallet_Tab::class )->register_tab_id( $tabs );
	}

	/**
	 * Filters the redirect URL to determine whether or not section key needs to be added.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $url Redirect URL.
	 *
	 * @return string
	 */
	public function filter_redirect_url( $url ) {
		return $this->container->make( Wallet_Tab::class )->filter_redirect_url( $url );
	}
}
