<?php
/**
 * The Singleton to interact with Subscribers.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Tickets_Plus\Waitlist\Tables\Waitlist_Subscribers as Subscribers_Table;
use TEC\Tickets_Plus\Waitlist\Tables\Waitlist_Pending_Users as Pending_Subscribers_Table;
use TEC\Common\StellarWP\DB\DB;
use WP_User;
use TEC\Tickets_Plus\Waitlist\Admin\Waitlist_Subscribers_Page as Admin_Page;

/**
 * Class Subscribers.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Subscribers {
	/**
	 * The subscribers table interface.
	 *
	 * @since 6.2.0
	 *
	 * @var Subscribers_Table
	 */
	private Subscribers_Table $subscribers_table;

	/**
	 * The pending subscribers table interface.
	 *
	 * @since 6.2.0
	 *
	 * @var Pending_Subscribers_Table
	 */
	private Pending_Subscribers_Table $pending_subscribers_table;

	/**
	 * Subscribers constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param Subscribers_Table         $subscribers_table  The Subscribers table schema.
	 * @param Pending_Subscribers_Table $pending_subs_table The Pending Subscribers table schema.
	 */
	public function __construct( Subscribers_Table $subscribers_table, Pending_Subscribers_Table $pending_subs_table ) {
		$this->subscribers_table         = $subscribers_table;
		$this->pending_subscribers_table = $pending_subs_table;
	}

	/**
	 * Get the URL for the subscribers table for an event.
	 *
	 * @since 6.2.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return string The URL for the subscribers table.
	 */
	public function get_table_url_for_event( int $event_id ): string {
		return tribe( Admin_Page::class )->get_url( [ 'tec_tc_events' => $event_id ] );
	}

	/**
	 * Get a subscriber by ID.
	 *
	 * @since 6.2.0
	 *
	 * @param int $subscriber_id The subscriber ID.
	 *
	 * @return Subscriber|null The subscriber or null if not found.
	 */
	public function get( int $subscriber_id ): ?Subscriber {
		$subscriber = $this->subscribers_table::get_by_id( $subscriber_id );

		if ( empty( $subscriber ) ) {
			return null;
		}

		$meta = $subscriber['meta'] ?? null;
		if ( $meta && is_string( $meta ) ) {
			$meta = json_decode( $meta, true );
			if ( ! empty( $meta['waitlist_data']['waitlist_id'] ) ) {
				$subscriber['waitlist_id'] = $meta['waitlist_data']['waitlist_id'];
			}
		}

		return new Subscriber( $subscriber );
	}

	/**
	 * Create a subscriber for a waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param Waitlist $waitlist The waitlist object.
	 * @param int      $wp_user_id The WordPress user ID.
	 * @param string   $email The email address.
	 * @param string   $name The full name.
	 * @param int      $created The created timestamp.
	 *
	 * @return Subscriber The subscriber.
	 */
	public function create_subscriber_for_waitlist( Waitlist $waitlist, int $wp_user_id = 0, string $email = '', string $name = '', int $created = 0 ): Subscriber {
		/**
		 * Fires before creating a subscriber.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist   The waitlist object.
		 * @param int      $wp_user_id The WordPress user ID.
		 * @param string   $email      The email address.
		 * @param string   $name       The full name.
		 * @param int      $created    The created timestamp.
		 */
		do_action( 'tec_tickets_plus_waitlist_before_create_subscriber', $waitlist, $wp_user_id, $email, $name, $created );

		$subscriber = new Subscriber();
		$subscriber->set_waitlist_id( $waitlist->get_id() )
			->set_post_id( $waitlist->get_post_id() )
			->set_fullname( $name )
			->set_email( $email )
			->set_wp_user_id( $wp_user_id )
			->set_created( $created )
			->set_meta( [ 'waitlist_data' => $waitlist->to_array() ], false )
			->save();

		/**
		 * Fires after creating a subscriber.
		 *
		 * @since 6.2.0
		 *
		 * @param Subscriber $subscriber The subscriber.
		 * @param Waitlist   $waitlist   The waitlist object.
		 * @param int        $wp_user_id The WordPress user ID.
		 * @param string     $email      The email address.
		 * @param string     $name       The full name.
		 * @param int        $created    The created timestamp.
		 */
		do_action( 'tec_tickets_plus_waitlist_after_create_subscriber', $subscriber, $waitlist, $wp_user_id, $email, $name, $created );

		return $subscriber;
	}

	/**
	 * Get pending subscribers.
	 *
	 * @since 6.2.0
	 *
	 * @param Waitlist $waitlist The waitlist object.
	 * @param int      $limit    The limit of subscribers to get.
	 * @param int      $page     The page of subscribers to get.
	 *
	 * @return Subscriber[] The pending subscribers.
	 */
	public function get_pending_subscribers_for_waitlist( Waitlist $waitlist, int $limit = 50, int $page = 1 ): array {
		$results = $this->pending_subscribers_table::paginate(
			[
				[
					'column' => 'waitlist_id',
					'value'  => $waitlist->get_id(),
				],
			],
			$limit,
			$page,
			[ '*' ],
			Subscribers_Table::class,
			'waitlist_user_id=waitlist_user_id',
			[ 'post_id', 'meta', 'status' ],
			ARRAY_A
		);

		return array_map(
			fn( $result ) => new Subscriber( $result ),
			$results
		);
	}

	/**
	 * Get pending subscribers.
	 *
	 * @since 6.2.0
	 *
	 * @param Waitlist $waitlist The waitlist object.
	 *
	 * @return Subscriber[] The pending subscribers.
	 */
	public function delete_subscribers( Waitlist $waitlist ): void {
		/**
		 * Fires before deleting pending subscribers.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The waitlist object.
		 */
		do_action( 'tec_tickets_plus_waitlist_before_delete_subscribers', $waitlist );

		$sub_ids = DB::get_col(
			DB::prepare( 'SELECT ' . $this->pending_subscribers_table::uid_column() . ' FROM %i WHERE waitlist_id=%d', $this->pending_subscribers_table::table_name( true ), $waitlist->get_id() )
		);

		if ( empty( $sub_ids ) ) {
			/**
			 * Fires when there are no pending subscribers to delete.
			 *
			 * @since 6.2.0
			 *
			 * @param Waitlist $waitlist The waitlist object.
			 */
			do_action( 'tec_tickets_plus_waitlist_no_pending_subscribers_to_delete', $waitlist );
			return;
		}

		$sub_ids = implode( ',', array_filter( array_map( 'intval', $sub_ids ) ) );

		DB::beginTransaction();

		$result_1 = DB::query(
			DB::prepare(
				"DELETE FROM %i WHERE waitlist_user_id IN ({$sub_ids})",
				$this->pending_subscribers_table::table_name( true ),
			)
		);

		$result_2 = DB::query(
			DB::prepare(
				"DELETE FROM %i WHERE waitlist_user_id IN ({$sub_ids})",
				$this->subscribers_table::table_name( true ),
			)
		);

		if ( ! $result_1 || ! $result_2 ) {
			DB::rollback();
			/**
			 * Fires when deleting pending subscribers failed.
			 *
			 * @since 6.2.0
			 *
			 * @param Waitlist $waitlist The waitlist object.
			 */
			do_action( 'tec_tickets_plus_waitlist_failed_delete_subscribers', $waitlist );
			return;
		}

		DB::commit();

		/**
		 * Fires after deleting pending subscribers.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The waitlist object.
		 * @param int      $result   The number of deleted subscribers.
		 */
		do_action( 'tec_tickets_plus_waitlist_after_delete_subscribers', $waitlist, $result_1 );
	}

	/**
	 * Count pending subscribers for a waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param Waitlist $waitlist The waitlist object.
	 *
	 * @return int The total number of pending subscribers.
	 */
	public function count_pending_subscribers_for_waitlist( Waitlist $waitlist ): int {
		return $this->pending_subscribers_table::get_total_items(
			[
				[
					'column' => 'waitlist_id',
					'value'  => $waitlist->get_id(),
				],
			]
		);
	}

	/**
	 * Get if a WP user is already subscribed to a waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param Waitlist $waitlist The waitlist object.
	 * @param ?int     $user_id  The user ID.
	 * @param string   $email    The email address.
	 *
	 * @return bool Whether the user is already subscribed to the waitlist.
	 */
	public function user_already_subscribed_to_waitlist( Waitlist $waitlist, ?int $user_id = null, string $email = '' ): bool {
		$user = get_user_by( 'ID', $user_id ?? get_current_user_id() );
		if ( $user instanceof WP_User && $user->ID > 0 ) {
			$user_id = $user->ID;
		}

		if ( $user_id ) {
			return ! empty(
				DB::table( $this->pending_subscribers_table::table_name( false ) )
					->where( 'waitlist_id', $waitlist->get_id() )
					->where( 'wp_user_id', $user_id )
					->get( ARRAY_A )
			);
		}

		if ( ! ( $email && is_email( $email ) ) ) {
			return false;
		}

		return ! empty(
			DB::table( $this->pending_subscribers_table::table_name( false ) )
				->where( 'waitlist_id', $waitlist->get_id() )
				->where( 'email', $email )
				->get( ARRAY_A )
		);
	}

	/**
	 * Returns the URL for the full delete action of a subscriber
	 *
	 * @since 6.2.0
	 *
	 * @param int $subscriber_id The subscriber ID.
	 *
	 * @return string The URL for the full delete action.
	 */
	public function get_subscriber_full_delete_url( int $subscriber_id ): string {
		return admin_url(
			add_query_arg(
				[
					'action' => Subscriber::DELETE_ACTION,
					'id'     => $subscriber_id,
					'nonce'  => wp_create_nonce( Subscriber::DELETE_ACTION ),
				],
				'admin-post.php'
			)
		);
	}

	/**
	 * Handle the unsubscribe URL.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function handle_unsubscribe_url(): void {
		$subscriber_id = (int) tec_get_request_var_raw( 'id', 0 );

		// phpcs:disable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
		if ( ! $subscriber_id ) {
			wp_safe_redirect( home_url(), 302, 'ETP Waitlist Unsubscribe' );
			tribe_exit();
			return;
		}

		$hash = tec_get_request_var_raw( 'hash', '' );

		if ( ! $hash ) {
			wp_safe_redirect( home_url(), 302, 'ETP Waitlist Unsubscribe' );
			tribe_exit();
			return;
		}

		$subscriber = $this->get( $subscriber_id );

		if ( ! $subscriber ) {
			wp_safe_redirect( home_url(), 302, 'ETP Waitlist Unsubscribe' );
			tribe_exit();
			return;
		}

		$stored_hash = $subscriber->get_meta()['unsubscribe_hash'] ?? '';

		if ( $hash !== $stored_hash ) {
			wp_safe_redirect( home_url(), 302, 'ETP Waitlist Unsubscribe' );
			tribe_exit();
			return;
		}

		$post_id = $subscriber->get_post_id();

		$subscriber->delete();

		if ( get_post_status( $post_id ) !== 'publish' ) {
			wp_safe_redirect( home_url(), 302, 'ETP Waitlist Unsubscribe' );
			tribe_exit();
			return;
		}

		wp_safe_redirect( add_query_arg( [ 'unsubscribed' => 1 ], get_the_permalink( $post_id ) ), 302, 'ETP Waitlist Unsubscribe' );
		tribe_exit();
		// phpcs:enable WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit
	}
}
