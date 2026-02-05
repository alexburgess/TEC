<?php
/**
 * The main Editor controller, for both Classic and Blocks.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\Asset;
use TEC\Common\Contracts\Container;
use TEC\Common\StellarWP\Assets\Assets;
use Tribe__Tickets__Main as Tickets_Plugin;
use Tribe__Tickets_Plus__Main as Tickets_Plus;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Blocks\Tickets\Block as Tickets_Block;
use TEC\Tickets\Seating\Service\Service;
use TEC\Tickets\Ticket_Data;
use WP_Post;
use WP_REST_Server;

/**
 * Class Editor.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Editor extends Controller_Contract {
	/**
	 * The slug for the block editor script.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const EDITOR_SCRIPT_SLUG = 'tec-tickets-plus-waitlist-block-editor';

	/**
	 * The slug for the block editor style.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const EDITOR_STYLE_SLUG = 'tec-tickets-plus-waitlist-block-editor-style';

	/**
	 * The waitlists service.
	 *
	 * @since 6.2.0
	 *
	 * @var Waitlists
	 */
	private Waitlists $waitlists;

	/**
	 * The subscribers service.
	 *
	 * @since 6.2.0
	 *
	 * @var Subscribers
	 */
	private Subscribers $subscribers;

	/**
	 * The waitlist template.
	 *
	 * @since 6.2.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * The ticket data service.
	 *
	 * @since 6.5.1
	 *
	 * @var Ticket_Data
	 */
	private Ticket_Data $ticket_data;

	/**
	 * Editor constructor.
	 *
	 * @since 6.2.0
	 * @since 6.5.1 Added $ticket_data parameter.
	 *
	 * @param Container   $container   The container.
	 * @param Waitlists   $waitlists   The waitlists service.
	 * @param Subscribers $subscribers The subscribers service.
	 * @param Template    $template    The waitlist template.
	 * @param Ticket_Data $ticket_data The ticket data service.
	 */
	public function __construct( Container $container, Waitlists $waitlists, Subscribers $subscribers, Template $template, Ticket_Data $ticket_data ) {
		parent::__construct( $container );
		$this->waitlists   = $waitlists;
		$this->subscribers = $subscribers;
		$this->template    = $template;
		$this->ticket_data = $ticket_data;
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		// Priority should be over 10.
		add_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ], 20 );
		add_action( 'save_post', [ $this, 'update_waitlist_data' ], 20, 2 );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ], 10, 2 );
		add_action( 'wp_ajax_tec_tickets_plus_waitlist_save', [ $this, 'handle_in_classic_editor' ] );
		add_action( 'wp_ajax_tec_tickets_plus_waitlist_delete', [ $this, 'delete_waitlist' ] );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$assets = Assets::instance();
		$assets->remove( self::EDITOR_SCRIPT_SLUG );
		$assets->remove( self::EDITOR_STYLE_SLUG );
		remove_action( 'admin_enqueue_scripts', [ $this, 'register_assets' ], 20 );
		remove_action( 'save_post', [ $this, 'update_waitlist_data' ], 20 );
		remove_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		remove_action( 'wp_ajax_tec_tickets_plus_waitlist_save', [ $this, 'handle_in_classic_editor' ] );
		remove_action( 'wp_ajax_tec_tickets_plus_waitlist_delete', [ $this, 'delete_waitlist' ] );
	}

	/**
	 * Adds the metabox to the ticket-able post types.
	 *
	 * @since 6.2.0
	 *
	 * @param string  $post_type The post type.
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function add_meta_boxes( $post_type, $post ): void {
		if ( ! in_array( $post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return;
		}

		// Hard coded for ease of access.
		if ( 'tribe_event_series' === $post_type ) {
			// Series not compatible with waitlist yet.
			return;
		}

		$current_screen = get_current_screen();

		if ( $current_screen && $current_screen->is_block_editor() ) {
			// Block editor. We bail.
			return;
		}

		add_meta_box(
			'tec-tickets-plus-waitlist-metabox',
			__( 'Waitlist', 'event-tickets-plus' ),
			[ $this, 'render_waitlist_metabox' ],
			$post_type,
			'normal',
			'high'
		);
	}

	/**
	 * Renders the waitlist metabox.
	 *
	 * @since 6.2.0
	 * @since 6.5.1 Updated to use the Ticket_Data class.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 */
	public function render_waitlist_metabox( WP_Post $post ) {
		if ( $this->is_using_asc_seating( $post->ID ) ) {
			// ASC seating is enabled. We bail.
			return;
		}

		$ticket_waitlist    = $this->waitlists->get_posts_ticket_waitlist( $post->ID );
		$rsvp_waitlist      = $this->waitlists->get_posts_rsvp_waitlist( $post->ID );
		$ticket_subscribers = $ticket_waitlist ? $this->subscribers->count_pending_subscribers_for_waitlist( $ticket_waitlist ) : 0;
		$rsvp_subscribers   = $rsvp_waitlist ? $this->subscribers->count_pending_subscribers_for_waitlist( $rsvp_waitlist ) : 0;

		/**
		 * Fires before the waitlist metabox is rendered.
		 *
		 * @since 6.2.0
		 *
		 * @param WP_Post $post The post object.
		 */
		do_action( 'tec_tickets_plus_waitlist_before_metabox', $post );

		$this->waitlists->add_about_to_seconds_hook();

		/**
		 * Filters the data passed to the waitlist metabox.
		 *
		 * @since 6.2.0
		 *
		 * @param array The data passed to the waitlist metabox.
		 *
		 * @return array
		 */
		$metabox_data = (array) apply_filters(
			'tec_tickets_plus_waitlist_metabox_data',
			[
				'event'              => $post,
				'ticket_waitlist'    => $ticket_waitlist,
				'rsvp_waitlist'      => $rsvp_waitlist,
				'ticket_subscribers' => $ticket_subscribers,
				'rsvp_subscribers'   => $rsvp_subscribers,
				'tickets_data'       => $this->ticket_data->get_posts_tickets_data( $post->ID ),
				'rsvp_data'          => $this->ticket_data->get_posts_rsvps_data( $post->ID ),
				'table_url'          => $this->subscribers->get_table_url_for_event( $post->ID ),
			]
		);

		$this->waitlists->remove_about_to_seconds_hook();

		$this->template->template( 'admin/metabox', $metabox_data );
	}

	/**
	 * Returns the store data used to hydrate the store in Block Editor context.
	 *
	 * @since 6.2.0
	 * @since 6.5.1 Updated to use the Ticket_Data class.
	 *
	 * @return array {
	 *
	 * }
	 */
	public function get_store_data(): array {
		if ( tribe_context()->is_new_post() ) {
			$is_using_asc_seating = true;
			$post_id              = 0;
			$waitlist_data        = [];
			$rsvp_data            = [];

			$ticket_availability            = [];
			$tickets_on_sale                = [];
			$tickets_have_not_started_sales = [];
			$tickets_have_ended_sales       = [];
			$ticket_count                   = 0;

			$rsvp_availability           = [];
			$rsvp_on_sale                = [];
			$rsvp_have_not_started_sales = [];
			$rsvp_have_ended_sales       = [];
			$rsvp_count                  = 0;

			$ticket_subscribers = 0;
			$rsvp_subscribers   = 0;
			$table_url          = '';
		} else {
			$post_id       = get_the_ID();
			$waitlist_data = $this->waitlists->get_posts_ticket_waitlist( $post_id );
			$rsvp_data     = $this->waitlists->get_posts_rsvp_waitlist( $post_id, 1 );

			$ticket_subscribers = $waitlist_data ? $this->subscribers->count_pending_subscribers_for_waitlist( $waitlist_data ) : 0;
			$rsvp_subscribers   = $rsvp_data ? $this->subscribers->count_pending_subscribers_for_waitlist( $rsvp_data ) : 0;
			$table_url          = $this->subscribers->get_table_url_for_event( $post_id );

			$this->waitlists->add_about_to_seconds_hook();

			$ticket_data = $this->ticket_data->get_posts_tickets_data( $post_id );

			$ticket_count                   = $ticket_data['ticket_count'];
			$ticket_availability            = $ticket_data['availability'];
			$tickets_on_sale                = $ticket_data['tickets_on_sale'];
			$tickets_have_not_started_sales = $ticket_data['tickets_have_not_started_sales'];
			$tickets_have_ended_sales       = $ticket_data['tickets_have_ended_sales'];

			$rsvps_data = $this->ticket_data->get_posts_rsvps_data( $post_id );

			$this->waitlists->remove_about_to_seconds_hook();

			$rsvp_count                  = $rsvps_data['ticket_count'];
			$rsvp_availability           = $rsvps_data['availability'];
			$rsvp_on_sale                = $rsvps_data['tickets_on_sale'];
			$rsvp_have_not_started_sales = $rsvps_data['tickets_have_not_started_sales'];
			$rsvp_have_ended_sales       = $rsvps_data['tickets_have_ended_sales'];

			$is_using_asc_seating = $this->is_using_asc_seating( $post_id );
		}

		return [
			'ticketData'             => [
				'count'                      => $ticket_count,
				'ticketsOnSale'              => $tickets_on_sale,
				'ticketsHaveNotStartedSales' => $tickets_have_not_started_sales,
				'ticketsHaveEndedSales'      => $tickets_have_ended_sales,
			],
			'rsvpData'               => [
				'count'                      => $rsvp_count,
				'ticketsOnSale'              => $rsvp_on_sale,
				'ticketsHaveNotStartedSales' => $rsvp_have_not_started_sales,
				'ticketsHaveEndedSales'      => $rsvp_have_ended_sales,
			],
			'availability'           => [
				'tickets' => $ticket_availability,
				'rsvp'    => $rsvp_availability,
			],
			'waitlistData'           => [
				'tickets' => $waitlist_data ? $waitlist_data->to_array() : [],
				'rsvp'    => $rsvp_data ? $rsvp_data->to_array() : [],
			],
			'isUsingAssignedSeating' => $this->has_seating_license() && $is_using_asc_seating,
			'localized'              => [
				'tableUrl'    => $table_url,
				'subscribers' => [
					'tickets' => $ticket_subscribers,
					'rsvp'    => $rsvp_subscribers,
				],
				'metaKeys'    => [
					'tickets'      => [
						'enabled'     => Meta::ENABLED_KEY,
						'conditional' => Meta::CONDITIONAL_KEY,
					],
					'rsvp'         => [
						'enabled'     => Meta::RSVP_ENABLED_KEY,
						'conditional' => Meta::RSVP_CONDITIONAL_KEY,
					],
					'conditionals' => [
						'always'      => Waitlist::ALWAYS_CONDITIONAL,
						'before_sale' => Waitlist::BEFORE_SALE_CONDITIONAL,
						'on_sold_out' => Waitlist::ON_SOLD_OUT_CONDITIONAL,
					],
				],
			],
		];
	}

	/**
	 * Checks if the site has a seating license.
	 *
	 * @since 6.2.0
	 *
	 * @return bool
	 */
	protected function has_seating_license(): bool {
		return $this->container->isBound( Service::class ) && ! tribe( Service::class )->get_status()->has_no_license();
	}

	/**
	 * Checks if the post is using ASC seating.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool
	 */
	public function is_using_asc_seating( int $post_id ): bool {
		return function_exists( 'tec_tickets_seating_enabled' ) && tec_tickets_seating_enabled( $post_id );
	}

	/**
	 * Registers the JavaScript and CSS assets.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function register_assets(): void {
		Asset::add(
			self::EDITOR_SCRIPT_SLUG,
			'blockEditor.js',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( 'tec-tickets-plus-waitlist' )
			->set_dependencies(
				'wp-hooks',
				'react',
				'react-dom',
				'tribe-common-gutenberg-vendor',
				'wp-i18n',
				'wp-data',
				'wp-hooks',
				'wp-components',
			)
			->add_localize_script( 'tec.ticketsPlus.waitlist.blockEditorData', [ $this, 'get_store_data' ] )
			->add_to_group( 'tec-tickets-plus-waitlist-editor' )
			->add_to_group( 'tec-tickets-plus-waitlist' )
			->register();

		Asset::add(
			self::EDITOR_STYLE_SLUG,
			'style-blockEditor.css',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( 'tec-tickets-plus-waitlist' )
			->add_to_group( 'tec-tickets-plus-waitlist-editor' )
			->add_to_group( 'tec-tickets-plus-waitlist' )
			->register();

		Asset::add(
			'tec-tickets-plus-waitlist-classic-editor',
			'waitlist/admin/metabox.js',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plus::class )
			->set_dependencies(
				'jquery',
				'wp-i18n'
			)
			->enqueue_on( 'tec_tickets_plus_waitlist_before_metabox' )
			->add_to_group( 'tec-tickets-plus-waitlist-admin' )
			->add_to_group( 'tec-tickets-plus-waitlist' )
			->register();

		Asset::add(
			'tec-tickets-plus-waitlist-classic-editor-style',
			'waitlist/admin/metabox.css',
			Tickets_Plugin::VERSION
		)
			->add_to_group_path( Tickets_Plus::class )
			->enqueue_on( 'tec_tickets_plus_waitlist_before_metabox' )
			->add_to_group( 'tec-tickets-plus-waitlist-admin' )
			->add_to_group( 'tec-tickets-plus-waitlist' )
			->register();

		$assets = Assets::instance();

		$tickets_block_editor_script = $assets->get( Tickets_Block::EDITOR_SCRIPT_SLUG );
		if ( ! $tickets_block_editor_script ) {
			return;
		}

		wp_scripts()->remove( [ Tickets_Block::EDITOR_SCRIPT_SLUG ] );
		$tickets_block_editor_script->set_dependencies(
			self::EDITOR_SCRIPT_SLUG,
			...$tickets_block_editor_script->get_dependencies()
		)
			->set_as_unregistered()
			->register();

		$tickets_block_editor_style = $assets->get( Tickets_Block::EDITOR_STYLE_SLUG );
		if ( ! $tickets_block_editor_style ) {
			return;
		}
		wp_styles()->remove( [ Tickets_Block::EDITOR_STYLE_SLUG ] );
		$tickets_block_editor_style->set_dependencies(
			self::EDITOR_STYLE_SLUG,
			...$tickets_block_editor_style->get_dependencies()
		)
			->set_as_unregistered()
			->register();
	}

	/**
	 * Updates the waitlist data for a post.
	 *
	 * @since 6.2.0
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function update_waitlist_data( int $post_id, WP_Post $post ): void {
		if ( ! in_array( $post->post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			// Not a ticket-able post type.
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			// Bail on revisions.
			return;
		}

		if ( wp_is_post_autosave( $post_id ) ) {
			// Bail on autosaves.
			return;
		}

		// This is fired on 'save_post' so after the post has been saved already. Permissions have been checked by WP.
		$this->handle_in_rest_context( $post_id );
	}

	/**
	 * Attempts to handle the waitlist data in the Block Editor context.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool Whether the data was handled or not.
	 */
	protected function handle_in_rest_context( int $post_id ): void {
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		$data = WP_REST_Server::get_raw_data();

		if ( ! ( $data && is_string( $data ) ) ) {
			return;
		}

		$data = json_decode( $data, true );

		if ( empty( $data['meta'] ) || ! is_array( $data['meta'] ) ) {
			return;
		}

		$data = $data['meta'];

		$waitlist      = [];
		$rsvp_waitlist = [];

		if ( isset( $data[ Meta::ENABLED_KEY ] ) ) {
			$waitlist['enabled'] = (bool) $data[ Meta::ENABLED_KEY ];
		}

		if ( isset( $data[ Meta::CONDITIONAL_KEY ] ) ) {
			$waitlist['conditional'] = $data[ Meta::CONDITIONAL_KEY ];
		}

		if ( isset( $data[ Meta::RSVP_ENABLED_KEY ] ) ) {
			$rsvp_waitlist['enabled'] = (bool) $data[ Meta::RSVP_ENABLED_KEY ];
		}

		if ( isset( $data[ Meta::RSVP_CONDITIONAL_KEY ] ) ) {
			$rsvp_waitlist['conditional'] = $data[ Meta::RSVP_CONDITIONAL_KEY ];
		}

		if ( empty( $waitlist ) && empty( $rsvp_waitlist ) ) {
			return;
		}

		if ( ! empty( $rsvp_waitlist ) ) {
			$this->waitlists->upsert_waitlist_for_post( $post_id, $rsvp_waitlist, 1 );
		}

		if ( ! empty( $waitlist ) ) {
			$this->waitlists->upsert_waitlist_for_post( $post_id, $waitlist );
		}
	}

	/**
	 * Handles the waitlist data in the Classic Editor context.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function handle_in_classic_editor(): void {
		check_ajax_referer( 'tec_tickets_plus_waitlist_save', 'nonce' );

		$post_id = (int) tec_get_request_var_raw( 'post_id', 0 );

		if ( ! $post_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post ID.', 'event-tickets-plus' ) ], 400 );
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets-plus' ) ], 403 );
			return;
		}

		$conditional = tec_get_request_var( 'conditional', '' );

		if ( ! in_array( $conditional, [ Waitlist::ALWAYS_CONDITIONAL, Waitlist::BEFORE_SALE_CONDITIONAL, Waitlist::ON_SOLD_OUT_CONDITIONAL ], true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid conditional.', 'event-tickets-plus' ) ], 400 );
			return;
		}

		$type = tec_get_request_var_raw( 'type', '' );

		if ( ! is_numeric( $type ) || ! in_array( (int) $type, [ Waitlist::TICKET_TYPE, Waitlist::RSVP_TYPE ], true ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid waitlist type.', 'event-tickets-plus' ) ], 400 );
			return;
		}

		$waitlist = [
			'enabled'     => true,
			'conditional' => $conditional,
		];

		$this->waitlists->upsert_waitlist_for_post( $post_id, $waitlist, (int) $type );

		ob_start();
		$this->render_waitlist_metabox( get_post( $post_id ) );
		$markup = ob_get_clean();

		wp_send_json_success( [ 'markup' => $markup ], 200 );
	}

	/**
	 * Deletes a waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function delete_waitlist(): void {
		check_ajax_referer( 'tec_tickets_plus_waitlist_delete', 'nonce' );

		$waitlist_id = (int) tec_get_request_var_raw( 'waitlist_id', 0 );

		if ( ! $waitlist_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid waitlist ID.', 'event-tickets-plus' ) ] );
		}

		$waitlist = $this->waitlists->get( $waitlist_id );

		if ( ! $waitlist ) {
			wp_send_json_error( [ 'message' => __( 'Invalid waitlist ID.', 'event-tickets-plus' ) ] );
		}

		if ( ! current_user_can( 'delete_post', $waitlist->get_post_id() ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets-plus' ) ] );
		}

		$post_id = $waitlist->get_post_id();

		$waitlist->delete();

		ob_start();
		$this->render_waitlist_metabox( get_post( $post_id ) );
		$markup = ob_get_clean();

		wp_send_json_success( [ 'markup' => $markup ] );
	}
}
