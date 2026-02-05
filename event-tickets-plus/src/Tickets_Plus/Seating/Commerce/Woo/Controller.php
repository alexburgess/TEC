<?php
/**
 * Control WooCommerce integration for the Seating feature.
 *
 * @since 6.3.0
 *
 * @package TEC\Tickets_Plus\Seating\Commerce\Woo
 */

namespace TEC\Tickets_Plus\Seating\Commerce\Woo;

use Exception;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\StellarWP\Arrays\Arr;
use TEC\Tickets\Seating\Frontend\Session;
use TEC\Tickets\Seating\Frontend\Timer;
use TEC\Tickets\Seating\Orders\Cart;
use TEC\Tickets\Seating\Service\Reservations;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Main as Woo_Provider;
use TEC\Tickets\Seating\Meta;
use WC_Order;
use TEC\Tickets\Seating\Logging;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WC_Product;
use TEC\Tickets\Seating\Service\Service;
use Automattic\WooCommerce\Internal\DataStores\Orders\DataSynchronizer;
use WP_Error;
use WP_Post;

/**
 * Class Controller
 *
 * @since 6.3.0
 *
 * @package TEC\Tickets_Plus\Seating\Commerce\Woo
 */
class Controller extends Controller_Contract {
	use Logging;

	/**
	 * The meta key used to store the reservations confirmed state of an order.
	 *
	 * @since 6.3.0
	 *
	 * @var string
	 */
	public const META_KEY_RESERVATIONS_CONFIRMED = '_tec_slr_reservations_confirmed';

	/**
	 * The meta key used to store the slr reservations as order meta.
	 *
	 * @since 6.3.0
	 *
	 * @var string
	 */
	public const META_KEY_RESERVATIONS = '_tec_slr_reservations';

	/**
	 * The Cart instance.
	 *
	 * @var Cart
	 */
	private Cart $cart;

	/**
	 * The Session instance.
	 *
	 * @var Session
	 */
	private Session $session;

	/**
	 * Controller constructor.
	 *
	 * @param Container $container The DI container.
	 * @param Cart      $cart      The Cart instance.
	 * @param Session   $session   The Session instance.
	 */
	public function __construct(
		Container $container,
		Cart $cart,
		Session $session
	) {
		parent::__construct( $container );
		$this->cart    = $cart;
		$this->session = $session;
	}

