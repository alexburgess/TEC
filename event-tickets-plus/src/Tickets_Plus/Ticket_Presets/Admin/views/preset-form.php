<?php
/**
 * Ticket Preset form view.
 *
 * @since 6.6.0
 *
 * @var Admin_Template $this
 * @var string $page_title                                              The title for the page.
 * @var WP_Post[] $templates                                            Array with the saved field sets.
 * @var array $meta                                                     Array containing the meta.
 * @var null|Ticket_Preset $preset                                      The preset object.
 * @var int $preset_id                                                  The preset ID.
 * @var array<string,mixed> $preset_data                                The preset data.
 * @var bool $fieldset_form                                             True if in fieldset form context.
 * @var Tribe__Tickets_Plus__Meta $meta_object                          The meta object.
 * @var Tribe__Tickets_Plus__Meta__Field__Abstract_Field[] $active_meta Array containing objects of active meta.
 * @var string $form_action                                             The form action.
 */

defined( 'ABSPATH' ) || exit;

use TEC\Tickets_Plus\Ticket_Presets\Admin\Admin_Template;
use TEC\Tickets_Plus\Ticket_Presets\Models\Ticket_Preset;
use Tribe\Tickets\Plus\Attendee_Registration\IAC;
?>
<div id="poststuff" class="wrap">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			id="tec-tickets-plus-preset-form" class="tec-tickets-plus-preset__form">
		<input type="hidden" name="preset_id" value="<?php echo esc_attr( $preset_id ); ?>">
		<input type="hidden" name="action" value="tec_tickets_save_preset">
		<input type="hidden" name="form_action" value="<?php echo esc_attr( $form_action ); ?>">
		<?php wp_nonce_field( 'save_ticket_preset', 'ticket_preset_nonce' ); ?>
		<section class="tec-tickets-plus-preset__form-section">
			<div class="tec-tickets-plus-preset__field preset_name">
				<div class="tec-tickets-plus-preset__field-wrapper">
					<input
						type="text"
						id="preset-name"
						name="preset[name]"
						required
						value="<?php echo esc_attr( $preset_data['name'] ?? '' ); ?>"
						placeholder="<?php echo esc_attr__( 'The Preset Name', 'event-tickets-plus' ); ?>"
					>
					<p class="description"><?php esc_html_e( 'For admin purposes only.  This name will not be displayed to purchasers.', 'event-tickets-plus' ); ?></p>
				</div>
			</div>
		</section>
		<section class="tec-tickets-plus-preset__form-section">
			<div class="tec-tickets-plus-preset__ticket_details-header">
				<span class="tec-tickets-plus-preset__ticket_details__label">
					<?php esc_html_e( 'Ticket Details', 'event-tickets-plus' ); ?>
				</span>
			</div>
			<div class="tec-tickets-plus-preset__field">
				<label
					class="tec-tickets-plus-preset__field-label required"
					for="preset-ticket-name"><?php esc_html_e( 'Ticket Name', 'event-tickets-plus' ); ?>
				</label>
				<div class="tec-tickets-plus-preset__field-wrapper">
					<input
						type="text"
						id="preset-ticket-name"
						name="preset[ticket_name]"
						required
						value="<?php echo esc_attr( $preset_data['ticket_name'] ?? '' ); ?>">
				</div>
			</div>
			<div class="tec-tickets-plus-preset__field">
				<label class="tec-tickets-plus-preset__field-label"
						for="preset-description"><?php esc_html_e( 'Description', 'event-tickets-plus' ); ?>
				</label>
				<div class="tec-tickets-plus-preset__field-wrapper">
					<input
						id="preset-description"
						name="preset[description]"
						type="text"
						value="<?php echo esc_attr( $preset_data['description'] ?? '' ); ?>"
					>
				</div>
			</div>

			<div class="tec-tickets-plus-preset__field">
				<label
					class="tec-tickets-plus-preset__field-label required"
					for="preset-cost"><?php esc_html_e( 'Ticket Price', 'event-tickets-plus' ); ?>
				</label>
				<div class="tec-tickets-plus-preset__field-wrapper">
					<?php
					$display_cost = $preset_data['cost'] ?? '';
					?>
					<input
						type="text"
						id="preset-cost"
						name="preset[cost]"
						class="tec-tickets-plus-preset__cost-field"
						required
						value="<?php echo esc_attr( $display_cost ); ?>">
				</div>
			</div>

			<input
				type="hidden"
				name="preset[ticket_type]"
				value="default"
			>

			<?php $this->template( 'preset-form/capacity', [ 'preset_data' => $preset_data ?? [] ] ); ?>

			<?php $this->template( 'preset-form/sale-period', [ 'preset_data' => $preset_data ?? [] ] ); ?>
			<div class="accordion">
				<?php
				/** @var IAC $iac */
				$iac = tribe( 'tickets-plus.attendee-registration.iac' );

				$iac_options = $iac->get_iac_setting_options();
				$iac_default = $iac->get_default_iac_setting();
				$selected    = $preset_data['iac_setting'] ?? $iac_default;

				// If showing a new ticket form, use the default IAC setting.
				if ( empty( $preset_id ) ) {
					$selected = $iac_default;
				}

				$this->template(
					'preset-form/attendee-collection',
					[
						'preset_id'   => $preset_id,
						'iac_options' => $iac_options,
						'iac_default' => $iac_default,
						'selected'    => $selected,
					]
				);
				?>
				<section class="tec-tickets-plus-preset__attendee_information">
					<h3 class="accordion-header tec-tickets-plus-preset__attendee_information-title">
						<?php esc_html_e( 'Attendee Information', 'event-tickets-plus' ); ?>
					</h3>
					<div class="accordion-content">
						<?php
						$this->template(
							'preset-form/attendee-meta',
							[
								'active_meta'   => $active_meta,  // Array containing objects of active meta.
								'fieldset_form' => $fieldset_form, // True if in fieldset form context.
								'meta_object'   => $meta_object,  // The meta object.
								'meta'          => $meta,         // Array containing the meta.
								'templates'     => $templates,    // Array with the saved field sets.
								'preset_id'     => $preset_id,    // The preset ID.
							]
						);
						?>
					</div>
				</section>
			</div>
		</section>
		<div class="tec-tickets-plus-preset__submit">
			<button
				type="submit"
				class="button button-primary">
				<?php esc_html_e( 'Save Preset', 'event-tickets-plus' ); ?>
			</button>
		</div>
	</form>
</div>
