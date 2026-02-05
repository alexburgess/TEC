<?php
/**
 * View: Map View - Single Event Actions - Directions
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/map/event-cards/event-card/actions/directions.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 7.7.9
 * @since 5.0.0
 * @since 7.7.9 Added aria-label with venue name for accessibility. [TEC-5220]
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( ! $event->venues->count() ) {
	return;
}

$venue = $event->venues[0];

/* translators: %s: The name of the venue. */
$aria_label = sprintf( __( 'Get Directions: %s', 'tribe-events-calendar-pro' ), $venue->post_title );
?>
<a href="<?php echo esc_url( $venue->directions_link ); ?>"
	class="tribe-events-c-small-cta__link tribe-common-cta tribe-common-cta--thin-alt"
	target="_blank"
	aria-label="<?php echo esc_attr( $aria_label ); ?>"
>
	<?php esc_html_e( 'Get Directions', 'tribe-events-calendar-pro' ); ?>
</a>

