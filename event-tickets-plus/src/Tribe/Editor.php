<?php
use Tribe\Tickets\Plus\Editor\Settings\Data\Capacity_Table as Capacity_Table;
/**
 * Class Tribe__Tickets_Plus__Editor
 *
 * @since 4.7
 */
class Tribe__Tickets_Plus__Editor extends Tribe__Tickets__Editor {

	/**
	 * Configure all action and filters user by this Class
	 *
	 * @since 4.6.2
	 *
	 * @return void
	 */
	public function hook() {
		add_action( 'tribe_events_tickets_settings_content_before', [ $this, 'render_capacity_data' ] );
		add_action( 'tribe_events_tickets_settings_content', tribe_callback( 'tickets-plus.admin.views', 'template', 'editor/settings-attendees' ) );
	}

	/**
	 * Filters the link to the Orders page to show the correct one.
	 *
	 * By default the link would point to PayPal ticket orders.
	 *
	 * @since 4.7
	 * @deprecated 4.10
	 *
	 * @param string $url
	 * @param int    $post_id
	 *
	 * @return string The updated Orders page URL
	 */
	public function filter_attendee_order_link( $url, $post_id ) {
		_deprecated_function( __METHOD__, '4.10', 'Method moved to each Commerce to modify filter tribe_filter_attendee_order_link' );

		$provider = Tribe__Tickets__Tickets::get_event_ticket_provider( $post_id );

		if ( 'Tribe__Tickets__Commerce__PayPal__Main' === $provider ) {
			return $url;
		}

		$url = remove_query_arg( 'page', $url );
		$url = add_query_arg( array( 'page' => 'tickets-orders' ), $url );

		return $url;
	}

	/**
	 * Render the capacity data on the settings page.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	public function render_capacity_data(): void {
		$post_id = get_the_ID();
		$capacity_table = new Capacity_Table( $post_id );
		$admin_views = tribe( 'tickets-plus.admin.views' );
		$admin_views->template( 'editor/fieldset/settings-capacity', [ 'capacity_table' => $capacity_table ] );
	}
}
