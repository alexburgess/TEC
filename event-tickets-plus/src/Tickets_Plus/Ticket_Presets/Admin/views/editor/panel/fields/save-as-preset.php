<?php
/**
 * Save as preset checkbox template.
 *
 * @since 6.6.0
 *
 * @var int $post_id   The post ID.
 * @var int $ticket_id The ticket ID.
 * @var string $provider_name The provider class name.
 *
 * @version 6.6.0
 */

if ( empty( $ticket_id ) ) {
	return;
}

$dependency = '#' . $provider_name . '_global';
?>
<div class="tribe-dependent" data-depends="<?php echo esc_attr( $dependency ); ?>" data-condition-not-checked>
	<label class="ticket_form_save_as_preset" for="tec-tickets-plus-preset__save-checkbox">
		<input
			type="checkbox"
			id="tec-tickets-plus-preset__save-checkbox"
			name="ticket_form_save_as_preset"
			class="tec-tickets__save-as-preset-checkbox"
			value="1"
		/>
		<?php esc_html_e( 'Save as preset', 'event-tickets-plus' ); ?>
	</label>
</div>
