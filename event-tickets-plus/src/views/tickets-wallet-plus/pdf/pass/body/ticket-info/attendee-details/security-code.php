<?php
/**
 * PDF Pass: Body - Ticket Info - Attendee Details - Security Code
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/body/ticket-info/attendee-details/security-code.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

if ( empty( $attendee['security_code'] ) ) {
	return;
}

?>
<table class="tec-tickets__wallet-plus-pdf-attendee-details-security-code">
	<tr>
		<td>
			<?php echo esc_html( trim( $attendee['security_code'] ) ); ?>
		</td>
	</tr>
</table>
