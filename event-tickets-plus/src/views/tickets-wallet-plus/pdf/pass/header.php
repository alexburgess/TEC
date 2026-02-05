<?php
/**
 * PDF Pass: Header
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/header.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 *
 * @var string $header_image_url The header image URL.
 */

$classes = [
	'tec-tickets__wallet-plus-pdf-header-table',
	'tec-tickets__wallet-plus-pdf-header-table--no-image' => empty( $header_image_url ),
];
?>
<tr>
	<td >
		<table <?php tribe_classes( $classes ); ?>>
			<tr>
				<td>
					<?php if ( ! empty( $header_image_url ) ) : ?>
					<img
						class="tec-tickets__wallet-plus-pdf-header-image"
						src="<?php echo esc_url( $header_image_url ); ?>"
					/>
					<?php endif; ?>
				</td>
			</tr>
		</table>
	</td>
</tr>
