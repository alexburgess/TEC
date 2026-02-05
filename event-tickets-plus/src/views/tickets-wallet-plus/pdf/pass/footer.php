<?php
/**
 * PDF Pass: Footer
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/footer.php
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

?>
<tr>
	<td>
		<table class="tec-tickets__wallet-plus-pdf-footer-table">
			<?php $this->template( 'pdf/pass/footer/credit' ); ?>
		</table>
	</td>
</tr>
