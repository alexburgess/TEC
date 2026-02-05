<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Passes;

use TEC\Common\Contracts\Provider\Controller as Common_Controller_Contract;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Settings_Abstract;

abstract class Controller_Abstract extends Common_Controller_Contract implements Controller_Interface {
	/**
	 * The modifiers for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var array<string|Modifier_Abstract> The modifiers for the pass.
	 */
	protected array $modifiers = [];

	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		$this->add_actions();
		$this->add_filters();
		$this->register_modifiers();
	}

	/**
	 * @inheritDoc
	 */
	public function unregister(): void {
		$this->remove_actions();
		$this->remove_filters();
		$this->unregister_modifiers();
	}

	/**
	 * @inheritDoc
	 */
	abstract public function get_slug(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_name(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_settings(): Settings_Abstract;

	/**
	 * @inheritDoc
	 */
	public function get_modifiers(): array {
		// We apply the filter here to ensure that the modifiers are consistent.
		$modifiers = $this->filter_modifiers( $this->modifiers );

		$modifiers = array_map(
			function ( $modifier ) {
				$modifier_class          = is_string( $modifier ) ? $modifier : get_class( $modifier );
				$modifier_was_registered = $this->container->isBound( $modifier_class );

				// Only register if it is a string and it is not registered.
				if (
					! $modifier_was_registered
					&& method_exists( $modifier_class, 'do_register' )
				) {
					$this->container->register( $modifier_class );
					/**
					 * @uses \TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract::set_pass_controller()
					 */
					$modifier_object = $this->container->make( $modifier_class )->set_pass_controller( $this );
				}
				return $modifier_object ?? $this->container->make( $modifier_class );
			},
			$modifiers
		);

		$this->modifiers = array_filter(
			$modifiers,
			static function ( $modifier ) {
				return $modifier instanceof Modifier_Abstract;
			}
		);

		return $this->modifiers;
	}

	/**
	 * Enables the filtering of the modifiers to be consistent.
	 *
	 * @param array $modifiers The modifiers for the pass.
	 *
	 * @return array
	 */
	protected function filter_modifiers( array $modifiers ): array {
		/**
		 * Filters the modifiers for the pass.
		 *
		 * This filters allows you to remove or add modifiers to the pass, you can add the String class name or the
		 * instance of the modifier, keep in mind that any modifier here needs to extend the Modifier_Abstract class.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array<string|Modifier_Abstract> $modifiers The modifiers for the pass.
		 */
		return (array) apply_filters( 'tec_tickets_wallet_plus_passes_controller_get_modifiers', $modifiers, $this );
	}

	/**
	 * @inheritDoc
	 */
	public function register_modifiers(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Simply calling the method will make the modifiers register.
		$this->get_modifiers();
	}

	/**
	 * @inheritDoc
	 */
	public function unregister_modifiers(): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		foreach ( $this->get_modifiers() as $modifier ) {
			if ( ! method_exists( $modifier, 'unregister' ) ) {
				continue;
			}
			$modifier->unregister();
		}
	}

	/**
	 * Add the actions for the pass controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function add_actions(): void {

	}

	/**
	 * Remove the actions for the pass controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function remove_actions(): void {

	}

	/**
	 * Add the filters for the pass controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function add_filters(): void {
		add_filter( 'tec_tickets_wallet_plus_settings_sections', [ $this, 'filter_include_settings_section' ] );
		add_filter( 'tec_tickets_wallet_plus_settings_fields', [ $this, 'filter_include_settings_fields' ], 15, 2 );
	}

	/**
	 * Removes the filters for the pass controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	protected function remove_filters(): void {
		remove_filter( 'tec_tickets_wallet_plus_settings_sections', [ $this, 'filter_include_settings_section' ] );
		remove_filter( 'tec_tickets_wallet_plus_settings_fields', [ $this, 'filter_include_settings_fields' ], 15 );
	}

	/**
	 * Add the settings section for this pass settings.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $sections The sections.
	 *
	 * @return array The modified sections settings.
	 */
	public function filter_include_settings_section( $sections ): array {
		return $this->get_settings()->add_settings_section( (array) $sections );
	}

	/**
	 * Add the settings for this pass settings.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array  $fields  The fields.
	 * @param string $section The section.
	 *
	 * @return array The modified sections settings.
	 */
	public function filter_include_settings_fields( $fields, $section ): array {
		return $this->get_settings()->get_settings( (array) $fields, (string) $section );
	}

	/**
	 * Determines if this controller is enabled.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return true;
	}
}
