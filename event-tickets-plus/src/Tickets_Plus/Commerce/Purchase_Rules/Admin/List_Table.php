<?php
/**
 * The Purchase Rules List Table.
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules/Admin
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Admin;

use TEC\Common\Abstracts\Custom_List_Table;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Repository\Rules_Repository;
use TEC\Common\Contracts\Custom_Table_Repository_Interface as Repository;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;

/**
 * Class List_Table
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules/Admin
 */
class List_Table extends Custom_List_Table {
	/**
	 * The plural name of the item in the list.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	protected const PLURAL = 'purchase-rules';

	/**
	 * The table ID.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	protected const TABLE_ID = 'tec-tickets-plus-purchase-rules-table';

	/**
	 * Returns the search placeholder.
	 *
	 * @since 6.9.0
	 *
	 * @return string
	 */
	protected function get_search_placeholder(): string {
		return __( 'Search by purchase rule name', 'event-tickets-plus' );
	}

	/**
	 * Outputs the results of the filters above the table.
	 *
	 * It should echo the output.
	 *
	 * @since 6.9.0
	 */
	public function do_top_tablename_filters(): void {
	}

	/**
	 * Outputs the content to display when the list is completely empty.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function empty_content(): void {
		tribe( Page::class )->render_empty_content();
	}

	/**
	 * Returns the subscriber's status.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule $item The current item.
	 *
	 * @return string
	 */
	public function column_status( Rule $item ) {
		return Rule::get_label( $item->get_status() );
	}

	/**
	 * Returns the type of the rule.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule $item The current item.
	 *
	 * @return string
	 */
	public function column_type( Rule $item ) {
		return Rule::get_label( $item->get_type() );
	}

	/**
	 * Returns the name of the rule.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule $item The rule.
	 *
	 * @return string The name of the rule.
	 */
	public function column_name( Rule $item ) {
		ob_start();
		$name = $item->get_name();
		?>
		<?php // translators: %d: Rule's ID. ?>
		<a href="<?php echo esc_url( tribe( Page::class )->get_url( [ 'rule_id' => $item->get_id() ] ) ); ?>" class="row-title tec-tickets-plus-purchase-rule-edit-rule" aria-label="<?php echo esc_attr( sprintf( __( 'Edit Rule with id &#8220;%d&#8221;', 'event-tickets-plus' ), $item->get_id() ) ); ?>">
			<?php echo esc_html( $name ); ?>
		</a>
		<?php
		return ob_get_clean();
	}

	/**
	 * Returns the list of columns.
	 *
	 * @since 6.9.0
	 *
	 * @return array An associative array in the format [ <slug> => <title> ]
	 */
	public function get_columns(): array {
		/**
		 * Filters the list of columns for the Purchase Rules table.
		 *
		 * @since 6.9.0
		 *
		 * @param array $columns List of columns.
		 */
		return (array) apply_filters(
			'tec_tickets_plus_purchase_rules_table_columns',
			[
				'cb'     => '<input type="checkbox" />',
				'name'   => __( 'Rule Name', 'event-tickets-plus' ),
				'status' => __( 'Status', 'event-tickets-plus' ),
				'type'   => __( 'Type', 'event-tickets-plus' ),
			]
		);
	}

