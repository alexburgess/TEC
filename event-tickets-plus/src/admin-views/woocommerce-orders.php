<?php
/**
 * The WooCommerce orders report template.
 *
 * @var int $event_id The event ID.
 * @var WP_Post $event The event object.
 * @var string $order_summary The order summary template.
 * @var string $table The Orders table template.
 *
 * @since 4.10.6
 *
 * @version 5.8.0
 */

/**
 * Whether or not we should display order report title.
 *
 * @since 4.10.6
 *
 * @param boolean $show_title (false) Whether or not to show the title.
 */
$show_title = apply_filters( 'tribe_tickets_order_report_show_title', false );

/**
 * Whether or not we should display order report title for WooCommerce orders.
 *
 * @since 4.10.6
 *
 * @param boolean $show_title (false) Whether or not to show the title.
 */
$show_title = apply_filters( 'tribe_tickets_woocommerce_order_report_show_title', $show_title );

$title = __( 'Orders Report', 'event-tickets-plus' );
/**
 * Allows filtering of the WooCommerce order report title.
 *
 * @since 4.10.6
 *
 * @param string $title the title.
 */
$title = apply_filters( 'tribe_tickets_woocommerce_order_report_title', $title );
?>

<div class="wrap tribe-report-page">
	<?php if ( $show_title ) : ?>
		<h1><?php echo esc_html( $title ); ?></h1>
	<?php endif; ?>
	<div id="icon-edit" class="icon32 icon32-tickets-orders"><br></div>

	<?php echo $order_summary; ?>

	<form id="topics-filter" method="get">
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'page' : 'tribe[page]' ); ?>" value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : '' ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'event_id' : 'tribe[event_id]' ); ?>" id="event_id" value="<?php echo esc_attr( $event_id ); ?>"/>
		<input type="hidden" name="<?php echo esc_attr( is_admin() ? 'post_type' : 'tribe[post_type]' ); ?>" value="<?php echo esc_attr( $event->post_type ); ?>"/>
		<?php echo $table; ?>
	</form>
</div>
