<?php
/**
 * Apple Wallet link on emails.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/apple-wallet/emails/template-parts/body/apple-pass.php
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

if ( empty( $apple_wallet_pass_url ) ) {
	return;
}
?>
<table class="tec-tickets__email-table-content-apple-wallet">
	<tr>
		<td class="tec-tickets__email-table-content-apple-wallet-image-container">
			<?php $this->template( 'components/apple-wallet-button' ); ?>
		</td>
	</tr>
</table>
