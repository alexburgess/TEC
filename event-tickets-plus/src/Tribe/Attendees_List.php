<?php

use Tribe\Tickets\Events\Attendees_List;

/**
 * Class Tribe__Tickets_Plus__Attendees_List.
 */
class Tribe__Tickets_Plus__Attendees_List extends Attendees_List {

	/**
	 * Get (and instantiate, if necessary) the instance of the class.
	 *
	 * @static
	 *
	 * @return self The instance of the class.
	 */
	public static function instance() {
		static $instance;

		if ( ! $instance instanceof self ) {
			$instance = new self;
		}

		return $instance;
	}

	/**
	 * Hook the necessary filters and Actions!
	 *
	 * @return void
	 */
	public static function hook() {
		$myself = self::instance();

		// This will include before the RSVP.
		add_action( 'tribe_tickets_before_front_end_ticket_form', [ $myself, 'render' ], 4, 2 );

		// Unhook Event Ticket's "View your RSVP's" rendering logic so that we can re-render with ET+'s "Who's attending?" list.
		add_action( 'init', [ $myself, 'unhook_event_tickets_order_link_logic' ] );

		// Add the Admin Option for removing the Attendees List.
		add_action( 'tribe_events_tickets_metabox_pre', [ $myself, 'render_admin_options' ] );

		// Create the ShortCode.
		add_shortcode( 'tribe_attendees_list', [ $myself, 'shortcode' ] );

		// Hook into Event Ticket's to hide the attendee list optout.
		add_filter( 'tec_tickets_hide_attendee_list_optout', [ $myself, 'modify_hide_attendee_list_optout' ], 10, 2 );
	}

	/**
	 * Modifies the value of tec_tickets_hide_attendee_list_optout filter found within Event Tickets.
	 *
	 * @since 5.7.3
	 *
	 * @param bool $hide_attendee_list_optout The original value of hide_attendee_list_optout.
	 * @param int  $post_id                   The post ID.
	 *
	 * @return bool                           The modified value of hide_attendee_list_optout.
	 */
	public function modify_hide_attendee_list_optout( $hide_attendee_list_optout, $post_id ) {
		return self::is_hidden_on( $post_id );
	}

	/**
	 * Determine if we need to hide the attendees list.
	 *
	 * @param int|WP_Post $post   The post object or ID.
	 * @param boolean     $strict Whether to strictly check the meta value.
	 *
	 * @return bool Whether the attendees list is hidden.
	 */
	public static function is_hidden_on( $post, $strict = true ) {
		/**
		 * Use this to filter and hide the Attendees List for a specific post or all of them.
		 *
		 * @param bool    $is_hidden Whether the attendees list is hidden.
		 * @param WP_Post $post      The post object.
		 */
		return apply_filters( 'tribe_tickets_plus_hide_attendees_list', parent::is_hidden_on( $post ), $post );
	}

	/**
	 * Renders the Administration option to hide Attendees List.
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function render_admin_options( $post_id = null ) {
		$is_attendees_list_hidden = self::is_hidden_on( $post_id );

		include_once Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/attendees-list.php';
	}

	/**
	 * Wrapper to create the Shortcode with the Attendees List.
	 *
	 * @todo Move to using Common-extended \Tribe\Tickets\Plus\Service_Providers\Shortcode.
	 *
	 * @param array $atts The shortcode attributes.
	 *
	 * @return string The HTML of the attendees list.
	 */
	public function shortcode( $atts ) {
		$atts = (object) shortcode_atts( array(
			'event' => null,
			'limit' => 20,
		), $atts, 'tribe_attendees_list' );

		ob_start();
		$this->render( $atts->event, $atts->limit );
		$html = ob_get_clean();

		return $html;
	}

	/**
	 * Remove the Post Transients when a EDD Ticket is bought.
	 *
	 * @param int $unused_attendee_id The (unused) attendee ID.
	 * @param int $order_id    The order ID.
	 *
	 * @return void
	 */
	public function edd_purge_transient( $unused_attendee_id, $order_id ) {
		$event_id = Tribe__Tickets_Plus__Commerce__EDD__Main::get_instance()->get_event_id_from_order_id( $order_id );
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
	}

	/**
	 * Remove the Post Transients for the Tickets Attendees during attendee generation.
	 *
	 * @param int $unused_attendee_id The (unused) attendee ID.
	 * @param int $event_id           The event ID.
	 * @param int $unused_product_id  The (unused) product ID.
	 *
	 * @return void
	 */
	public function purge_transient( $unused_attendee_id, $event_id, $unused_product_id ) {
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
	}

	/**
	 * Remove the Post Transients for the Tickets Attendees during late ticket generation.
	 *
	 * @since 4.10.1.1
	 *
	 * @param int $unused_product_id  The (unused) product ID.
	 * @param int $unused_order_id    The (unused) order ID.
	 * @param int $unused_quantity    The (unused) quantity.
	 * @param int $event_id           The event ID.
	 *
	 * @return void
	 */
	public function purge_transient_post_ticket( $unused_product_id, $unused_order_id, $unused_quantity, $event_id ) {
		Tribe__Post_Transient::instance()->delete( $event_id, Tribe__Tickets__Tickets::ATTENDEES_CACHE );
	}

