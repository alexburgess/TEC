<?php

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

use TEC\Common\StellarWP\DB\DB;


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table
 *
 * See documentation for WP_List_Table
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table extends WP_List_Table {

	public        $event_id;
	public        $total_purchased   = 0;
	public        $overall_total     = 0;
	public        $valid_order_items = array();
	public static $pass_fees_to_user = true;
	public static $fee_percent       = 0;
	public static $fee_flat          = 0;

	/**
	 * In-memory cache of orders per event, where each key represents the event ID
	 * and the value is an array of orders.
	 *
	 * @var array
	 */
	protected static $orders = array();

	/**
	 * @var string The user option that will be used to store the number of orders per page to show.
	 */
	public $per_page_option;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$args = array(
			'singular' => 'order',
			'plural'   => 'orders',
			'ajax'     => true,
		);

		$this->per_page_option = Tribe__Tickets_Plus__Commerce__WooCommerce__Screen_Options::$per_page_user_option;

		$screen = get_current_screen();

		if ( $screen instanceof WP_Screen ) {
			$screen->add_option( 'per_page', array(
				'label'  => __( 'Number of orders per page:', 'event-tickets-plus' ),
				'option' => $this->per_page_option,
			) );
		}

		parent::__construct( $args );
	}//end __construct

	/**
	 * Overrides the list of CSS classes for the WP_List_Table table tag.
	 * This function is not hookable in core, so it needs to be overridden!
	 *
	 * @since 4.10.6
	 *
	 * @return array List of CSS classes for the table tag.
	 */
	protected function get_table_classes() {
		$classes = [ 'widefat', 'striped', 'orders', 'woocommerce-orders' ];

		if ( is_admin() ) {
			$classes[] = 'fixed';
		}

		/**
		 * Filters the default classes added to the woocommerce order report `WP_List_Table`.
		 *
		 * @since 4.10.6
		 *
		 * @param array $classes The array of classes to be applied.
		 */
		$classes = apply_filters( 'tribe_tickets_plus_woocommerce_order_table_classes', $classes );

		return $classes;
	}

	/**
	 * Display the search box.
	 * We don't want Core's search box, because we implemented our own jQuery based filter,
	 * so this function overrides the parent's one and returns empty.
	 *
	 * @param string $text     The search button text
	 * @param string $input_id The search input id
	 */
	public function search_box( $text, $input_id ) {
		return;
	}//end search_box

	/**
	 * Checks the current user's permissions
	 */
	public function ajax_user_can() {
		$post_type = get_post_type_object( $this->screen->post_type );

		return ! empty( $post_type->cap->edit_posts ) && current_user_can( $post_type->cap->edit_posts );
	}//end ajax_user_can

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 *
	 * @since 4.10.6 - add filter of the columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'order'     => __( 'Order', 'event-tickets-plus' ),
			'purchaser' => __( 'Purchaser', 'event-tickets-plus' ),
			'email'     => __( 'Email', 'event-tickets-plus' ),
			'purchased' => __( 'Purchased', 'event-tickets-plus' ),
			'date'      => __( 'Date', 'event-tickets-plus' ),
			'status'    => __( 'Status', 'event-tickets-plus' ),
		);

		/**
		 * Allow filtering of the columns in the WooCommmce Order|Sales Report
		 *
		 * @since 4.10.6
		 *
		 * @param array $columns  An array of columns
		 * @param int   $event_id Event ID.
		 */
		$columns = apply_filters( 'tribe_tickets_plus_woocommerce_orders_columns', $columns, $this->event_id );

		$columns['total'] = __( 'Total', 'event-tickets-plus' );

		return $columns;
	}//end get_columns

	/**
	 * Handler for the columns that don't have a specific column_{name} handler function.
	 *
	 * @param object $item The current item.
	 * @param string $column The column.
	 *
	 * @return string
	 */
	public function column_default( $item, $column ) {
		$value = empty( $item->$column ) ? '' : $item->$column;

		return apply_filters( 'tribe_events_tickets_orders_table_column', $value, $item, $column );
	}//end column_default

	/**
	 * Handler for the date column
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_date( $item ) {
		$date = ( $item['status'] == 'completed' ) ? $item['completed_at'] : $item['created_at'];

		return Tribe__Date_Utils::reformat( $date, Tribe__Date_Utils::DATEONLYFORMAT );
	}//end column_date

	/**
	 * Handler for the ship to column
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_address( $item ) {
		$shipping = $item['shipping'];

		if ( empty( $shipping['address_1'] ) || empty( $shipping['city'] ) ) {
			return '';
		}

		$address = trim( "{$shipping['first_name']} {$shipping['last_name']}" );

		if ( ! empty( $shipping['company'] ) ) {
			if ( $address ) {
				$address .= '<br>';
			}

			$address .= $shipping['company'];
		}

		$address .= "<br>{$shipping['address_1']}<br>";

		if ( ! empty( $shipping['address_2'] ) ) {
			$address .= "{$shipping['address_2']}<br>";
		}

		$address .= $shipping['city'];

		if ( ! empty( $shipping['state'] ) ) {
			$address .= ", {$shipping['state']}";
		}

		if ( ! empty( $shipping['country'] ) ) {
			$address .= " {$shipping['country']}";
		}

		if ( ! empty( $shipping['postcode'] ) ) {
			$address .= " {$shipping['postcode']}";
		}

		return $address;
	}//end column_address

	/**
	 * Handler for the purchased column
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_purchased( $item ) {

		$tickets   = array();
		$num_items = 0;

		foreach ( $item['line_items'] as $line_item ) {
			$ticket_id = $line_item['product_id'];

			if ( ! isset( $this->valid_order_items[ $item['id'] ][ $ticket_id ] ) ) {
				continue;
			}

			$num_items += $line_item['quantity'];

			if ( empty( $tickets[ $line_item['name'] ] ) ) {
				$tickets[ $line_item['name'] ] = 0;
			}

			$tickets[ $line_item['name'] ] += $line_item['quantity'];
		}

		$this->total_purchased = $num_items;

		ksort( $tickets );

		$output = '';

		foreach ( $tickets as $name => $quantity ) {

			$output .= '<div class="tribe-line-item">' . esc_html( $quantity ) . ' - ' . esc_html( $name ) . '</div>';
		}

		return $output;
	}//end column_purchased

	/**
	 * Handler for the order column
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_order( $item ) {
		$icon    = '';
		$warning = false;

		$order_number = $item['id'];

		$order_url = add_query_arg(
			array(
				'post'   => $order_number,
				'action' => 'edit',
			), admin_url( 'post.php' )
		);

		$order_number_link = '<a href="' . esc_url( $order_url ) . '">#' . absint( $order_number ) . '</a>';

		/**
		 * Allows for control of the order number link in the attendee report
		 *
		 * @since 4.7.3
		 *
		 * @param string $order_number_link The default "order" link.
		 * @param int    $order_number      The Post ID of the order.
		 */
		$order_number_link = apply_filters( 'tribe_tickets_plus_woocommerce_order_link_url', $order_number_link, $order_number );

		$output = sprintf(
			esc_html__(
				'%1$s', 'event-tickets-plus'
			), $order_number_link
		);

		if ( 'completed' !== $item['status'] ) {
			$output .= '<div class="order-status order-status-' . esc_attr( $item['status'] ) . '">' . esc_html(
					wc_get_order_status_name( $item['status'] )
				) . '</div>';
		}

		return $output;
	}//end column_order

	/**
	 * Handler for the total column
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_total( $item ) {
		$total = 0;
		foreach ( $this->valid_order_items[ $item['id'] ] as $line_item ) {
			$total += (float) $line_item['subtotal'];
			if ( self::item_has_discount( $line_item ) ) {
				$total -= self::item_get_discount( $line_item );
			}
		}

		$post_id = Tribe__Utils__Array::get_in_any( array( $_GET, $_REQUEST ), 'event_id', null );

		/**
		 * Allow filtering of the WooCommerce Sales Report Total Column
		 *
		 * @since 4.10.6
		 *
		 * @param int   $order_total The order total.
		 * @param array $item        An array of order data.
		 * @param int   $post_id     Post type ID.
		 */
		$total = apply_filters( 'tribe_tickets_plus_woocommerce_filter_column_total', $total, $item, $post_id );

		return tribe_format_currency( number_format( (float) $total, 2 ), $post_id );
	}//end column_total

	/**
	 * Generates content for a single row of the table
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr class="' . esc_attr( $item['status'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}//end single_row

	/**
	 * Get All Orders for an Event
	 *
	 * @since 4.10.4 - modified to use retrieve_orders_ids_from_a_product_id method
	 *
	 * @since 5.6.5 Removed usage of WC REST API and make use of Order API to generate order data.
	 *
	 * @param int $event_id The Event ID.
	 *
	 * @return array|mixed
	 */
	public static function get_orders( $event_id ) {
		if ( ! $event_id ) {
			return [];
		}

		if ( isset( self::$orders[ $event_id ] ) ) {
			return self::$orders[ $event_id ];
		}

		$orders  = [];
		$results = self::get_orders_by_event( $event_id );

		if ( empty( $results ) ) {
			return [];
		}

		foreach ( $results as $ticket ) {
			foreach ( $ticket as $order_id ) {
				if ( empty( $order_id ) ) {
					continue;
				}

				$order = wc_get_order( $order_id );

				if ( $order ) {
					// Skip any order that is a sub-order or partial-refund.
					if ( 0 !== $order->get_parent_id() ) {
						continue;
					}

					$orders[ $order_id ] = $order->get_data();

					$orders[ $order_id ]['billing_email'] = $order->get_billing_email();
					$orders[ $order_id ]['created_at']    = $order->get_date_created();
					$orders[ $order_id ]['completed_at']  = $order->get_date_completed();
				}
			}
		}

		self::$orders[ $event_id ] = $orders;

		return $orders;
	}

	/**
	 * Get All Orders with the given Product IDS
	 *
	 * @since 4.10.4 - modified to use retrieve_orders_ids_from_a_product_id method
	 * @since 5.9.1 Updated logic to new WooCommerce HPOS requirement.
	 *
	 * @deprecated  5.9.4
	 *
	 * @param array $product_ids an array of product ids.
	 *
	 * @return array an array of order ids
	 */
	public static function retrieve_orders_ids_from_a_product_id( $product_ids ) {
		_deprecated_function( __METHOD__, '5.9.4', 'get_orders_by_event' );
		if ( empty( $product_ids ) || ! is_array( $product_ids ) ) {
			return [];
		}

		$order_statuses = (array) tribe( 'tickets.status' )->get_statuses_by_action( 'all', 'woo' );

		$args = [
			'limit'   => -1,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => $order_statuses,
		];

		$orders               = wc_get_orders( $args );
		$order_ids_by_product = [];

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				if ( in_array( $product_id, $product_ids, true ) ) {
					$order_ids_by_product[ $product_id ][] = $order->get_id();
				}
			}
		}

		return $order_ids_by_product;
	}
	public static function get_valid_order_items_for_event( $event_id, $items ) {
		$valid_order_items = [];

		$event_id = absint( $event_id );

		foreach ( $items as $order ) {
			if ( ! isset( $valid_order_items[ $order['id'] ] ) ) {
				$valid_order_items[ $order['id'] ] = [];
			}

			foreach ( $order['line_items'] as $line_item ) {
				$ticket_id       = $line_item['product_id'];
				$ticket_event_id = absint(
					get_post_meta( $ticket_id, Tribe__Tickets_Plus__Commerce__WooCommerce__Main::get_instance()->event_key, true )
				);

				// if the ticket isn't for the currently viewed event, skip it
				if ( $ticket_event_id !== $event_id ) {
					continue;
				}

				$valid_order_items[ $order['id'] ][ $ticket_id ] = $line_item;
			}
		}

		return $valid_order_items;
	}

	/**
	 * Prepares the list of items for displaying.
	 *
	 * @since 5.6.5 Added support for column sorting.
	 */
	public function prepare_items() {
		$this->event_id = Tribe__Utils__Array::get( $_GET, 'event_id', Tribe__Utils__Array::get( $_GET, 'post_id', 0 ) );

		$items       = self::get_orders( $this->event_id );
		$total_items = count( $items );
		$per_page    = $this->get_items_per_page( $this->per_page_option );
		$orderby     = tribe_get_request_var( 'orderby' ) ?? 'id';
		$order       = tribe_get_request_var( 'order' ) ?? 'DESC';

		/**
		 * Allow plugins to modify the default number of orders shown per page.
		 *
		 * @since 4.9.2
		 *
		 * @param int The number of orders shown per page.
		 */
		$per_page = apply_filters( 'tribe_tickets_plus_order_pagination', $per_page );

		$this->valid_order_items = self::get_valid_order_items_for_event( $this->event_id, $items );

		$current_page = $this->get_pagenum();

		$items = wp_list_sort( $items, $orderby, $order );

		$this->items = array_slice( $items, ( $current_page - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * List of sortable columns.
	 *
	 * @since 5.6.5
	 *
	 * @return array $sortable_columns An array with sortable columns.
	 */
	public function get_sortable_columns(): array {
		return [
			'order'  => 'id',
			'email'  => 'billing_email',
			'date'   => 'completed_at',
			'status' => 'status',
			'total'  => 'total'
		];
	}

	/**
	 * Return sales for the given event.
	 *
	 * @param int $event_id Event post ID
	 *
	 * @return float
	 */
	public static function event_sales( $event_id ) {

		$orders            = self::get_orders( $event_id );
		$valid_order_items = self::get_valid_order_items_for_event( $event_id, $orders );

		$total = 0;

		foreach ( $valid_order_items as $order_id => $order ) {
			if ( 'cancelled' === $orders[ $order_id ]['status']
				|| 'refunded' === $orders[ $order_id ]['status']
				|| 'failed' === $orders[ $order_id ]['status']
			) {
				continue;
			}

			$order_total = 0;

			foreach ( $order as $line_item ) {
				$order_total += $line_item['subtotal'];
			}

			/**
			 * Allow filtering of Individual WooCommerce Order Totals in the Event Sales Total
			 *
			 * @since 4.10.6
			 *
			 * @param int $order_total  the order total
			 * @param int     $event_id Event ID.
			 */
			$order_total = apply_filters( 'tribe_tickets_plus_woocommerce_filter_individual_order_totals_in_event_sales', $order_total, $event_id );

			$total += $order_total;
		}

		/**
		 * Allow filtering of the WooCommerce Order Sales Total
		 *
		 * @since 4.10.6
		 *
		 * @param int $total  the total sales for an order
		 * @param int     $event_id Event ID.
		 */
		$total = apply_filters( 'tribe_tickets_plus_woocommerce_filter_report_event_sales_total', $total, $event_id );

		return $total;
	}

	/**
	 * Get the total of discounts for the given event
	 *
	 * @param int $event_id  Event post ID
	 *
	 * @return float|int
	 */
	public static function event_discounts( $event_id ) {
		$orders            = self::get_orders( $event_id );
		$valid_order_items = self::get_valid_order_items_for_event( $event_id, $orders );

		$discounts = 0;

		foreach ( $valid_order_items as $order_id => $order ) {
			$item = $orders[ $order_id ];

			if ( 'cancelled' === $item['status']
				|| 'refunded' === $item['status']
				|| 'failed' === $item['status']
			) {
				continue;
			}

			foreach ( $order as $line_item ) {
				if ( self::item_has_discount( $line_item ) ) {
					$discounts += self::item_get_discount( $line_item );
				}
			}
		}

		return $discounts;
	}

	/**
	 * Logic to detect if an item has a discount based on a discrepancy between total and subtotal.
	 *
	 * @see https://github.com/woocommerce/woocommerce/blob/869fb52927b675bd4c200cf3480a8813c9465a28/includes/admin/meta-boxes/views/html-order-item.php#L54
	 *
	 * @since 4.7.3
	 *
	 * @param array $item The line item to review if has a discount
	 *
	 * @return bool
	 */
	public static function item_has_discount( $item ) {
		return (
			isset( $item['subtotal'] )
			&& isset( $item['total'] )
			&& $item['subtotal'] !== $item['total']
		);
	}

	/**
	 * Get the amount of the discount to be applied
	 *
	 * @since 4.7.3
	 *
	 * @param array $item The line item with the data to process the order
	 *
	 * @return float
	 */
	public static function item_get_discount( $item ) {
		return (float) $item['subtotal'] - (float) $item['total'];
	}

	/**
	 * Echoes the customer name.
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_purchaser( $item ) {
		$customer = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer::make_from_item( $item );
		return $customer->get_name();
	}

	/**
	 * Echoes the customer email.
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_email( $item ) {
		$customer = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Customer::make_from_item( $item );
		return $customer->get_email();
	}

	/**
	 * Echoes the order status.
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$order = wc_get_order( $item['id'] );

		if ( empty( $order ) ) {
			return '';
		}

		return wc_get_order_status_name( $order->get_status() );
	}

	/**
	 * Return fees for the given event
	 *
	 * @deprecated 4.10.6
	 *
	 * @param int $event_id Event post ID
	 *
	 * @return float
	 */
	public static function event_fees( $event_id ) {
		_deprecated_function( __METHOD__, '4.10.6' );

		$orders            = self::get_orders( $event_id );
		$valid_order_items = self::get_valid_order_items_for_event( $event_id, $orders );

		$fees = 0;

		foreach ( $valid_order_items as $order_id => $order ) {
			if ( 'cancelled' === $orders[ $order_id ]['status']
				|| 'refunded' === $orders[ $order_id ]['status']
				|| 'failed' === $orders[ $order_id ]['status']
			) {
				continue;
			}

			$order_total = 0;

			foreach ( $order as $line_item ) {
				$order_total += $line_item['subtotal'];
			}

			$fees += self::calc_site_fee( $order_total, self::$pass_fees_to_user );
		}

		return $fees;
	}

	/**
	 * Return total revenue for the given event
	 *
	 * @deprecated 4.10.6
	 *
	 * @param int $event_id Event post ID
	 *
	 * @return float
	 */
	public static function event_revenue( $event_id ) {
		_deprecated_function( __METHOD__, '4.10.6' );

		return self::event_sales( $event_id, self::$pass_fees_to_user ) + self::event_fees( $event_id, self::$pass_fees_to_user );
	}


	/**
	 * Calculate site fees
	 *
	 * @deprecated 4.10.6
	 *
	 * @param int $amount Total to calculate site fees on
	 *
	 * @return float
	 */
	public static function calc_site_fee( $amount ) {
		_deprecated_function( __METHOD__, '4.10.6', 'tribe( \'events-community-tickets.fees\' )->calculate_event_fee( $events, $gateway ) and calculate_ticket_fee( $tickets, $gateway )' );

		return round( $amount * ( self::$fee_percent / 100 ), 2 ) + self::$fee_flat;
	}

	/**
	 * Handler for the subtotal column
	 *
	 * @deprecated 4.10.6
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_subtotal( $item ) {
		_deprecated_function( __METHOD__, '4.10.6' );

		$total = 0;

		foreach ( $this->valid_order_items[ $item['id'] ] as $line_item ) {
			$total += $line_item['subtotal'];
		}

		if ( ! self::$pass_fees_to_user ) {
			$total -= self::calc_site_fee( $total );
		}

		return tribe_format_currency( number_format( $total, 2 ) );
	}//end column_subtotal

	/**
	 * Handler for the site fees column
	 *
	 * @deprecated 4.10.6
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_site_fee( $item ) {
		_deprecated_function( __METHOD__, '4.10.6' );

		$total = 0;

		foreach ( $this->valid_order_items[ $item['id'] ] as $line_item ) {
			$total += $line_item['subtotal'];
		}

		return tribe_format_currency( number_format( $this->calc_site_fee( $total ), 2 ) );
	}//end column_site_fee

	/**
	 * Retrieve the Order ID's associated with the tickets within an Event.
	 *
	 * @since 5.9.4
	 *
	 * @param int $event_id The ID of the event for which to retrieve orders.
	 *
	 * @return array
	 */
	public static function get_orders_by_event( $event_id ) {
		$ticket_ids = tribe( 'tickets-plus.commerce.woo' )->get_tickets_ids( $event_id );
		if ( empty( $ticket_ids ) ) {
			return [];
		}

		$order_statuses = (array) tribe( 'tickets.status' )->get_statuses_by_action( 'all', 'woo' );

		// Preparing the status list and ticket list for use in IN clauses
		// Note: This approach is used because $wpdb->prepare() cannot directly prepare arrays for IN clauses.
		$status_list_placeholders = implode( ',', array_fill( 0, count( $order_statuses ), '%s' ) );
		$ticket_list_placeholders = implode( ',', array_fill( 0, count( $ticket_ids ), '%s' ) );

		global $wpdb;

		$is_hpos_enabled = tribe( 'tickets-plus.commerce.woo' )->is_hpos_enabled();

		if ( ! $is_hpos_enabled ) {
			$query = DB::prepare(
				"SELECT DISTINCT woi.order_id
					FROM {$wpdb->prefix}woocommerce_order_itemmeta AS woim
					INNER JOIN {$wpdb->prefix}woocommerce_order_items AS woi ON woim.order_item_id = woi.order_item_id
					INNER JOIN {$wpdb->prefix}posts AS p ON p.ID = woi.order_id
					WHERE woim.meta_key IN ('_product_id', '_variation_id')
					AND p.post_status IN ($status_list_placeholders)
					AND woim.meta_value IN ($ticket_list_placeholders)
					ORDER BY p.post_date DESC",
				array_merge( $order_statuses, $ticket_ids )
			);
		} else {
			$query = DB::prepare(
				"SELECT DISTINCT opl.order_id
					FROM {$wpdb->prefix}wc_order_product_lookup AS opl
					INNER JOIN {$wpdb->prefix}wc_order_stats AS os ON opl.order_id = os.order_id
					WHERE opl.product_id IN ($ticket_list_placeholders)
					AND os.status IN ($status_list_placeholders)
					ORDER BY os.date_created DESC",
				array_merge( $ticket_ids, $order_statuses )
			);
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return $wpdb->get_results( $query );
	}
}
