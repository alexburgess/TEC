<?php

namespace TEC\Tickets_Wallet_Plus\Passes;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Controller_Abstract;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Controller as PDF_Controller;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Controller as Apple_Wallet_Controller;

/**
 * Passes Manager.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes
 */
class Manager {

	/**
	 * Passes controllers.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var array<Controller_Abstract|string> $passes The pass controllers.
	 */
	protected array $passes = [
		PDF_Controller::class,
		Apple_Wallet_Controller::class,
	];

	/**
	 * The existing hash, generated when the get_controllers was last called.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string|null The existing hash.
	 */
	protected ?string $existing_hash = null;

	/**
	 * Get pass controllers.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array<Controller_Abstract> All the pass controllers.
	 */
	public function get_controllers(): array {
		/**
		 * Filters the Wallet Plus passes.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array<Controller_Abstract|string> $passes Wallet Plus passes.
		 */
		$passes = apply_filters( 'tec_tickets_wallet_plus_passes', $this->passes );

		// If the hash is the same, return the passes.
		if ( $this->existing_hash === $this->generate_hash( $passes ) ) {
			return $passes;
		}

		// Prevent any passes from being a string.
		$passes = array_map(
			function ( $controller ) {
				if ( is_string( $controller ) ) {
					$controller = tribe( $controller );
				}
				return $controller;
			},
			$passes
		);

		$this->passes = array_filter(
			$passes,
			static function ( $modifier ) {
				return $modifier instanceof Controller_Abstract;
			}
		);

		// Update the existing hash.
		$this->existing_hash = $this->generate_hash( $this->passes );

		return $this->passes;
	}

	/**
	 * Get enabled pass controllers.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array
	 */
	public function get_enabled_controllers(): array {
		$passes = array_filter(
			$this->get_controllers(),
			static function ( $pass ) {
				return $pass->is_enabled();
			}
		);

		return $passes;
	}

	/**
	 * Returns a list of all pass controllers slugs.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array<string>
	 */
	public function get_controllers_slugs(): array {
		$passes = $this->get_controllers();
		$slugs  = [];
		foreach ( $passes as $pass ) {
			$slugs[] = $pass->get_slug();
		}
		return $slugs;
	}

	/**
	 * Given a set of pass controllers, generate a hash.
	 *
	 * @param array $pass_controllers List of pass controllers that will generate the hash with.
	 *
	 * @return string
	 */
	protected function generate_hash( array $pass_controllers ): string {
		return md5(
			serialize(
				array_map(
					static function( $controller ) {
						if ( ! is_object( $controller ) ) {
							return null;
						}

						return get_class( $controller );
					},
					$pass_controllers
				)
			)
		);
	}

	/**
	 * Get pass controller by slug.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $slug The pass slug.
	 *
	 * @return Controller_Abstract|bool The pass controller object or false if none exists.
	 */
	public function get_controllers_by_slug( string $slug ): ?Controller_Abstract {
		$pass_controllers = $this->get_controllers();
		foreach ( $pass_controllers as $controller ) {
			if ( $slug === $controller->get_slug() ) {
				return $controller;
			}
		}
		return null;
	}
}
