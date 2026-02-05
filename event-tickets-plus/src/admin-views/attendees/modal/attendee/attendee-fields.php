<?php
/**
 * Attendees modal > Attendee registration fields.
 *
 * @since 5.10.1
 *
 * @var Tribe_Template $this           Current template object.
 * @var \WP_Post       $attendee       The attendee object.
 * @var int            $attendee_id    The attendee ID.
 * @var string         $attendee_name  The attendee name.
 * @var string         $attendee_email The attendee email.
 * @var int            $post_id        The ID of the associated post.
 * @var int            $ticket_id      The ID of the associated ticket.
 * @var bool           $qr_enabled     True if QR codes are enabled for the site.
 */

if ( empty( $attendee_meta ) ) {
	return;
}

$count = 0;
?>
<div class="tec-tickets__admin-attendees-modal-section tec-tickets__admin-attendees-modal-attendee-fields">
	<h3 class="tribe-common-h5">
		<?php esc_html_e( 'Attendee information', 'event-tickets-plus' ); ?>
	</h3>

	<?php foreach ( $attendee_meta as $key => $value ) : ?>

		<?php if ( 0 === $count % 2 ) : ?>
			<div class="tribe-common-g-row tribe-common-g-row--gutters tec-tickets__admin-attendees-modal-attendee-info-row">
		<?php endif; ?>

			<div class="tribe-common-g-col tec-tickets__admin-attendees-modal-attendee-info-col">
				<div class="tribe-common-b2--bold"><?php echo esc_html( $key ); ?></div>
				<div class="tec-tickets__admin-attendees-modal-attendee-info-value"><?php echo esc_html( $value ); ?></div>
			</div>
		<?php if ( 1 === $count % 2 || count( $attendee_meta ) - 1 === $count ) : ?>
			</div>
		<?php endif; ?>
		<?php ++$count; ?>

	<?php endforeach; ?>
</div>
