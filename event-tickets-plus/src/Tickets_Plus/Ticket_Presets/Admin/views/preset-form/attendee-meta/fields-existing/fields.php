<?php
/**
 * Admin Attendee Meta: Active fields.
 *
 * @since 6.6.0
 *
 * @version 6.6.0
 *
 * @var Tribe__Tickets_Plus__Admin__Views                  $this          [Global] Template object.
 * @var WP_Post[]                                          $templates     [Global] Array with the saved fieldsets.
 * @var array                                              $meta          [Global] Array containing the meta.
 * @var null|int                                           $preset_id     [Global] The ticket ID.
 * @var bool                                               $fieldset_form  [Global] True if in fieldset form context.
 * @var TEC\Tickets_Plus\Ticket_Presets\Meta               $meta_object   [Global] The meta object.
 * @var Tribe__Tickets_Plus__Meta__Field__Abstract_Field[] $active_meta   [Global] Array containing objects of active meta.
 */

defined( 'ABSPATH' ) || exit;
?>
<div id="tribe-tickets-attendee-sortables" class="sortable ui-sortable">
	<?php
	$foo = '';
	foreach ( $active_meta as $meta_field ) {
		$field = $meta_object->generate_field( $preset_id, $meta_field->type, (array) $meta_field );

		// Outputs HTML input field - no escaping.
		echo $field->render_admin_field(); // phpcs:ignore
	}
	?>
</div>
