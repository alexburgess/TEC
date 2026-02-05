<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Settings;

/**
 * Class Settings_Abstract
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Contracts\Settings
 */
abstract class Settings_Abstract implements Settings_Interface {
	/**
	 * Slug for the section.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public static string $section_slug = '';

	/**
	 * @inheritDoc
	 */
	abstract public function add_settings_section( $sections ): array;

	/**
	 * @inheritDoc
	 */
	abstract public function get_settings( $fields, $section ): array;
}
