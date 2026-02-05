<?php
/**
 * @var Tribe\Tickets\Plus\Editor\Settings\Data\Capacity_Table $capacity_table
 */
/** @var Tribe__Tickets__Tickets_Handler $tickets_handler */
$tickets_handler = tribe( 'tickets.handler' );

$default_type_capacity      = $capacity_table->get_default_ticket_capacity();
$default_rsvp_type_capacity = $capacity_table->get_default_rsvp_type_capacity();
$other_type_tickets         = $capacity_table->get_other_type_capacity();
$total_capacity             = $capacity_table->get_total_capacity();
?>
<table id="tribe_expanded_capacity_table"
		class="eventtable ticket_list tribe-tickets-editor-capacity-table eventForm tribe-tickets-editor-table striped fixed">
	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-shared">
		<td><?php esc_html_e( 'Shared Capacity:', 'event-tickets-plus' ); ?></td>
		<td>
			<input
				id="settings_global_capacity_edit"
				class="settings_field"
				size="8"
				name="tribe-tickets[settings][event_capacity]"
				value="<?php echo esc_attr( $default_type_capacity['shared']['capacity'] ); ?>"
				aria-label="<?php esc_attr_e( 'Global Shared Capacity field', 'event-tickets-plus' ); ?>"
				<?php echo esc_attr( empty( $default_type_capacity['shared']['capacity'] ) ? 'disabled' : '' ); ?>
			/>
			<button
				id="global_capacity_edit_button"
				class="global_capacity_edit_button tribe-button-icon tribe-button-icon-edit"
				title="<?php esc_attr_e( 'Edit Shared Capacity', 'event-tickets-plus' ); ?>"
				aria-controls="settings_global_capacity_edit"
				<?php echo esc_attr( empty( $default_type_capacity['shared']['capacity'] ) ? 'disabled' : '' ); ?>
			></button>
		</td>
		<td>
			<?php if ( ! empty( $default_type_capacity['shared']['labels'] ) ) : ?>
				<span
					class="tribe_capacity_table_ticket_list"><?php echo esc_html( implode( ', ', $default_type_capacity['shared']['labels'] ) ); ?></span>
			<?php endif; ?>
		</td>
	</tr>
	<?php if ( empty( $default_type_capacity['independent'] ) ) : ?>
		<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-independent">
			<td><?php esc_html_e( 'Independent Capacity:', 'event-tickets-plus' ); ?></td>
			<td colspan="2">0</td>
		</tr>
	<?php endif; ?>
	<?php foreach ( $default_type_capacity['independent'] as $index => $ticket ) : ?>
		<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-independent"
			data-capacity="<?php echo esc_attr( $ticket['capacity'] ); ?>">
			<td>
				<?php
				if ( 0 === $index ) {
					esc_html_e( 'Independent Capacity:', 'event-tickets-plus' );
				}
				?>
			</td>
			<td>
				<?php tribe_tickets_get_readable_amount( $ticket['capacity'], null, true ); ?>
			</td>
			<td>
				<span class="tribe_capacity_table_ticket_list"><?php echo esc_html( $ticket['label'] ); ?></span>
			</td>
		</tr>
	<?php endforeach; ?>

	<?php if ( ! empty( $default_type_capacity['unlimited'] ) ) : ?>
		<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-unlimited">
			<td><?php esc_html_e( 'Unlimited Capacity:', 'event-tickets-plus' ); ?></td>
			<td>
				<?php echo esc_html( $tickets_handler->unlimited_term ); ?>
			</td>
			<td>
				<span class="tribe_capacity_table_ticket_list">
					<?php echo esc_html( implode( ', ', $default_type_capacity['unlimited'] ) ); ?>
				</span>
			</td>
		</tr>
	<?php endif; ?>

	<?php if ( ! empty( $default_rsvp_type_capacity ) ) : ?>
		<?php foreach ( $default_rsvp_type_capacity['independent'] as $index => $ticket ) : ?>
			<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-rsvp"
				data-capacity="<?php echo esc_attr( $ticket['capacity'] ); ?>">
				<td>
					<?php
					if ( 0 === $index ) {
						esc_html_e( 'RSVPs:', 'event-tickets-plus' );
					}
					?>
				</td>
				<td>
					<?php tribe_tickets_get_readable_amount( $ticket['capacity'], null, true ); ?>
				</td>
				<td>
					<span class="tribe_capacity_table_ticket_list"><?php echo esc_html( $ticket['label'] ); ?></span>
				</td>
			</tr>
		<?php endforeach; ?>
		<?php if ( ! empty( $default_rsvp_type_capacity['unlimited'] ) ) : ?>
			<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-rsvp-unlimited">
				<td></td>
				<td>
					<?php echo esc_html( $tickets_handler->unlimited_term ); ?>
				</td>
				<td>
				<span class="tribe_capacity_table_ticket_list">
					<?php echo esc_html( implode( ', ', $default_rsvp_type_capacity['unlimited'] ) ); ?>
				</span>
				</td>
			</tr>
		<?php endif; ?>
	<?php endif; ?>

	<?php
	if ( ! empty( $other_type_tickets ) ) :
		$label_type_displayed = false;
		?>
		<?php foreach ( $other_type_tickets as $type => $tickets ) : ?>
			<?php if ( ! empty( $tickets['shared']['capacity'] ) ) : ?>
			<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-<?php echo esc_attr( $type ); ?>"
				data-capacity="<?php echo esc_attr( $tickets['shared']['capacity'] ); ?>">
				<td>
					<?php
					if ( ! $label_type_displayed ) {
						esc_html_e( $capacity_table->get_label_for_type( $type ), 'event-tickets-plus' );
						$label_type_displayed = true;
					}
					?>
				</td>
				<td>
					<?php tribe_tickets_get_readable_amount( $tickets['shared']['capacity'], null, true ); ?>
				</td>
				<td>
					<span class="tribe_capacity_table_ticket_list">
						<?php echo esc_html( implode( ', ', $tickets['shared']['labels'] ) ); ?>
					</span>
				</td>
			</tr>
		<?php endif; ?>
			<?php if ( ! empty( $tickets['independent'] ) ) : ?>
				<?php foreach ( $tickets['independent'] as $ticket ) : ?>
				<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-<?php echo esc_attr( $type ); ?>"
					data-capacity="<?php echo esc_attr( $ticket['capacity'] ); ?>">
					<td>
						<?php
						if ( ! $label_type_displayed ) {
							esc_html_e( $capacity_table->get_label_for_type( $type ), 'event-tickets-plus' );
							$label_type_displayed = true;
						}
						?>
					</td>
					<td>
						<?php echo esc_html( $ticket['capacity'] ); ?>
					</td>
					<td>
						<span class="tribe_capacity_table_ticket_list">
							<?php echo esc_html( $ticket['label'] ); ?>
						</span>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>

			<?php if ( ! empty( $tickets['unlimited'] ) ) : ?>
			<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-<?php echo esc_attr( $type ); ?>">
				<td>
					<?php
					if ( ! $label_type_displayed ) {
						esc_html_e( $capacity_table->get_label_for_type( $type ), 'event-tickets-plus' );
						$label_type_displayed = true;
					}
					?>
				</td>
				<td>
					<?php tribe_tickets_get_readable_amount( - 1, null, true ); ?>
				</td>
				<td>
					<span class="tribe_capacity_table_ticket_list">
						<?php echo esc_html( implode( ', ', $tickets['unlimited'] ) ); ?>
					</span>
				</td>
			</tr>
		<?php endif; ?>
	<?php endforeach; ?>
	<?php endif; ?>

	<tr class="tribe-tickets-editor-table-row tribe-tickets-editor-table-row-capacity-total"
		data-total-capacity="<?php echo esc_attr( $total_capacity ); ?>">
		<td><?php esc_html_e( 'Total Capacity:', 'event-tickets-plus' ); ?></td>
		<td colspan="2" class="tribe-tickets-editor-total-capacity">
			<?php
			if ( ! $total_capacity ) {
				esc_html_e( 'Create a ticket to add event capacity', 'event-tickets-plus' );
			} else {
				tribe_tickets_get_readable_amount( $total_capacity, null, true );
			}
			?>
		</td>
	</tr>
</table>