	/**
	 * Register the actions.
	 *
	 * @since 6.3.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_plus_woo_generated_tickets', [ $this, 'save_attendee_seating_info' ] );
		add_action( 'woocommerce_checkout_create_order', [ $this, 'update_order_meta_for_seating' ] );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'update_order_meta_for_seating' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_updates' ], 15, 4 );

		// Handle order deletion or trash actions.
		add_action( 'wp_trash_post', [ $this, 'handle_order_deletion' ] );
		add_action( 'before_delete_post', [ $this, 'handle_order_deletion' ] );
		add_action( 'woocommerce_before_delete_order', [ $this, 'handle_order_deletion' ] );
		add_action( 'woocommerce_before_trash_order', [ $this, 'handle_order_deletion' ] );

		// Handle attendee deletion.
		add_filter( 'pre_trash_post', [ $this, 'handle_attendee_delete' ], 99, 2 );
		add_filter( 'pre_delete_post', [ $this, 'handle_attendee_delete' ], 99, 2 );

		// Timer actions.
		add_action( 'woocommerce_before_cart_table', tribe_callback( Timer::class, 'render_to_sync' ) );
		add_action( 'woocommerce_before_checkout_form', tribe_callback( Timer::class, 'render_to_sync' ) );
		add_action( 'pre_render_block', [ $this, 'render_timer_for_cart_and_checkout' ], 10, 2 );
		add_action( 'tec_tickets_seating_session_interrupt', [ $this, 'clear_cart_on_session_interrupt' ], 10, 2 );

		add_filter( 'tribe_tickets_attendee_data', [ $this, 'add_seating_data_to_attendee' ], 10, 2 );
		add_filter( 'event_tickets_woo_ticket_generating_order_stati', [ $this, 'filter_attendee_generation_status' ], 10, 2 );

		// Settings filter.
		add_filter( 'tribe_tickets_woo_settings', [ $this, 'filter_attendee_generation_status_notice' ] );

		add_filter( 'tec_tickets_plus_seating_register_ar_assets', [ $this, 'cart_has_seating_tickets' ] );
		add_filter( 'tec_tickets_plus_seating_is_checkout_page', [ $this, 'is_checkout_page' ] );

		// Cart restrictions.
		add_filter( 'woocommerce_quantity_input_args', [ $this, 'filter_cart_quantity_input' ], 10, 2 );
		add_filter( 'woocommerce_store_api_product_quantity_editable', [ $this, 'filter_block_cart_quantity_input' ], 10, 2 );
		add_action( 'woocommerce_check_cart_items', [ $this, 'validate_cart_contents' ] );
		add_action( 'woocommerce_add_to_cart', [ $this, 'validate_cart_contents' ] );

		add_filter( 'tribe_tickets_plus_woo_meta_data_filter', [ $this, 'display_seating_data_for_order_details' ], 10, 2 );
		add_filter( 'tec_tickets_plus_woocommerce_attendee_regeneration', [ $this, 'disable_attendee_regeneration' ], 10, 2 );

		// Remove the default WooCommerce order again button.
		add_action( 'woocommerce_order_details_before_order_table', [ $this, 'hide_order_again_button' ] );
	}

	/**
	 * Unregister the actions.
	 *
	 * @since 6.3.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_plus_woo_generated_tickets', [ $this, 'save_attendee_seating_info' ] );
		remove_action( 'woocommerce_checkout_create_order', [ $this, 'update_order_meta_for_seating' ] );
		remove_action( 'woocommerce_store_api_checkout_update_order_from_request', [ $this, 'update_order_meta_for_seating' ] );
		remove_action( 'woocommerce_order_status_changed', [ $this, 'handle_order_status_updates' ], 15 );
		remove_action( 'wp_trash_post', [ $this, 'handle_order_deletion' ] );
		remove_action( 'before_delete_post', [ $this, 'handle_order_deletion' ] );
		remove_action( 'woocommerce_before_delete_order', [ $this, 'handle_order_deletion' ] );
		remove_action( 'woocommerce_before_trash_order', [ $this, 'handle_order_deletion' ] );
		remove_action( 'tec_tickets_seating_session_interrupt', [ $this, 'clear_cart_on_session_interrupt' ] );
		remove_action( 'woocommerce_before_checkout_form', tribe_callback( Timer::class, 'render_to_sync' ) );
		remove_action( 'woocommerce_before_cart_table', tribe_callback( Timer::class, 'render_to_sync' ) );
		remove_action( 'pre_render_block', [ $this, 'render_timer_for_cart_and_checkout' ] );
		remove_action( 'woocommerce_order_details_before_order_table', [ $this, 'hide_order_again_button' ] );
		remove_action( 'woocommerce_check_cart_items', [ $this, 'validate_cart_contents' ] );
		remove_action( 'woocommerce_add_to_cart', [ $this, 'validate_cart_contents' ] );

		remove_filter( 'tribe_tickets_attendee_data', [ $this, 'add_seating_data_to_attendee' ] );
		remove_filter( 'event_tickets_woo_ticket_generating_order_stati', [ $this, 'filter_attendee_generation_status' ] );
		remove_filter( 'tribe_tickets_woo_settings', [ $this, 'filter_attendee_generation_status_notice' ] );
		remove_filter( 'tec_tickets_plus_seating_register_ar_assets', [ $this, 'cart_has_seating_tickets' ] );
		remove_filter( 'tec_tickets_plus_seating_is_checkout_page', [ $this, 'is_checkout_page' ] );
		remove_filter( 'woocommerce_quantity_input_args', [ $this, 'filter_cart_quantity_input' ], 10, 2 );
		remove_filter( 'tribe_tickets_plus_woo_meta_data_filter', [ $this, 'display_seating_data_for_order_details' ], 10, 2 );
		remove_filter( 'tec_tickets_plus_woocommerce_attendee_regeneration', [ $this, 'disable_attendee_regeneration' ] );
		remove_filter( 'woocommerce_store_api_product_quantity_editable', [ $this, 'filter_block_cart_quantity_input' ] );
		remove_filter( 'pre_trash_post', [ $this, 'handle_attendee_delete' ], 99, 2 );
		remove_filter( 'pre_delete_post', [ $this, 'handle_attendee_delete' ], 99, 2 );
	}

	/**
	 * Save the attendee seating information.
	 *
	 * @since 6.3.0
	 *
	 * @param int $order_id The order ID.
	 *
	 * @return void
	 */
	public function save_attendee_seating_info( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		/** @var Woo_Provider $woo_provider */
		$woo_provider = tribe( 'tickets-plus.commerce.woo' );

		$has_tickets = $order->get_meta( $woo_provider->order_has_tickets );

		if ( ! $has_tickets ) {
			return;
		}

		$reservations_confirmed = $order->get_meta( $this::META_KEY_RESERVATIONS_CONFIRMED );

		if ( $reservations_confirmed ) {
			return;
		}

		$attendees = $woo_provider->get_attendees_by_id( $order_id );

		if ( empty( $attendees ) || ! is_array( $attendees ) ) {
			return;
		}

		foreach ( $attendees as $attendee ) {
			if ( ! isset( $attendee['product_id'], $attendee['attendee_id'], $attendee['event_id'] ) ) {
				continue;
			}

			$seated_ticket = get_post_meta( $attendee['product_id'], META::META_KEY_SEAT_TYPE, true );

			if ( ! $seated_ticket ) {
				continue;
			}

			$has_reservation = get_post_meta( $attendee['attendee_id'], META::META_KEY_RESERVATION_ID, true );

			if ( $has_reservation ) {
				continue;
			}

			$attendee_post = get_post( $attendee['attendee_id'] );
			if ( ! $attendee_post ) {
				continue;
			}

			$attendee_post->event_id   = (int) $attendee['event_id'];
			$attendee_post->product_id = (int) $attendee['product_id'];

			$ticket_object = $woo_provider->get_ticket( $attendee_post->event_id, $attendee_post->product_id );

			if ( ! $ticket_object instanceof Ticket_Object ) {
				continue;
			}

			$this->cart->save_seat_data_for_attendee( $attendee_post, $ticket_object );
		}

		$this->confirm_all_reservations_on_completion();
		$order->update_meta_data( self::META_KEY_RESERVATIONS_CONFIRMED, true );
		$order->add_order_note( __( 'Seating reservations confirmed.', 'event-tickets-plus' ) );
		$order->save();
	}

