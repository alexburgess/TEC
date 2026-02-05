<?php
/**
 * PDF Pass: Body - Additional Information
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/body/additional-information.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

if ( empty( $additional_info ) ) {
	return;
}

?>
<table class="tec-tickets__wallet-plus-pdf-additional-content-table">
	<tr>
		<td>
			<?php $this->template( 'pass/body/additional-information/heading' ); ?>
			<?php $this->template( 'pass/body/additional-information/content' ); ?>
		</td>
	</tr>
</table>
