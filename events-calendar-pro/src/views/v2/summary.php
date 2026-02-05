<?php
/**
 * View: Summary View
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events-pro/v2/summary.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://evnt.is/1aiy
 *
 * @version 7.7.10
 *
 * @since 6.1.2 Changing our nonce verification structures.
 * @since 6.2.0 Defer header rendering to the components/header template.
 * @since 7.7.10 Adjust tags to use unordered lists for accessibility.
 *
 * @var array    $events               The array containing the events.
 * @var array    $events_by_date       An array containing the events indexed by date.
 * @var string   $rest_url             The REST URL.
 * @var string   $rest_method          The HTTP method, either `POST` or `GET`, the View will use to make requests.
 * @var int      $should_manage_url    Int containing if it should manage the URL.
 * @var bool     $disable_event_search Boolean on whether to disable the event search.
 * @var string[] $container_classes    Classes used for the container of the view.
 * @var array    $container_data       An additional set of container `data` attributes.
 * @var string   $breakpoint_pointer   String we use as a pointer to the current view we are setting up with breakpoints.
 */

use Tribe__Date_Utils as Dates;

$header_classes = [ 'tribe-events-header' ];
if ( empty( $disable_event_search ) ) {
	$header_classes[] = 'tribe-events-header--has-event-search';
}

$event_month = '';
?>
<div
	<?php tec_classes( $container_classes ); ?>
	data-js="tribe-events-view"
	data-view-rest-url="<?php echo esc_url( $rest_url ); ?>"
	data-view-rest-method="<?php echo esc_attr( $rest_method ); ?>"
	<?php // phpcs:ignore WordPressVIPMinimum.Security.ProperEscapingFunction.hrefSrcEscUrl ?>
	data-view-manage-url="<?php echo esc_attr( $should_manage_url ); ?>"
	<?php foreach ( $container_data as $key => $value ) : ?>
		data-view-<?php echo esc_attr( $key ); ?>="<?php echo esc_attr( $value ); ?>"
	<?php endforeach; ?>
	<?php if ( ! empty( $breakpoint_pointer ) ) : ?>
		data-view-breakpoint-pointer="<?php echo esc_attr( $breakpoint_pointer ); ?>"
	<?php endif; ?>
>
	<section class="tribe-common-l-container tribe-events-l-container">
		<?php $this->template( 'components/loader', [ 'text' => __( 'Loading...', 'tribe-events-calendar-pro' ) ] ); ?>

		<?php $this->template( 'components/json-ld-data' ); ?>

		<?php $this->template( 'components/data' ); ?>

		<?php $this->template( 'components/before' ); ?>

		<?php $this->template( 'components/header' ); ?>

		<?php $this->template( 'components/filter-bar' ); ?>

		<ul class="tribe-events-pro-summary">

			<?php foreach ( $events_by_date as $group_date => $events_data ) : ?>
				<?php
				if ( empty( $events_data ) ) {
					continue;
				}

				$event = current( $events_data );
				$this->setup_postdata( $event );
				$raw_group_date = $group_date;
				$group_date     = Dates::build_date_object( $group_date );
				$group_month    = $group_date->format( 'n' );
				$new_month      = $event_month !== $group_month;
				$first_month    = array_key_first( $events_by_date ) === $raw_group_date;
				?>

				<?php if ( $new_month ) : // Close the previous month's event list and block, except when doing the first month. Open a month block. ?>
					<?php if ( ! $first_month ) : ?>
						</ul>
					</li>
					<?php endif; ?>

					<li class="tribe-events-pro-summary__month-block">
				<?php endif; ?>

				<?php
				$this->template(
					'summary/month-separator',
					[
						'events'     => $events,
						'event'      => $event,
						'group_date' => $group_date,
					]
				);
				?>

				<?php if ( $new_month ) : // Open the month's event list. ?>
						<ul class="tribe-events-pro-summary__month-event-list">
				<?php endif; ?>

				<?php
				$this->template(
					'summary/date-group',
					[
						'events_for_date' => $events_data,
						'group_date'      => $group_date,
					]
				);
				?>

				<?php $event_month = $group_month; ?>
			<?php endforeach; ?>
				</ul>
			</li>
		</ul>

		<?php $this->template( 'summary/nav' ); ?>

		<?php $this->template( 'components/ical-link' ); ?>

		<?php $this->template( 'components/after' ); ?>

	</section>
</div>

<?php $this->template( 'components/breakpoints' ); ?>
