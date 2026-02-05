<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Attendee Registration Field Value.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/emails/template-parts/body/ticket/ar-fields/value.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @since 5.6.10
 * @since 5.8.0 Fix text escaping.
 *
 * @version 5.8.0
 */

if ( empty( $value ) ) {
	return;
}

?>
<div class="tec-tickets__email-table-content-ar-fields-data-value-container">
	<?php echo esc_html( $value ); ?>
</div>
