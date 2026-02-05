<?php
/**
 * Single order - Fees section
 *
 * @since 6.9.0
 *
 * @version 6.9.0
 *
 * @var array $discounts The discounts.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $discounts ) ) {
	return;
}

?>
<tr class="tec-tickets-commerce__fees-section">
	<td colspan="5"><h2><?php esc_html_e( 'Discount:', 'event-tickets-plus' ); ?></h2></td>
</tr>
<?php foreach ( $discounts as $discount ) : ?>
	<tr class="tec-tickets-commerce-single-order--items--table--row">
		<td><?php echo esc_html( $discount['display_name'] ); ?></td>
		<td></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--info-column"></td>
		<td class="tec-tickets-commerce-single-order--items--table--row--price-column" colspan="2">
			<div class="tec-tickets-commerce-price-container">
				<ins>
					<span class="tec-tickets-commerce-price">
						<?php echo esc_html( $discount['sub_total']->get() ); ?>
					</span>
				</ins>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
