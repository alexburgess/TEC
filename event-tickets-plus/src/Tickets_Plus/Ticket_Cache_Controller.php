<?php
/**
 * Handles the caching of Ticket objects extending the base
 *
 * @since 5.7.4
 *
 * @package TEC\Tickets_Plus;
 */

namespace TEC\Tickets_Plus;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Tickets\Ticket_Cache_Controller as ET_Ticket_Cache_Controller;
use WC_Abstract_Order;
use WC_Order_Item;
use EDD_Payment;

/**
 * Class Ticket_Cache_Controller.
 *
 * @since 5.7.4
 *
 * @package TEC\Tickets_Plus;
 */
class Ticket_Cache_Controller extends Controller {
	/**
	 * A reference to the Event Tickets Ticket Cache Controller.
	 *
	 * @since 5.7.4
	 *
	 * @var ?ET_Ticket_Cache_Controller
	 */
	private ?ET_Ticket_Cache_Controller $et_cache_controller = null;

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.7.4
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_ticket_cache_post_types', [ $this, 'add_tickets_plus_post_types' ] );
		add_filter( 'tec_tickets_ticket_cache_related_post_types', [ $this, 'add_tickets_plus_related_post_types' ] );
		add_action( 'woocommerce_order_status_changed', [ $this, 'clean_ticket_cache_on_wc_order_update' ], 20 );
		add_action( 'woocommerce_checkout_order_created', [ $this, 'clean_ticket_cache_on_wc_order_update' ], 20 );
		add_action( 'tec_tickets_plus_woo_generated_tickets', [ $this, 'clean_ticket_cache_on_wc_order_update' ] );
		add_action( 'edd_built_order', [ $this, 'clean_ticket_cache_on_edd_order_update' ] );
		add_action( 'edd_payment_saved', [ $this, 'clean_ticket_cache_on_edd_order_update' ] );
		add_action( 'edd_order_deleted', [ $this, 'clean_ticket_cache_on_edd_order_delete' ] );
		$this->et_cache_controller = $this->container->get( ET_Ticket_Cache_Controller::class );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.7.4
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_ticket_cache_post_types', [ $this, 'add_tickets_plus_post_types' ] );
		remove_filter( 'tec_tickets_ticket_cache_related_post_types', [
			$this,
			'add_tickets_plus_related_post_types'
		] );
		remove_action( 'woocommerce_order_status_changed', [ $this, 'clean_ticket_cache_on_wc_order_update' ], 20 );
		remove_action( 'woocommerce_checkout_order_created', [ $this, 'clean_ticket_cache_on_wc_order_update' ], 20 );
		remove_action( 'tec_tickets_plus_woo_generated_tickets', [ $this, 'clean_ticket_cache_on_wc_order_update' ] );
		remove_action( 'edd_built_order', [ $this, 'clean_ticket_cache_on_edd_order_update' ] );
		remove_action( 'edd_payment_saved', [ $this, 'clean_ticket_cache_on_edd_order_update' ] );
		remove_action( 'edd_order_deleted', [ $this, 'clean_ticket_cache_on_edd_order_delete' ] );
	}

	/**
	 * Adds the WooCommerce and Easy Digital Downloads post types to the list of post types that should
	 * be handled in the context of Ticket caching.
	 *
	 * @since 5.7.4
	 *
	 * @param array<string> $post_types The list of post types that should be handled in the context of Ticket caching.
	 *
	 * @return array<string> The list of post types that should be handled in the context of Ticket caching.
	 */
	public function add_tickets_plus_post_types( array $post_types ): array {
		$post_types[] = 'product'; // WooCommerce: post type defined and registered by WooCommerce.
		$post_types[] = 'download'; // Easy Digital Downloads: post type defined and registered by EDD.

		return $post_types;
	}

	/**
	 * Adds the WooCommerce and Easy Digital Downloads post types to the list of post types that should
	 * trigger a Ticket cache clean when they are updated.
	 *
	 * @since 5.7.4
	 *
	 * @param array<string> $post_types The list of post types that should trigger a Ticket cache clean when they are
	 *                                  updated.
	 *
	 * @return array<string> The list of post types that should trigger a Ticket cache clean when they are updated.
	 */
	public function add_tickets_plus_related_post_types( array $post_types ): array {
		/*
		 * WooCommerce: post type defined and registered by WooCommerce.
		 * Hard-coded to avoid having to load the class.
		 */
		$post_types['shop_order']           = [
			$this,
			'get_woocommerce_tickets_by_order',
		];
		$post_types['shop_order_placehold'] = [
			$this,
			'get_woocommerce_tickets_by_order',
		];

		/*
		 * Where is the post type defined and registered by EDD?
		 * EDD uses custom tables to model orders an payments: there is no related post type to hook into.
		 */

		return $post_types;
	}

	/**
	 * Returns the list of Tickets associated with the given WooCommerce order.
	 *
	 * @since 5.7.4
	 *
	 * @param int|WC_Abstract_Order $order The ID of the order to get the Tickets for, or the order object.
	 *
	 * @return array<int> The IDs of the Tickets associated with the order.
	 */
	private function get_tickets_ids_from_woocommerce_order( WC_Abstract_Order $order ): array {
		$items = $order->get_items();

		if ( ! count( $items ) ) {
			return [];
		}

		$product_ids = array_map( static fn( WC_Order_Item $item ) => $item->get_product_id(), $items );
		// Hard-coded to avoid having to load the class.
		$relationship_key = '_tribe_wooticket_for_event';
		// Filter out any product IDs that does not link to a post as a Ticket would do.
		$ticket_ids = array_filter(
			$product_ids,
			static fn( int $product_id ) => get_post_meta( $product_id, $relationship_key, true )
		);

		return $ticket_ids;
	}

	/**
	 * Get the IDs of the Tickets associated with a WooCommerce order.
	 *
	 * @since 5.7.4
	 *
	 * @param int|WC_Abstract_Order $order_id The ID of the order to get the Tickets for, or the order object.
	 *
	 * @return array<int> The IDs of the Tickets associated with the order.
	 */
	public function get_woocommerce_tickets_by_order( $order_id ): array {
		if ( ! function_exists( 'wc_get_order' ) ) {
			// This _should_ not be invoked if WooCommerce is not active, but just in case.
			return [];
		}

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Abstract_Order ) {
			return [];
		}

		return $this->get_tickets_ids_from_woocommerce_order( $order );
	}

	/**
	 * On a WooCommerce order update, clean the cache for all tickets associated with the order.
	 *
	 * @since 5.7.4
	 *
	 * @param int|WC_Abstract_Order $order The order that was updated.
	 */
	public function clean_ticket_cache_on_wc_order_update( $order ): void {
		foreach ( $this->get_woocommerce_tickets_by_order( $order ) as $ticket_id ) {
			$this->et_cache_controller->clean_ticket_cache( $ticket_id );
		}
	}

	/**
	 * Clean the cache for all tickets associated with an EDD payment when the payment is created or updated.
	 *
	 * @since 5.7.4
	 *
	 * @param int|EDD_Payment $payment The payment that was created or updated.
	 */
	public function clean_ticket_cache_on_edd_order_update( $payment ): void {
		if ( ! function_exists( 'edd_get_payment' ) ) {
			// This _should_ not be invoked if EDD is not active, but just in case.
			return;
		}

		$payment = edd_get_payment( $payment );

		if ( ! $payment instanceof EDD_Payment ) {
			return;
		}

		$downloads = $payment->downloads;

		$ticket_ids = array_reduce( $downloads, static function ( array $tickets, $download ): array {
			if ( ! ( is_array( $download ) && isset( $download['id'] ) ) ) {
				return $tickets;
			}

			// Hard-coded to avoid having to load the class.
			$relationship_key = '_tribe_eddticket_for_event';
			$post_id          = get_post_meta( $download['id'], $relationship_key, true );

			if ( ! empty( $post_id ) && is_numeric( $post_id ) ) {
				$tickets[] = (int) $download['id'];
			}

			return $tickets;
		}, [] );

		foreach ( $ticket_ids as $ticket_id ) {
			$this->et_cache_controller->clean_ticket_cache( $ticket_id );
		}
	}

	/**
	 * Clean the cache for all tickets associated with an EDD payment when the payment is deleted.
	 *
	 * EDD will not fire an action before deleting payments; by the time this fires, the Payment information and
	 * cached values will be gone. The method leverages the information stored by the Stock Control class to
	 * determine which Tickets were purchased and clean the cache for them.
	 *
	 * @since 5.7.4
	 *
	 * @param int $payment_id The ID of the payment that was deleted, note this is not the post ID: it's the ID of the
	 *                        payment in the EDD custom table.
	 */
	public function clean_ticket_cache_on_edd_order_delete( $payment_id ): void {
		if ( ! is_int( $payment_id ) ) {
			return;
		}

		/*
		 * While payments are modeled in custom tables, the Stock Control will still store a meta key with the
		 * format `_edd_tickets_qty_{ticket_id}` whenever a Ticket is purchased via EDD on the post ID that corresponds
		 * to the Payment.
		 *
		 * The meta value could end up being set on a post that is not remotely related to Tickets, or might be set on
		 * a non-existing post ID, the `update_post_meta` function does not verify the post ID.
		 */
		$meta = get_post_meta( $payment_id );

		// Filter out any meta entry that does not start with `_edd_tickets_qty_`.
		$related_ticket_meta = array_filter(
			$meta,
			static fn( string $key ) => strpos( $key, '_edd_tickets_qty_' ) === 0,
			ARRAY_FILTER_USE_KEY
		);

		// Extract the ticket IDs from the meta keys, the format is `_edd_tickets_qty_{ticket_id}`.
		$keys       = array_keys( $related_ticket_meta );
		$ticket_ids = array_map(
			static fn( string $key ) => (int) str_replace( '_edd_tickets_qty_', '', $key ),
			$keys
		);

		foreach ( $ticket_ids as $ticket_id ) {
			$this->et_cache_controller->clean_ticket_cache( $ticket_id );
		}
	}
}
