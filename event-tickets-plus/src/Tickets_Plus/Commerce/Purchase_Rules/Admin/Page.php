<?php
/**
 * Purchase Rules Page which renders the Purchase Rules list table.
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules/Admin
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\Asset;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables\Rules;
use Tribe__Tickets_Plus__Main as Tickets_Plus;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Template;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use TEC\Tickets\Commerce\Utils\Currency;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints\Rules as Rules_Endpoint;

/**
 * Class Purchase_Rules_Page
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules/Admin
 */
class Page extends Controller_Contract {
	/**
	 * Event Tickets menu page slug.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const PARENT_SLUG = 'tec-tickets';

	/**
	 * Purchase Rules page slug.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const SLUG = 'tec-tickets-plus-admin-purchase-rules';

	/**
	 * The table instance.
	 *
	 * @since 6.9.0
	 *
	 * @var List_Table
	 */
	private ?List_Table $table = null;

	/**
	 * A reference to the template object.
	 *
	 * @since 6.9.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Purchase_Rules_Page constructor.
	 *
	 * @since 6.9.0
	 *
	 * @param Container $container The DI container.
	 * @param Template  $template  The template object.
	 */
	public function __construct( Container $container, Template $template ) {
		parent::__construct( $container );
		$this->template = $template;
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'admin_init', [ $this, 'register_assets' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ], 15 );
		add_action( 'current_screen', [ $this, 'prepare_table_data' ] );
		add_filter( 'set-screen-option', [ List_Table::class, 'store_custom_per_page_option' ], 10, 3 );
		add_action( 'admin_post_' . Rule::DELETE_ACTION, [ $this, 'delete_purchase_rule' ] );
		add_action( 'admin_post_' . Rule::TOGGLE_STATUS_ACTION, [ $this, 'toggle_purchase_rule_status' ] );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'admin_init', [ $this, 'register_assets' ] );
		remove_action( 'admin_menu', [ $this, 'register_admin_page' ], 15 );
		remove_action( 'current_screen', [ $this, 'prepare_table_data' ] );
		remove_filter( 'set-screen-option', [ List_Table::class, 'store_custom_per_page_option' ] );
		remove_action( 'admin_post_' . Rule::DELETE_ACTION, [ $this, 'delete_purchase_rule' ] );
		remove_action( 'admin_post_' . Rule::TOGGLE_STATUS_ACTION, [ $this, 'toggle_purchase_rule_status' ] );
	}

	/**
	 * Prepares the table data.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function prepare_table_data(): void {
		if ( ! $this->is_on_page() ) {
			return;
		}

		$this->table = $this->container->get( List_Table::class );

		$doaction = $this->table->current_action();

		if ( $doaction ) {
			check_admin_referer( 'bulk-purchase-rules' );

			$sendback = remove_query_arg( [ 'trashed', 'untrashed', 'deleted', 'locked', 'ids' ], wp_get_referer() );
			if ( ! $sendback ) {
				$sendback = $this->get_url();
			}
			$sendback = add_query_arg( 'paged', get_query_var( 'paged', 1 ), $sendback );

			$rule_ids = array_filter( array_map( 'intval', (array) tec_get_request_var_raw( 'item_id', [] ) ) );

			//phpcs:disable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
			if ( empty( $rule_ids ) ) {
				wp_safe_redirect( $sendback );
				tribe_exit();
			}

			switch ( $doaction ) {
				case 'delete':
					if ( ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to delete this item.', 'event-tickets-plus' ) );
					}

					$deleted = 0;
					foreach ( $rule_ids as $rule_id ) {
						$rule = Rules::get_by_id( $rule_id );
						if ( ! $rule ) {
							continue;
						}

						$rule->delete();
						++$deleted;
					}
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );
					break;
				case 'activate':
				case 'deactivate':
					if ( ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to change the status of this item.', 'event-tickets-plus' ) );
					}

					$updated = 0;
					foreach ( $rule_ids as $rule_id ) {
						$rule = Rules::get_by_id( $rule_id );
						if ( ! $rule ) {
							continue;
						}

						$rule->set_status( $doaction === 'activate' ? Rule::ACTIVE_STATUS : Rule::INACTIVE_STATUS );
						$rule->save();
						++$updated;
					}
					$sendback = add_query_arg( 'updated', $updated, $sendback );
					break;
			}

			$sendback = remove_query_arg( [ 'action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'rule', 'bulk_edit', 'post_view' ], $sendback );

			wp_safe_redirect( $sendback );
			tribe_exit();
		}

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitizedDetected
		if ( tec_get_request_var_raw( '_wp_http_referer', false ) ) {
			// Done by wp core as well on wp-admin/edit.php:230.
			wp_safe_redirect( remove_query_arg( [ '_wp_http_referer', '_wpnonce' ], wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
			tribe_exit();
		}
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitizedDetected

		$this->table->prepare_items();
		//phpcs:enable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
	}

	/**
	 * Registers the assets for the Purchase Rules page.
	 *
	 * @since 6.9.0
	 */
	public function register_assets(): void {
		$hook = get_plugin_page_hookname( static::SLUG, static::PARENT_SLUG );

		if ( ! $hook ) {
			return;
		}

		Asset::add(
			'tec-tickets-plus-purchase-rules-page-style',
			'purchaseRules/admin/page.css'
		)
		->add_to_group_path( Tickets_Plus::class . '-packages' )
		->enqueue_on( $hook )
		->set_dependencies( 'wp-components' );

		Asset::add(
			'tec-tickets-plus-purchase-rules-page',
			'purchaseRules/admin/page.js'
		)
		->set_dependencies( 'jquery', 'tec-api' )
		->add_to_group_path( Tickets_Plus::class . '-packages' )
		->add_localize_script(
			'tec.ticketsPlus.commerce.purchaseRules.data',
			[ $this, 'get_localized_data' ]
		)
		->enqueue_on( $hook );

		Asset::add(
			'tec-tickets-plus-purchase-rules-list-table',
			'purchaseRules/admin/list-table.js'
		)
		->add_to_group_path( Tickets_Plus::class . '-packages' )
		->enqueue_on( $hook );
	}

	/**
	 * Gets the localized data for the Purchase Rules page.
	 *
	 * @since 6.9.0
	 *
	 * @return array The localized data for the Purchase Rules page.
	 */
	public function get_localized_data(): array {
		/** @var Rules_Endpoint $endpoint */
		$endpoint = tribe( Rules_Endpoint::class );

		$rule_id = tec_get_request_var_raw( 'rule_id', 0 );

		$rule = $rule_id ? Rules::get_by_id( $rule_id ) : null;

		$term_map = [
			'category' => [],
			'series'   => [],
			'tag'      => [],
			'venue'    => [],
		];

		if ( $rule ) {
			$scope = $rule->get_scope();
			if ( $scope && $scope['connector'] !== 'all' && $scope['connector'] !== 'none' ) {
				foreach ( $scope['criteria'] as $criterion ) {
					$current_term  = $criterion['term'] ?? '';
					$current_value = $criterion['value'] ?? '';
					if ( ! ( $current_term && $current_value ) ) {
						continue;
					}

					switch ( $current_term ) {
						case 'category':
						case 'tag':
							$term_map[ $current_term ][] = [
								'value' => $current_value,
								'label' => get_term( $current_value )->name ?? '',
							];
							break;

						case 'venue':
						case 'series':
							$term_map[ $current_term ][] = [
								'value' => $current_value,
								'label' => get_post( $current_value )->post_title ?? '',
							];
							break;
						default:
							break;
					}
				}
			}
		}

		return [
			'ruleDescriptions'       => Rule::get_rule_descriptions(),
			'currencySymbol'         => html_entity_decode( Currency::get_currency_symbol( Currency::get_currency_code() ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
			'currencySymbolPosition' => Currency::get_currency_symbol_position( Currency::get_currency_code() ),
			'roles'                  => wp_roles()->get_names(),
			'endpoint'               => $endpoint->get_url(),
			'rule'                   => $rule ? $rule->toArray() : null,
			'termMap'                => $term_map,
		];
	}

	/**
	 * Defines whether the current page is this Page.
	 *
	 * @since 6.9.0
	 *
	 * @return boolean
	 */
	protected function is_on_page(): bool {
		/** @var \Tribe\Admin\Pages */
		$admin_pages = tribe( 'admin.pages' );
		$admin_page  = $admin_pages->get_current_page();

		return ! empty( $admin_page ) && static::SLUG === $admin_page;
	}

	/**
	 * Registers the Purchase Rules page in the admin menu.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		/** @var \Tribe\Admin\Pages */
		$admin_pages = tribe( 'admin.pages' );

		$admin_pages->register_page(
			[
				'id'       => static::SLUG,
				'path'     => static::SLUG,
				'parent'   => static::PARENT_SLUG,
				'title'    => esc_html__( 'Purchase Rules', 'event-tickets-plus' ),
				'position' => 5.8,
				'callback' => [
					$this,
					'render',
				],
			]
		);
	}

	/**
	 * Gets the URL of the Purchase Rules page.
	 *
	 * @since 6.9.0
	 *
	 * @param array $params The query parameters to add to the URL.
	 *
	 * @return string
	 */
	public function get_url( array $params = [] ): string {
		return add_query_arg( array_merge( $params, [ 'page' => static::SLUG ] ), admin_url( 'admin.php' ) );
	}

	/**
	 * Renders the Purchase Rules page.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function render(): void {
		// This is something being done by Core's WP_List_Table during render, so we do it as well.
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$_SERVER['REQUEST_URI'] = remove_query_arg( [ 'locked', 'skipped', 'updated', 'deleted', 'trashed', 'untrashed' ], $_SERVER['REQUEST_URI'] );
			// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}

		$purchase_rule_id = tec_get_request_var_raw( 'rule_id', null );
		if ( null === $purchase_rule_id ) {
			$this->template->template(
				'admin/list',
				[
					'table' => $this->table,
				]
			);
			return;
		}

		$purchase_rule_id = (int) $purchase_rule_id;

		if ( ! $purchase_rule_id ) {
			$this->template->template( 'admin/edit' );
			return;
		}

		$purchase_rule = Rules::get_by_id( $purchase_rule_id );
		if ( ! $purchase_rule ) {
			wp_die( esc_html__( 'Purchase rule not found.', 'event-tickets-plus' ) );
			return;
		}

		$this->template->template(
			'admin/edit',
			[
				'rule' => $purchase_rule,
			]
		);
	}

	/**
	 * Renders the empty content.
	 *
	 * This is happening when the Purchase Rules table is completely empty!
	 * Not when the results of a search or/and filtered query are empty.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function render_empty_content(): void {
		$this->template->template( 'admin/empty-content' );
	}

	/**
	 * Deletes a purchase rule.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function delete_purchase_rule(): void {
		if ( ! wp_verify_nonce( tec_get_request_var_raw( 'nonce', '' ), Rule::DELETE_ACTION ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'event-tickets-plus' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete this purchase rule.', 'event-tickets-plus' ) );
			return;
		}

		$purchase_rule_id = (int) tec_get_request_var_raw( 'id', 0 );

		$purchase_rule = Rules::get_by_id( $purchase_rule_id );

		if ( ! $purchase_rule ) {
			wp_die( esc_html__( 'Purchase rule not found.', 'event-tickets-plus' ) );
			return;
		}

		$purchase_rule->delete();

		$return_to = add_query_arg( [ 'deleted' => 1 ], wp_get_referer() );

		//phpcs:disable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
		wp_safe_redirect( $return_to, 302, 'ETP Purchase Rule Deleted' );
		tribe_exit();
		//phpcs:enable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
	}

	/**
	 * Toggles the status of a purchase rule.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function toggle_purchase_rule_status(): void {
		if ( ! wp_verify_nonce( tec_get_request_var_raw( 'nonce', '' ), Rule::TOGGLE_STATUS_ACTION ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'event-tickets-plus' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to toggle the status of this purchase rule.', 'event-tickets-plus' ) );
			return;
		}

		$purchase_rule_id = (int) tec_get_request_var_raw( 'id', 0 );

		$purchase_rule = Rules::get_by_id( $purchase_rule_id );

		if ( ! $purchase_rule ) {
			wp_die( esc_html__( 'Purchase rule not found.', 'event-tickets-plus' ) );
			return;
		}

		$purchase_rule->set_status( $purchase_rule->get_status() === Rule::ACTIVE_STATUS ? Rule::INACTIVE_STATUS : Rule::ACTIVE_STATUS );
		$purchase_rule->save();

		$return_to = add_query_arg( [ 'updated' => 1 ], wp_get_referer() );

		//phpcs:disable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
		wp_safe_redirect( $return_to, 302, 'ETP Purchase Rule Status Updated' );
		tribe_exit();
		//phpcs:enable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
	}

	/**
	 * Returns the full delete URL for a purchase rule.
	 *
	 * @since 6.9.0
	 *
	 * @param int $purchase_rule_id The ID of the purchase rule.
	 *
	 * @return string The full delete URL for a purchase rule.
	 */
	public function get_purchase_rule_full_delete_url( int $purchase_rule_id ): string {
		return admin_url(
			add_query_arg(
				[
					'action' => Rule::DELETE_ACTION,
					'id'     => $purchase_rule_id,
					'nonce'  => wp_create_nonce( Rule::DELETE_ACTION ),
				],
				'admin-post.php'
			)
		);
	}

	/**
	 * Returns the full toggle status URL for a purchase rule.
	 *
	 * @since 6.9.0
	 *
	 * @param int $purchase_rule_id The ID of the purchase rule.
	 *
	 * @return string The full toggle status URL for a purchase rule.
	 */
	public function get_purchase_rule_full_toggle_status_url( int $purchase_rule_id ): string {
		return admin_url(
			add_query_arg(
				[
					'action' => Rule::TOGGLE_STATUS_ACTION,
					'id'     => $purchase_rule_id,
					'nonce'  => wp_create_nonce( Rule::TOGGLE_STATUS_ACTION ),
				],
				'admin-post.php'
			)
		);
	}
}
