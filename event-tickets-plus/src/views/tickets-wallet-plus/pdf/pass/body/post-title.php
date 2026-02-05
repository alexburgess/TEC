<?php
/**
 * PDF Pass: Body - Post Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass/body/post-title.php
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
<table class="tec-tickets__wallet-plus-pdf-post-title">
	<tr>
		<td>
			<?php echo esc_html( $post->post_title ); ?>
		</td>
	</tr>
</table>
