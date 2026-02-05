<?php
/**
 * Template for showing Attendee Collection settings for the Ticket Presets form.
 *
 * @since 6.6.0
 *
 * @var \TEC\Tickets_Plus\Ticket_Presets\Admin\Admin_Template $this          [Global] Template object.
 * @var null|int                                              $preset_id     [Global] The preset ID.
 * @var array                                                 $iac_options   [Global] Available IAC options.
 * @var string                                                $selected      [Global] Current IAC option for the preset.
 */
	
defined( 'ABSPATH' ) || exit;
?>

<section id="ticket_form_attendee_collection" class="attendee-collection">
	<h3 class="accordion-header"><?php esc_html_e( 'Attendee Collection', 'event-tickets-plus' ); ?></h3>
	
	<div class="accordion-content">
		<p><?php esc_html_e( 'Select the default way to sell tickets. Enabling Individual Attendee Collection will allow purchasers to enter a name and email for each ticket.', 'event-tickets-plus' ); ?></p>
	
		<?php foreach ( $iac_options as $value => $label ) : ?>
			<div class="input_block">
				<input
					type="radio"
					name="preset[iac_setting]"
					class="tribe-tickets-iac-setting"
					id="ticket_iac_setting_<?php echo esc_attr( sanitize_title_with_dashes( $value ) ); ?>"
					value="<?php echo esc_attr( $value ); ?>"
					<?php checked( $value, $selected ); ?>
				/>
	
				<label for="ticket_iac_setting_<?php echo esc_attr( sanitize_title_with_dashes( $value ) ); ?>">
					<?php echo esc_html( $label ); ?>
				</label>
			</div>
		<?php endforeach; ?>
	
		<div id="attendee_collection_fields">
			<?php
			/**
			 * Allows for the insertion of additional content into the ticket edit form - Attendee Collection section.
			 *
			 * @since 5.1.0
			 */
			$this->do_entry_point( 'additional_fields' );
			?>
		</div>
	</div>
</section><!-- #ticket_form_attendee-collection -->
