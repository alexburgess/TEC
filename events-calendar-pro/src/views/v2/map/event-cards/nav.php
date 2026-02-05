<?php
/**
 * View: Map View Nav Template
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/map/event-cards/nav.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @since 5.0.1
 * @since 7.6.3 Add aria-label attribute to nav. [TEC-5222]
 *
 * @var string $prev_url The URL to the previous page, if any, or an empty string.
 * @var string $next_url The URL to the next page, if any, or an empty string.
 * @var string $today_url The URL to the today page, if any, or an empty string.
 *
 * @version 7.6.3
 */

$nav_aria_label = sprintf(
	// Translators: %s: Events (plural).
	__( '%s Pagination', 'tribe-events-calendar-pro' ),
	tribe_get_event_label_plural()
);
?>
<nav class="tribe-events-pro-map__nav tribe-events-c-nav" aria-label="<?php echo esc_attr( $nav_aria_label ); ?>">
	<ul class="tribe-events-c-nav__list">
		<?php
		if ( ! empty( $prev_url ) ) {
			$this->template( 'map/event-cards/nav/prev', [ 'link' => $prev_url ] );
		} else {
			$this->template( 'map/event-cards/nav/prev-disabled' );
		}
		?>

		<?php $this->template( 'map/event-cards/nav/today' ); ?>

		<?php
		if ( ! empty( $next_url ) ) {
			$this->template( 'map/event-cards/nav/next', [ 'link' => $next_url ] );
		} else {
			$this->template( 'map/event-cards/nav/next-disabled' );
		}
		?>
	</ul>
</nav>
