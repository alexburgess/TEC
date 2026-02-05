<?php
/**
 * WooCommerce Refunded Orders.
 *
 * Implements methods to get the refunded orders for WooCommerce
 * (complete and partial orders) by $ticket_id.
 *
 * @since 4.7.3
 *
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Refunded {

	/**
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Refunded[]
	 */
	protected static $instances;

	/**
	 * @var int
	 */
	protected $ticket_id = 0;

	/**
	 * Static cache for counts.
	 *
	 * @var int[]
	 */
	protected static $count_cache = [];

	/**
	 * Get refunds count. If there's something in cache, then
	 * the cached number, if not run `real_get_count()`.
	 *
	 * @since 4.7.3
	 * @since Updated caching logic.
	 *
	 * @param int $ticket_id The Ticket ID.
	 *
	 * @return int|null
	 */
	public function get_count( $ticket_id ) {
		if ( ! is_numeric( $ticket_id ) || empty( get_post( $ticket_id ) ) ) {
			return null;
		}

		if ( ! isset( self::$count_cache[ $ticket_id ] ) ) {
			self::$count_cache[ $ticket_id ] = $this->real_get_count( $ticket_id );
		}

		return self::$count_cache[ $ticket_id ];
	}

	/**
	 * Get the number of refunds for a ticket
	 * (both complete refunds, plus partial refunds)
	 *
	 * @since 4.7.3
	 * @since 5.9.3 Updated HPOS logic.
	 *
	 * @return int
	 */
	protected function real_get_count( $ticket_id ) {
		$return = $this->get_refunded_order_query( $ticket_id );
		return is_null( $return ) ? 0 : intval( $return );
	}

	/**
	 * Get the order_item_ids where the ticket is involved (mapped)
	 *
	 * @since 4.7.3
	 * @since 5.9.1 Updated logic to new WooCommerce HPOS requirement.
	 *
	 * @depecated  5.9.3 No longer used.
	 *
	 * @return array
	 */
	protected function get_order_item_ids( $ticket_id ) {
		_deprecated_function( __FUNCTION__, '5.9.3', 'No replacement' );

		$args = [
			'limit'      => -1, // Fetch all orders.
			'meta_query' => [
				[
					'key'     => '_product_id',
					'value'   => $ticket_id,
					'compare' => '=',
				],
			],
		];

		$orders         = wc_get_orders( $args );
		$order_item_ids = [];

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item_id => $item ) {
				if ( $item->get_product_id() == $ticket_id ) {
					$order_item_ids[] = $item_id;
				}
			}
		}

		return implode( ',', array_map( 'intval', $order_item_ids ) );
	}

	/**
	 * Get the actual order ids for the ticket, given the mapped values
	 *
	 * @since 4.7.3
	 * @since 5.9.1 Updated logic to new WooCommerce HPOS requirement.
	 *
	 * @return string
	 */
	public function get_order_ids( $order_item_ids_interval ) {
		$order_item_ids = array_map(
			'intval',
			explode(
				',',
				$order_item_ids_interval
			)
		);
		$order_ids      = [];

		foreach ( $order_item_ids as $item_id ) {
			$order_item = new WC_Order_Item_Product( $item_id );
			$order_id   = $order_item->get_order_id();
			if ( $order_id ) {
				$order_ids[] = $order_id;
			}
		}

		return implode( ',', array_unique( $order_ids ) );
	}

	/**
	 * Get the order ids where there was a refund
	 *
	 * @since 4.7.3
	 * @since 5.9.1 Updated logic to new WooCommerce HPOS requirement.
	 *
	 * @return array
	 */
	public function get_refunded_order_post_ids( $order_post_ids_interval ) {
		$order_ids = array_map(
			'intval',
			explode(
				',',
				$order_post_ids_interval
			)
		);

		$refunded_order_ids = [];

		foreach ( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order && $order->has_status( 'refunded' ) ) {
				// Check if the order has refunds and add the order ID to the array.
				$refunds = $order->get_refunds();
				if ( ! empty( $refunds ) ) {
					$refunded_order_ids[] = $order_id;
				}
			}
		}

		$refunded_order_ids = array_unique( $refunded_order_ids );

		return $refunded_order_ids;
	}

	/**
	 * Reset the count cache for a specific ticket ID or all tickets.
	 *
	 * @since 5.1.0
	 * @since 5.9.3 Updated caching logic.
	 *
	 * @param null|int $ticket_id The ticket ID to reset or null to reset all.
	 */
	public function reset_count_cache( $ticket_id = null ) {
		if ( null === $ticket_id ) {
			self::$count_cache = [];
		}

		if ( isset( self::$count_cache[ $ticket_id ] ) ) {
			unset( self::$count_cache[ $ticket_id ] );
		}
	}

	/**
	 * Constructs and executes the SQL query to fetch the total quantity of cancelled tickets.
	 * This is due to the WooCommerce ORM being unable to lookup orders by Product ID efficiently.
	 * The query used depends on whether HPOS is enabled or not.
	 *
	 * @param int $ticket_id The ticket ID to lookup.
	 *
	 * @return string|null The sum of quantities for refunded tickets, or null if no tickets are found.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	protected function get_refunded_order_query( $ticket_id ) {
		global $wpdb;

		if ( ! tribe( 'tickets-plus.commerce.woo' )->is_hpos_enabled() ) {
			// HPOS is disabled.
			$query = "SELECT COALESCE(SUM(qty_meta.meta_value), 0)
				FROM {$wpdb->prefix}woocommerce_order_itemmeta AS woim
				INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woi ON woim.order_item_id = woi.order_item_id
				INNER JOIN {$wpdb->prefix}posts AS p ON p.ID = woi.order_id
				LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS qty_meta ON woim.order_item_id = qty_meta.order_item_id AND qty_meta.meta_key = '_qty'
				WHERE woim.meta_key IN ('_product_id', '_variation_id')
				AND p.post_status = %s
				AND woim.meta_value = %s";
		} else {
			// HPOS is enabled.
			$query = "SELECT COALESCE(SUM(opl.product_qty), 0)
				FROM {$wpdb->prefix}wc_order_product_lookup opl
				INNER JOIN {$wpdb->prefix}wc_orders o ON opl.order_id = o.id
				WHERE o.type = 'shop_order'
				AND o.status = %s
				AND opl.product_id = %d";
		}

		$sql = $wpdb->prepare( $query, 'wc-refunded', intval( $ticket_id ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $sql );
	}

}
