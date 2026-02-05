<?php

namespace TEC\Tickets_Wallet_Plus\Emails\Settings;

use TEC\Tickets\Emails\Email\RSVP;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Checkbox_List_Setting_Abstract;
use TEC\Tickets_Wallet_Plus\Passes\Manager;

/**
 * Class Include_Passes_Setting.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Emails\Settings
 */
class RSVP_Include_Passes_Setting extends Checkbox_List_Setting_Abstract {
	/**
	 * {@inheritdoc}
	 */
	public function get_key(): string {
		// Get key name from Tickets Emails RSVP settings.
		return tribe( RSVP::class )->get_option_key( 'include-wallet-plus-passes' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return esc_html__( 'Wallet & PDF', 'event-tickets-plus' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_slug(): string {
		// Base slug name off of key name.
		return str_replace( '-', '_', $this->get_key() );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_default(): array {
		$controllers = tribe( Manager::class )->get_enabled_controllers();
		$enabled_slugs  = [];
		foreach ( $controllers as $controller ) {
			$enabled_slugs[] = $controller->get_slug();
		}
		return $enabled_slugs;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_options(): array {
		$options     = [];
		$controllers = tribe( Manager::class )->get_enabled_controllers();

		foreach ( $controllers as $controller ) {
			$options[ $controller->get_slug() ] = sprintf(
				// Translators: %s is the pass name.
				__( 'Include %s in email', 'event-tickets-plus' ),
				$controller->get_name()
			);
		}
		return $options;
	}

	/**
	 * Check if pass is included based on slug.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $slug The pass slug.
	 *
	 * @return bool If the pass is included.
	 */
	public function is_pass_included( string $slug ): bool {
		$passes = $this->get_value();
		return in_array( $slug, $passes );
	}
}
