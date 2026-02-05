<?php
/**
 * Handle AJAX requests for the Seating feature.
 *
 * @since 6.3.0
 */

namespace TEC\Tickets_Plus\Seating;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Seating\Admin\Ajax as Seating_Ajax;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Main as Woo_Provider;

/**
 * Class Ajax
 *
 * @since 6.3.0
 *
 * @pacakge TEC\Tickets_Plus\Seating
 */
class Ajax extends Controller_Contract {
	/**
	 * The action for handling seating tickets for WooCommerce.
	 *
	 * @var string
	 */
	public const ACTION_WOO_CART_UPDATE = 'tec_tickets_seating_woo_cart_update';

	/**
	 * Register the AJAX actions.
	 *
	 * @since 6.3.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'wp_ajax_' . self::ACTION_WOO_CART_UPDATE, [ $this, 'woo_seating_form_handler' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION_WOO_CART_UPDATE, [ $this, 'woo_seating_form_handler' ] );
		add_filter( 'tec_tickets_seating_frontend_ticket_block_data', [ $this, 'filter_ticket_block_localized_data' ] );
	}

	/**
	 * Unregister the AJAX actions.
	 *
	 * @since 6.3.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'wp_ajax_' . self::ACTION_WOO_CART_UPDATE, [ $this, 'woo_seating_form_handler' ] );
		remove_action( 'wp_ajax_nopriv_' . self::ACTION_WOO_CART_UPDATE, [ $this, 'woo_seating_form_handler' ] );
		remove_filter( 'tec_tickets_seating_frontend_ticket_block_data', [ $this, 'filter_ticket_block_localized_data' ] );
	}

	/**
	 * Handle the AJAX request for updating the WooCommerce cart.
	 *
	 * @since 6.3.0
	 *
	 * @return void The function does not return a value but will send the JSON response.
	 */
	public function woo_seating_form_handler(): void {
		if ( ! check_ajax_referer( Seating_Ajax::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				[
					'error' => __( 'Unauthorized action.', 'event-tickets-plus' ),
				],
				403
			);

			return;
		}
		// Clear the Woocommerce cart.
		WC()->cart->empty_cart();

		$tickets_cart = tribe( 'tickets.commerce.cart' );
		$tickets_cart->process_cart();

		/** @var Woo_Provider $woo_provider */
		$woo_provider = tribe( 'tickets-plus.commerce.woo' );

		wp_send_json_success(
			[
				'url' => $woo_provider->get_checkout_url(),
			],
			200
		);
	}

	/**
	 * Filter the localized data for the ticket block.
	 *
	 * @since 6.3.0
	 *
	 * @param array<string,mixed> $data The localized data.
	 *
	 * @return array<string,mixed> The filtered localized data.
	 */
	public function filter_ticket_block_localized_data( array $data ): array {
		$data['ACTION_WOO_CART_UPDATE'] = self::ACTION_WOO_CART_UPDATE;
		return $data;
	}
}
