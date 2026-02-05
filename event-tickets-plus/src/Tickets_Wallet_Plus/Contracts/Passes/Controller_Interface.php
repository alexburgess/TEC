<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Passes;

use TEC\Tickets_Wallet_Plus\Contracts\Settings\Settings_Abstract;

/**
 * Interface Controller_Interface
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Contracts\Controller
 */
interface Controller_Interface {

	/**
	 * Gets the slug for the pass type.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	public function get_slug(): string;

	/**
	 * Gets the name for the pass type.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Gets the instance of settings for this pass type.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return Settings_Abstract
	 */
	public function get_settings(): Settings_Abstract;

	/**
	 * Gets the modifiers for the pass, will filter to ensure that only valid modifiers are returned.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array<Modifier_Abstract>
	 */
	public function get_modifiers(): array;

	/**
	 * Registers all the modifiers associated with this pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function register_modifiers(): void;

	/**
	 * Unregisters all the modifiers associated with this pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function unregister_modifiers(): void;
}
