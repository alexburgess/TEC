<?php
/**
 * PDF Pass: Body
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/body.php
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
<tr>
	<td height="670">
		<table class="tec-tickets__wallet-plus-pdf-body-table">
			<tr>
				<td>
					<?php $this->template( 'pass/body/post-title' ); ?>
				</td>
			</tr>
			<tr>
				<td width="320">
					<table class="tec-tickets__wallet-plus-pdf-ticket-info-table">
						<tr>
							<td width="300">
								<?php $this->template( 'pass/body/ticket-info' ); ?>
							</td>
						</tr>
					</table>
				</td>
				<td>
					<?php $this->template( 'pass/body/sidebar' ); ?>
				</td>
			</tr>
		</table>
	</td>
</tr>
