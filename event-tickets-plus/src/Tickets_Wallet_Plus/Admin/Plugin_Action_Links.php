<?php

namespace TEC\Tickets_Wallet_Plus\Admin;

use TEC\Tickets_Wallet_Plus\Plugin;
use TEC\Tickets_Wallet_Plus\Admin\Settings\Wallet_Tab;

/**
 * Class Plugin_Action_Links
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Admin
 */
class Plugin_Action_Links {

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
		$actions['tec-tickets-wallet-plus-settings']        = '<a href="' . tribe( Wallet_Tab::class )->get_url() . '">' . esc_html__( 'Settings', 'event-tickets-plus' ) . '</a>';
		$actions['tec-tickets-wallet-plus-getting-started'] = '<a href="https://evnt.is/event-tickets-wallet-plus" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Getting started', 'event-tickets-plus' ) . '</a>';

		return $actions;
	}
}
