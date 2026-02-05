<?php
/**
 * Handles the integration between Flexible Tickets and WooCommerce.
 *
 * @since 5.9.0
 *
 * @package TEC\Tickets_Plus\Flexible_Tickets;
 */

namespace TEC\Tickets_Plus\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events\Custom_Tables\V1\Models\Occurrence;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes;
use Tribe__Date_Utils as Dates;
use WP_Post;

/**
 * Class WooCommerce.
 *
 * @since 5.9.0
 *
 * @package TEC\Tickets_Plus\Flexible_Tickets;
 */
class WooCommerce extends Controller {

	/**
	 * Registers the bindings, service providers and controllers part of the feature.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_plus_woocommerce_order_event_details', [ $this, 'print_series_pass_details' ], 10, 3 );
	}

	/**
	 * Unregisters the bindings, service providers and controllers part of the feature.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_plus_woocommerce_order_event_details', [ $this, 'print_series_pass_details' ] );
	}

	/**
	 * Prints the series pass details in the WooCommerce order.
	 *
	 * @since 5.9.0
	 *
	 * @param array<string> $details   The details to print.
	 * @param WP_Post       $post      The post object the Ticket is attached to.
	 * @param int           $ticket_id The ID of the ticket.
	 *
	 * @return array<string> The details to print.
	 */
	public function print_series_pass_details( array $details, $post, int $ticket_id ): array {
		if ( get_post_meta( $ticket_id, '_type', true ) !== Series_Passes::TICKET_TYPE ) {
			// Not a Series Pass.
			return $details;
		}

		if ( get_post_type( $post ) !== Series_Post_Type::POSTTYPE ) {
			// Not a Series post.
			return $details;
		}

		$details[] = sprintf(
			'<a href="%1$s" class="event-title">%2$s</a>',
			esc_attr( get_permalink( $post ) ),
			esc_html( get_the_title( $post ) )
		);

		$event_post_ids = Series_Relationship::where( 'series_post_id', '=', $post->ID )
		                                     ->pluck( 'event_post_id' );
		$first          = Occurrence::where_in( 'post_id', $event_post_ids )
		                            ->order_by( 'start_date', 'ASC' )->first();
		$last           = Occurrence::where_in( 'post_id', $event_post_ids )
		                            ->order_by( 'start_date', 'DESC' )->first();

		if ( ! ( $first instanceof Occurrence && $last instanceof Occurrence ) ) {
			// Do not print a date range if there are not first and last; fine to print about the same Event.
			return $details;
		}

		$format              = '<em><span class="tribe-event-date-start">%s</span>%s<span class="tribe-event-date-end">%s</span></em>';
		$first_start         = Dates::immutable( $first->start_date );
		$last_end            = Dates::immutable( $last->end_date );
		$start_end_same_year = $first_start->format( 'Y' ) === $last_end->format( 'Y' );
		$date_format         = $start_end_same_year ?
			tribe_get_option( 'dateWithoutYearFormat', 'F j' )
			: tribe_get_option( 'dateWithYearFormat', 'F j, Y' );
		$details[]           = sprintf(
			$format,
			esc_html( $first_start->format( $date_format ) ),
			tribe_get_option( 'timeRangeSeparator', ' - ' ),
			esc_html( $last_end->format( $date_format ) )
		);

		return $details;
	}
}
