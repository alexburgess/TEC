<?php
/**
 * Control WooCommerce integration for the Seating feature.
 *
 * @since 6.8.4
 *
 * @package TEC\Tickets_Plus\Seating\Commerce\TicketsCommerce
 */

namespace TEC\Tickets_Plus\Seating\Commerce\TicketsCommerce;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Seating\Orders\Cart as Seating_Cart;

/**
 * Class Controller for the Seating feature with Tickets Commerce.
 *
 * @since 6.8.4
 *
 * @package TEC\Tickets_Plus\Seating\Commerce\TicketsCommerce
 */
class Controller extends Controller_Contract {
	/**
	 * Register the hooks.
	 *
	 * @since 6.8.4
	 */
	public function do_register(): void {
		add_filter( 'tec_tickets_plus_seating_is_checkout_page', [ $this, 'filter_is_checkout_page' ] );
		add_filter( 'tec_tickets_plus_seating_register_ar_assets', [ $this, 'filter_should_register_ar_assets' ] );
	}
	
	/**
	 * Unregister the hooks.
	 *
	 * @since 6.8.4
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_plus_seating_is_checkout_page', [ $this, 'filter_is_checkout_page' ] );
		remove_filter( 'tec_tickets_plus_seating_register_ar_assets', [ $this, 'filter_should_register_ar_assets' ] );
	}
	
	/**
	 * Filters whether the current page is the Tickets Commerce checkout page.
	 *
	 * @since 6.8.4
	 *
	 * @param bool $is_checkout_page Whether the current page is the Tickets Commerce checkout page.
	 *
	 * @return bool Whether the current page is the Tickets Commerce checkout page.
	 */
	public function filter_is_checkout_page( bool $is_checkout_page ): bool {
		// If already on checkout page, then no need to determine.
		if ( $is_checkout_page ) {
			return $is_checkout_page;
		}
		
		return tribe( Checkout::class )->is_current_page();
	}
	
	/**
	 * Filters whether AR assets should be registered.
	 *
	 * @since 6.8.4
	 *
	 * @param bool $should_register Whether AR assets should be registered.
	 *
	 * @return bool Whether AR assets should be registered.
	 */
	public function filter_should_register_ar_assets( bool $should_register ): bool {
		// If other providers already registered the assets, we don't need to do it.
		if ( $should_register ) {
			return $should_register;
		}
		
		return tribe( Seating_Cart::class )->cart_has_seating_tickets();
	}
}
