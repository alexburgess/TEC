<?php
/**
 * Apple Wallet styles on emails.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/apple-wallet/emails/template-parts/header/head/styles.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 *
 * @var string $apple_wallet_image_src The image source of the Apple Wallet icon.
 * @var string $apple_wallet_pass_url  The link to the Apple Wallet pass.
 */

?>
<style type="text/css">
.tribe-common-a11y-hidden {
	display: none !important;
	visibility: hidden;
}

table.tec-tickets__email-table-content-apple-wallet {
	padding: 20px 0 0 0;
}

td.tec-tickets__email-table-content-apple-wallet-image-container {
	text-align: left;
	vertical-align: top;
}

.tec-tickets__email-table-content-apple-wallet-image-container a img {
	display: inline-block;
	height: 45px;
	width: auto;
}

@media screen and ( max-width: 500px ) {
	td.tec-tickets__email-table-content-apple-wallet-image-container {
		display: block;
		padding: 0;
		text-align: center;
	}
}
</style>
