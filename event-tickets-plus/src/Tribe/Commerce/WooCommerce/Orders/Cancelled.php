<?php

class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled {

	/**
	 * @var Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled[]
	 */
	protected static $instances;

	/**
	 * @var int
	 */
	protected $ticket_id;

	/**
	 * @var int
	 */
	protected $count_cache = false;

	/**
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled|WP_Error
	 */
	public static function for_ticket( $ticket_id ) {

		if ( empty( self::$instances[ $ticket_id ] ) ) {
			try {
				self::$instances[ $ticket_id ] = new self( $ticket_id );
			} catch ( InvalidArgumentException $e ) {
				return new WP_Error( 'invalid-ticket-id', $e->getMessage() );
			}
		}

		return self::$instances[ $ticket_id ];
	}

	/**
	 * Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Cancelled constructor.
	 *
	 * Recommended way to instance the object is using the `for_ticket` factory method.
	 *
	 * @param int $ticket_id The ticket ID.
	 */
	public function __construct( $ticket_id ) {
		if ( ! is_numeric( $ticket_id ) ) {
			throw new InvalidArgumentException(
				esc_html(
					sprintf(
						__(
							'%s post ID must be an int or a numeric string.',
							'event-tickets-plus'
						),
						tribe_get_ticket_label_singular( 'woo_orders_cancelled_exception' )
					)
				)
			);
		}

		$ticket_post = get_post( $ticket_id );
		if ( empty( $ticket_post ) ) {
			throw new InvalidArgumentException(
				esc_html(
					sprintf(
						__(
							'%1$s with ID %2$s does not exist.',
							'event-tickets-plus'
						),
						tribe_get_ticket_label_singular( 'woo_orders_cancelled_exception' ),
						esc_html( $ticket_id )
					)
				)
			);
		}

		$this->ticket_id = $ticket_id;
	}

	public function get_count() {
		if ( false === $this->count_cache ) {
			$this->count_cache = $this->real_get_count();
		}

		return $this->count_cache;
	}

	protected function real_get_count() {
		$result                  = $this->get_cancelled_order_query();
		$cancelled_tickets_count = is_null( $result ) ? 0 : intval( $result );

		return $cancelled_tickets_count;
	}

	/**
	 * Constructs and executes the SQL query to fetch the total quantity of cancelled tickets.
	 * This is due to the WooCommerce ORM being unable to lookup orders by Product ID efficiently.
	 * The query used depends on whether HPOS is enabled or not.
	 *
	 * @return string|null The sum of quantities for cancelled tickets, or null if no tickets are found.
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 */
	protected function get_cancelled_order_query() {
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

		$sql = $wpdb->prepare( $query, 'wc-cancelled', intval( $this->ticket_id ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_var( $sql );
	}

}
