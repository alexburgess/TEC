<?php
/**
 * Admin Attendee Meta: Active fields section.
 *
 * @since 5.2.2
 *
 * @version 5.2.2
 *
 * @var \TEC\Tickets_Plus\Ticket_Presets\Admin\Admin_Template $this          [Global] Template object.
 * @var WP_Post[]                                             $templates     [Global] Array with the saved fieldsets.
 * @var array                                                 $meta          [Global] Array containing the meta.
 * @var null|int                                              $preset_id     [Global] The preset ID.
 * @var bool                                                  $fieldset_form [Global] True if in fieldset form context.
 * @var \TEC\Tickets_Plus\Ticket_Presets\Meta                 $meta_object   [Global] The meta object.
 * @var Tribe__Tickets_Plus__Meta__Field__Abstract_Field[]    $active_meta   [Global] Array containing objects of active meta.
 */
	
defined( 'ABSPATH' ) || exit;
?>
<?php $this->template( 'preset-form/attendee-meta/fields-existing/iac/notice' ); ?>

<h5><?php esc_html_e( 'Active Fields:', 'event-tickets-plus' ); ?></h5>

<?php $this->template( 'preset-form/attendee-meta/fields-existing/iac/fields' ); ?>

<?php $this->template( 'preset-form/attendee-meta/fields-existing/fieldset' ); ?>

<?php $this->template( 'preset-form/attendee-meta/fields-existing/fields' ); ?>

<?php $this->template( 'preset-form/attendee-meta/fields-existing/fieldset-save' ); ?>

<input type="hidden" name="tribe-tickets-input[0]" value="">
<input type="hidden" name="preset[fieldset_id]" id="selected-fieldset-id" value="<?php echo esc_attr( $preset_data['fieldset_id'] ?? '' ); ?>">
