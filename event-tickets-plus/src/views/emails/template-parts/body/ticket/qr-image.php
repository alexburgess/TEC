<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > QR Image.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/emails/template-parts/body/ticket/qr-image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @since 5.6.10
 *
 * @version 5.6.10
 *
 * @var string  $qr_url     URL of QR image.
 * @var boolean $include_qr Whether or not QR codes are set to be shown.
 */

// If QR codes not included or URL is empty, bail.
if ( ! $include_qr || empty( $qr ) ) {
	return;
}

?>
<td class="tec-tickets__email-table-content-qr-container" rowspan="2">
	<img
		src="<?php echo esc_attr( $qr ); ?>"
		class="tec-tickets__email-table-content-qr-image"
	/>
</td>

