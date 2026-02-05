<?php
/**
 * Sale period form view.
 *
 * @since 6.6.0
 *
 * @version 6.6.0
 *
 * @var bool $fieldset_form True if in fieldset form context.
 * @var array<string, mixed> $preset_data Preset data.
 */

defined( 'ABSPATH' ) || exit;

$start_sale_period = $preset_data['sale_start_logic']['period'] ?? 'day';
$end_sale_period   = $preset_data['sale_end_logic']['period'] ?? 'day';

$start_sale_length = $preset_data['sale_start_logic']['length'] ?? 1;
$end_sale_length   = $preset_data['sale_end_logic']['length'] ?? 1;

$start_relative_to = $preset_data['sale_start_logic']['relative_to'] ?? 'start';
$end_relative_to   = $preset_data['sale_end_logic']['relative_to'] ?? 'end';
?>
<div class="tec-tickets-plus-preset__field">
	<div class="tec-tickets-plus-preset__sale-logic">
		<div class="tec-tickets-plus-preset__sale-date tec-tickets-plus-preset__sale-start">
			<label class="tec-tickets-plus-preset__field-label"><?php esc_html_e( 'Start Sale', 'event-tickets-plus' ); ?></label>
			<select name="preset[sale_start_logic][type]" class="tec-tickets-plus-preset__sale-type-select">
				<option value="published" <?php selected( ( $preset_data['sale_start_logic']['type'] ?? '' ) === 'now' ); ?>><?php esc_html_e( 'Immediately', 'event-tickets-plus' ); ?></option>
				<option value="relative" <?php selected( ( $preset_data['sale_start_logic']['type'] ?? '' ) === 'relative' ); ?>><?php esc_html_e( 'On a relative date', 'event-tickets-plus' ); ?></option>
			</select>
			<div class="tec-tickets-plus-preset__sale-relative tribe-common-a11y-hidden">
				<span class="tec-tickets-plus-preset__sale-info tec-tickets-plus-preset__sale-info-start"><?php esc_html_e( 'starts', 'event-tickets-plus' ); ?></span>
				<label for="preset[sale_start_logic][length]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Length', 'event-tickets-plus' ); ?></label>
				<input type="number" name="preset[sale_start_logic][length]" required min="1" value="<?php echo esc_attr( $start_sale_length ); ?>" class="tec-tickets-plus-preset__sale-length">
				<label for="preset[sale_start_logic][period]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Period', 'event-tickets-plus' ); ?></label>
				<select name="preset[sale_start_logic][period]" class="tec-tickets-plus-preset__sale-period">
					<option value="hour" <?php selected( $start_sale_period, 'hour' ); ?>><?php esc_html_e( 'Hours', 'event-tickets-plus' ); ?></option>
					<option value="day" <?php selected( $start_sale_period, 'day' ); ?>><?php esc_html_e( 'Days', 'event-tickets-plus' ); ?></option>
					<option value="week" <?php selected( $start_sale_period, 'week' ); ?>><?php esc_html_e( 'Weeks', 'event-tickets-plus' ); ?></option>
				</select>
				<label for="preset[sale_start_logic][direction]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Direction', 'event-tickets-plus' ); ?></label>
				<span class="tec-tickets-plus-preset__sale-info tec-tickets-plus-preset__sale-info-before"><?php esc_html_e( 'before', 'event-tickets-plus' ); ?></span>
				<label for="preset[sale_start_logic][relative_to]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Relative to', 'event-tickets-plus' ); ?></label>
				<select name="preset[sale_start_logic][relative_to]" class="tec-tickets-plus-preset__sale-relative-to">
					<option value="start" <?php selected( $start_relative_to, 'start' ); ?>><?php esc_html_e( 'Event start', 'event-tickets-plus' ); ?></option>
					<option value="end" <?php selected( $start_relative_to, 'end' ); ?>><?php esc_html_e( 'Event end', 'event-tickets-plus' ); ?></option>
				</select>
			</div>
		</div>

		<div class="tec-tickets-plus-preset__sale-date tec-tickets-plus-preset__sale-end">
			<label class="tec-tickets-plus-preset__field-label"><?php esc_html_e( 'End Sale', 'event-tickets-plus' ); ?></label>
			<select name="preset[sale_end_logic][type]" class="tec-tickets-plus-preset__sale-type-select">
				<option value="start" <?php selected( ( $preset_data['sale_end_logic']['type'] ?? '' ) === 'start' ); ?>><?php esc_html_e( 'When event starts', 'event-tickets-plus' ); ?></option>
				<option value="relative" <?php selected( ( $preset_data['sale_end_logic']['type'] ?? '' ) === 'relative' ); ?>><?php esc_html_e( 'On a relative date', 'event-tickets-plus' ); ?></option>
			</select>
			<div class="tec-tickets-plus-preset__sale-relative tribe-common-a11y-hidden">
				<span class="tec-tickets-plus-preset__sale-info tec-tickets-plus-preset__sale-info-start"><?php esc_html_e( 'ends', 'event-tickets-plus' ); ?> </span>
				<label for="preset[sale_end_logic][length]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Length', 'event-tickets-plus' ); ?></label>
				<input type="number" name="preset[sale_end_logic][length]" required min="1" value="<?php echo esc_attr( $end_sale_length ); ?>" class="tec-tickets-plus-preset__sale-length">
				<label for="preset[sale_end_logic][period]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Period', 'event-tickets-plus' ); ?></label>
				<select name="preset[sale_end_logic][period]" class="tec-tickets-plus-preset__sale-period">
					<option value="hour" <?php selected( $end_sale_period, 'hour' ); ?>><?php esc_html_e( 'Hours', 'event-tickets-plus' ); ?></option>
					<option value="day" <?php selected( $end_sale_period, 'day' ); ?>><?php esc_html_e( 'Days', 'event-tickets-plus' ); ?></option>
					<option value="week" <?php selected( $end_sale_period, 'week' ); ?>><?php esc_html_e( 'Weeks', 'event-tickets-plus' ); ?></option>
				</select>
				<label for="preset[sale_end_logic][direction]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Direction', 'event-tickets-plus' ); ?></label>
				<span class="tec-tickets-plus-preset__sale-info tec-tickets-plus-preset__sale-info-before"><?php esc_html_e( 'before', 'event-tickets-plus' ); ?></span>
				<label for="preset[sale_end_logic][relative_to]" class="tec-tickets-plus-preset__field-label screen-reader-text"><?php esc_html_e( 'Relative to', 'event-tickets-plus' ); ?></label>
				<select name="preset[sale_end_logic][relative_to]" class="tec-tickets-plus-preset__sale-relative-to">
					<option value="end" <?php selected( $end_relative_to, 'end' ); ?>><?php esc_html_e( 'Event end', 'event-tickets-plus' ); ?></option>
					<option value="start" <?php selected( $end_relative_to, 'start' ); ?>><?php esc_html_e( 'Event start', 'event-tickets-plus' ); ?></option>
				</select>
			</div>
		</div>
	</div>
</div>
