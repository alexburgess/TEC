<?php
/**
 * Render the purchase rule button for the single post.
 *
 * @since 6.9.0
 *
 * @version 6.9.0
 */

defined( 'ABSPATH' ) || exit;

?>
<button id="purchase_rules_form_toggle" class="button-secondary tec-tickets-plus-purchase-rules-button">
	<?php
	echo tec_svg( '@ticketsPlus/purchase-rules' ); // phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscapedExpected, StellarWP.XSS.EscapeOutput.OutputNotEscaped
	esc_html_e( 'Purchase Rules', 'event-tickets-plus' );
	?>
</button>
