<?php
/**
 * View: Week View - Grid Header
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/week/grid-header.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 7.7.12
 *
 * @since 5.0.0
 * @since 7.7.6 Add aria-label to header column daynum link.
 * @since 7.7.12 Improve accessibility for Week View markup and ARIA structure.
 *
 * @var array  $day Array of data for the day.
 * @var string $today_date Today's date in the `Y-m-d` format.
 * @var int    $day_index Number of the day (Mon - 1, Tues - 2).
 */
$classes = [
	'tribe-events-pro-week-grid__header-column'          => true,
	'tribe-events-pro-week-grid__header-column--current' => $today_date === $day['datetime'],
];
?>
<div
	<?php tec_classes( $classes ); ?>
	role="columnheader"
	aria-colindex="<?php echo esc_attr( $day_index ); ?>"
	aria-label="<?php echo esc_attr( $day[ 'full_date' ] ); ?>"
>
	<div
		aria-hidden="true"
		class="tribe-events-pro-week-grid__header-column-title"
		role="presentation"
	>
		<time
			class="tribe-events-pro-week-grid__header-column-datetime"
			datetime="<?php echo esc_attr( $day[ 'datetime' ] ); ?>"
		>
			<span class="tribe-events-pro-week-grid__header-column-weekday tribe-common-h8 tribe-common-h--alt">
				<?php echo esc_html( $day[ 'weekday' ] ); ?>
			</span>
			<span class="tribe-events-pro-week-grid__header-column-daynum tribe-common-h4">
				<?php if ( ! empty( $day['found_events'] ) ) : ?>
					<?php
					$date_format  = tribe_get_date_option( 'dateWithoutYearFormat', 'F j' );
					$date_ordinal = date_i18n( $date_format, strtotime( $day['datetime'] ) );
					?>
					<a
						class="tribe-events-pro-week-grid__header-column-daynum-link"
						href="<?php echo esc_url( $day['day_url'] ); ?>"
						aria-label="<?php echo esc_attr( $date_ordinal ); ?>"
					>
						<?php echo esc_html( $day[ 'daynum' ] ); ?>
					</a>
				<?php else : ?>
					<?php echo esc_html( $day[ 'daynum' ] ); ?>
				<?php endif; ?>
			</span>
		</time>
	</div>
</div>
