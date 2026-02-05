<?php
/**
 * Ticket Presets List Table
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Admin;

use TEC\Common\Admin\Abstract_Custom_List_Table;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;

/**
 * Ticket Presets List Table
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */
class Presets_Table extends Abstract_Custom_List_Table {

	protected const PLURAL = 'ticket_presets';

	protected const TABLE_ID = 'tec-tickets-presets';

	protected const SCREEN_ID = 'tickets_page_tec-tickets-admin-tickets-presets';

	/**
	 * Presets_Table constructor.
	 *
	 * @since 6.6.0
	 */
	public function __construct() {
		parent::__construct(
			[
				'screen' => self::SCREEN_ID,
			]
		);
	}

	/**
	 * Get the total number of items.
	 *
	 * @since 6.6.0
	 *
	 * @return int
	 */
	public function get_total_items(): int {
		$items = tribe( Ticket_Presets::class )->get_all();
		return count( $items );
	}

	/**
	 * Get the items for the current page.
	 *
	 * @since 6.6.0
	 *
	 * @param int $per_page Number of items per page.
	 *
	 * @return array
	 */
	public function get_items( int $per_page ): array {
		$page   = $this->get_pagenum();
		$offset = ( $page - 1 ) * $per_page;
		$items  = tribe( Ticket_Presets::class )->get_all();

		if ( empty( $items ) ) {
			return [];
		}

		// Handle pagination.
		return array_slice( $items, $offset, $per_page );
	}

	/**
	 * Get the columns for the table.
	 *
	 * @since 6.6.0
	 *
	 * @return array
	 */
	public function get_columns(): array {
		return [
			'name'     => __( 'Preset Name', 'event-tickets-plus' ),
			'capacity' => __( 'Capacity', 'event-tickets-plus' ),
			'cost'     => __( 'Price', 'event-tickets-plus' ),
		];
	}

	/**
	 * Get the sortable columns for the table.
	 *
	 * @since 6.6.0
	 *
	 * @return array
	 */
	public function get_sortable_columns(): array {
		return [
			'name'     => 'name',
			'capacity' => 'capacity',
			'cost'     => 'cost',
		];
	}

	/**
	 * Get the default column value.
	 *
	 * @since 6.6.0
	 *
	 * @param object $item        The current item.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		$data = json_decode( $item->data, true );

		switch ( $column_name ) {
			case 'capacity':
				return 'unlimited' === $data['capacity']['type']
					? __( 'Unlimited', 'event-tickets-plus' )
					: esc_html( $data['capacity']['amount'] );
			case 'cost':
				return tribe_format_currency( $data['cost'] ?? 0 );
			default:
				return '';
		}
	}

	/**
	 * Get the checkbox column value.
	 *
	 * @since 6.6.0
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="preset[]" value="%d" />',
			$item->id
		);
	}

	/**
	 * Get the name column value.
	 *
	 * @since 6.6.0
	 *
	 * @param object $item The current item.
	 *
	 * @return string
	 */
	public function column_name( $item ): string {
		$data = json_decode( $item->data, true );
		$name = $data['name'] ?? '';

		// Edit link points to the form page.
		$edit_link = add_query_arg(
			[
				'page'   => 'tec-tickets-preset-form',
				'action' => 'edit',
				'id'     => $item->id,
			],
			admin_url( 'admin.php' )
		);

		// Duplicate link uses the Controller's action.
		$duplicate_link = add_query_arg(
			[
				'page'      => 'tec-tickets-admin-tickets',
				'tab'       => 'presets',
				'action'    => 'duplicate-preset',
				'preset_id' => $item->id,
				'tec_nonce' => wp_create_nonce( 'tec-tec-tickets-plus_duplicate-preset' ),
			],
			admin_url( 'admin.php' )
		);

		$delete_link = add_query_arg(
			[
				'page'      => 'tec-tickets-admin-tickets',
				'tab'       => 'presets',
				'action'    => 'delete-preset',
				'preset_id' => $item->id,
				'tec_nonce' => wp_create_nonce( 'tec-tec-tickets-plus_delete-preset' ),
			],
			admin_url( 'admin.php' )
		);

		$actions = [
			'edit'      => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $edit_link ),
				esc_html__( 'Edit', 'event-tickets-plus' )
			),
			'duplicate' => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $duplicate_link ),
				esc_html__( 'Duplicate', 'event-tickets-plus' )
			),
			'delete'    => sprintf(
				'<a href="%s">%s</a>',
				esc_url( $delete_link ),
				esc_html__( 'Delete', 'event-tickets-plus' )
			),
		];

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong> %3$s',
			esc_url( $edit_link ),
			esc_html( $name ),
			$this->row_actions( $actions )
		);
	}

	/**
	 * Returns the search placeholder.
	 *
	 * @since 6.6.0
	 *
	 * @return string
	 */
	protected function get_search_placeholder(): string {
		return __( 'Search by preset name', 'event-tickets-plus' );
	}

	/**
	 * Returns the per page option name.
	 *
	 * @since 6.6.0
	 *
	 * @return string
	 */
	public static function get_per_page_option_name(): string {
		return str_replace( '-', '_', static::TABLE_ID . '_per_page' );
	}
}
