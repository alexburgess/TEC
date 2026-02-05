<?php
/**
 * Waitlist Subscribers Page which renders the Waitlist Subscribers list table.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist/Admin
 */

namespace TEC\Tickets_Plus\Waitlist\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Tickets_Plus\Waitlist\Template;
use TEC\Tickets_Plus\Waitlist\Subscriber;
use TEC\Tickets_Plus\Waitlist\Subscribers;
use TEC\Common\Asset;
use WP_Post;
use Tribe__Tickets_Plus__Main as Tickets_Plus;

/**
 * Class Waitlist_Subscribers_Page
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist/Admin
 */
class Waitlist_Subscribers_Page extends Controller_Contract {
	/**
	 * Event Tickets menu page slug.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const PARENT_SLUG = 'tec-tickets';

	/**
	 * Waitlist Subscribers page slug.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const SLUG = 'tec-tickets-plus-admin-waitlist-subscribers';

	/**
	 * The table instance.
	 *
	 * @since 6.2.0
	 *
	 * @var Waitlist_Subscribers_Table
	 */
	private ?Waitlist_Subscribers_Table $table = null;

	/**
	 * A reference to the template object.
	 *
	 * @since 6.2.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * A reference to the subscribers object.
	 *
	 * @since 6.2.0
	 *
	 * @var Subscribers
	 */
	private Subscribers $subscribers;

