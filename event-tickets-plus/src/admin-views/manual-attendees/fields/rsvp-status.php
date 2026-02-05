<?php
/**
 * Manual Attendees: Form RSVP status template.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/admin-views/manual-attendees/fields/rsvp-status.php
 *
 * @since 5.6.8
 * @since 6.1.2 Added template override path to docblock.
 *
 * @version 6.1.2
 *
 * @var Tribe__Tickets_Plus__Admin__Views    $this                  [Global] Template object.
 * @var false|Tribe__Tickets__Tickets        $provider              [Global] The tickets provider class.
 * @var string                               $provider_class        [Global] The tickets provider class name.
 * @var string                               $provider_orm          [Global] The tickets provider ORM name.
 * @var null|Tribe__Tickets__Ticket_Object   $ticket                [Global] The ticket to add/edit.
 * @var null|int                             $ticket_id             [Global] The ticket ID to add/edit.
 * @var Tribe__Tickets__Ticket_Object[]      $tickets               [Global] List of tickets for the given post.
 * @var Tribe__Tickets__Commerce__Currency   $currency              [Global] Tribe Currency object.
 * @var bool                                 $is_rsvp               [Global] True if the ticket to add/edit an attendee is RSVP.
 * @var array                                $rsvp_options          [Global] Available RSVP options.
 * @var array                                $attendee              [Global] The attendee information.
 * @var int                                  $attendee_id           [Global] The attendee ID.
 * @var string                               $attendee_name         [Global] The attendee name.
 * @var string                               $attendee_email        [Global] The attendee email.
 * @var array                                $attendee_meta         [Global] The attendee meta field values.
 * @var int                                  $post_id               [Global] The post ID.
 * @var string                               $step                  [Global] The step the views are on.
 * @var bool                                 $multiple_tickets      [Global] If there's more than one ticket for the event.
 * @var bool                                 $allow_resending_email [Global] If resending email is allowed.
 */

// Bail if we're not on edit or there's no ticket or not rsvp.
if ( empty( $ticket_id ) || ! $is_rsvp || empty( $rsvp_options ) ) {
	return;
}
?>
<div class="tribe-tickets__manual-attendees-rsvp-status">
	<label for="tribe-tickets__manual-attendees-rsvp-status-custom" class="tribe-common-b1 tribe-common-b2--min-medium">
		<?php echo esc_html__( 'Status' , 'event-tickets-plus' ); ?>
	</label>
	<select
		id="tribe-tickets__manual-attendees-rsvp-status-custom"
		name="tribe_tickets[<?php echo esc_attr( absint( $ticket_id ) ); ?>][attendees][<?php echo esc_attr( absint( $attendee_id ) ); ?>][tribe-tickets-plus-ma-rsvp-status]"
		class="tribe-common-form-control-select"
	>
		<?php foreach ( $rsvp_options as $status => $label ) : ?>
			<option
				value="<?php echo esc_attr( $status ); ?>"
				<?php selected( $status, $attendee['order_status'] ); ?>
			>
				<?php echo esc_html( $label ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</div>
