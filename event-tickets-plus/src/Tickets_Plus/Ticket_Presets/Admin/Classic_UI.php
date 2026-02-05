<?php
/**
 * Handles the Ticket Presets UI in the Classic Editor.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Admin;

use TEC\Tickets_Plus\Ticket_Presets\Meta;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;
use TEC\Tickets_Plus\Ticket_Presets\Custom_Tables\Ticket_Presets as Preset_Table;
use TEC\Tickets_Plus\Ticket_Presets\Models\Ticket_Preset;
use Tribe__Tickets__RSVP;
use Tribe__Tickets_Plus__Meta;
use WP_Error;
use TEC\Tickets_Plus\Ticket_Presets\Admin\Controller as Admin_Controller;
/**
 * Class Classic_UI
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */
class Classic_UI {
	/**
	 * Stores the template instance.
	 *
	 * @since 6.6.0
	 *
	 * @var Admin_Template
	 */
	protected $template;

	/**
	 * Constructor.
	 *
	 * @since 6.6.0
	 */
	public function __construct() {
		$this->template = tribe( Admin_Template::class );
	}

	/**
	 * Hooks all the required actions and filters.
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function hook(): void {
		if ( tec_get_request_var( 'is_admin' ) === 'false' ) {
			return;
		}

		add_action( 'tribe_events_tickets_pre_edit', [ $this, 'prepend_presets_panel' ], 10, 4 );
		add_action( 'wp_ajax_tec_tickets_plus_get_preset', [ $this, 'ajax_get_preset' ] );
		add_action( 'wp_ajax_tec_tickets_plus_render_saved_fields', [ $this, 'ajax_render_saved_fields' ] );
		add_action( 'tribe_events_tickets_bottom_start', [ $this, 'render_checkbox' ], 10, 2 );
		add_action( 'tribe_tickets_metabox_end', [ $this, 'render_modal' ], 20, 2 );
		add_action( 'wp_ajax_tec_tickets_plus_save_as_preset', [ $this, 'ajax_save_as_preset' ] );
		add_action( 'wp_ajax_tec_tickets_plus_preset_get_cost', [ $this, 'ajax_get_formatted_price' ] );
	}

	/**
	 * Unhooks the actions and filters.
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function unhook(): void {
		remove_action( 'tribe_events_tickets_pre_edit', [ $this, 'prepend_presets_panel' ], 10 );
		remove_action( 'wp_ajax_tec_tickets_plus_get_preset', [ $this, 'ajax_get_preset' ] );
		remove_action( 'wp_ajax_tec_tickets_plus_render_saved_fields', [ $this, 'ajax_render_saved_fields' ] );
		remove_action( 'tribe_events_tickets_bottom_start', [ $this, 'render_checkbox' ], 10 );
		remove_action( 'tribe_tickets_metabox_end', [ $this, 'render_modal' ], 20 );
		remove_action( 'wp_ajax_tec_tickets_plus_save_as_preset', [ $this, 'ajax_save_as_preset' ] );
		remove_action( 'wp_ajax_tec_tickets_plus_preset_get_cost', [ $this, 'ajax_get_formatted_price' ] );
	}

	/**
	 * Maybe show the presets panel instead of the ticket panel.
	 *
	 * @since 6.6.0
	 *
	 * @param int    $post_id    The post ID.
	 * @param int    $ticket_id  The ticket ID.
	 * @param string $ticket_type The ticket type.
	 */
	public function prepend_presets_panel( $post_id, $ticket_id, $ticket_type ): void {
		// If we're not in "add new" mode, return the original panel.
		if ( ! empty( $ticket_id ) ) {
			return;
		}

		// We don't apply presets to RSVPs for now.
		if ( 'rsvp' == $ticket_type ) {
			return;
		}

		// Check if the feature is enabled.
		if ( ! apply_filters( 'tec_tickets_plus_ticket_presets_enabled', true ) ) {
			return;
		}

		// Check if the tables exist.
		if ( ! tribe( Preset_Table::class )->exists() ) {
			return;
		}

		$presets = $this->get_available_presets();

		if ( empty( $presets ) ) {
			return;
		}

		$this->template->template(
			'editor/panels/presets',
			[
				'post_id'   => $post_id,
				'ticket_id' => $ticket_id,
				'presets'   => $presets,
			]
		);
	}

