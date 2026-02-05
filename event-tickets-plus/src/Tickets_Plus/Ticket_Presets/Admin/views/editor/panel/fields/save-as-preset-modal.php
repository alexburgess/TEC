<?php
/**
 * Modal: Save as Preset.
 *
 * @since 6.6.0
 *
 * @var $post_id int The post ID.
 * @var $ticket_id int The ticket ID.
 */

use TEC\Tickets_Plus\Ticket_Presets\Admin\Admin_Template;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
?>

<dialog id="tec-tickets-plus-preset-save-modal" class="tec-tickets-plus-preset-modal">
	<div class="tec-tickets-plus-preset-modal__content">
		<div class="tec-tickets-plus-preset-modal__header">
			<h2 id="modal-title"><?php esc_html_e( 'New Ticket Preset', 'event-tickets-plus' ); ?></h2>
			<button type="button" class="tec-tickets-plus-preset-modal__close" aria-label="<?php esc_attr_e( 'Close modal', 'event-tickets-plus' ); ?>">&times;</button>
		</div>
		<div class="tec-tickets-plus-preset-modal__body">
			<section class="tec-tickets-plus-preset-modal__ticket-view">
				<section class="tec-tickets-plus-preset-modal__ticket-details">
					<div class="tec-tickets-plus-preset-modal__ticket-title"></div>
					<div class="tec-tickets-plus-preset-modal__ticket-description"></div>
				</section>
				<section class="tec-tickets-plus-preset-modal__ticket-price">
					<div class="tec-tickets-plus-preset-modal__ticket-amount"></div>
					<div class="tec-tickets-plus-preset-modal__ticket-capacity"></div>
				</section>
			</section>
			<p id="modal-description"><?php esc_html_e( 'We need some additional details in order to create a Preset from this ticket.  You can access your saved Ticket Presets in the sidebar under All Tickets.', 'event-tickets-plus' ); ?></p>
			<div class="tec-tickets-plus-preset-modal__fields">
				<input type="hidden" name="preset[post_id]" value="<?php echo esc_attr( $post_id ); ?>">
				<input type="hidden" name="preset[ticket_id]" class="tec-tickets-plus-preset-modal__ticket-id" value="<?php echo esc_attr( $ticket_id ); ?>">
				<div class="tec-tickets-plus-preset-modal__field">
					<label for="preset-name" class="tec-tickets-plus-preset-modal__label required"><?php esc_html_e( 'Preset Name', 'event-tickets-plus' ); ?></label>
					<input type="text" id="preset-name" name="preset[name]" class="tec-tickets-plus-preset-modal__input">
				</div>
				<?php
					$admin_templates = tribe( Admin_Template::class );
					$admin_templates->template( 'preset-form/sale-period', [] );
				?>
				<?php
				/**
				 * Allows for the insertion of additional fields into the save as preset modal.
				 *
				 * @since 6.6.0
				 */
				do_action( 'tec_tickets_plus_save_as_preset_modal_fields' );
				?>
			</div>
			<div class="tec-tickets-plus-preset-modal__footer">
				<button type="button" id="tec-tickets-plus-preset-modal__cancel-button" class="button button-secondary"><?php esc_html_e( 'Cancel', 'event-tickets-plus' ); ?></button>
				<button type="button" id="tec-tickets-plus-preset-modal__save-button" class="button button-primary"><?php esc_html_e( 'Create Preset', 'event-tickets-plus' ); ?></button>
			</div>
		</div>
	</div>
</dialog>
