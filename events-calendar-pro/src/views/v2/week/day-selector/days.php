<?php
/**
 * View: Week View - Day Selector Days
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/week/day-selector/days.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 7.7.10
 *
 * @since 5.0.0
 * @since 7.7.10 Use role="tablist" instead of ul.
 *
 * @var array $days An array of each day data.
 */
?>
<ul class="tribe-events-pro-week-day-selector__days-list" role="tablist">

	<?php foreach ( $days as $day ) : ?>
		<?php $this->template( 'week/day-selector/days/day', [ 'day' => $day ] ); ?>
	<?php endforeach; ?>

</ul>
