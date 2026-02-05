<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Passes;

interface Modifier_Interface {
	/**
	 * Gets the key for the modifier.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	public function get_key(): string;

	/**
	 * Fetches the pass this modifier is associated with.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return Controller_Abstract
	 */
	public function get_pass_controller(): Controller_Abstract;
}
