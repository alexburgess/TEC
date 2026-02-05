<?php
/**
 * Widget: Featured Venue - Venue - Phone
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/widgets/widget-featured-venue/venue/phone.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/1aiy
 *
 * @version 5.3.0
 *
 * @var WP_Post $venue The venue post object with properties added by the `tribe_get_venue_object` function.
 *
 * @see tribe_get_venue_object() For the format of the venue object.
 */

if ( empty( $venue->phone ) ) {
	return;
}
?>
<div class="tribe-common-h7 tribe-common-h--alt tribe-events-widget-featured-venue__venue-info-group tribe-events-widget-featured-venue__venue-info-group--phone">
	<span class="tribe-events-widget-featured-venue__venue-icon" >
		<?php
		$this->template(
			'components/icons/phone',
			[
				'classes' => [
					'tribe-events-widget-featured-venue__venue-icon-svg',
					'tribe-events-widget-featured-venue__venue-icon-svg--phone',
				],
			]
		);
		?>
	</span>
	<span class="tribe-events-widget-featured-venue__venue-icon-text tribe-common-a11y-visual-hide">
		<?php esc_html_e( 'Phone', 'tribe-events-calendar-pro' ); ?>
	</span>
	<div class="tribe-events-widget-featured-venue__venue-content tribe-events-widget-featured-venue__venue-phone">
		<?php echo esc_html( $venue->phone ); ?>
	</div>
</div>
