<?php
/**
 * PDF Pass: Body - Ticket Info - Attendee Details - QR Image
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/body/ticket-info/attendee-details/qr-image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

if ( empty( $qr_enabled ) || empty( $qr_image_url ) ) {
	return;
}

?>
<td width="120" rowspan="2">
	<img width="100" src="<?php echo esc_url( $qr_image_url ); ?>" />
</td>
