<?php
/**
 * Service Provider for the Square gateway functionality in Event Tickets Plus.
 *
 * @since 6.8.1
 *
 * @package TEC\Tickets_Plus\Commerce\Gateways\Square
 */

namespace TEC\Tickets_Plus\Commerce\Gateways\Square;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets_Plus__PUE as PUE;

/**
 * Service provider for the Square gateway in Event Tickets Plus.
 *
 * @since 6.8.1
 *
 * @package TEC\Tickets_Plus\Commerce\Gateways\Square
 */
class Controller extends Controller_Contract {

	/**
	 * Register the provider.
	 *
	 * @since 6.8.1
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_commerce_square_settings', [ $this, 'remove_square_fee_description' ] );
	}

	/**
	 * Unregister the provider.
	 *
	 * @since 6.8.1
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_commerce_square_settings', [ $this, 'remove_square_fee_description' ] );
	}

	/**
	 * Remove the Square fee description from settings when ETP license is valid.
	 *
	 * @since 6.8.1
	 *
	 * @param array $settings The list of Square Commerce settings.
	 *
	 * @return array
	 */
	public function remove_square_fee_description( $settings ) {
		if ( ! tribe( PUE::class )->is_current_license_valid() ) {
			return $settings;
		}

		unset( $settings['tickets-commerce-square-commerce-description'] );

		return $settings;
	}
}
