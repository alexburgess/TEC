<?php
/**
 * The Waitlist Subscribers Table.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist/Admin
 */

namespace TEC\Tickets_Plus\Waitlist\Admin;

use TEC\Common\Admin\Abstract_Custom_List_Table;
use TEC\Tickets_Plus\Waitlist\Tables\Waitlist_Subscribers;
use TEC\Tickets_Plus\Waitlist\Subscribers;

/**
 * Class Waitlist_Subscribers_Table
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist/Admin
 */
class Waitlist_Subscribers_Table extends Abstract_Custom_List_Table {
	/**
	 * The plural name of the item in the list.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected const PLURAL = 'waitlist-subscribers';

	/**
	 * The table ID.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected const TABLE_ID = 'tec-tickets-plus-waitlist-subscribers-table';

	/**
	 * Returns the total number of items.
	 *
	 * @since 6.2.0
	 *
	 * @return int The total number of items.
	 */
	protected function get_total_items(): int {
		return Waitlist_Subscribers::get_total_items( $this->get_args() );
	}

	/**
	 * Returns the search placeholder.
	 *
	 * @since 6.2.0
	 *
	 * @return string
	 */
	protected function get_search_placeholder(): string {
		return __( 'Search by subscriber name or email', 'event-tickets-plus' );
	}

	/**
	 * Outputs the results of the filters above the table.
	 *
	 * It should echo the output.
	 *
	 * @since 6.2.0
	 */
	public function do_top_tablename_filters(): void {
		$this->date_range_dropdown();
		$this->ticket_able_post_dropdown();
	}

	/**
	 * Returns the items for the current page.
	 *
	 * @since 6.2.0
	 *
	 * @param int $per_page The number of items to display per page.
	 *
	 * @return array The items for the current page.
	 */
	protected function get_items( int $per_page ): array {
		return Waitlist_Subscribers::paginate( $this->get_args(), $per_page, $this->get_pagenum() );
	}

	/**
	 * Returns whether the list is completely empty.
	 *
	 * @since 6.2.0
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return 0 === Waitlist_Subscribers::get_total_items();
	}

	/**
	 * Outputs the content to display when the list is completely empty.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function empty_content(): void {
		tribe( Waitlist_Subscribers_Page::class )->render_empty_content();
	}

	/**
	 * Returns the arguments to query the items.
	 *
	 * @since 6.2.0
	 *
	 * @return array
	 */
	protected function get_args(): array {
		$args = [
			'orderby' => tec_get_request_var( 'orderby', '' ) ? $this->get_orderby() : 'created',
			'order'   => tec_get_request_var( 'order', '' ) ? $this->get_order() : 'DESC',
			'term'    => tec_get_request_var( 's', '' ),
		];

		$event_id = $this->get_event_id();
		if ( $event_id ) {
			$args[] = [
				'column' => 'post_id',
				'value'  => $event_id,
			];
		}

		[ $date_from, $date_to ] = array_values( $this->get_date_range() );

		if ( $date_from ) {
			$args[] = [
				'column'   => 'created',
				'operator' => '>=',
				'value'    => $date_from . ' 00:00:00',
			];
		}

		if ( $date_to ) {
			$args[] = [
				'column'   => 'created',
				'operator' => '<=',
				'value'    => $date_to . ' 23:59:59',
			];
		}

		return array_filter( $args );
	}

