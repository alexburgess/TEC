<?php
/**
 * Purchase rules discount template.
 *
 * Override this template in your own theme by creating a file at:
 *
 * [your-theme]/tribe/tickets-plus/purchase-rules/checkout.php
 *
 * @since 6.9.0
 *
 * @version 6.9.0
 *
 * @var Currency_Value $total_discount The total discount.
 */

defined( 'ABSPATH' ) || exit;

use TEC\Tickets\Commerce\Values\Currency_Value;
?>
<div class="tribe-tickets__commerce-checkout-cart-footer-order-modifier-type">
	<ul>
		<li>
			<span class="tribe-tickets__commerce-checkout-cart-footer-quantity-label">
				<?php esc_html_e( 'Discount:', 'event-tickets-plus' ); ?>
			</span>
			<span class="tribe-tickets__commerce-checkout-cart-footer-quantity-number">
				<?php echo esc_html( $total_discount->get() ); ?>
			</span>
		</li>
	</ul>
</div>
