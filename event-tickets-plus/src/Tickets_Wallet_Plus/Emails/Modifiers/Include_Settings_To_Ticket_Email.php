<?php

namespace TEC\Tickets_Wallet_Plus\Emails\Modifiers;

use TEC\Tickets_Wallet_Plus\Emails\Settings\Ticket_Include_Passes_Setting;

/**
 * Class Include_Settings_To_Ticket_Email.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Emails\Modifiers
 */
class Include_Settings_To_Ticket_Email {

	/**
	 * Add the include pass settings.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $settings The email settings.
	 *
	 * @return array The modified email settings.
	 */
	public function add_include_pass_settings( $settings ): array {
		$include_passes_setting                         = tribe( Ticket_Include_Passes_Setting::class );
		$settings[ $include_passes_setting->get_key() ] = $include_passes_setting->get_definition();
		return $settings;
	}
}
