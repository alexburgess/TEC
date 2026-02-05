<?php
/**
 * Capacity form view.
 *
 * @since 6.6.0
 *
 * @version 6.6.0
 *
 * @var array<string, mixed> $preset_data Preset data.
 */
	
defined( 'ABSPATH' ) || exit;
use TEC\Common\StellarWP\Arrays\Arr;

$capacity_type   = Arr::get( $preset_data, [ 'capacity', 'type' ], 'unlimited' );
$capacity_amount = absint( Arr::get( $preset_data, [ 'capacity', 'amount' ], 1 ) );

?>
<div class="tec-tickets-plus-preset__field capacity_field">
	<label class="tec-tickets-plus-preset__field-label required" for="preset-capacity-amount">
		<?php esc_html_e( 'Ticket Capacity', 'event-tickets-plus' ); ?>
	</label>
	<div class="tec-tickets-plus-preset__capacity">
		<select id="preset-capacity-type" name="preset[capacity][type]">
			<option value="unlimited" <?php selected( $capacity_type, 'unlimited' ); ?>><?php esc_html_e( 'Unlimited', 'event-tickets-plus' ); ?></option>
			<option value="own" <?php selected( $capacity_type, 'own' ); ?>><?php esc_html_e( 'Fixed capacity', 'event-tickets-plus' ); ?></option>
		</select>
		<div class="capacity_amount_wrapper tribe-common-a11y-hidden" id="capacity-amount-wrapper">
			<label class="capacity_amount_text required" for="preset-capacity-amount">
				<?php esc_html_e( 'Number of tickets available', 'event-tickets-plus' ); ?>
			</label>
			<input type="number" id="preset-capacity-amount" name="preset[capacity][amount]" min="1" value="<?php echo esc_attr( $capacity_amount ); ?>">
		</div>
	</div>
</div>