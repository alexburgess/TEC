<?php

namespace TEC\Tickets_Wallet_Plus;

use TEC\Common\Contracts\Provider\Controller;
use Tribe\Tickets\Admin\Settings as Tickets_Settings;
use Tribe__Tickets_Plus__Main as Tickets_Plus;

/**
 * Class Assets
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus
 */
class Assets extends Controller {
	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		$plugin = Tickets_Plus::instance();

		tec_asset(
			$plugin,
			'tec-tickets-wallet-plus-passes-css',
			'tickets-wallet-plus-passes.css',
			[],
			null,
			[
				'groups' => [
					'tribe-tickets-rsvp',
					'tribe-tickets-page-assets',
					'tec-tickets-wallet-plus-order-page-assets',
					'event-tickets-admin-attendees',
				],
			]
		);

		tec_asset(
			$plugin,
			'tec-tickets-wallet-plus-admin-settings-css',
			'tickets-wallet-plus-settings.css',
			[],
			'admin_enqueue_scripts',
			[
				'conditional' => [ $this, 'is_admin_settings' ]
			]
		);

	}

	/**
	 * Check if the current page is the admin settings page.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool
	 */
	public function is_admin_settings(): bool {
		return $this->container->make( Tickets_Settings::class )->is_tec_tickets_settings();
	}

	/**
	 * @inheritDoc
	 */
	public function unregister(): void {

	}
}
