<?php
/**
 * PDF link on RSVP block confirmation state.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/rsvp/pdf-button.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 *
 * @var array  $attendee_id  The attendee ID.
 * @var string $pdf_pass_url The link to the PDF pass.
 */

if ( empty( $attendee_id ) ) {
	return;
}

?>
<div class="tec-tickets__wallet-plus-rsvp-button tec__tickets-wallet-plus-rsvp-button--pdf">
	<?php $this->template( 'components/pdf-button' ); ?>
</div>

