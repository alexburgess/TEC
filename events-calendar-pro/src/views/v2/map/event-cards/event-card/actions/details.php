<?php
/**
 * View: Map View - Single Event Actions - Details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/map/event-cards/event-card/actions/details.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 7.7.9
 * @since 5.0.3
 * @since 7.7.9 Added aria-label with event title for accessibility. [TEC-5220]
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 *
 */

/* translators: %s: The title of the event. */
$aria_label = sprintf( __( 'Event Details: %s', 'tribe-events-calendar-pro' ), get_the_title( $event->ID ) );
?>
<a href="<?php echo esc_url( $event->permalink ); ?>"
	rel="bookmark"
	class="tribe-events-c-small-cta__link tribe-common-cta tribe-common-cta--thin-alt"
	data-js="tribe-events-pro-map-event-actions-link-details"
	aria-label="<?php echo esc_attr( $aria_label ); ?>">
	<?php esc_html_e( 'Event Details', 'tribe-events-calendar-pro' ); ?>
</a>
