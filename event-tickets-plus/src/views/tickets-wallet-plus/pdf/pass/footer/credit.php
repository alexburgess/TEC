<?php
/**
 * PDF Pass: Footer - Credit
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/footer/credit.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

if ( empty( $include_credit ) ) {
	return;
}

$et_link = sprintf(
	'<a href="%1$s" class="tec-tickets__wallet-plus-pdf-footer-credit-link">%2$s</a>',
	'https://evnt.is/etwp-in-app-pdf-credit',
	esc_html__( 'Event Tickets', 'event-tickets' )
);

$credit_html = sprintf(
	// Translators: %s - HTML link to `Event Tickets` website.
	__( 'Powered by %1$s', 'event-tickets' ),
	$et_link
);

?>
<tr>
	<td class="tec-tickets__wallet-plus-pdf-footer-credit" align="right">
		<?php echo wp_kses_post( $credit_html ); ?>
	</td>
</tr>
