<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Passes;

use TEC\Common\Contracts\Provider\Controller as Common_Controller_Contract;

/**
 * Class Modifier_Abstract.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Contracts\Passes
 */
abstract class Modifier_Abstract extends Common_Controller_Contract implements Modifier_Interface {

	/**
	 * The pass this modifier is associated with.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var Controller_Abstract The pass this modifier is associated with.
	 */
	protected Controller_Abstract $pass_controller;

	/**
	 * Whether the modifier has been registered.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var array Stores which modifiers have been registered.
	 */
	protected static array $registered = [];

	/**
	 * Builds the modifier dependencies, this is specifically protected so that you cannot instantiate it directly, only
	 * using the `register` method, which forces it to be a singleton that receives all the correct params.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param Controller_Abstract $pass The pass this modifier is associated with.
	 */
	public function set_pass_controller( Controller_Abstract $pass ): void {
		$this->pass_controller = $pass;
	}

	/**
	 * @inheritDoc
	 */
	abstract public function get_key(): string;

	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		// Only register itself once.
		if ( isset( static::$registered[ static::class ] ) ) {
			return;
		}

		static::$registered[ static::class ] = true;

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * @inheritDoc
	 */
	public function unregister(): void {
		// Only register itself once.
		if ( ! isset( static::$registered[ static::class ] ) ) {
			return;
		}

		$this->remove_actions();
		$this->remove_filters();

		unset( static::$registered[ static::class ] );
	}

	/**
	 * @inheritDoc
	 */
	public function get_pass_controller(): Controller_Abstract {
		return $this->pass_controller;
	}

	/**
	 * Add the actions for the modifier.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function add_actions(): void {}

	/**
	 * Remove the actions for the modifier.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function remove_actions(): void {}

	/**
	 * Add the filters for the modifier.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function add_filters(): void {}

	/**
	 * Removes the filters for the modifier.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function remove_filters(): void {}
}
