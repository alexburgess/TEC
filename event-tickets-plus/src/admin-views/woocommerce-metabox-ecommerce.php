<?php
$do_ticket_admin_link  = ! empty( $ticket->admin_link );
/**
 * Filter the display of eCommerce links for the ticket
 *
 * @since 4.6
 *
 * @param boolean true/false - show/hide
 */
if ( apply_filters( 'tribe_events_tickets_woo_display_ecommerce_links', true ) && ( $do_ticket_admin_link ) ) : ?>
	<div
		id="ecommerce"
		class="<?php $this->tr_class(); ?> input_block tribe-dependent"
		data-depends="#ticket_id"
		data-condition-is-numeric
	>
		<label class="ticket_form_label ticket_form_left"><?php esc_html_e( 'Ecommerce:', 'event-tickets-plus' ); ?></label>
		<div class="ticket_form_right">
			<?php if ( $do_ticket_admin_link ) : ?>
				<a href="<?php echo esc_url( $ticket->admin_link ); ?> "><?php esc_html_e( 'Edit ticket in WooCommerce', 'event-tickets-plus' ); ?></a>
			<?php endif; ?>
		</div>
	</div>
	<?php
endif;