	/**
	 * On completion of an Order, confirm all the reservations and clear the session.
	 *
	 * @since 6.3.0
	 *
	 * @return void
	 */
	public function confirm_all_reservations_on_completion(): void {
		// Attendees needing the session information will likely be generated after, warmup the session cache now.
		$this->cart->warmup_caches();

		$this->session->confirm_all_reservations();
	}

	/**
	 * Add seating data to the Woo attendee data.
	 *
	 * @since 6.3.0
	 *
	 * @param array<string,mixed> $attendee_data The attendee data.
	 * @param string              $provider_slug The provider slug.
	 *
	 * @return array<string,mixed> The attendee data.
	 */
	public function add_seating_data_to_attendee( array $attendee_data, string $provider_slug ): array {
		/** @var Woo_Provider $woo_provider */
		$woo_provider = tribe( 'tickets-plus.commerce.woo' );

		if ( $provider_slug !== $woo_provider->orm_provider ) {
			return $attendee_data;
		}
		$attendee_id = $attendee_data['attendee_id'];
		$seat_label  = get_post_meta( $attendee_id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true );

		if ( ! $seat_label ) {
			return $attendee_data;
		}

		$attendee_data['seat_label']   = get_post_meta( $attendee_id, Meta::META_KEY_ATTENDEE_SEAT_LABEL, true );
		$attendee_data['seat_type_id'] = get_post_meta( $attendee_id, Meta::META_KEY_SEAT_TYPE, true );
		$attendee_data['layout_id']    = get_post_meta( $attendee_id, Meta::META_KEY_LAYOUT_ID, true );

		return $attendee_data;
	}

