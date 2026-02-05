<?php
/**
 * Event Tickets Emails: Main template > Body > Ticket > Attendee Registration Field Key.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/emails/template-parts/body/ticket/ar-fields/key.php
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

if ( empty( $key ) ) {
	return;
}

?>
<div class="tec-tickets__email-table-content-ar-fields-data-key-container">
	<?php echo esc_html( $key ); ?>
</div>
