<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Settings;

/**
 * Interface Setting_Interface.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Contracts\Setting
 */
interface Setting_Interface {
	/**
	 * Get the setting key.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The key for the setting in the options table.
	 */
	public function get_key(): string;

	/**
	 * Get the setting label.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The label for the setting in the settings page.
	 */
	public function get_label(): string;

	/**
	 * Get the setting slug, mostly used for the filter names.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	public function get_slug(): string;


	/**
	 * Get the setting default value.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return mixed The setting default value.
	 */
	public function get_default();

	/**
	 * Get the default validation callback.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return Callable|array|string The validation callback.
	 */
	public function get_validation_callback();

	/**
	 * Get the default validation type.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The validation type.
	 */
	public function get_validation_type(): string;

	/**
	 * Get the setting tooltip.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string|null The setting tooltip.
	 */
	public function get_tooltip(): ?string;

	/**
	 * Get whether field can be empty.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool Can setting field be empty.
	 */
	public function can_be_empty(): bool;

	/**
	 * Get the setting definition.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array The setting definition.
	 */
	public function get_definition(): array;

	/**
	 * Get the setting value.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return mixed The setting value.
	 */
	public function get_value();
}
