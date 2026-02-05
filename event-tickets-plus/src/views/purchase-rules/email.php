<?php
/**
 * Purchase Rules Discount Section in Emails
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/purchase-rules/email.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/tickets-emails-tpl Help article for Tickets Emails template files.
 *
 * @version 6.9.0
 *
 * @since 6.9.0
 *
 * @var Currency_Value $total_discount The total discount.
 */

use TEC\Tickets\Commerce\Values\Currency_Value;

defined( 'ABSPATH' ) || exit;
?>
<tr class="tec-tickets__email-table-content-order-ticket-totals-fees-row">
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-left" align="left">
		&nbsp;
	</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-center" align="center">
		<?php esc_html_e( 'Discount:', 'event-tickets-plus' ); ?>
	</td>
	<td class="tec-tickets__email-table-content-order-ticket-totals-cell tec-tickets__email-table-content-order-align-right" align="right">
		<?php echo esc_html( $total_discount->get() ); ?>
	</td>
</tr>
