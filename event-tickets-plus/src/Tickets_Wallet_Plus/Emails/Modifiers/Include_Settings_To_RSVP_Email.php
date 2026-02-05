<?php

namespace TEC\Tickets_Wallet_Plus\Emails\Modifiers;

use TEC\Tickets\Emails\Email\RSVP as RSVP_Email;
use TEC\Tickets_Wallet_Plus\Emails\Settings\RSVP_Include_Passes_Setting;

/**
 * Class Include_Settings_To_RSVP_Email.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Emails\Modifiers
 */
class Include_Settings_To_RSVP_Email {

	/**
	 * Add the include pass fieldset attributes.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $definition The include passes setting definition.
	 *
	 * @return array The modified include passes setting definition.
	 */
	public function add_fieldset_attributes( $definition ) {
		$definition['fieldset_attributes'] = [
			'data-depends'                  => '#' . tribe( RSVP_Email::class )->get_option_key( 'use-ticket-email' ),
			'data-condition-is-not-checked' => true,
		];
		return $definition;
	}

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
		$include_passes_setting                         = tribe( RSVP_Include_Passes_Setting::class );
		$settings[ $include_passes_setting->get_key() ] = $include_passes_setting->get_definition();
		return $settings;
	}
}
