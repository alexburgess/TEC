<?php
/**
 * PDF Pass: Body - Ticket Info - Attendee Field
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/integrations/event-tickets-wallet-plus/pdf/pass/body/ticket-info/attendee-fields/field.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 5.8.0
 *
 * @version 5.8.0
 */

if ( empty( $key ) && empty( $value ) ) {
	return;
}

?>
<table class="tec-tickets__wallet-plus-pdf-attendee-fields-field-table">
	<?php $this->template( 'pass/body/ticket-info/attendee-fields/key' ); ?>
	<?php $this->template( 'pass/body/ticket-info/attendee-fields/value' ); ?>
</table>
