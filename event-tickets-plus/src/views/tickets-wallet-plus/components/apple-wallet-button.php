<?php
/**
 * Apple Wallet Button component.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/components/apple-wallet-button.php
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

$container_classes = [ 'tec-tickets__wallet-plus-component-apple-wallet-button-container' ];
if ( ! empty( $classes ) ) {
	$container_classes = array_merge( $container_classes, $classes );
}

$link_classes = [
	'tribe-common-c-btn-border' => empty( $apple_wallet_image_src ),
	'tec-tickets__wallet-plus-component-apple-wallet-button-link',
];

$link_text = __( 'Add to Apple Wallet', 'event-tickets-plus' );

?>
<div <?php tribe_classes( $container_classes ); ?>>
	<a
		href="<?php echo esc_url( $apple_wallet_pass_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
		<?php tribe_classes( $link_classes ); ?>
	>
	<?php if ( ! empty( $apple_wallet_image_src ) ) : ?>
		<img
			class="tec-tickets__wallet-plus-component-apple-wallet-button-image"
			src="<?php echo esc_url( $apple_wallet_image_src ); ?>"
			alt="<?php esc_attr_e( 'Add to Apple Wallet', 'event-tickets-plus' ); ?>"
		/><span class="tribe-common-a11y-hidden"><?php echo esc_html( $link_text ); ?></span>
	<?php else : ?>
		<?php echo esc_html( $link_text ); ?>
	<?php endif; ?>
	</a>
</div>
