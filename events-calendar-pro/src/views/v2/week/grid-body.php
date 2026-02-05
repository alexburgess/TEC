<?php
/**
 * View: Week View - Grid Body
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/week/grid-body.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 7.7.12
 *
 * @since 5.0.0
 * @since 7.7.8 Removed invalid role attributes from structure.
 * @since 7.7.12 Improve ARIA grid structure with role="row" wrappers for accessibility.
 *
 * @var array $multiday_events     An array of each day multi-day events and more event count, if any, in the shape
 *                                 `[ <Y-m-d> => [ 'events' => [ ...$multiday_events], 'more_events' => <int> ] ]`.
 * @var bool  $has_multiday_events Boolean whether the week has multiday events or not.
 * @var array $events              An array of each day non multi-day events, if any, in the shape `[ <Y-m-d> => [ ...$events ] ]`.
 * @var array $days                An array of days with additional data.
 */

$event_keys = array_keys( $events );
$first_day  = reset( $event_keys );
$last_day   = end( $event_keys );
$week_label = sprintf(
/* translators: 1: Start date, 2: End date of the week */
	__( 'Week of %1$s – %2$s', 'tribe-events-calendar-pro' ),
	date_i18n( 'F j', strtotime( $first_day ) ),
	date_i18n( 'F j, Y', strtotime( $last_day ) )
);

?>
<div class="tribe-events-pro-week-grid__body" role="rowgroup">

	<?php if ( count( $multiday_events ) && $has_multiday_events ) : ?>
		<div class="tribe-events-pro-week-grid__multiday-events-row-outer-wrapper">
			<div class="tribe-events-pro-week-grid__multiday-events-row-wrapper">
				<div
					class="tribe-events-pro-week-grid__multiday-events-row"
					data-js="tribe-events-pro-week-multiday-events-row"
					aria-label="<?php echo esc_attr( $week_label ); ?>"
					role="row"
				>
					<?php
					// Render the time column (visually hidden on mobile).
					$this->template( 'week/grid-body/multiday-events-row-header' );

					foreach ( $multiday_events as $day => [ $day_multiday_events, $more_events ] ) {
						$this->template(
							'week/grid-body/multiday-events-day',
							[
								'day'    => $day,
								'events' => $day_multiday_events,
							]
						);
					}
					?>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="tribe-events-pro-week-grid__events-scroll-wrapper">
		<div
			class="tribe-events-pro-week-grid__events-row-outer-wrapper"
			data-js="tribe-events-pro-week-grid-events-row-outer-wrapper"
		>
			<div
				class="tribe-events-pro-week-grid__events-row-wrapper"
				data-js="tribe-events-pro-week-grid-events-row-wrapper"
			>
				<div
					class="tribe-events-pro-week-grid__events-row"
					role="row"
					aria-label="<?php echo esc_attr( $week_label ); ?>"
				>
					<?php
					// Render the left-hand hour labels column.
					$this->template( 'week/grid-body/events-row-header' );

					foreach ( $events as $day => $day_events ) {
						$this->template(
							'week/grid-body/events-day',
							[
								'events'      => $day_events,
								'more_events' => ! empty( $days[ $day ]['more_events'] ) ? $days[ $day ]['more_events'] : false,
								'more_url'    => ! empty( $days[ $day ]['day_url'] ) ? $days[ $day ]['day_url'] : false,
								'datetime'    => $day,
							]
						);
					}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
