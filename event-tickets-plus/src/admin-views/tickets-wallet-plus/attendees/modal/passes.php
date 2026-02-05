<?php
/**
 * Attendees modal - Apple Wallet and PDF passes.
 *
 * @since 1.0.4
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

?>
<div class="tec-tickets__admin-attendees-modal-section tec-tickets__wallet-plus-passes-container tec-tickets__wallet-plus-passes-container--attendee-modal">
	<?php
	/**
	 * Allow enabled and future passes to be placed onto the My Tickets Page as needed.
	 * Example: The PDF pass would pass the /components/pdf-button.php view.
	 *
	 * @since 1.0.0
	 *
	 * @see  Tribe__Template\do_entry_point()
	 * @link https://docs.theeventscalendar.com/reference/classes/tribe__template/do_entry_point/
	 */
	$this->do_entry_point( 'wallet_plus_attendee_modal' );
	?>
</div>
