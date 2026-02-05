<?php
/**
 * PDF Button Component.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/components/pdf-button.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 *
 * @var string $pdf_pass_url The link to the PDF pass.
 */

if ( empty( $pdf_pass_url ) ) {
	return;
}

$container_classes = [ 'tec-tickets__wallet-plus-component-pdf-button-container' ];
if ( ! empty( $classes ) ) {
	$container_classes = array_merge( $container_classes, $classes );
}

?>
<div <?php tribe_classes( $container_classes ); ?>>
	<a
		href="<?php echo esc_url( $pdf_pass_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
		class="tribe-common-c-btn-border tec-tickets__wallet-plus-component-pdf-button-link"
	>
		<?php esc_html_e( 'PDF ticket', 'event-tickets-plus' ); ?>
	</a>
</div>
