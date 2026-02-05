<?php
/**
 * PDF Pass: Body - Ticket Info - Image
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/body/ticket-info/image.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

if ( empty( $post_image_url ) ) {
	return;
}

?>
<tr>
	<td>
		<img src="<?php echo esc_url( $post_image_url ); ?>" width="320" />
	</td>
</tr>
