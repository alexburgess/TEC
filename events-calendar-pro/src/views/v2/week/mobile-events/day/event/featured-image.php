<?php
/**
 * View: Week View - Mobile Event Featured Image
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/week/mobile-events/day/event/featured-image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @since 5.0.0
 * @since 7.6.3 Removed link around featured image for accessibility update.
 *
 * @version 7.6.3
 *
 * @var WP_Post $event The event post object, decorated with additional properties by the `tribe_get_event` function.
 *
 * @see tribe_get_event() for the additional properties added to the event post object.
 */

if ( ! $event->thumbnail->exists ) {
	return;
}

// Always show post title as image alt, if not available fallback to image alt.
$image_alt_attr = ! empty( $event->title )
	? $event->title
	: ( ! empty( $event->thumbnail->alt )
		? $event->thumbnail->alt
		: ''
	);

?>
<div class="tribe-events-pro-week-mobile-events__event-featured-image-wrapper tribe-common-g-col">
	<img
		src="<?php echo esc_url( $event->thumbnail->full->url ); ?>"
		<?php if ( ! empty( $event->thumbnail->srcset ) ) : ?>
			srcset="<?php echo esc_attr( $event->thumbnail->srcset ); ?>"
		<?php endif; ?>
		alt="<?php echo esc_attr( $image_alt_attr ); ?>"
		class="tribe-events-pro-week-mobile-events__event-featured-image"
	/>
</div>
