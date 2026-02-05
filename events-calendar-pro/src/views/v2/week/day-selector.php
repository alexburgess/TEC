<?php
/**
 * View: Week View - Day Selector
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/week/day-selector.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 7.7.12
 * @since 5.0.0
 * @since 7.7.12 Wrap navigation arrows in nav elements to improve accessibility.
 *
 * @var bool   $hide_weekends Boolean on whether to hide weekends.
 * @var string $prev_url      The URL to the previous page, if any, or an empty string.
 * @var string $next_url      The URL to the next page, if any, or an empty string.
 */
$classes = [ 'tribe-events-pro-week-day-selector' ];
if ( $hide_weekends ) {
	$classes[] = 'tribe-events-pro-week-day-selector--hide-weekends';
}
?>
<section <?php tec_classes( $classes ); ?>>

	<nav class="tribe-events-pro-week-day-selector__nav">
		<ul class="tribe-events-pro-week-day-selector__nav-list">
			<?php $this->template( 'week/day-selector/nav/prev', [ 'link' => $prev_url ] ); ?>
		</ul>
	</nav>

	<?php $this->template( 'week/day-selector/days' ); ?>

	<nav class="tribe-events-pro-week-day-selector__nav">
		<ul class="tribe-events-pro-week-day-selector__nav-list">
			<?php $this->template( 'week/day-selector/nav/next', [ 'link' => $next_url ] ); ?>
		</ul>
	</nav>

</section>