	/**
	 * Update the order meta for seating.
	 *
	 * @since 6.3.0
	 *
	 * @param WC_Order $order The order.
	 *
	 * @return void
	 */
	public function update_order_meta_for_seating( WC_Order $order ): void {
		$order_items = $order->get_items();

		foreach ( $order_items as $item ) {
			$product = $item->get_product();

			if ( ! $product ) {
				continue;
			}

			$is_seating = get_post_meta( $product->get_id(), META::META_KEY_SEAT_TYPE, true );

			if ( ! $is_seating ) {
				continue;
			}
			/** @var Woo_Provider $woo_provider */
			$woo_provider = tribe( 'tickets-plus.commerce.woo' );

			$event_id     = get_post_meta( $product->get_id(), $woo_provider->event_key, true );
			$reservations = $this->session->get_post_ticket_reservations( $event_id, $product->get_id() );

			if ( empty( $reservations ) ) {
				continue;
			}

			$order->update_meta_data( self::META_KEY_RESERVATIONS, true );
			break;
		}
	}

	/**
	 * Filter the attendee generation status.
	 *
	 * @since 6.3.0
	 *
	 * @param array<string> $status_list The status list.
	 * @param WC_Order      $order       The Order object.
	 *
	 * @return array<string> The status list.
	 */
	public function filter_attendee_generation_status( $status_list, $order ): array {
		// If the status list already contains immediate, we don't need to check for seating.
		if ( in_array( 'immediate', $status_list, true ) ) {
			return $status_list;
		}

		$has_reservations = (bool) $order->get_meta( self::META_KEY_RESERVATIONS );

		if ( $has_reservations ) {
			$status_list[] = 'immediate';
		}

		return $status_list;
	}

	/**
	 * Handle order status updates.
	 *
	 * @since 6.3.0
	 *
	 * @param int      $order_id    The order ID.
	 * @param string   $from_status The from status.
	 * @param string   $to_status   The to status.
	 * @param WC_Order $order       The order object.
	 *
	 * @return void
	 */
	public function handle_order_status_updates( $order_id, $from_status, $to_status, $order ): void {
		// Cancel reservations for cancel and failed statuses.
		if ( ! in_array( $to_status, [ 'cancelled', 'failed' ], true ) ) {
			return;
		}

		$this->cancel_reservations_for_order( $order );
	}

	/**
	 * Cancel the reservations for an order.
	 *
	 * @since 6.3.0
	 *
	 * @param WC_Order $order The order.
	 *
	 * @return bool Whether the reservations were cancelled.
	 */
	public function cancel_reservations_for_order( WC_Order $order ): bool {
		$has_seating = (bool) $order->get_meta( self::META_KEY_RESERVATIONS );

		if ( ! $has_seating ) {
			return false;
		}

		$confirmed = $order->get_meta( self::META_KEY_RESERVATIONS_CONFIRMED );

		if ( ! $confirmed ) {
			return false;
		}

		// Get the attendees for this order and fetch the reservations.
		$attendees = tribe( Woo_Provider::class )->get_attendees_by_order_id( $order->get_id() );

		if ( empty( $attendees ) ) {
			return false;
		}

		$failed = false;

		$event_reservations = [];
		foreach ( $attendees as $attendee ) {
			$reservation_id = get_post_meta( $attendee['attendee_id'], Meta::META_KEY_RESERVATION_ID, true );

			if ( ! $reservation_id ) {
				continue;
			}

			$event_id = get_post_meta( $attendee['product_id'], tribe( Woo_Provider::class )->event_key, true );

			if ( ! $event_id ) {
				continue;
			}

			if ( ! isset( $event_reservations[ $event_id ] ) ) {
				$event_reservations[ $event_id ] = [];
			}

			$event_reservations[ $event_id ][] = $reservation_id;
		}

		$reservations_handler = tribe( Reservations::class );

		foreach ( $event_reservations as $event => $reservations ) {
			$cancelled = $reservations_handler->cancel( $event, $reservations );

			if ( ! $cancelled ) {
				$this->log_error(
					'Failed to cancel reservations from service during order status update.',
					[
						'source'       => __METHOD__,
						'order_id'     => $order->get_id(),
						'event_id'     => $event,
						'reservations' => $reservations,
					]
				);

				$failed = true;

				continue;
			}

			// If the reservations were cancelled, delete them from the attendees.
			try {
				$reservations_handler->delete_reservations_from_attendees( $reservations );
			} catch ( \Exception $e ) {
				$this->log_error(
					'Failed to delete reservations from attendees after cancellation from service.',
					[
						'source'       => __METHOD__,
						'error'        => $e->getMessage(),
						'order_id'     => $order->get_id(),
						'event_id'     => $event,
						'reservations' => $reservations,
					]
				);
			}
		}

		if ( $failed ) {
			return false;
		}

		$order->delete_meta_data( self::META_KEY_RESERVATIONS );
		$order->add_order_note( __( 'Seating reservations cancelled.', 'event-tickets-plus' ) );
		$order->save();

		return true;
	}