	/**
	 * Unhook Event Ticket's "View your RSVPs" rendering logic.
	 *
	 * Better enables re-rendering of that link
	 * with ET+'s "Who's attending?" list across all tickets-enabled post types.
	 *
	 * @since 4.5.4
	 */
	public function unhook_event_tickets_order_link_logic() {
		$tickets_view = Tribe__Tickets__Tickets_View::instance();

		remove_action( 'tribe_events_single_event_after_the_meta', [ $tickets_view, 'inject_link_template' ], 4 );
		remove_filter( 'the_content', [ $tickets_view, 'inject_link_template_the_content' ], 9 );
	}

	/**
	 * Includes the Attendees List HTML.
	 *
	 * @since 5.10.1 Updated docblock.
	 *
	 * @param int|WP_Post $event The event or event ID.
	 * @param int         $limit Limit to how many to show.
	 *
	 * @return void
	 */
	public function render( $event = null, $limit = 20 ) {
		$event = get_post( $event );
		if ( ! $event instanceof WP_Post ) {
			$event = get_post();
		}

		/** @var Tribe__Tickets__Attendees $attendees */
		$attendees = tribe( 'tickets.attendees' );

		// Prevent injecting into content if hidden or using blocks.
		if (
			'tribe_tickets_before_front_end_ticket_form' === current_filter()
			&& (
				self::is_hidden_on( $event )
				|| $this->is_showing_attendee_list_with_blocks( $event )
			)
		) {
			return;
		}

		$attendees_list = $this->get_attendees( $event->ID, $limit );

		if ( ! $attendees_list ) {
			return;
		}

		$attendees_total = $this->get_attendance_counts( $event->ID );

		if ( empty( $attendees_total ) ) {
			return;
		}

		include_once Tribe__Tickets_Plus__Main::instance()->get_template_hierarchy( 'attendees-list' );

		$tickets_view = Tribe__Tickets__Tickets_View::instance();

		$tickets_view->inject_link_template();
	}

	/**
	 * Returns an Array ready for printing of the Attendees List.
	 *
	 * @since 5.10.1 Added additional logic to catch when $limit is not an integer.
	 *
	 * @param WP_Post|int $post_id Post object or ID.
	 * @param int         $limit   Limit of attendees to be retrieved.
	 *
	 * @return array The attendees list.
	 */
	public function get_attendees( $post_id, $limit = 20 ) {
		$default_limit = 20;
		/**
		 * Allow for adjusting the limit of attendees fetched from the database for the front-end "Who's Attending?" list.
		 *
		 * @since 4.10.5
		 *
		 * @param int $limit_attendees Number of attendees to retrieve. Default (-1) is no limit.
		 */
		$limit_attendees = (int) apply_filters( 'tribe_tickets_plus_attendees_list_limit_attendees', - 1 );

		$attendees_to_display = $this->get_attendees_for_post( $post_id, $limit_attendees );

		if ( empty( $attendees_to_display ) ) {
			return [];
		}

		$output = [];

		/**
		 * Allow for adjusting the limit of attendees retrieved for the front-end "Who's Attending?" list.
		 *
		 * @since 4.5.5
		 *
		 * @param int $limit Number of attendees to retrieve.
		 */
		$filtered_limit = apply_filters( 'tribe_tickets_plus_attendees_list_limit', $limit );

		// Ensure that $filtered_limit is numeric and a non-negative integer; otherwise, use a default limit.
		if ( is_numeric( $filtered_limit ) && $filtered_limit > 0 ) {
			$limit = (int) $filtered_limit;
		} else {
			$limit = $default_limit;
		}

		$has_broken = false;

		foreach ( $attendees_to_display as $key => $attendee ) {
			if ( ! $has_broken && is_numeric( $limit ) && $limit < $key + 1 ) {
				$has_broken = true;
			}

			$class = $has_broken ? 'hidden' : 'shown';

			$output[ $attendee['attendee_id'] ] = sprintf(
				'<span class="tribe-attendees-list-%1$s">%2$s</span>',
				$class,
				get_avatar( $attendee['purchaser_email'], 40, '', $attendee['purchaser_name'] )
			);
		}

		if ( $has_broken ) {
			$output['show-more'] = sprintf(
				'<a href="#show-all-attendees"
					data-offset="%1$s"
					title="%2$s"
					class="tribe-attendees-list-showall avatar">
					%3$s
				</a>',
				esc_attr( $limit ),
				esc_attr__( 'Load all attendees', 'event-tickets-plus' ),
				get_avatar( '', 40, '', esc_attr__( 'Load all attendees', 'event-tickets-plus' ) )
			);
		}

		return $output;
	}

}
