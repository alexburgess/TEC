<?php
/**
 * The Waitlist Triggers Controller.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use Tribe__Tickets__Tickets as Tickets;
use TEC\Tickets\Ticket_Data;
use WP_Post;
use InvalidArgumentException;

/**
 * Class Triggers.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Triggers extends Controller_Contract {
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_plus_waitlist_triggers_registered';

	/**
	 * The action that will be fired when a Waitlist should be processed.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const PROCESS_WAITLIST_ACTION = 'tec_tickets_plus_waitlist_process_waitlist';

	/**
	 * The group for the Waitlist processing actions.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const PROCESS_WAITLISTS_GROUP = 'tec_tickets_plus_waitlist_process_waitlists';

	/**
	 * The action that handles an unsubscribe.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const UNSUBSCRIBE_WAITLIST_ACTION = 'tec_tickets_plus_waitlist_unsubscribe';

	/**
	 * The Waitlists instance.
	 *
	 * @since 6.2.0
	 *
	 * @var Waitlists
	 */
	private $waitlists;

	/**
	 * The Subscribers instance.
	 *
	 * @since 6.2.0
	 *
	 * @var Subscribers
	 */
	private $subscribers;

	/**
	 * Triggers constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param Container   $container   The DI container.
	 * @param Waitlists   $waitlists   The Waitlists instance.
	 * @param Subscribers $subscribers The Subscribers instance.
	 */
	public function __construct( Container $container, Waitlists $waitlists, Subscribers $subscribers ) {
		parent::__construct( $container );
		$this->waitlists   = $waitlists;
		$this->subscribers = $subscribers;
	}

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'tickets_start_date_is_being_reached' ], 10, 4 );
		add_action( 'tec_tickets_ticket_stock_changed', [ $this, 'tickets_stock_changed' ], 10, 3 );
		add_action( 'tec_tickets_ticket_stock_added', [ $this, 'tickets_stock_added' ], 10, 2 );
		add_action( 'tec_tickets_plus_waitlist_subscriber_being_processed', [ $this, 'notify_subscriber' ] );
		add_action( 'tec_tickets_plus_waitlist_after_delete', [ $this, 'delete_subscribers' ] );
		add_action( 'before_delete_post', [ $this, 'delete_waitlists' ] );
		add_action( self::PROCESS_WAITLIST_ACTION, [ $this->waitlists, 'process_waitlist' ] );
		add_action( 'admin_post_nopriv_' . self::UNSUBSCRIBE_WAITLIST_ACTION, [ $this->subscribers, 'handle_unsubscribe_url' ] );
		add_action( 'admin_post_' . self::UNSUBSCRIBE_WAITLIST_ACTION, [ $this->subscribers, 'handle_unsubscribe_url' ] );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_ticket_start_date_trigger', [ $this, 'tickets_start_date_is_being_reached' ] );
		remove_action( 'tec_tickets_ticket_stock_changed', [ $this, 'tickets_stock_changed' ] );
		remove_action( 'tec_tickets_ticket_stock_added', [ $this, 'tickets_stock_added' ] );
		remove_action( 'tec_tickets_plus_waitlist_subscriber_being_processed', [ $this, 'notify_subscriber' ] );
		remove_action( 'tec_tickets_plus_waitlist_after_delete', [ $this, 'delete_subscribers' ] );
		remove_action( 'before_delete_post', [ $this, 'delete_waitlists' ] );
		remove_action( self::PROCESS_WAITLIST_ACTION, [ $this->waitlists, 'process_waitlist' ] );
		remove_action( 'admin_post_nopriv_' . self::UNSUBSCRIBE_WAITLIST_ACTION, [ $this->subscribers, 'handle_unsubscribe_url' ] );
		remove_action( 'admin_post_' . self::UNSUBSCRIBE_WAITLIST_ACTION, [ $this->subscribers, 'handle_unsubscribe_url' ] );
	}

	/**
	 * Delete waitlists on post deletion.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function delete_waitlists( int $post_id ): void {
		$ticket_waitlist = $this->waitlists->get_posts_ticket_waitlist( $post_id );
		$rsvp_waitlist   = $this->waitlists->get_posts_rsvp_waitlist( $post_id );
		if ( ! $ticket_waitlist && ! $rsvp_waitlist ) {
			return;
		}

		if ( $ticket_waitlist ) {
			$ticket_waitlist->delete();
		}

		if ( $rsvp_waitlist ) {
			$rsvp_waitlist->delete();
		}
	}

	/**
	 * Delete pending subscribers on waitlist deletion.
	 *
	 * @since 6.2.0
	 *
	 * @param Waitlist $waitlist The waitlist.
	 *
	 * @return void
	 */
	public function delete_subscribers( Waitlist $waitlist ): void {
		as_unschedule_action( self::PROCESS_WAITLIST_ACTION, [ $waitlist->get_id() ], self::PROCESS_WAITLISTS_GROUP );
		$this->subscribers->delete_subscribers( $waitlist );
	}

	/**
	 * Notify subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param Subscriber $subscriber The subscriber.
	 *
	 * @return void
	 */
	public function notify_subscriber( Subscriber $subscriber ): void {
		$subscriber->notify();
	}

	/**
	 * Ticket start date listener for scheduling waitlist process event.
	 *
	 * @since 6.2.0
	 *
	 * @param int     $ticket_id       The ticket ID.
	 * @param bool    $its_happening   Whether the event is happening or not.
	 * @param int     $start_timestamp The start timestamp of the event.
	 * @param WP_Post $event           The event post object.
	 *
	 * @return void
	 * @throws InvalidArgumentException If the conditional for the Waitlist is invalid.
	 */
	public function tickets_start_date_is_being_reached( int $ticket_id, bool $its_happening, int $start_timestamp, WP_Post $event ): void {
		// Alter the conditions that $its_happening should be true for Waitlist processing.
		$its_happening = $its_happening || ( time() + Ticket_Data::get_ticket_about_to_go_to_sale_seconds( $ticket_id ) >= $start_timestamp );

		if ( ! $its_happening ) {
			return;
		}

		if ( 1 > $event->ID ) {
			return;
		}

		/**
		 * We are not loading a ticket object here on purpose.
		 *
		 * This is happening along with a user/admin request. We want it to be as light as possible.
		 *
		 * Since we can do our basic checks without loading the ticket object, we are going to do so.
		 *
		 * All of the date below except the query for the Waitlist are already in memory.
		 */

		$ptype = get_post_type( $ticket_id );

		if ( ! in_array( $ptype, tribe_tickets()->ticket_types(), true ) ) {
			return;
		}

		$waitlist = 'tribe_rsvp_tickets' === $ptype ? $this->waitlists->get_posts_rsvp_waitlist( $event->ID ) : $this->waitlists->get_posts_ticket_waitlist( $event->ID );

		if ( ! ( $waitlist && $waitlist instanceof Waitlist ) ) {
			return;
		}

		if ( ! $waitlist->is_enabled() ) {
			return;
		}

		switch ( $waitlist->get_conditional() ) {
			case Waitlist::ALWAYS_CONDITIONAL:
			case Waitlist::BEFORE_SALE_CONDITIONAL:
				// Schedule the Waitlist processing.
				break;
			case Waitlist::ON_SOLD_OUT_CONDITIONAL:
				// Bail.
				return;
			default:
				throw new InvalidArgumentException( 'Invalid conditional for Waitlist' );
		}

		// Unschedule prior scheduling to avoid duplicates which could cause raise conditions in installations with multiple AS workers.
		as_unschedule_action( self::PROCESS_WAITLIST_ACTION, [ $waitlist->get_id() ], self::PROCESS_WAITLISTS_GROUP );
		as_schedule_single_action( time(), self::PROCESS_WAITLIST_ACTION, [ $waitlist->get_id() ], self::PROCESS_WAITLISTS_GROUP );
	}

	/**
	 * Ticket stock changed listener for scheduling waitlist process event.
	 *
	 * @since 6.2.0
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $new_stock The new stock.
	 * @param int $old_stock The old stock.
	 *
	 * @return void
	 */
	public function tickets_stock_changed( int $ticket_id, int $new_stock, int $old_stock ): void {
		if ( ! $new_stock ) {
			// The ticket didn't become available... we can bail.
			return;
		}

		if ( 0 < $old_stock ) {
			// The ticket didn't become available just now. It was available, we can bail.
			return;
		}

		$this->ticket_stock_updated( $ticket_id );
	}

	/**
	 * Ticket stock added listener for scheduling waitlist process event.
	 *
	 * @since 6.2.0
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $new_stock The added stock.
	 *
	 * @return void
	 */
	public function tickets_stock_added( int $ticket_id, int $new_stock ): void {
		if ( ! $new_stock ) {
			// The ticket didn't become available... we can bail.
			return;
		}

		$this->ticket_stock_updated( $ticket_id );
	}

	/**
	 * Ticket stock updated listener for scheduling waitlist process event.
	 *
	 * @since 6.2.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 * @throws InvalidArgumentException If the conditional for the Waitlist is invalid.
	 */
	protected function ticket_stock_updated( int $ticket_id ): void {
		$ptype = get_post_type( $ticket_id );

		if ( ! in_array( $ptype, tribe_tickets()->ticket_types(), true ) ) {
			return;
		}

		$cache     = tribe_cache();
		$cache_key = __METHOD__ . $ticket_id;

		$event = $cache[ $cache_key ] ?? null;

		if ( false === $event ) {
			return;
		}

		if ( ! $event instanceof WP_Post || $event->ID < 1 ) {
			foreach ( Tickets::modules() as $provider_class => $name ) {
				$provider = Tickets::get_ticket_provider_instance( $provider_class );

				if ( empty( $provider ) ) {
					continue;
				}

				$event = $provider->get_event_for_ticket( $ticket_id );

				if ( ! $event instanceof WP_Post || $event->ID < 1 ) {
					continue;
				}

				break;
			}

			if ( ! $event ) {
				$event = false;
			}
		}

		$cache[ $cache_key ] = $event;

		if ( ! $event ) {
			return;
		}

		$waitlist = 'tribe_rsvp_tickets' === $ptype ? $this->waitlists->get_posts_rsvp_waitlist( $event->ID ) : $this->waitlists->get_posts_ticket_waitlist( $event->ID );

		if ( ! ( $waitlist && $waitlist instanceof Waitlist ) ) {
			return;
		}

		if ( ! $waitlist->is_enabled() ) {
			return;
		}

		switch ( $waitlist->get_conditional() ) {
			case Waitlist::ALWAYS_CONDITIONAL:
			case Waitlist::ON_SOLD_OUT_CONDITIONAL:
				// Schedule the Waitlist processing.
				break;
			case Waitlist::BEFORE_SALE_CONDITIONAL:
				// Bail.
				return;
			default:
				throw new InvalidArgumentException( 'Invalid conditional for Waitlist' );
		}

		// Unschedule prior scheduling to avoid duplicates which could cause raise conditions in installations with multiple AS workers.
		as_unschedule_action( self::PROCESS_WAITLIST_ACTION, [ $waitlist->get_id() ], self::PROCESS_WAITLISTS_GROUP );
		as_schedule_single_action( time(), self::PROCESS_WAITLIST_ACTION, [ $waitlist->get_id() ], self::PROCESS_WAITLISTS_GROUP );
	}
}
