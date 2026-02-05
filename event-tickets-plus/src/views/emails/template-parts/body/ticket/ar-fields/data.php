<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Attendee Registration Field Data.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/emails/template-parts/body/ticket/ar-fields/data.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @since 5.6.10
 *
 * @version 5.6.10
 */

if ( empty( $key ) && empty( $value ) ) {
	return;
}

?>
<td class="tec-tickets__email-table-content-ar-fields-data-container">
	<?php $this->template( 'emails/template-parts/body/ticket/ar-fields/key' ); ?>
	<?php $this->template( 'emails/template-parts/body/ticket/ar-fields/value' ); ?>
</td>