	/**
	 * Generates and displays row action links.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule   $item        Item being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 *
	 * @return string Row actions output.
	 */
	protected function handle_row_actions( $item, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$rule_status = $item->get_status();

		return $this->row_actions(
			[
				'edit'   => sprintf(
					'<a href="%s" class="edit" aria-label="%s">%s</a>',
					esc_url( tribe( Page::class )->get_url( [ 'rule_id' => $item->get_id() ] ) ),
					/* translators: %d: Rule's ID. */
					esc_attr( sprintf( __( 'Edit Rule with id &#8220;%d&#8221;', 'event-tickets-plus' ), $item->get_id() ) ),
					__( 'Edit', 'event-tickets-plus' )
				),
				$rule_status === Rule::ACTIVE_STATUS ? 'deactivate' : 'activate' => sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					esc_url( tribe( Page::class )->get_purchase_rule_full_toggle_status_url( $item->get_id() ) ),
					esc_attr(
						sprintf(
							$rule_status === Rule::ACTIVE_STATUS ?
							/* translators: %d: Rule's ID. */
							__( 'Deactivate Rule with id &#8220;%d&#8221;', 'event-tickets-plus' ) :
							/* translators: %d: Rule's ID. */
							__( 'Activate Rule with id &#8220;%d&#8221;', 'event-tickets-plus' ),
							$item->get_id()
						)
					),
					$rule_status === Rule::ACTIVE_STATUS ? __( 'Deactivate', 'event-tickets-plus' ) : __( 'Activate', 'event-tickets-plus' )
				),
				'delete' => sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					esc_url( tribe( Page::class )->get_purchase_rule_full_delete_url( $item->get_id() ) ),
					/* translators: %d: Rule's ID. */
					esc_attr( sprintf( __( 'Delete Rule with id &#8220;%d&#8221; permanently', 'event-tickets-plus' ), $item->get_id() ) ),
					__( 'Delete', 'event-tickets-plus' )
				),
			]
		);
	}

	/**
	 * Returns the repository.
	 *
	 * @since 6.9.0
	 *
	 * @return Repository
	 */
	protected function get_repository(): Repository {
		return tribe( Rules_Repository::class );
	}

	/**
	 * Returns this object's query arguments.
	 *
	 * @since 6.9.0
	 *
	 * @return array The query arguments.
	 */
	protected function get_object_query_args(): array {
		$status = tec_get_request_var( 'status', null );
		return [
			'status' => 'all' === $status ? null : $status,
		];
	}

	/**
	 * Returns the bulk actions.
	 *
	 * @since 6.9.0
	 *
	 * @return array
	 */
	public function get_bulk_actions(): array {
		return [
			'activate'   => __( 'Activate', 'event-tickets-plus' ),
			'deactivate' => __( 'Deactivate', 'event-tickets-plus' ),
			'delete'     => __( 'Delete', 'event-tickets-plus' ),
		];
	}

	/**
	 * Returns the views.
	 *
	 * @since 6.9.0
	 *
	 * @return array
	 */
	public function get_views(): array {
		$repo = clone $this->get_repository();

		$repo->set_default_args( [] );
		$all_count      = $repo->found();
		$active_count   = $repo->by_args( [ 'status' => Rule::ACTIVE_STATUS ] )->found();
		$inactive_count = $repo->by_args( [ 'status' => Rule::INACTIVE_STATUS ] )->found();

		$status = tec_get_request_var( 'status', 'all' );

		$views = [
			'all'                 => [
				'url'     => esc_url( tribe( Page::class )->get_url() ),
				/* translators: %d: Number of purchase rules. */
				'label'   => sprintf( __( 'All <span class="count">(%d)</span>', 'event-tickets-plus' ), $all_count ),
				'current' => $status === 'all',
			],
			Rule::ACTIVE_STATUS   => [
				'url'     => esc_url( tribe( Page::class )->get_url( [ 'status' => Rule::ACTIVE_STATUS ] ) ),
				/* translators: %d: Number of active purchase rules. */
				'label'   => sprintf( __( 'Active <span class="count">(%d)</span>', 'event-tickets-plus' ), $active_count ),
				'current' => $status === Rule::ACTIVE_STATUS,
			],
			Rule::INACTIVE_STATUS => [
				'url'     => esc_url( tribe( Page::class )->get_url( [ 'status' => Rule::INACTIVE_STATUS ] ) ),
				/* translators: %d: Number of inactive purchase rules. */
				'label'   => sprintf( __( 'Inactive <span class="count">(%d)</span>', 'event-tickets-plus' ), $inactive_count ),
				'current' => $status === Rule::INACTIVE_STATUS,
			],
		];

		return $this->get_views_links( $views );
	}
}
