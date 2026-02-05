<?php
/**
 * Manages meta data for Ticket Presets.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */

namespace TEC\Tickets_Plus\Ticket_Presets;

use Tribe__Tickets_Plus__Meta__Field__Abstract_Field;
use Tribe__Tickets_Plus__Meta__Fieldset;
use TEC\Tickets_Plus\Ticket_Presets\Models\Ticket_Preset;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;
use Tribe\Tickets\Plus\Attendee_Registration\IAC;
use WP_Post;

/**
 * Class Meta.
 *
 * @since 6.6.0
 */
class Meta {
	/**
	 * The meta key for the preset.
	 *
	 * @since 6.6.0
	 */
	const META_KEY = '_tribe_tickets_meta_template';

	/**
	 * The key to use for when IAC is off.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public static $none_key = 'none';

	/**
	 * The key to use for when IAC is allowed.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public static $allowed_key = 'allowed';

	/**
	 * The key to use for when IAC is required.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public static $required_key = 'required';

	/**
	 * The meta fieldset.
	 *
	 * @since 6.6.0
	 *
	 * @var Tribe__Tickets_Plus__Meta__Fieldset
	 */
	private $meta_fieldset;

	/**
	 * The IAC instance.
	 *
	 * @since 6.6.0
	 *
	 * @var IAC
	 */
	public $iac;

	/**
	 * The IAC defaults.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public $iac_defaults;

	/**
	 * Constructor.
	 *
	 * @since 6.6.0
	 */
	public function __construct() {
		// Set up AJAX handlers for the fieldset preview.
		add_action( 'wp_ajax_tec-tickets-plus-presets-load-saved-fields', [ $this, 'ajax_render_saved_fields' ] );

		$this->iac          = tribe( 'tickets-plus.attendee-registration.iac' );
		$this->iac_defaults = $this->iac->get_default_iac_setting();

		// We want these to be duplicates.
		self::$none_key     = IAC::NONE_KEY;
		self::$allowed_key  = IAC::ALLOWED_KEY;
		self::$required_key = IAC::REQUIRED_KEY;
	}

	/**
	 * Get the list of meta field objects for the preset.
	 *
	 * @since 6.6.0
	 *
	 * @param int $preset_id The preset ID.
	 *
	 * @return Tribe__Tickets_Plus__Meta__Field__Abstract_Field[] The list of meta field objects for the preset.
	 */
	public function get_iac_by_preset( $preset_id ): array {
		if ( empty( $preset_id ) ) {
			return [];
		}

		if ( $preset_id instanceof Ticket_Preset ) {
			$preset_id = $preset_id->id;
		}

		if ( ! is_numeric( $preset_id ) ) {
			return [];
		}

		$preset = tribe( Ticket_Presets::class )->find_by_id( $preset_id );

		if ( ! $preset instanceof Ticket_Preset ) {
			return [];
		}

		$field_meta = $preset->get_iac();
		$fields     = [];

		foreach ( $field_meta as $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}

			$field_object = $this->generate_field( $preset_id, $field['type'], $field );

			if ( ! $field_object ) {
				continue;
			}

			$fields[] = $field_object;
		}