	/**
	 * Gets the available presets.
	 *
	 * @since 6.6.0
	 *
	 * @return array<mixed> Array of available presets.
	 */
	protected function get_available_presets() {
		$presets = tribe( Ticket_Presets::class )->get_all( ARRAY_A );
		return $presets ?? [];
	}

	/**
	 * Handles the AJAX request to get a preset.
	 *
	 * @since 6.6.0
	 */
	public function ajax_get_preset() {
		if ( ! check_ajax_referer( 'tec-tickets-plus_apply-preset', 'nonce', false ) ) {
			wp_send_json_error( new WP_Error( 'invalid_nonce', __( 'Invalid nonce', 'event-tickets-plus' ) ) );
		}

		$preset_id = absint( tribe_get_request_var( 'preset_id', 0 ) );

		if ( ! $preset_id ) {
			wp_send_json_error( new WP_Error( 'invalid_preset', __( 'Invalid preset ID', 'event-tickets-plus' ) ) );
		}

		$preset = tribe( Ticket_Presets::class )->find_by_id( $preset_id );

		if ( ! $preset ) {
			wp_send_json_error( new WP_Error( 'preset_not_found', __( 'Preset not found', 'event-tickets-plus' ) ) );
		}

		wp_send_json_success( $preset->to_array() );
	}

	/**
	 * Handles the AJAX request to render saved fields.
	 *
	 * @since 6.6.0
	 */
	public function ajax_render_saved_fields() {
		if ( ! check_ajax_referer( 'tec-tickets-plus_apply-preset', 'nonce', false ) ) {
			wp_send_json_error( new WP_Error( 'invalid_nonce', __( 'Invalid nonce', 'event-tickets-plus' ) ) );
		}

		$iac = tec_get_request_var( 'attendeeMeta', [] );

		if ( empty( $iac ) ) {
			wp_send_json_error( new WP_Error( 'no_iac_provided', __( 'No IAC provided', 'event-tickets-plus' ) ) );
		}

		$html = tribe( Meta::class )->convert_field_data_to_html( $iac );

		wp_send_json_success( $html );
	}

	/**
	 * Renders the save as preset checkbox.
	 *
	 * @since 6.6.0
	 *
	 * @param int $post_id   The post ID.
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return void
	 */
	public function render_checkbox( $post_id, $ticket_id ) {
		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		if ( ! $provider ) {
			return;
		}

		if ( Tribe__Tickets__RSVP::class === $provider->class_name ) {
			return;
		}

		$provider_name = sanitize_html_class( $provider->class_name );

		$this->template->template(
			'editor/panel/fields/save-as-preset',
			[
				'post_id'       => $post_id,
				'ticket_id'     => $ticket_id,
				'provider'      => $provider,
				'provider_name' => $provider_name,
			]
		);
	}

