<?php
/**
 * Event Tickets Emails: Main template > Header > Head > ETP Styles.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/emails/template-parts/header/head/etp-styles.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @since 5.7.3
 *
 * @version 5.7.3
 *
 */

?>
<style type="text/css">
	div.tec-tickets__email-table-content-ar-fields-container {
		clear: both;
		color: <?php echo esc_attr( $ticket_text_color ); ?>;
		display: block;
		padding: 15px 0 0 0;
	}

	table.tec-tickets__email-table-content-ar-fields-table {
		border-top: 1px solid <?php echo esc_attr( $ticket_text_color ); ?>33;
		width: 100%;
	}

	td.tec-tickets__email-table-content-ar-fields-data-container {
		padding-top: 15px;
		width: 50%;
	}

	div.tec-tickets__email-table-content-ar-fields-data-key-container {
		font-size: 16px;
		font-weight: 400;
	}

	div.tec-tickets__email-table-content-ar-fields-data-value-container {
		font-size: 16px;
		font-weight: 700;
	}

	td.tec-tickets__email-table-content-qr-container {
		padding: 0 0 0 15px;
		text-align: right;
		vertical-align: top;
	}

	img.tec-tickets__email-table-content-qr-image {
		display: inline-block;
		max-height: 130px;
		max-width: 130px;
	}

	@media screen and ( max-width: 500px ) {
		td.tec-tickets__email-table-content-qr-container {
			display: block;
			padding: 0;
			text-align: center;
		}
	}
</style>