	/**
	 * Filter the attendee generation status notice.
	 *
	 * @since 6.3.0
	 *
	 * @param array<string,mixed> $settings_fields The settings fields.
	 *
	 * @return array<string,mixed> The settings fields.
	 */
	public function filter_attendee_generation_status_notice( array $settings_fields ): array {
		$service_status = tribe( Service::class )->get_status();

		if ( $service_status->has_no_license() || $service_status->is_license_invalid() ) {
			return $settings_fields;
		}

		// Check if the required settings field exists and has a tooltip.
		if ( empty( $settings_fields['tickets-woo-generation-status']['tooltip'] ) ) {
			return $settings_fields;
		}

		// Define the notice text for assigned seating behavior.
		$notice_text = esc_html_x(
			'attendees with assigned seats are always generated when the order is created to prevent overbooking.',
			'WooCommerce settings assigned seating notice',
			'event-tickets-plus'
		);

		// Create formatted note with label and notice text.
		$note_label = esc_html_x( 'Note:', 'WooCommerce settings note', 'event-tickets-plus' );
		$note_html  = sprintf(
			'<br/><br/><b>%1$s</b> %2$s',
			$note_label,
			$notice_text
		);

		// Append the note to the existing tooltip.
		$settings_fields['tickets-woo-generation-status']['tooltip'] .= ' ' . $note_html;

		return $settings_fields;
	}

