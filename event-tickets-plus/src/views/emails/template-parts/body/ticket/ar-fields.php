<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Attendee Registration Fields.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/emails/template-parts/body/ticket/ar-fields.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @since 5.6.10
 *
 * @version 5.6.10
 */

if ( empty( $ticket['attendee_meta'] ) ) {
	return;
}

?>
<div class="tec-tickets__email-table-content-ar-fields-container">
	<table class="tec-tickets__email-table-content-ar-fields-table">
		<tr>
			<?php

			// Start counter.
			$ct = 0;
			foreach ( $ticket['attendee_meta' ] as $key => $value ) :

				// After two fields, start a new row and reset counter.
				if ( $ct == 2 ) {
					echo '</tr><tr>';
					$ct = 0;
				}

				$this->template( 'emails/template-parts/body/ticket/ar-fields/data', [ 'key' => $key, 'value' => $value ] );

				// Increment counter.
				$ct++;
			endforeach;
			?>
		</tr>
	</table>
</div>