	/**
	 * Handles the AJAX request to save a ticket as a preset.
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function ajax_save_as_preset() {
		if ( ! check_ajax_referer( 'tec-tickets-plus_create-preset', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'event-tickets-plus' ) ] );
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets-plus' ) ] );
			return;
		}

		$preset_data = tec_get_request_var( 'preset', false );

		if ( ! $preset_data ) {
			wp_send_json_error( [ 'message' => __( 'No ticket data provided.', 'event-tickets-plus' ) ] );
			return;
		}

		$post_id   = $preset_data['post_id'] ?? 0;
		$ticket_id = $preset_data['ticket_id'] ?? 0;

		if ( ! ( $ticket_id && $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'No valid ticket or post provided.', 'event-tickets-plus' ) ] );
			return;
		}

		$provider      = tribe_tickets_get_ticket_provider( $ticket_id );
		$ticket_object = $provider->get_ticket( $post_id, $ticket_id );
		$price         = $ticket_object->regular_price;
		$capacity      = $ticket_object->capacity();
		$capacity_mode = ( -1 === $capacity || '' == $capacity ) ? 'unlimited' : $ticket_object->global_stock_mode();
		// get iac option.
		$iac        = tribe( 'tickets-plus.attendee-registration.iac' );
		$iac_option = $iac->get_iac_setting_for_ticket( $ticket_id );

		// get meta fields.
		$meta = Tribe__Tickets_Plus__Meta::get_attendee_meta_fields( $ticket_id );

		$sale_start = $preset_data['sale_start_logic'] ?? [];
		$sale_end   = $preset_data['sale_end_logic'] ?? [];

		// Create the preset.
		$preset_arr = [
			'name'             => sanitize_text_field( $preset_data['name'] ?? '' ),
			'cost'             => $price,
			'ticket_name'      => $ticket_object->name,
			'description'      => $ticket_object->description,
			'ticket_type'      => 'default',
			'capacity'         => [
				'type'   => $capacity_mode,
				'amount' => 'unlimited' === $capacity_mode ? -1 : absint( $capacity ),
			],
			'iac_setting'      => $iac_option,
			'iac'              => $meta,
			'sale_start_logic' => tribe( Admin_Controller::class )->sanitize_sale_logic( $sale_start ),
			'sale_end_logic'   => tribe( Admin_Controller::class )->sanitize_sale_logic( $sale_end ),
		];

		try {
			$preset = new Ticket_Preset(
				[
					'slug' => sanitize_title( $preset_arr['name'] ),
					'data' => wp_json_encode( $preset_arr ),
				]
			);

			$preset->save();
			
			$edit_link = add_query_arg(
				[
					'page'   => 'tec-tickets-preset-form',
					'action' => 'edit',
					'id'     => $preset->id,
				],
				admin_url( 'admin.php' )
			);
			
			$message = sprintf(
				/* translators: %1$s is the link to the preset. */
				__( '%1$s saved successfully.', 'event-tickets-plus' ),
				'<a href="' . esc_url( $edit_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Preset', 'event-tickets-plus' ) . '</a>'
			);
			
			wp_send_json_success(
				[
					'message'   => $message,
					'preset_id' => $preset->id,
				]
			);
		} catch ( \Exception $e ) {
			wp_send_json_error( [ 'message' => $e->getMessage() ] );
		}
	}

	/**
	 * Renders the save as preset modal.
	 *
	 * @since 6.6.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $ticket_id The ticket ID.
	 */
	public function render_modal( $post_id, $ticket_id ) {
		static $rendered = false;

		if ( $rendered ) {
			return;
		}

		$this->template->template(
			'editor/panel/fields/save-as-preset-modal',
			[
				'post_id'   => $post_id,
				'ticket_id' => $ticket_id,
			]
		);

		$rendered = true;
	}

	/**
	 * Handles the AJAX request to get the cost of a ticket for the preset.
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function ajax_get_formatted_price() {
		if ( ! check_ajax_referer( 'tec-tickets-plus_create-preset', 'nonce', false ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid nonce.', 'event-tickets-plus' ) ] );
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets-plus' ) ] );
			return;
		}

		$ticket_id = absint( tribe_get_request_var( 'ticket_id', 0 ) );
		$post_id   = absint( tribe_get_request_var( 'post_id', 0 ) );

		if ( ! ( $ticket_id && $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid ticket or post ID.', 'event-tickets-plus' ) ] );
			return;
		}

		$provider = tribe_tickets_get_ticket_provider( $ticket_id );

		wp_send_json_success(
			[ 'price' => $provider->get_price_html( $ticket_id ) ]
		);
	}
}