	/**
	 * Returns the subscriber's status.
	 *
	 * @since 6.2.0
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_status( $item ) {
		$status = $item['status'] ?? 0;
		$slug   = $status ? 'completed' : 'pending';
		ob_start();
		?>
		<mark class="tec-tickets-plus-waitlist-subscriber-status status-<?php echo esc_attr( $slug ); ?>">
			<?php
			$dashicon = '';
			switch ( $slug ) {
				case 'completed':
					$dashicon = 'bell';
					break;
				case 'pending':
					$dashicon = 'admin-users';
					break;
			}

			if ( $dashicon ) {
				printf( '<span class="dashicons dashicons-%s"></span>', esc_attr( $dashicon ) );
			}
			?>
			<span>
				<?php echo $status ? esc_html__( 'Notified', 'event-tickets-plus' ) : esc_html__( 'New', 'event-tickets-plus' ); ?>
			</span>
		</mark>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the subscriber's status.
	 *
	 * @since 6.2.0
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_event( $item ): string {
		$event_id = $item['post_id'] ?? 0;

		if ( ! $event_id ) {
			return '';
		}

		$event = get_post( $event_id );
		if ( ( ! in_array( $event->post_type, get_post_types( [ 'show_ui' => true ] ), true ) ) ) {
			return sprintf(
				'<div>%s</div>',
				esc_html( get_the_title( $event->ID ) )
			);
		}

		if ( ! current_user_can( 'edit_post', $event->ID ) ) {
			return sprintf(
				'<div>%s</div>',
				esc_html( get_the_title( $event->ID ) )
			);
		}

		if ( 'trash' === $event->post_status ) {
			// translators: 1) is the event's title and 2) is an indication as a text that it is now trashed.
			return sprintf(
				'<div>%1$s %2$s</div>',
				esc_html( get_the_title( $event->ID ) ),
				esc_html_x( '(trashed)', 'This is about an "event" that now has been trashed.', 'event-tickets-plus' )
			);
		}

		return sprintf(
			'<div><a href="%s">%s</a></div>',
			esc_url( get_edit_post_link( $event->ID ) ),
			esc_html( get_the_title( $event->ID ) )
		);
	}

	/**
	 * Returns the subscriber's subscription date.
	 *
	 * @since 6.2.0
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_created( $item ): string {
		return $this->format_date( $item['created']->format( 'Y-m-d H:i:s' ) );
	}

	/**
	 * List of sortable columns.
	 *
	 * @since 6.2.0
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		/**
		 * Filters the list of sortable columns for the Waitlist Subscribers table.
		 *
		 * @since 6.2.0
		 *
		 * @param array $columns List of columns that can be sorted.
		 */
		return (array) apply_filters(
			'tec_tickets_plus_waitlist_waitlist_subscribers_table_sortable_columns',
			[
				'fullname' => 'fullname',
				'email'    => 'email',
				'status'   => 'status',
				'event'    => 'post_id',
				'created'  => 'created',
			]
		);
	}

	/**
	 * Returns the list of columns.
	 *
	 * @since 6.2.0
	 *
	 * @return array An associative array in the format [ <slug> => <title> ]
	 */
	public function get_columns(): array {
		/**
		 * Filters the list of columns for the Waitlist Subscribers table.
		 *
		 * @since 6.2.0
		 *
		 * @param array $columns List of columns.
		 */
		return (array) apply_filters(
			'tec_tickets_plus_waitlist_waitlist_subscribers_table_columns',
			[
				'cb'       => '<input type="checkbox" />',
				'fullname' => __( 'Subscriber Name', 'event-tickets-plus' ),
				'email'    => __( 'Email', 'event-tickets-plus' ),
				'status'   => __( 'Status', 'event-tickets-plus' ),
				'event'    => __( 'Event', 'event-tickets-plus' ),
				'created'  => __( 'Subscription Date', 'event-tickets-plus' ),
			]
		);
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @since 6.2.0
	 *
	 * @param object $item The current object.
	 */
	public function column_cb( $item ): void {
		$show = current_user_can( 'manage_options' );

		if ( ! $show ) {
			return;
		}

		?>
		<input id="cb-select-<?php echo esc_attr( $item['waitlist_user_id'] ); ?>; ?>" type="checkbox" name="subscriber[]" value="<?php echo esc_attr( $item['waitlist_user_id'] ); ?>" />
		<label for="cb-select-<?php echo esc_attr( $item['waitlist_user_id'] ); ?>; ?>">
			<span class="screen-reader-text">
			<?php
				/* translators: %s: Subscriber name. */
				printf( esc_html__( 'Select %s', 'event-tickets-plus' ), esc_html( $item['fullname'] ) );
			?>
			</span>
		</label>
		<?php
	}

	/**
	 * Returns the bulk actions.
	 *
	 * @since 6.2.0
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return [ 'delete' => __( 'Delete', 'event-tickets-plus' ) ];
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @since 6.2.0
	 *
	 * @param object $item        Item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string Row actions output.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		return $this->row_actions(
			[
				'delete' => sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					tribe( Subscribers::class )->get_subscriber_full_delete_url( $item['waitlist_user_id'] ),
					/* translators: %s: Subscriber's name. */
					esc_attr( sprintf( __( 'Delete &#8220;%s&#8221; permanently', 'event-tickets-plus' ), $item['fullname'] ) ),
					__( 'Delete', 'event-tickets-plus' )
				),
			]
		);
	}
}
