<?php
/**
 * The Singleton to interact with Waitlists.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Tickets_Plus\Waitlist\Tables\Waitlists as Waitlists_Table;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Generator;
use Exception;
use TEC\Tickets\Ticket_Data;

/**
 * Class Waitlists.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Waitlists {
	/**
	 * The table interface.
	 *
	 * @since 6.2.0
	 *
	 * @var Waitlists_Table
	 */
	private Waitlists_Table $table;

	/**
	 * The subscribers instance.
	 *
	 * @since 6.2.0
	 *
	 * @var Subscribers
	 */
	private Subscribers $subscribers;

	/**
	 * The ticket data instance.
	 *
	 * @since 6.5.1
	 *
	 * @var Ticket_Data
	 */
	private Ticket_Data $ticket_data;

	/**
	 * Waitlists constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param Waitlists_Table $table The table schema.
	 * @param Subscribers     $subscribers The subscribers instance.
	 */
	public function __construct( Waitlists_Table $table, Subscribers $subscribers, Ticket_Data $ticket_data ) {
		$this->table       = $table;
		$this->subscribers = $subscribers;
		$this->ticket_data = $ticket_data;
	}

	/**
	 * Get the ticket waitlist for a post.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Waitlist|null The waitlist object or null if not found.
	 */
	public function get_posts_ticket_waitlist( int $post_id ): ?Waitlist {
		return $this->get_posts_waitlist( $post_id, Waitlist::TICKET_TYPE );
	}

	/**
	 * Get the RSVP waitlist for a post.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Waitlist|null The waitlist object or null if not found.
	 */
	public function get_posts_rsvp_waitlist( int $post_id ): ?Waitlist {
		return $this->get_posts_waitlist( $post_id, Waitlist::RSVP_TYPE );
	}

	/**
	 * Get the tickets for a post.
	 *
	 * @since 6.2.0
	 *
	 * @deprecated 6.5.1
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Generator<Ticket_Object> The ticket.
	 */
	public function get_posts_tickets( int $post_id ): Generator {
		_deprecated_function( __METHOD__, '6.5.1', 'Ticket_Data::get_posts_tickets' );
		return $this->ticket_data->get_posts_tickets( $post_id );
	}

	/**
	 * Get the RSVP for a post.
	 *
	 * @since 6.2.0
	 *
	 * @deprecated 6.5.1
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Ticket_Object|null The ticket object or null if not found.
	 */
	public function get_posts_rsvp( int $post_id ): ?Ticket_Object {
		_deprecated_function( __METHOD__, '6.5.1', 'Ticket_Data::get_posts_rsvp' );
		return $this->ticket_data->get_posts_rsvp( $post_id );
	}

	/**
	 * Get the ticket data for a post.
	 *
	 * @since 6.2.0
	 *
	 * @deprecated 6.5.1
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array The ticket data.
	 */
	public function get_posts_tickets_data( int $post_id ): array {
		_deprecated_function( __METHOD__, '6.5.1', 'Ticket_Data::get_posts_tickets_data' );
		return $this->ticket_data->get_posts_tickets_data( $post_id );
	}

	/**
	 * Get the RSVP data for a post.
	 *
	 * @since 6.2.0
	 *
	 * @deprecated 6.5.1
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array The RSVP data.
	 */
	public function get_posts_rsvps_data( int $post_id ): array {
		_deprecated_function( __METHOD__, '6.5.1', 'Ticket_Data::get_posts_rsvps_data' );
		return $this->ticket_data->get_posts_rsvps_data( $post_id );
	}

	/**
	 * Upsert a waitlist for a post.
	 *
	 * @since 6.2.0
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data    The data to upsert.
	 * @param int   $type    The type of waitlist. 0 for tickets, 1 for RSVP.
	 *
	 * @return bool True if the upsert was successful, false otherwise.
	 */
	public function upsert_waitlist_for_post( int $post_id, array $data, int $type = Waitlist::TICKET_TYPE ): bool {
		$data = [
			'post_id'     => $post_id,
			'enabled'     => $data['enabled'] ?? '',
			'conditional' => $data['conditional'] ?? '',
			'type'        => $type,
		];

		$data = array_filter(
			$data,
			static fn ( $value ) => '' !== $value // we do want to keep 0 or false.
		);

		$waitlist = new Waitlist( $data );

		if ( ! isset( $data['enabled'] ) ) {
			// This is an update then.
			$waitlist->update_by_post_id( true );

			return true;
		}

		/**
		 * 2 cases now:
		 *
		 * 1 - either the waitlist is enabled and we definitely need to upsert
		 *
		 * 2 - or the waitlist is disabled - in that case we only want to UPDATE if there is an existing waitlist
		 * We want to avoid filling the database with disabled waitlists just because the user toggled the enable/disable switch.
		 */

		if ( ! $data['enabled'] ) {
			// In this case we want to fail silently! If we don't find a waitlist, we don't want to create one just to mark it disabled.
			$waitlist->update_by_post_id( true, true );

			return true;
		}

		// The waitlist is enabled and we need to upsert. So an additional read operation is required :cry:.
		$existing_waitlist = $this->get_posts_waitlist( $post_id, $type );
		if ( ! $existing_waitlist ) {
			$waitlist->enable()->set_conditional( $data['conditional'] ?? Waitlist::ALWAYS_CONDITIONAL )->save( true );

			return (bool) $waitlist->get_id();
		}

		$existing_waitlist->enable()->set_conditional( $data['conditional'] ?? Waitlist::ALWAYS_CONDITIONAL )->save();

		return (bool) $existing_waitlist->get_id();
	}

	/**
	 * Get a waitlist by its id.
	 *
	 * @since 6.2.0
	 *
	 * @param int $waitlist_id The waitlist ID.
	 *
	 * @return Waitlist|null The waitlist object or null if not found.
	 */
	public function get( int $waitlist_id ): ?Waitlist {
		try {
			$waitlist = $this->table::get_by_id( $waitlist_id );
		} catch ( DatabaseQueryException $e ) {
			return null;
		}

		if ( ! $waitlist ) {
			return null;
		}

		return new Waitlist( $waitlist );
	}

	/**
	 * Get the waitlist for a post.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $type    The type of waitlist. 0 for tickets, 1 for RSVP.
	 *
	 * @return Waitlist|null The waitlist object or null if not found.
	 */
	protected function get_posts_waitlist( int $post_id, int $type = Waitlist::TICKET_TYPE ): ?Waitlist {
		try {
			$waitlist = DB::table( $this->table::table_name( false ) )
				->where( 'post_id', $post_id )
				->where( 'type', $type )
				->get( ARRAY_A );
		} catch ( DatabaseQueryException $e ) {
			return null;
		}

		if ( ! $waitlist ) {
			return null;
		}

		return new Waitlist( $waitlist );
	}

	/**
	 * Process the Waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param int $waitlist_id The Waitlist ID.
	 *
	 * @return void
	 * @throws Exception If errors occur during processing and on AS context.
	 */
	public function process_waitlist( int $waitlist_id ): void {
		$waitlist = $this->get( $waitlist_id );

		if ( ! $waitlist instanceof Waitlist ) {
			return;
		}

		if ( ! $waitlist->is_enabled() ) {
			return;
		}

		if ( $waitlist->has_ended() ) {
			return;
		}

		if ( ! $waitlist->has_tickets() ) {
			return;
		}

		if ( ! $waitlist->has_tickets_on_sale() && ! $waitlist->has_tickets_about_to_go_on_sale() ) {
			return;
		}

		/**
		 * Filter the batch size for processing subscribers.
		 *
		 * @since 6.2.0
		 *
		 * @param int      $batch_size The batch size for processing subscribers.
		 * @param Waitlist $waitlist   The Waitlist being processed.
		 *
		 * @return int
		 */
		$batch_size = (int) apply_filters( 'tec_tickets_plus_waitlist_process_subscribers_batch_size', 100, $waitlist );

		if ( $batch_size < 1 ) {
			// We have been forced to not process any subscribers... we bail.
			return;
		}

		as_unschedule_action( Triggers::PROCESS_WAITLIST_ACTION, [ $waitlist_id ], Triggers::PROCESS_WAITLISTS_GROUP );

		$subscribers = $this->subscribers->get_pending_subscribers_for_waitlist( $waitlist, $batch_size );

		if ( empty( $subscribers ) ) {
			/**
			 * Action that fires when there are no subscribers to process.
			 *
			 * @since 6.2.0
			 *
			 * @param Waitlist $waitlist The Waitlist with no subscribers to process.
			 */
			do_action( 'tec_tickets_plus_waitlist_no_subscribers_to_process', $waitlist );
			return;
		}

		if ( count( $subscribers ) === $batch_size ) {
			/**
			 * Instead of figuring out for sure if there is a next batch, let the next batch decide that.
			 *
			 * Now on the amount of subscribers we can handle. Lets do the math!
			 *
			 * We start ~20 minutes before a ticket goes live.
			 *
			 * We process 100 subscribers every ~10 seconds => ~600 subscribers per minute.
			 *
			 * So we will notify ~12000 subscribers before the ticket goes live. I think that is a good number for launch.
			 */

			/**
			 * Filter the interval between batches of subscribers.
			 *
			 * Negative values will be converted to 0.
			 *
			 * @since 6.2.0
			 *
			 * @param int      $interval The interval between batches of subscribers.
			 * @param Waitlist $waitlist The Waitlist the subscribers are part of.
			 *
			 * @return int The interval between batches of subscribers.
			 */
			$interval = max( 0, (int) apply_filters( 'tec_tickets_plus_waitlist_interval_between_batches', ( MINUTE_IN_SECONDS / 6 ), $waitlist ) );

			as_schedule_single_action( time() + $interval, Triggers::PROCESS_WAITLIST_ACTION, [ $waitlist_id ], Triggers::PROCESS_WAITLISTS_GROUP );
		}

		$errors = [];

		foreach ( $subscribers as $subscriber ) {
			try {
				/**
				 * Action that fires when a subscriber is being processed.
				 *
				 * @since 6.2.0
				 *
				 * @param Subscriber $subscriber The subscriber being processed.
				 * @param Waitlist   $waitlist   The Waitlist the subscriber is part of.
				 */
				do_action( 'tec_tickets_plus_waitlist_subscriber_being_processed', $subscriber, $waitlist );
			} catch ( Exception $e ) {
				$errors[ $subscriber->get_id() ] = [
					'subscriber_id' => $subscriber->get_id(),
					'waitlist_id'   => $waitlist->get_id(),
					'error'         => $e->getMessage(),
				];
				continue;
			}
		}

		/**
		 * Filter the errors that occurred during processing.
		 *
		 * @since 6.2.0
		 *
		 * @param array        $errors      The errors that occurred during processing.
		 * @param Subscriber[] $subscribers The subscribers being processed.
		 * @param Waitlist     $waitlist    The Waitlist the subscribers are part of.
		 *
		 * @return array The errors that occurred during processing.
		 */
		$errors = (array) apply_filters( 'tec_tickets_plus_waitlist_subscribers_batch_errors', $errors, $subscribers, $waitlist );

		/**
		 * Action that fires after batch of subscribers has been processed.
		 *
		 * @since 6.2.0
		 *
		 * @param Subscriber[] $subscribers The processed subscribers.
		 * @param Waitlist     $waitlist    The Waitlist the subscribers are part of.
		 * @param array        $errors      The errors that occurred during processing.
		 */
		do_action( 'tec_tickets_plus_waitlist_subscribers_batch_processed', $subscribers, $waitlist, $errors );

		if ( empty( $error ) ) {
			return;
		}

		do_action( 'tribe_log', 'error', 'Errors occurred while processing subscribers', $errors );

		if ( ! did_action( 'action_scheduler_before_process_queue' ) ) {
			return;
		}

		/**
		 * We are in AS context and AS expects to catch exceptions to mark an action as failed using the exception's message as the reason.
		 */
		throw new Exception(
			sprintf(
				// translators: %1$d is the total number of subscribers, %2$d is the number of errors.
				esc_html__( 'Errors occurred while processing subscribers. Out of the %1$d subscribers, %2$d failed to be processed. Check your log file for more details.', 'event-tickets-plus' ),
				count( $subscribers ),
				count( $errors ),
			)
		);
	}

	/**
	 * Add the about to seconds hook.
	 *
	 * @since 6.5.1
	 *
	 * @return void
	 */
	public function add_about_to_seconds_hook(): void {
		add_action( 'tec_tickets_ticket_about_to_go_to_sale_seconds', [ $this, 'ticket_about_to_go_to_sale_seconds' ], 10, 2 );
	}

	/**
	 * Remove the about to seconds hook.
	 *
	 * @since 6.5.1
	 *
	 * @return void
	 */
	public function remove_about_to_seconds_hook(): void {
		remove_action( 'tec_tickets_ticket_about_to_go_to_sale_seconds', [ $this, 'ticket_about_to_go_to_sale_seconds' ], 10 );
	}

	/**
	 * Ticket about to go to sale seconds.
	 *
	 * @since 6.5.1
	 *
	 * @param int $seconds   The seconds before a ticket goes on sale that we consider it about to go on sale.
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return int The seconds before a ticket goes on sale that we consider it about to go on sale.
	 */
	public function ticket_about_to_go_to_sale_seconds( int $seconds, int $ticket_id ): int {
		/**
		 * Filter the seconds before a ticket goes on sale that we consider it about to go on sale.
		 *
		 * @since 6.5.1
		 *
		 * @param int $seconds The seconds before a ticket goes on sale that we consider it about to go on sale.
		 * @param int $ticket_id The ticket ID.
		 *
		 * @return int The seconds before a ticket goes on sale that we consider it about to go on sale.
		 */
		return (int) apply_filters( 'tec_tickets_plus_waitlist_ticket_about_to_go_to_sale_seconds', Waitlist::ABOUT_TO_MINUTES * MINUTE_IN_SECONDS, $ticket_id );
	}
}
