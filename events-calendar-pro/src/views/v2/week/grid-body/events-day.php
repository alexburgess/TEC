<?php
/**
 * View: Week View - Events Day
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/week/grid-body/events-day.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 7.7.12
 *
 * @since 5.0.0
 * @since 7.7.12 Improve accessibility for Week View markup and ARIA structure.
 *
 * @var WP_Post[]      $events The day events post objects.
 * @var array|boolean  $more_events The number of additional (not displayed) events for the day. Boolean false if none.
 * @var string|boolean $more_url The url to the day view for this day. Boolean false if not available.
 * @var string         $datetime The date of the day.
 *
 * @see tribe_get_event() for the additional properties added to the event post object.
 */

$day_label_id = 'tec-week-view-day-' . md5( $datetime );
?>
<div
	class="tribe-events-pro-week-grid__events-day"
	role="gridcell"
	aria-labelledby="<?php echo esc_attr( $day_label_id ); ?>"
>
	<h3 id="<?php echo esc_attr( $day_label_id ); ?>" class="tribe-common-a11y-visual-hide">
		<?php echo esc_html( date_i18n( 'l, F j, Y', strtotime( $datetime ) ) ); ?>
	</h3>

	<?php if ( ! empty( $events ) ) : ?>
		<ul class="tribe-events-pro-week-grid__events-list">
			<?php foreach ( $events as $event ) : ?>
				<?php $this->setup_postdata( $event ); ?>
					<?php $this->template( 'week/grid-body/events-day/event', [ 'event' => $event ] ); ?>
			<?php endforeach; ?>
			<?php wp_reset_postdata(); ?>
		</ul>
	<?php else : ?>
		<p class="tribe-common-a11y-visual-hide">
			<?php
			// Translators: Accessibility text announcing there are no events for this date.
			esc_html_e( 'No events on this day.', 'tribe-events-calendar-pro' );
			?>
		</p>
	<?php endif; ?>

	<?php $this->template( 'week/grid-body/events-day/more-events' ); ?>
</div>
