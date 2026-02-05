<?php
/**
 * Handles the Ticket Presets operations in the admin.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Arrays\Arr;
use TEC\Tickets_Plus\Ticket_Presets\Controller as Presets_Controller;
use TEC\Tickets_Plus\Ticket_Presets\Models\Ticket_Preset;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;
use TEC\Tickets_Plus\Ticket_Presets\Meta;
use Tribe__Tickets_Plus__Meta;
use Tribe__Tickets_Plus__Meta__Fieldset;
use WP_Error;

/**
 * Class Controller
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */
class Controller extends Controller_Contract {
	/**
	 * @var Ticket_Presets
	 */
	private $repository;

	/**
	 * Whether the controller is active or not.
	 *
	 * @since 6.6.0
	 *
	 * @return bool Whether the controller is active or not.
	 */
	public function is_active(): bool {
		return tribe( Presets_Controller::class )->is_active();
	}

	/**
	 * Registers the service provider.
	 *
	 * @since 6.6.0
	 */
	public function do_register(): void {
		$this->repository = tribe( Ticket_Presets::class );
		$this->container->singleton( Admin_Template::class );
		$this->container->bind( Classic_UI::class, Classic_UI::class );
		tribe( Classic_UI::class )->hook();

		add_action( 'admin_post_tec_tickets_save_preset', [ $this, 'handle_save_preset' ] );
		add_action( 'admin_init', [ $this, 'handle_delete_preset' ] );
		add_action( 'admin_init', [ $this, 'handle_duplicate_preset' ] );
		add_action( 'admin_notices', [ $this, 'display_notices' ] );
		add_action( 'wp_ajax_tec-tickets-plus-presets-load-saved-fields', [ $this, 'handle_get_fieldset_preview' ] );
	}

	/**
	 * Unregisters the service provider.
	 *
	 * @since 6.6.0
	 */
	public function unregister(): void {
		tribe( Classic_UI::class )->unhook();
		remove_action( 'admin_post_tec_tickets_save_preset', [ $this, 'handle_save_preset' ] );
		remove_action( 'admin_init', [ $this, 'handle_delete_preset' ] );
		remove_action( 'admin_init', [ $this, 'handle_duplicate_preset' ] );
		remove_action( 'admin_notices', [ $this, 'display_notices' ] );
		remove_action( 'wp_ajax_tec-tickets-plus-presets-load-saved-fields', [ $this, 'handle_get_fieldset_preview' ] );
	}

	/**
	 * Handles saving a new ticket preset.
	 *
	 * @since 6.6.0
	 */
	public function handle_save_preset(): void {
		if ( ! check_admin_referer( 'save_ticket_preset', 'ticket_preset_nonce' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'event-tickets-plus' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'event-tickets-plus' ) );
		}

		// Sanitize the preset data. note the POST data is sanitized in the following function.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$preset_data = $this->sanitize_preset_data( tec_get_request_var( 'preset', [] ) );
		$action      = tec_get_request_var( 'action' ) ?? 'create';
		$preset_id   = tec_get_request_var( 'preset_id' ) ?? null;

		$capacity_type = Arr::get( $preset_data, [ 'capacity', 'type' ], 'own' );
		$capacity      = 'unlimited' === $capacity_type ? -1 : Arr::get( $preset_data, [ 'capacity', 'amount' ], 1 );