	/**
	 * Check if the cart has seating tickets.
	 *
	 * @since 6.3.0
	 *
	 * @param bool $register_assets Whether to register the assets.
	 *
	 * @return bool
	 */
	public function cart_has_seating_tickets( bool $register_assets ): bool {
		// If other providers already registered the assets, we don't need to do it.
		if ( $register_assets ) {
			return true;
		}

		// If the cart has any item with seating enabled then we return true.
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = $cart_item['product_id'];

			$is_seating = get_post_meta( $product_id, Meta::META_KEY_SEAT_TYPE, true );

			if ( $is_seating ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if the current page is the checkout page.
	 *
	 * @since 6.3.0
	 *
	 * @param bool $is_checkout_page Whether the current page is the checkout page.
	 *
	 * @return bool
	 */
	public function is_checkout_page( bool $is_checkout_page ): bool {
		return $is_checkout_page || is_checkout() || is_cart();
	}

	/**
	 * Clear the cart on session interrupt.
	 *
	 * @since 6.3.0
	 *
	 * @param int    $post_id The post ID.
	 * @param string $token   The token.
	 *
	 * @return void
	 */
	public function clear_cart_on_session_interrupt( int $post_id, string $token ): void {
		if ( Tickets::get_event_ticket_provider( $post_id ) !== Woo_Provider::class ) {
			return;
		}

		WC()->cart->empty_cart();
	}

	/**
	 * Filter the cart quantity input to make it readonly for seating tickets.
	 *
	 * @since 6.3.0
	 *
	 * @param array<string,mixed> $args The arguments.
	 * @param WC_Product          $product The product.
	 *
	 * @return array<string,mixed> The arguments.
	 */
	public function filter_cart_quantity_input( array $args, WC_Product $product ): array {
		$seat_type = get_post_meta( $product->get_id(), Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $seat_type ) {
			return $args;
		}

		$args['readonly'] = true;

		return $args;
	}

	/**
	 * Display seating data for order details.
	 *
	 * @since 6.3.0
	 *
	 * @param array<string,mixed> $meta_data The meta data.
	 * @param array<string,mixed> $attendee The attendee.
	 *
	 * @return array<string,mixed> The meta data.
	 */
	public function display_seating_data_for_order_details( array $meta_data, array $attendee ): array {
		if ( ! isset( $attendee['seat_label'] ) ) {
			return $meta_data;
		}

		$meta_data[] = [
			'label' => __( 'Seat', 'event-tickets-plus' ),
			'value' => $attendee['seat_label'],
		];

		return $meta_data;
	}

	/**
	 * Hide the order again button if the order has seating tickets.
	 *
	 * @since 6.3.0
	 *
	 * @param WC_Order $order The order.
	 *
	 * @return void
	 */
	public function hide_order_again_button( $order ): void {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$has_seating = (bool) $order->get_meta( self::META_KEY_RESERVATIONS );

		if ( ! $has_seating ) {
			return;
		}

		remove_action( 'woocommerce_order_details_after_order_table', 'woocommerce_order_again_button' );
	}

	/**
	 * Validate the cart contents.
	 *
	 * @since 6.3.0
	 *
	 * @throws Exception If the cart contents are invalid.
	 */
	public function validate_cart_contents(): void {
		$cart = WC()->cart;

		foreach ( $cart->get_cart_contents() as $cart_item ) {
			$product_id = $cart_item['product_id'];
			$quantity   = $cart_item['quantity'];

			$is_seating = get_post_meta( $product_id, Meta::META_KEY_SEAT_TYPE, true );

			if ( ! $is_seating ) {
				continue;
			}

			/** @var Woo_Provider $woo_provider */
			$woo_provider = tribe( 'tickets-plus.commerce.woo' );

			$event_id     = get_post_meta( $product_id, $woo_provider->event_key, true );
			$reservations = $this->session->get_post_ticket_reservations( $event_id, $product_id );

			$found = is_array( $reservations ) ? count( $reservations ) : 0;

			if ( ! $found || $found !== $quantity ) {

				// Restore the quantity to the correct amount.
				$cart->set_quantity( $cart_item['key'], $found );

				$error_message = __( 'A seating ticket was added without any seats, invalid tickets are removed.', 'event-tickets-plus' );

				// If we are in the add to cart action, throw an exception. It will be converted to notice by WC.
				if ( doing_action( 'woocommerce_add_to_cart' ) ) {
					throw new Exception( $error_message );
				}

				wc_add_notice(
					$error_message,
					'error'
				);
			}
		}
	}

	/**
	 * Cancel the reservations for trashed orders.
	 *
	 * @since 6.3.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function handle_order_deletion( $post_id ): void {
		$post_type = get_post_type( $post_id );

		if ( ! in_array( $post_type, [ 'shop_order', DataSynchronizer::PLACEHOLDER_ORDER_POST_TYPE ], true ) ) {
			return;
		}

		$order = wc_get_order( $post_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$has_seating = (bool) $order->get_meta( self::META_KEY_RESERVATIONS );

		if ( ! $has_seating ) {
			return;
		}

		$cancelled = $this->cancel_reservations_for_order( $order );

		if ( ! $cancelled ) {
			$order_link = sprintf(
				'<a href="%s" target="_blank" rel="noreferrer noopener">%s</a>',
				esc_url( $order->get_edit_order_url() ),
				$order->get_id()
			);

			$message = sprintf(
				/* translators: %s: The order link. */
				__( 'Failed to cancel reservations for the trashed order: %s. Please try again.', 'event-tickets-plus' ),
				$order_link
			);

			wp_admin_notice(
				$message,
				[
					'type'        => 'error',
					'dismissible' => true,
				]
			);

			return;
		}

		/** @var Woo_Provider $woo_provider */
		$woo_provider = tribe( 'tickets-plus.commerce.woo' );