		/**
		 * Allow filtering the list of meta fields for a ticket.
		 *
		 * @param Tribe__Tickets_Plus__Meta__Field__Abstract_Field[] $fields    List of meta field objects for the preset.
		 * @param int                                                $preset_id The preset ID.
		 */
		return apply_filters( 'event_tickets_plus_meta_fields_by_preset', $fields, $preset_id );
	}

	/**
	 * Get the saved IAC fieldset.
	 *
	 * @since 6.6.0
	 *
	 * @param int $fieldset_id The fieldset ID.
	 *
	 * @return string The saved IAC fieldset.
	 */
	public function get_saved_iac_fieldset( $fieldset_id ): string {
		if ( empty( $fieldset_id ) ) {
			return '';
		}

		$fieldset = get_post( $fieldset_id );

		if ( ! $fieldset instanceof WP_Post ) {
			return '';
		}

		$field_meta = get_post_meta( $fieldset->ID, self::META_KEY, true );

		if ( empty( $field_meta ) ) {
			return '';
		}

		$field_meta = maybe_unserialize( $field_meta );

		$fields = '';

		foreach ( $field_meta as $field ) {
			if ( empty( $field['type'] ) ) {
				continue;
			}

			$field_object = $this->generate_field( null, $field['type'], $field );

			if ( ! $field_object ) {
				continue;
			}

			$fields .= $field_object->render_admin_field();
		}

		/**
		 * Allow filtering the list of meta fields for a ticket.
		 *
		 * @param Tribe__Tickets_Plus__Meta__Field__Abstract_Field[] $fields    List of meta field objects for the preset.
		 */
		return $fields;
	}

	/**
	 * Generates a field object.
	 *
	 * @since 4.1 Method introduced.
	 * @since 5.1.0 Added support for filtering the field class.
	 *
	 * @param int    $preset_id ID of preset post the field is attached to.
	 * @param string $type      Type of field being generated.
	 * @param array  $data      Field settings for the field.
	 *
	 * @return ?Tribe__Tickets_Plus__Meta__Field__Abstract_Field child class
	 */
	public function generate_field( $preset_id, $type, $data = [] ): ?object {
		$class = 'Tribe__Tickets_Plus__Meta__Field__' . ucwords( $type );

		/**
		 * Allow filtering the field class used so custom field classes can be supported.
		 *
		 * @since 5.1.0
		 *
		 * @param string $class Class name to use for the field.
		 * @param string $type  Type of field being generated.
		 * @param array  $data  Field settings for the field.
		 */
		$class = apply_filters( 'tribe_tickets_plus_meta_field_class', $class, $type, $data );

		if ( ! class_exists( $class ) ) {
			return null;
		}

		return new $class( $preset_id, $data );
	}

	/**
	 * Checks if a preset has meta fields.
	 *
	 * @since 6.6.0
	 *
	 * @param int $preset_id The preset ID.
	 *
	 * @return bool
	 */
	public function preset_has_meta( $preset_id ): bool {
		if ( empty( $preset_id ) ) {
			return false;
		}

		if ( $preset_id instanceof Ticket_Preset ) {
			$preset_id = $preset_id->id;
		}

		if ( ! is_numeric( $preset_id ) ) {
			return false;
		}

		$preset = tribe( Ticket_Presets::class )->find_by_id( $preset_id );

		if ( ! $preset instanceof Ticket_Preset ) {
			return false;
		}

		return ! empty( $preset->get_iac() );
	}

	/**
	 * Returns the meta fieldset.
	 *
	 * @since 6.6.0
	 *
	 * @return Tribe__Tickets_Plus__Meta__Fieldset
	 */
	public function meta_fieldset(): Tribe__Tickets_Plus__Meta__Fieldset {
		if ( ! $this->meta_fieldset ) {
			$this->meta_fieldset = new Tribe__Tickets_Plus__Meta__Fieldset();
		}

		return $this->meta_fieldset;
	}

	/**
	 * AJAX handler for loading saved fields from a fieldset.
	 *
	 * @since 6.6.0
	 */
	public function ajax_render_saved_fields(): void {
		$response = [
			'success' => false,
			'data'    => [
				'html' => '',
			],
		];

		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'tribe_ticket_attendee_info_nonce' ) ) {
			$response['data']['error'] = 'Invalid nonce';
			wp_send_json( $response );
		}

		if ( ! tec_get_request_var( 'fieldset_id', false ) ) {
			$response['data']['error'] = 'No fieldset ID provided';
			wp_send_json( $response );
		}

		$fieldset_id = absint( tec_get_request_var( 'fieldset_id', 0 ) );
		$post        = get_post( $fieldset_id );

		if ( ! $post instanceof WP_Post ) {
			$response['data']['error'] = 'Invalid fieldset ID';
			wp_send_json( $response );
		}

		// Get data from post meta.
		$meta = get_post_meta( $fieldset_id, self::META_KEY, true );
		if ( empty( $meta ) || empty( $meta['fields'] ) ) {
			$response['data']['error'] = 'No meta fields found';
			wp_send_json( $response );
		}

		// Generate field objects.
		$fields = [];
		foreach ( $meta['fields'] as $field_data ) {
			$type = $field_data['type'] ?? '';
			if ( empty( $type ) ) {
				continue;
			}

			$field = $this->generate_field( $fieldset_id, $type, $field_data );
			if ( $field instanceof Tribe__Tickets_Plus__Meta__Field__Abstract_Field ) {
				$fields[] = $field;
			}
		}

		// Get rendered fields HTML by calling render_admin_field for each field.
		$html = '';
		foreach ( $fields as $field ) {
			$html .= $field->render_admin_field();
		}

		$response['success']      = true;
		$response['data']['html'] = $html;

		wp_send_json( $response );
	}

	/**
	 * Convert json-encoded fields to admin HTML.
	 *
	 * @since 6.6.0
	 *
	 * @param string $field_data The encoded fields from the database.
	 * @return string The admin HTML.
	 */
	public function convert_field_data_to_html( $field_data ): string {
		if ( empty( $field_data ) ) {
			return '';
		}

		$fields = maybe_unserialize( wp_unslash( $field_data ) );
		$html   = '';

		foreach ( $fields as $field ) {
			$object = $this->generate_field( $field['preset_id'] ?? 0, $field['type'], $field );
			
			if ( $object instanceof Tribe__Tickets_Plus__Meta__Field__Abstract_Field ) {
				$html .= $object->render_admin_field();
			}
		}

		return $html;
	}

	/**
	 * Get meta fields by preset.
	 *
	 * @since 6.6.0
	 *
	 * @param int $preset_id The preset ID.
	 *
	 * @return array The preset meta data.
	 */
	public function get_meta_by_preset( $preset_id ) {
		if ( empty( $preset_id ) ) {
			return [];
		}

		$repository = tribe( Ticket_Presets::class );
		$preset     = $repository->find_by_id( $preset_id );

		if ( ! $preset instanceof Ticket_Preset ) {
			return [];
		}

		$preset_data = json_decode( $preset->data, true );
		$meta        = $preset_data['iac'] ?? [];

		/**
		 * Filters the meta data for a preset.
		 *
		 * @since 6.6.0
		 *
		 * @param array $meta      The meta data.
		 * @param int   $preset_id The preset ID.
		 */
		return apply_filters( 'tec_tickets_plus_preset_meta', $meta, $preset_id );
	}

	/**
	 * Get the IAC setting for a preset.
	 *
	 * @since 6.6.0
	 *
	 * @param int $preset_id The preset ID.
	 *
	 * @return string The IAC setting for a preset (none, allowed, required).
	 */
	public function get_iac_setting_for_preset( $preset_id ): string {
		if ( empty( $preset_id ) ) {
			return $this->get_default_iac_setting();
		}

		$preset = tribe( Ticket_Presets::class )->find_by_id( $preset_id );

		if ( ! $preset instanceof Ticket_Preset ) {
			return $this->get_default_iac_setting();
		}

		return $preset->get_iac_setting() ?: $this->get_default_iac_setting();
	}

	/**
	 * Gets the default IAC setting.
	 *
	 * @since 6.6.0
	 *
	 * @return string
	 */
	public function get_default_iac_setting(): string {
		return $this->iac_defaults;
	}
}
