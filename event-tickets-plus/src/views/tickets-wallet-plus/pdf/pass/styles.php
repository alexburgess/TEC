<?php
/**
 * PDF Pass: Styles
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/styles.php
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
<style>
	.tec-tickets__wallet-plus-pdf-table {
		color: #141827;
		font-family: freesans;
		padding: 0;
	}
	.tec-tickets__wallet-plus-pdf-header-table {
		background-color: <?php echo $header_bg_color; ?>;
		line-height: -1pt;
		padding: 5pt;
		padding-left: 10pt;
		padding-right: 10pt;
		text-align: <?php echo $header_image_alignment; ?>;
		width: 100%;
	}
	.tec-tickets__wallet-plus-pdf-header-table--no-image {
		padding: 4pt;
	}
	.tec-tickets__wallet-plus-pdf-header-image {
		height: 45pt;
		display: inline-block;
	}
	.tec-tickets__wallet-plus-pdf-body-table {
		padding-top: 10pt;
		padding-left: 10pt;
		padding-right: 10pt;
	}
	.tec-tickets__wallet-plus-pdf-post-title {
		font-family: freesans;
		font-weight: bold;
		font-size: 12pt;
		line-height: 2;
		padding-bottom: 3pt;
		padding-left: 0;
		padding-right: 0;
		padding-top: 3pt;
	}
	.tec-tickets__wallet-plus-pdf-ticket-info-table {
		padding-bottom: 10pt;
		padding-top: 10pt;
	}
	.tec-tickets__wallet-plus-pdf-attendee-details-wrapper {
		border-bottom-color: #cacaca;
		border-left-color: #cacaca;
		border-right-color: #cacaca;
		border-top-color: #cacaca;
		border-style: solid;
		border-width: 0.1pt;
		line-height: 1.5;
	}
	.tec-tickets__wallet-plus-pdf-attendee-details-table {
		padding: 10pt;
	}
	.tec-tickets__wallet-plus-pdf-attendee-details-name {
		font-size: 14pt;
		font-weight: bold;
		padding: 0;
	}
	.tec-tickets__wallet-plus-pdf-attendee-details-ticket-title {
		font-size: 10pt;
		font-weight: normal;
		padding: 0;
	}
	.tec-tickets__wallet-plus-pdf-attendee-details-security-code {
		font-size: 10pt;
		font-weight: normal;
		padding: 0;
	}
	.tec-tickets__wallet-plus-pdf-ticket-info-table {
		line-height: 0;
		padding: 0;
	}
	.tec-tickets__wallet-plus-pdf-additional-content-table {
		padding-bottom: 40pt;
		padding-left: 0;
		padding-right: 0;
		padding-top: 0;
	}
	.tec-tickets__wallet-plus-pdf-additional-content-title-table {
		padding: 0;
	}
	.tec-tickets__wallet-plus-pdf-additional-content-title {
		font-size: 10.5pt;
		font-weight: bold;
	}
	.tec-tickets__wallet-plus-pdf-additional-content-text-table {
		padding-left: 0;
		padding-top: 10pt;
	}
	.tec-tickets__wallet-plus-pdf-additional-content-text {
		font-size: 10.5pt;
		font-weight: normal;
	}
	.tec-tickets__wallet-plus-pdf-footer-table {
		border-top-color: #cacaca;
		border-top-style: solid;
		border-top-width: 1px;
		padding: 6pt;
	}
	.tec-tickets__wallet-plus-pdf-footer-credit {
		font-size: 10pt;
	}
	a.tec-tickets__wallet-plus-pdf-footer-credit-link {
		color: #141827;
		font-weight: bold;
		text-decoration: none;
	}
</style>