		if ( empty( $preset_data ) ) {
			$this->add_notice( __( 'Invalid preset data provided.', 'event-tickets-plus' ), 'error' );
			wp_safe_redirect( wp_get_referer() ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
			tribe_exit();
		}

		if ( ! empty( $preset_id ) || 0 === $preset_id ) {
			$preset           = $this->repository->find_by_id( $preset_id );
			$preset->data     = wp_json_encode( $preset_data );
			$preset->name     = sanitize_text_field( $preset_data['name'] );
			$preset->capacity = $capacity;
			$decimal_places   = (int) tribe_get_option( 'tickets-commerce-currency-number-of-decimals', 2 );
			$preset->cost     = (string) $preset_data['cost'] ?? '0';
			$preset->save();

			$this->add_notice( __( 'Preset saved successfully.', 'event-tickets-plus' ), 'success' );

			wp_safe_redirect( wp_get_referer() ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
			tribe_exit();
		}

		try {
			$preset = Ticket_Preset::create(
				[
					'slug'     => sanitize_title( $preset_data['name'] ),
					'data'     => wp_json_encode( $preset_data ),
					'name'     => sanitize_text_field( $preset_data['name'] ),
					'capacity' => $capacity,
					'cost'     => (string) $preset_data['cost'] ?? '0',
				]
			);

			if ( ! $preset instanceof Ticket_Preset ) {
				$this->add_notice( __( 'Failed to save preset.', 'event-tickets-plus' ), 'error' );
				wp_safe_redirect( $this->get_redirect_url_with_data( $preset_data ) ); // phpcs:ignore
				tribe_exit();
			}

			$this->add_notice( __( 'Preset saved successfully.', 'event-tickets-plus' ), 'success' );
			// phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
			wp_safe_redirect(
				add_query_arg(
					[
						'page' => 'tec-tickets-admin-tickets',
						'tab'  => 'presets',
					],
					admin_url( 'admin.php' )
				)
			);
			tribe_exit();

		} catch ( \Exception $e ) {
			$this->add_notice( $e->getMessage(), 'error' );
			wp_safe_redirect( $this->get_redirect_url_with_data( $preset_data ) );  // phpcs:ignore
			tribe_exit();
		}
	}

	/**
	 * Handles saving a fieldset for reuse.
	 *
	 * @since 6.6.0
	 *
	 * @param array $meta The meta data.
	 */
	private function handle_save_fieldset( $meta ) {
		// Save templates too.
		if ( ! tec_get_request_var( 'tribe-tickets-save-fieldset', false ) ) {
			return;
		}

		$fieldset = wp_insert_post(
			[
				'post_type'   => Tribe__Tickets_Plus__Meta__Fieldset::POSTTYPE,
				'post_title'  => sanitize_text_field( tec_get_request_var( 'tribe-tickets-saved-fieldset-name', null ) ),
				'post_status' => 'publish',
			]
		);

		// This is for the meta fields template.
		update_post_meta( $fieldset, Tribe__Tickets_Plus__Meta__Fieldset::META_KEY, $meta );
	}

	/**
	 * Handles deleting a ticket preset.
	 *
	 * @since 6.6.0
	 */
	public function handle_delete_preset(): void {
		if ( 'delete-preset' !== tec_get_request_var( 'action' ) ) {
			return;
		}

		$nonce = tec_get_request_var( 'tec_nonce', '' );

		if (
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'tec-tec-tickets-plus_delete-preset' ) ||
			! current_user_can( 'manage_options' )
		) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'event-tickets-plus' ) );
		}

		$preset_id = absint( tec_get_request_var( 'preset_id', 0 ) );

		if ( ! $preset_id ) {
			$this->add_notice( __( 'Invalid preset ID.', 'event-tickets-plus' ), 'error' );
			wp_safe_redirect( wp_get_referer() );  // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
			tribe_exit();
		}

		$preset = $this->repository->find_by_id( $preset_id );

		if ( ! $preset instanceof Ticket_Preset ) {
			$this->add_notice( __( 'Preset not found.', 'event-tickets-plus' ), 'error' );
			wp_safe_redirect( wp_get_referer() );  // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
			tribe_exit();
		}

		$deleted = $this->repository->delete( $preset );

		if ( ! $deleted ) {
			$this->add_notice( __( 'Failed to delete preset.', 'event-tickets-plus' ), 'error' );
		} else {
			$this->add_notice( __( 'Preset deleted successfully.', 'event-tickets-plus' ), 'success' );
		}

		// Redirect to the presets list page.
		  // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
		wp_safe_redirect(
			add_query_arg(
				[
					'page' => 'tec-tickets-admin-tickets',
					'tab'  => 'presets',
				],
				admin_url( 'admin.php' )
			)
		);
		tribe_exit();
	}

	/**
	 * Handle duplicate preset action.
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function handle_duplicate_preset(): void {
		if ( 'duplicate-preset' !== tec_get_request_var( 'action' ) ) {
			return;
		}

		$nonce = tec_get_request_var( 'tec_nonce', '' );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $nonce ) ), 'tec-tec-tickets-plus_duplicate-preset' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action. Bad nonce.', 'event-tickets-plus' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'event-tickets-plus' ) );
		}

		// Get the repository.
		$repository = tribe( Ticket_Presets::class );

		$preset_id = absint( tec_get_request_var( 'preset_id', 0 ) );

		if ( ! $preset_id ) {
			$this->add_notice( __( 'Invalid preset ID.', 'event-tickets-plus' ), 'error' );
			wp_safe_redirect( wp_get_referer() ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
			tribe_exit();
		}

		// Get the preset to duplicate.
		$preset = $repository->find_by_id( $preset_id );

		if ( empty( $preset ) ) {
			// Preset not found, redirect with error.
			$redirect_url = add_query_arg(
				[
					'page'  => 'tec-tickets-admin-tickets',
					'tab'   => 'presets',
					'error' => 'preset-not-found',
				],
				admin_url( 'admin.php' )
			);

			$this->add_notice( __( 'Preset not found.', 'event-tickets-plus' ), 'error' );

			wp_safe_redirect( $redirect_url ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
			tribe_exit();
		}

		// Get the preset data.
		$preset_data = json_decode( $preset->data, true );

		// Modify the name to indicate it's a copy.
		$name = sprintf(
			// translators: %s: The name of the preset we are duplicating.
			__( 'Copy of %s', 'event-tickets-plus' ),
			$preset_data['name'] ?? ''
		);

		$capacity = 'unlimited' === $preset_data['capacity']['type'] ? -1 : $preset_data['capacity']['amount'];

		// Create a new preset with the duplicated data.
		$new_preset = Ticket_Preset::create(
			[
				'slug'     => sanitize_title( $name ),
				'data'     => wp_json_encode( array_merge( $preset_data, [ 'name' => $name ] ) ),
				'name'     => $name,
				'capacity' => $capacity,
				'cost'     => (string) $preset_data['cost'],
			]
		);

		if ( $new_preset->id ) {
			// Success, redirect back to the list with a success message.
			$redirect_url = add_query_arg(
				[
					'page'       => 'tec-tickets-admin-tickets',
					'tab'        => 'presets',
					'duplicated' => 1,
				],
				admin_url( 'admin.php' )
			);
			$this->add_notice( __( 'Preset duplicated successfully.', 'event-tickets-plus' ), 'success' );
		} else {
			// Error creating duplicate, redirect with error.
			$redirect_url = add_query_arg(
				[
					'page'  => 'tec-tickets-admin-tickets',
					'tab'   => 'presets',
					'error' => 'duplicate-failed',
				],
				admin_url( 'admin.php' )
			);
			$this->add_notice( __( 'Failed to duplicate preset.', 'event-tickets-plus' ), 'error' );
		}

		wp_safe_redirect( $redirect_url ); // phpcs:ignore WordPressVIPMinimum.Security.ExitAfterRedirect.NoExit,StellarWP.CodeAnalysis.RedirectAndDie.Error
		tribe_exit();
	}

	/**
	 * Gets all ticket presets.
	 *
	 * @since 6.6.0
	 *
	 * @return array<Ticket_Preset>|WP_Error The list of presets or WP_Error on failure.
	 */
	public function get_presets() {
		return $this->repository->get_all();
	}

	/**
	 * Sanitizes the preset data from the form submission.
	 *
	 * @since 6.6.0
	 *
	 * @param array<string,mixed> $data The preset data to sanitize.
	 *
	 * @return array<string,mixed> The sanitized preset data.
	 */
	protected function sanitize_preset_data( $data ) {
		if ( empty( $data ) ) {
			return [];
		}

		$capacity_type = in_array( $data['capacity']['type'] ?? '', [ 'own', 'unlimited' ] ) ? $data['capacity']['type'] : 'own';

		// Get the meta fields data properly structured.
		$meta = $this->handle_attendee_meta();

		return [
			'name'             => sanitize_text_field( $data['name'] ?? '' ),
			'description'      => sanitize_textarea_field( $data['description'] ?? '' ),
			'cost'             => (string) $data['cost'] ?? '0',
			'ticket_name'      => sanitize_text_field( $data['ticket_name'] ?? '' ),
			'ticket_type'      => 'default',
			'iac_setting'      => $data['iac_setting'] ?? 'none',
			'iac'              => maybe_serialize( $meta ),
			'capacity'         => [
				'amount' => 'unlimited' === $capacity_type ? -1 : absint( $data['capacity']['amount'] ?? 0 ),
				'type'   => $capacity_type,
			],
			'sale_start_logic' => $this->sanitize_sale_logic( $data['sale_start_logic'] ?? [] ),
			'sale_end_logic'   => $this->sanitize_sale_logic( $data['sale_end_logic'] ?? [] ),
		];
	}

	/**
	 * Handles the attendee meta data.
	 *
	 * @since 6.6.0
	 *
	 * @return array<string,mixed> The attendee meta data.
	 */
	public function handle_attendee_meta(): array {
		$tickets_input = tec_get_request_var( 'tribe-tickets-input', [] );

		if ( empty( $tickets_input ) ) {
			return [];
		}

		$meta = Tribe__Tickets_Plus__Meta::instance()->build_field_array(
			0, // We need a Ticket ID, but we don't have one here, it works fine.
			[
				'tribe-tickets-input' => $tickets_input,
			]
		);

		$this->handle_save_fieldset( $meta );

		return $meta;
	}

	/**
	 * Sanitizes the sale logic data.
	 *
	 * @since 6.6.0
	 *
	 * @param array $data The raw sale logic data.
	 *
	 * @return array The sanitized sale logic data.
	 */
	public function sanitize_sale_logic( array $data ): array {
		$allowed_types       = [ 'published', 'start', 'relative' ];
		$allowed_relative_to = [ 'start', 'end', 'published', 'now' ];
		$allowed_directions  = [ 'before', 'after' ];
		$allowed_periods     = [ 'minute', 'hour', 'day', 'week', 'month' ];

		$type = in_array( $data['type'] ?? '', $allowed_types ) ? $data['type'] : 'relative';

		// If type is not relative, return just the type.
		if ( 'relative' !== $type ) {
			return [ 'type' => $type ];
		}

		// For relative type, include all the relative date fields.
		return [
			'type'        => $type,
			'relative_to' => in_array( $data['relative_to'] ?? '', $allowed_relative_to ) ? $data['relative_to'] : 'start',
			'direction'   => in_array( $data['direction'] ?? '', $allowed_directions ) ? $data['direction'] : 'before',
			'period'      => in_array( $data['period'] ?? '', $allowed_periods ) ? $data['period'] : 'hour',
			'length'      => absint( $data['length'] ?? 0 ),
		];
	}

	/**
	 * Adds a notice to be displayed.
	 *
	 * @since 6.6.0
	 *
	 * @param string $message The notice message.
	 * @param string $type The notice type (error, warning, success, info).
	 */
	public function add_notice( string $message, string $type = 'info' ): void {
		$notices   = get_transient( 'tec_tickets_preset_notices' ) ?: [];
		$notices[] = [
			'message' => $message,
			'type'    => $type,
		];
		set_transient( 'tec_tickets_preset_notices', $notices, 60 );
	}

	/**
	 * Displays admin notices.
	 *
	 * @since 6.6.0
	 */
	public function display_notices(): void {
		$notices = get_transient( 'tec_tickets_preset_notices' );

		if ( empty( $notices ) ) {
			return;
		}

		foreach ( $notices as $notice ) {
			printf(
				'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
				esc_attr( $notice['type'] ),
				esc_html( $notice['message'] )
			);
		}

		delete_transient( 'tec_tickets_preset_notices' );
	}

	/**
	 * Handles the AJAX request to get a fieldset preview.
	 *
	 * @since 6.6.0
	 */
	public function handle_get_fieldset_preview() {
		check_ajax_referer( 'tribe_ticket_attendee_info_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'You do not have permission to perform this action.', 'event-tickets-plus' ) ] );
		}

		$fieldset_id = tec_get_request_var( 'fieldset_id' );

		if ( empty( $fieldset_id ) ) {
			wp_send_json_error( [ 'message' => __( 'No fieldset ID provided.', 'event-tickets-plus' ) ] );
		}

		$html = tribe( Meta::class )->get_saved_iac_fieldset( $fieldset_id );

		if ( empty( $html ) ) {
			wp_send_json_success( [ 'html' => '' ] );
		}

		wp_send_json_success( [ 'html' => $html ] );
	}

	/**
	 * Get the redirect URL with data.
	 *
	 * @since 6.6.0
	 *
	 * @param array $data The data to include in the redirect URL.
	 *
	 * @return string The redirect URL with the data.
	 */
	public function get_redirect_url_with_data( array $data ): string {
		$key = wp_rand();
		set_transient( $key, $data, 5 * MINUTE_IN_SECONDS );

		return add_query_arg(
			[
				'key' => $key,
			],
			wp_get_referer()
		);
	}
}
