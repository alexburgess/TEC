<?php

namespace TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Qr_Codes_Setting;

/**
 * Class Settings
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Settings {
	/**
	 * Add attendee registration fields setting to Apple Wallet settings.
	 *
	 * @since 5.8.0
	 *
	 * @param array $fields Apple Wallet settings.
	 *
	 * @return array Modified Apple Wallet settings.
	 */
	public function add_attendee_registration_fields_setting( array $fields ): array {
		$attendee_registration_fields_setting = tribe( Attendee_Registration_Fields_Setting::class );
		$setting_key                          = $attendee_registration_fields_setting->get_key();
		$setting_definition                   = $attendee_registration_fields_setting->get_definition();
		$qr_code_key                          = tribe( Qr_Codes_Setting::class )->get_key();
		$arf_setting                          = [ $setting_key => $setting_definition ];

		// We want our Settings field within the HTML that we use for settings, so place it after our last real field.
		$fields = \Tribe__Main::array_insert_after_key(
			$qr_code_key,
			$fields,
			$arf_setting
		);

		return $fields;
	}
}
