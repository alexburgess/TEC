<?php
/**
 * Apple Wallet link on RSVP block confirmation state.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/apple-wallet/rsvp/apple-wallet-button.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 *
 * @var array  $attendee_id            The attendee ID.
 * @var string $apple_wallet_image_src The image source of the Apple Wallet icon.
 * @var string $apple_wallet_pass_url  The link to the Apple Wallet pass.
 */

if ( empty( $attendee_id ) ) {
	return;
}

?>
<div class="tec-tickets__wallet-plus-rsvp-button tec__tickets-wallet-plus-rsvp-button--apple-wallet">
	<?php $this->template( 'components/apple-wallet-button' ); ?>
</div>
