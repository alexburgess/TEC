<?php
/**
 * PDF Pass: Body - Additional Information Content
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/body/additional-information/content.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

?>
<table class="tec-tickets__wallet-plus-pdf-additional-content-text-table">
	<tr>
		<td class="tec-tickets__wallet-plus-pdf-additional-content-text">
			<?php echo wp_kses_post( $additional_info ); ?>
		</td>
	</tr>
</table>
