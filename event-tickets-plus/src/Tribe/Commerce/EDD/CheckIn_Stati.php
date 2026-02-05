<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__EDD__CheckIn_Stati
 *
 * @since 4.6.2
 *
 */
class Tribe__Tickets_Plus__Commerce__EDD__CheckIn_Stati {

	/**
	 * Filters the checkin stati for EDD ticket orders.
	 *
	 * @since 4.6.2
	 *
	 * @since 5.6.6 Updated statuses array with EDD core method.
	 *
	 * @param array $checkin_stati The allowed statuses for check-in.
	 */
	public function filter_attendee_ticket_checkin_stati( array $checkin_stati ) {

		$backward_compatible = [ 'Completed' ];
		$edd_statuses        = function_exists( 'edd_get_deliverable_order_item_statuses' )
			? edd_get_deliverable_order_item_statuses()
			: [];

		return array_unique( array_merge( $checkin_stati, $edd_statuses, $backward_compatible ) );
	}
}