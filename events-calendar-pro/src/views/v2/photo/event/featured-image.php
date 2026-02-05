<?php
/**
 * View: Photo View - Single Event Featured Image
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/photo/event/featured-image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @since 5.0.0
 * @since 7.6.3 Removed link around featured image for accessibility update [TEC-5181].
 *
 * @version 7.6.3
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 * @var string $placeholder_url The url for the placeholder image if a featured image does not exist.
 *
 * @see tribe_get_event() For the format of the event object.
 */

?>
<div class="tribe-events-pro-photo__event-featured-image-wrapper">
	<a
		href="<?php echo esc_url( $event->permalink ); ?>"
		rel="bookmark"
		class="tribe-events-pro-photo__event-featured-image-link"
	>
		<span class="tribe-events-pro-photo__event-featured-image-link-inner tribe-common-a11y-hidden"><?php echo esc_html( get_the_title( $event ) ); ?></span>
		<img
			class="tribe-events-pro-photo__event-featured-image"
			src="<?php echo esc_url( $event->thumbnail->full->url ?? $placeholder_url ); ?>"
			role="presentation"
			<?php if ( ! empty( $event->thumbnail->srcset ) ) : ?>
				srcset="<?php echo esc_attr( $event->thumbnail->srcset ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $event->thumbnail->alt ) ) : ?>
				alt="<?php echo esc_attr( $event->thumbnail->alt ); ?>"
			<?php else : ?>
				alt=""
			<?php endif; ?>
			<?php if ( ! empty( $event->thumbnail->title ) ) : ?>
				title="<?php echo esc_attr( $event->thumbnail->title ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $event->thumbnail->full->width ) ) : ?>
				width="<?php echo esc_attr( $event->thumbnail->full->width ); ?>"
			<?php endif; ?>
			<?php if ( ! empty( $event->thumbnail->full->height ) ) : ?>
				height="<?php echo esc_attr( $event->thumbnail->full->height ); ?>"
			<?php endif; ?>
		/>
	</a>
</div>
