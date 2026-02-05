<?php
/**
 * PDF Pass: Body - Ticket Info - Attendee Fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/integrations/event-tickets-wallet-plus/pdf/pass/body/ticket-info/attendee-fields.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 */

if ( empty( $attendee['attendee_meta'] ) ) {
	return;
}

?>
<tr>
	<td class="tec-tickets__wallet-plus-pdf-attendee-details-wrapper">
		<table class="tec-tickets__wallet-plus-pdf-attendee-fields-table">
			<?php
			$count = 0;
			foreach ( $attendee['attendee_meta'] as $key => $value ) {
				if ( $count % 2 == 0 ) {
					echo '<tr>';
				}
				?>
					<td>
						<?php $this->template( 'pass/body/ticket-info/attendee-fields/field', [ 'key' => $key, 'value' => $value ] ); ?>
					</td>
				<?php
				// Close the row for every second item or at the end of the array.
				if ( $count % 2 === 1 || $count === count( $attendee['attendee_meta'] ) - 1 ) {
					echo '</tr>';
				}
				$count++;
			}
			?>
		</table>
	</td>
</tr>