		// Check if the order has been restocked already.
		if ( $order->get_meta( $woo_provider->restocked_refunded_order ) ) {
			return;
		}

		wc_increase_stock_levels( $order->get_id() );

		// Set meta to skip the item count while counting attendee stock.
		$order->add_meta_data( $woo_provider->restocked_refunded_order, true, true );

		$order->save();
	}

	/**
	 * Disable the attendee regeneration for orders with seating tickets.
	 *
	 * @since 6.3.0
	 *
	 * @param bool $allowed Whether the attendee regeneration is allowed.
	 * @param int  $order_id The order ID.
	 *
	 * @return bool Whether the attendee regeneration is allowed.
	 */
	public function disable_attendee_regeneration( $allowed, $order_id ): bool {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return $allowed;
		}

		$has_seating = (bool) $order->get_meta( self::META_KEY_RESERVATIONS );

		if ( $has_seating ) {
			return false;
		}

		return $allowed;
	}

	/**
	 * Render the timer for cart and checkout block.
	 *
	 * @since 6.3.0
	 *
	 * @param string|null $pre_render   The pre-rendered content. Default null.
	 * @param array       $parsed_block {
	 *     An associative array of the block being rendered. See WP_Block_Parser_Block.
	 *
	 *     @type string   $blockName    Name of block.
	 *     @type array    $attrs        Attributes from block comment delimiters.
	 *     @type array[]  $innerBlocks  List of inner blocks. An array of arrays that
	 *                                  have the same structure as this one.
	 *     @type string   $innerHTML    HTML from inside block comment delimiters.
	 *     @type array    $innerContent List of string fragments and null markers where
	 *                                  inner blocks were found.
	 * }
	 *
	 * @return void
	 */
	public function render_timer_for_cart_and_checkout( $pre_render, array $parsed_block ): void {
		if ( ! isset( $parsed_block['blockName'] ) ) {
			return;
		}

		if ( ! in_array( $parsed_block['blockName'], [ 'woocommerce/cart', 'woocommerce/checkout' ], true ) ) {
			return;
		}

		tribe( Timer::class )->render_to_sync();
	}

	/**
	 * Filter the cart line item editable status.
	 *
	 * @since 6.3.0
	 *
	 * @param bool       $editable Whether the cart is editable.
	 * @param WC_Product $product  The product.
	 *
	 * @return bool Whether the cart is editable.
	 */
	public function filter_block_cart_quantity_input( bool $editable, WC_Product $product ): bool {
		if ( ! $editable ) {
			return $editable;
		}

		$has_seating = get_post_meta( $product->get_id(), Meta::META_KEY_SEAT_TYPE, true );

		if ( ! $has_seating ) {
			return $editable;
		}

		return false;
	}

	/**
	 * Handle the deletion of an attendee.
	 *
	 * @since 6.3.0
	 *
	 * @param null    $delete Whether to delete the post. Null is default, returning WP_Error will prevent deletion.
	 * @param WP_Post $post The post object.
	 *
	 * @return WP_Error|null Whether to delete the post.
	 */
	public function handle_attendee_delete( $delete, WP_Post $post ) {
		if ( Woo_Provider::ATTENDEE_OBJECT !== $post->post_type ) {
			return $delete;
		}

		$has_reservation = get_post_meta( $post->ID, Meta::META_KEY_RESERVATION_ID, true );

		if ( empty( $has_reservation ) ) {
			return $delete;
		}

		$event_id = (int) get_post_meta( $post->ID, Woo_Provider::ATTENDEE_EVENT_KEY, true );

		$cancelled = tribe( Reservations::class )->cancel( $event_id, [ $has_reservation ] );

		if ( $cancelled ) {
			return $delete;
		}

		$this->log_error(
			'Failed to cancel reservation from service during attendee deletion.',
			[
				'source'      => __METHOD__,
				'attendee_id' => $post->ID,
				'reservation' => $has_reservation,
				'event_id'    => $event_id,
			]
		);

		return new WP_Error(
			'failed_to_cancel_reservation',
			__( 'Failed to cancel reservation from service, attendee is not deleted.', 'event-tickets-plus' )
		);
	}
}
