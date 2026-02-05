<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Settings;

/**
 * Interface Settings_Interface
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Contracts\Settings
 */
interface Settings_Interface {
	/**
	 * Add the settings section.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $sections The list of sections.
	 *
	 * @return array $sections The modified list of sections.
	 */
	public function add_settings_section( $sections ): array;

	/**
	 * Get the settings for the pass provider.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array  $fields  The fields.
	 * @param string $section The current section.
	 *
	 * @return array $fields The modified fields.
	 */
	public function get_settings( $fields, $section ): array;
}
