<?php
/**
 * Ticket Presets panel for Classic Editor.
 *
 * This is prepended to the ticket form panel
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin\Views
 *
 * @var int         $post_id The current post ID.
 * @var int         $ticket_id The current ticket ID.
 * @var array<mixed> $presets The available presets.
 */

// If we're not in "add new" mode, don't prepend to the original panel.
if ( ! empty( $ticket_id ) ) {
	return;
}

$classes = [
	'tec-tickets-plus-presets' => true,
	'active'                   => true, // @todo: change to a conditional
];
?>
<div id="tec-tickets-plus-presets" <?php tribe_classes( $classes ); ?>>
	<section class="tec-tickets-plus-presets__form">
		<div class="tec-tickets-plus-presets__input-block-header">
			<label for="ticket_preset" class="tec-tickets-plus-presets__label">
				<?php esc_html_e( 'Ticket Preset:', 'event-tickets-plus' ); ?>
			</label>
		</div>
		<div class="tec-tickets-plus-presets__input-block">
			<select
				id="ticket-preset"
				class="ticket_field"
				name="ticket_preset"
			>
				<option value=""><?php esc_html_e( 'Select a ticket preset', 'event-tickets-plus' ); ?></option>
				<?php foreach ( $presets as $preset ) : ?>
					<option value="<?php echo esc_attr( $preset['id'] ); ?>">
						<?php echo esc_html( $preset['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<p> Or <button id="tec-tickets-plus-presets-edit" class="button-link tec-tickets-plus-preset__button-edit"><?php esc_html_e( 'Create a new ticket for this event', 'event-tickets-plus' ); ?></button></p>
		</div>
		<div class="tec-tickets-plus-presets__actions">
				<button

					class="button-primary tec-tickets-plus-presets__button-add"
					disabled
				>
					<?php esc_html_e( 'Add Ticket', 'event-tickets-plus' ); ?>
				</button>

				<button

					class="button-link tec-tickets-plus-presets__button-review"
					disabled
				>
					<?php esc_html_e( 'Review Ticket', 'event-tickets-plus' ); ?>
				</button>

				<button
					id="tec-tickets-plus-presets-cancel"
					class="button-link tec-tickets-plus-presets__button-cancel"
				>
					<?php esc_html_e( 'Cancel', 'event-tickets-plus' ); ?>
				</button>
			</div>
	</section>
</div>
