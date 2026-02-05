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
 * @since 5.9.2 Remove incorrect aria-selected attribute.
 * @since 7.7.8 Remove invalid role attributes.
 * @since 7.7.12 Add aria-colindex to header columns for accessibility.
 *
 * @var bool  $has_multiday_events Boolean whether the week has multiday events or not.
 * @var array $days_of_week        An array of data of each day of the week, with '<Y-m-d>' as the key.
 */

$empty_header_column_classes = [
	'tribe-events-pro-week-grid__header-column',
	'tribe-events-pro-week-grid__header-column--empty',
	'tribe-events-pro-week-grid__header-column--border-bottom' => ! $has_multiday_events,
];
?>
<div class="tribe-events-pro-week-grid__header">

	<h2 class="tribe-common-a11y-visual-hide" id="tribe-events-pro-week-header">
		<?php printf( esc_html__( 'Week of %s', 'tribe-events-calendar-pro' ), tribe_get_event_label_plural() ); ?>
	</h2>

	<div class="tribe-events-pro-week-grid__header-row">

		<div
			<?php tec_classes( $empty_header_column_classes ); ?>
			role="presentation"
		>
		</div>

		<?php
		$day_index = 1;
		foreach ( $days_of_week as $day ) :
			$this->template(
				'week/grid-header/header-column',
				[
					'day'       => $day,
					'day_index' => $day_index,
				]
			);
			++$day_index;
		endforeach;
		?>
	</div>
</div>