	/**
	 * Waitlist_Subscribers_Page constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param Container $container The DI container.
	 * @param Template  $template  The template object.
	 */
	public function __construct( Container $container, Template $template, Subscribers $subscribers ) {
		parent::__construct( $container );
		$this->template = $template;
		$this->subscribers = $subscribers;
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'admin_init', [ $this, 'register_assets' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ], 15 );
		add_action( 'current_screen', [ $this, 'prepare_table_data' ] );
		add_filter( 'set-screen-option', [ Waitlist_Subscribers_Table::class, 'store_custom_per_page_option' ], 10, 3 );
		add_action( 'admin_post_' . Subscriber::DELETE_ACTION, [ $this, 'delete_subscriber' ] );
		add_filter( 'tec_tickets_admin_tickets_table_event_actions', [ $this, 'add_waitlist_subscribers_link' ], 10, 2 );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'admin_init', [ $this, 'register_assets' ] );
		remove_action( 'admin_menu', [ $this, 'register_admin_page' ], 15 );
		remove_action( 'current_screen', [ $this, 'prepare_table_data' ] );
		remove_filter( 'set-screen-option', [ Waitlist_Subscribers_Table::class, 'store_custom_per_page_option' ] );
		remove_action( 'admin_post_' . Subscriber::DELETE_ACTION, [ $this, 'delete_subscriber' ] );
		remove_filter( 'tec_tickets_admin_tickets_table_event_actions', [ $this, 'add_waitlist_subscribers_link' ] );
	}

	/**
	 * Adds the Waitlist Subscribers link to the All Tickets quick actions.
	 *
	 * @since 6.2.0
	 *
	 * @param array   $actions The event actions.
	 * @param WP_Post $event   The event post object.
	 *
	 * @return array
	 */
	public function add_waitlist_subscribers_link( array $actions, WP_Post $event ): array {
		$actions['waitlist_subscribers'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $this->subscribers->get_table_url_for_event( $event->ID ) ),
			esc_html__( 'Waitlist Subscribers', 'event-tickets-plus' )
		);

		return $actions;
	}

	/**
	 * Prepares the table data.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function prepare_table_data(): void {
		if ( ! $this->is_on_page() ) {
			return;
		}

		$this->table = $this->container->get( Waitlist_Subscribers_Table::class );

		$doaction = $this->table->current_action();

		if ( $doaction ) {
			check_admin_referer( 'bulk-waitlist-subscribers' );

			$sendback = remove_query_arg( [ 'trashed', 'untrashed', 'deleted', 'locked', 'ids' ], wp_get_referer() );
			if ( ! $sendback ) {
				$sendback = $this->get_url();
			}
			$sendback = add_query_arg( 'paged', get_query_var( 'paged', 1 ), $sendback );

			$subscriber_ids = array_filter( array_map( 'intval', (array) tec_get_request_var_raw( 'subscriber', [] ) ) );

			//phpcs:disable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
			if ( empty( $subscriber_ids ) ) {
				wp_safe_redirect( $sendback );
				tribe_exit();
			}

			switch ( $doaction ) {
				case 'delete':
					if ( ! current_user_can( 'manage_options' ) ) {
						wp_die( esc_html__( 'Sorry, you are not allowed to delete this item.', 'event-tickets-plus' ) );
					}

					$deleted = 0;
					foreach ( (array) $subscriber_ids as $sub_id ) {
						$subscriber = $this->subscribers->get( $sub_id );
						if ( ! $subscriber ) {
							continue;
						}

						$subscriber->delete();
						++$deleted;
					}
					$sendback = add_query_arg( 'deleted', $deleted, $sendback );
					break;
			}

			$sendback = remove_query_arg( [ 'action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'subscriber', 'bulk_edit', 'post_view' ], $sendback );

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
	 * Registers the assets for the Waitlist Subscribers page.
	 *
	 * @since 6.2.0
	 */
	public function register_assets(): void {
		$hook = get_plugin_page_hookname( static::SLUG, static::PARENT_SLUG );

		if ( ! $hook ) {
			return;
		}

		Asset::add(
			'tec-tickets-plus-waitlist-subscribers',
			'waitlist/admin/page.css'
		)
		->add_to_group_path( Tickets_Plus::class )
		->enqueue_on( $hook );

		Asset::add(
			'tec-tickets-plus-waitlist-subscribers-table',
			'waitlist/admin/table.js'
		)
		->set_dependencies( 'jquery', 'wp-i18n' )
		->add_to_group_path( Tickets_Plus::class )
		->enqueue_on( $hook );
	}

	/**
	 * Defines whether the current page is this Page.
	 *
	 * @since 6.2.0
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
	 * Registers the Waitlist Subscribers page in the admin menu.
	 *
	 * @since 6.2.0
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
				'title'    => esc_html__( 'Waitlist Subscribers', 'event-tickets-plus' ),
				'position' => 6.2,
				'callback' => [
					$this,
					'render',
				],
			]
		);
	}

	/**
	 * Gets the URL of the Waitlist Subscribers page.
	 *
	 * @since 6.2.0
	 *
	 * @param array $params The query parameters to add to the URL.
	 *
	 * @return string
	 */
	public function get_url( array $params = [] ): string {
		return add_query_arg( array_merge( $params, [ 'page' => static::SLUG ] ), admin_url( 'admin.php' ) );
	}

	/**
	 * Renders the Waitlist Subscribers page.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function render(): void {
		// This is something being done by Core's WP_List_Table during render, so we do it as well.
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			//phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$_SERVER['REQUEST_URI'] = remove_query_arg( [ 'locked', 'skipped', 'updated', 'deleted', 'trashed', 'untrashed' ], $_SERVER['REQUEST_URI'] );
		}

		$this->template->template(
			'admin/page',
			[
				'table' => $this->table,
			]
		);
	}

	/**
	 * Renders the empty content.
	 *
	 * This is happening when the Subscribers table is completely empty!
	 * Not when the results of a search or/and filtered query are empty.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function render_empty_content(): void {
		$this->template->template( 'admin/empty-content' );
	}

	/**
	 * Deletes a subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function delete_subscriber(): void {
		if ( ! wp_verify_nonce( tec_get_request_var_raw( 'nonce', '' ), Subscriber::DELETE_ACTION ) ) {
			wp_die( esc_html__( 'Invalid nonce.', 'event-tickets-plus' ) );
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete this subscriber.', 'event-tickets-plus' ) );
			return;
		}

		$subscriber_id = (int) tec_get_request_var_raw( 'id', 0 );

		$subscriber = $this->subscribers->get( $subscriber_id );

		if ( ! $subscriber ) {
			wp_die( esc_html__( 'Subscriber not found.', 'event-tickets-plus' ) );
			return;
		}

		$subscriber->delete();

		$return_to = add_query_arg( [ 'deleted' => 1 ], wp_get_referer() );

		//phpcs:disable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
		wp_safe_redirect( $return_to, 302, 'ETP Waitlist Subscriber Deleted' );
		tribe_exit();
		//phpcs:enable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
	}
}
