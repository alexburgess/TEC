<?php
/**
 * View: Summary Event
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/summary/date-group/event.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://evnt.is/1aiy
 *
 * @version 7.7.11
 *
 * @since 5.7.0
 * @since 7.7.10 Adjust tags to use unordered lists for accessibility.
 * @since 7.7.11 Move cost template into this template.
 *
 * @var WP_Post                          $event      The event post object with properties added by the `tribe_get_event` function.
 * @var \Tribe\Utils\Date_I18n_Immutable $group_date The date for the date group.
 *
 * @see tribe_get_event() For the format of the event object.
 */

$wrapper_classes = [ 'tribe-common-g-row', 'tribe-common-g-row--gutters' ];
$wrapper_classes['tribe-events-pro-summary__event-row--featured'] = $event->featured;
$event_classes = tribe_get_post_class( [ 'tribe-events-pro-summary__event', 'tribe-events-pro-summary__event-details' ], $event->ID );
?>
<li <?php tec_classes( $wrapper_classes ); ?>>
	<article <?php tec_classes( $event_classes ); ?>>

		<header class="tribe-events-pro-summary__event-header">
			<?php $this->template( 'summary/date-group/event/date', [ 'event' => $event, 'group_date' => $group_date ] ); ?>
			<?php $this->template( 'summary/date-group/event/title', [ 'event' => $event ] ); ?>
			<?php $this->template( 'summary/date-group/event/cost', [ 'event' => $event ] ); ?>
		</header>

	</article>
</li>
