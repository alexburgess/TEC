<?php
/**
 * Purchase rules discount template.
 *
 * Override this template in your own theme by creating a file at:
 *
 * [your-theme]/tribe/tickets-plus/purchase-rules/order-details.php
 *
 * @since 6.9.0
 *
 * @version 6.9.0
 *
 * @var Currency_Value $total_discount The total discount.
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

use TEC\Tickets\Commerce\Values\Currency_Value;

?>
<div class="tribe-tickets__commerce-order-details-row">
	<div class="tribe-tickets__commerce-order-details-col1">
		<?php esc_html_e( 'Discount:', 'event-tickets-plus' ); ?>
	</div>
	<div class="tribe-tickets__commerce-order-details-col2">
		<?php echo esc_html( $total_discount->get() ); ?>
	</div>
</div>
