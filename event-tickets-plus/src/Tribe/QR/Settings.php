<?php

use TEC\Tickets\QR\Settings as QR_Settings;
use TEC\Common\Admin\Entities\Div;
use TEC\Common\Admin\Entities\Field_Wrapper;
use TEC\Common\Admin\Entities\Heading;
use Tribe\Utils\Element_Classes as Classes;
use TEC\Common\QR\Controller as QR_Controller;
use TEC\Common\QR\Notices as QR_Notices;

/**
 * Adds settings relating directly to handling of ticket QR codes to the
 * Events ‣ Settings ‣ Tickets admin screen.
 *
 * @since 6.5.0 Added horizontal layout blocks for improved visual organization.
 * @since 4.7.5
 */
class Tribe__Tickets_Plus__QR__Settings {

	/**
	 * Hook into Event Tickets/Event Tickets Plus.
	 *
	 * @since 4.7.5
	 */
	public function hook() {
		add_filter( 'tec_tickets_plus_integrations_tab_fields', [ $this, 'add_settings' ], 15 );
	}

	/**
	 * Append global Event Tickets Plus settings section to tickets settings tab
	 *
	 * @since 4.7.5
	 *
	 * @param array $settings_fields Array of settings fields.
	 *
	 * @return array
	 */
	public function add_settings( array $settings_fields ) {
		$extra_settings = $this->additional_settings();

		return Tribe__Main::array_insert_before_key( 'tribe-form-content-end', $settings_fields, $extra_settings );
	}

	/**
	 * Adds the general ticket QR code settings to the Events ‣ Settings ‣ Tickets screen.
	 *
	 * @since 4.7.5
	 * @since 6.5.0 Updated HTML.
	 *
	 * @param array $settings The settings.
	 *
	 * @return array
	 */
	public function additional_settings( array $settings = [] ) {
		$ticket_label_plural_lower = esc_html( tribe_get_ticket_label_plural_lowercase( 'check_in_app' ) );

		$qr_settings = [
			'tec-settings-integration-etp-app-header' => ( new Div( new Classes( [ 'tec-settings-form__header-block' ] ) ) )->add_children(
				[
					new Heading(
						esc_html__( 'Event Tickets Plus App', 'event-tickets-plus' ),
						2,
						new Classes( [ 'tec-settings-form__section-header' ] )
					),
					( new Field_Wrapper(
						new Tribe__Field(
							'tecTicketsEmailTemplateExplanation',
							[
								'type' => 'html',
								'html' => '<p class="tec-settings-form__section-description">'
											. $this->get_intro_html()
											. '</p>',
							]
						)
					) ),
				]
			),
			[
				'type' => 'html',
				'html' => '<div>',
			],
			'tickets-plus-qr-options-app-banner'      => [
				'type' => 'html',
				'html' => $this->get_app_banner(),
			],
			QR_Settings::get_enabled_option_slug()    => [
				'type'            => 'toggle',
				'label'           => esc_html__( 'Use QR Codes', 'event-tickets-plus' ),
				'tooltip'         => esc_html(
					sprintf(
					// Translators: %s: 'tickets' label (plural, lowercase).
						__( 'Include QR codes in %s emails (required for Event Tickets Plus App)', 'event-tickets-plus' ),
						$ticket_label_plural_lower
					)
				),
				'default'         => true,
				'validation_type' => 'boolean',
			],
		];

		if ( tec_tickets_tec_events_is_active() ) {
			$enabled_post_types          = (array) tribe_get_option( 'ticket-enabled-post-types', [] );
			$events_label_singular_lower = tribe_get_event_label_singular_lowercase( 'check_in_app' );
			if ( in_array( Tribe__Events__Main::POSTTYPE, $enabled_post_types ) ) {
				$qr_settings['tickets-plus-qr-check-in-events-happening-now'] = [
					'type'            => 'toggle',
					'label'           => esc_html__( 'Restrict Check-In', 'event-tickets-plus' ),
					'tooltip'         => esc_html(
						sprintf(
						// Translators: %1$s: 'tickets' label (plural, lowercase). %2$s: 'event' label (singular, lowercase).
							__( 'Only allow check-in of QR %1$s during the date and time of the %2$s, including the check-in window below.', 'event-tickets-plus' ),
							$ticket_label_plural_lower,
							$events_label_singular_lower
						)
					),
					'default'         => false,
					'validation_type' => 'boolean',
				];

				$qr_settings['tickets-plus-qr-check-in-events-happening-now-time-buffer'] = [
					'type'            => 'text',
					'label'           => esc_html__( 'Check-in Window', 'event-tickets-plus' ),
					'tooltip'         => esc_html(
						sprintf(
						// Translators: %1$s: 'event' label (singular, lowercase).
							__( 'minutes before the %1$s', 'event-tickets-plus' ),
							$events_label_singular_lower
						)
					),
					'validation_type' => 'int',
					'size'            => 'small',
					'default'         => '0',
					'can_be_empty'    => true,
				];
			}
		}

		// Close the horizontal block wrapper.
		$qr_settings['tickets-plus-qr-options-end'] = [
			'type' => 'html',
			'html' => '</div>',
		];

		return Tribe__Main::array_insert_before_key(
			'tribe-form-content-end',
			$settings,
			$qr_settings
		);
	}

	/**
	 * Get the QR setting intro html content.
	 *
	 * @since 5.6.2
	 *
	 * @return string
	 */
	public function get_intro_html(): string {

		if ( tribe( QR_Controller::class )->can_use() ) {
			$app_store     = sprintf( '<a href="https://evnt.is/etp-app-apple-store" target="_blank" rel="noopener noreferrer">%s</a>', esc_html__( 'App Store', 'event-tickets-plus' ) );
			$play_store    = sprintf( '<a href="https://evnt.is/etp-app-google-play" target="_blank" rel="noopener noreferrer">%s</a>', esc_html__( 'Google Play Store', 'event-tickets-plus' ) );
			$knowledgebase = sprintf( '<a href="https://evnt.is/event-tickets-qr-support" target="_blank" rel="noopener noreferrer">%s</a>', esc_html__( 'Knowledgebase', 'event-tickets-plus' ) );

			return '<p>'
				. sprintf(
					/* translators: 1 is the app store URL, 2 is the play store URL, and 3 is the knowledgebase URL. */
					esc_html__(
						'Our Event Tickets Plus app makes on-site ticket validation and attendee management a breeze. Available for mobile devices through the iOS %1$s and %2$s
store. Learn more about the app in our %3$s.',
						'event-tickets-plus'
					),
					$app_store,
					$play_store,
					$knowledgebase
				)
				. '</p>';
		}

		return '<div id="modern-tribe-info" style="border-left: 3px solid #d63638;">' . tribe( QR_Notices::class )->get_dependency_notice_contents() . '</div>';
	}

	/**
	 * Get the Event Tickets Plus App connection details banner.
	 *
	 * @since 5.6.2
	 * @since 5.8.0 No longer `private`, just protected.
	 *
	 * @return false|string Settings banner html.
	 */
	protected function get_app_banner(): string {
		if ( ! tribe( QR_Controller::class )->can_use() ) {
			return '';
		}

		$qr_settings       = tribe( QR_Settings::class );
		$connector         = tribe( \TEC\Tickets\QR\Connector::class );
		$api_key           = $qr_settings->get_api_key();
		$qrcode_base64_src = $connector->get_base64_code_src();

		if ( is_wp_error( $qrcode_base64_src ) ) {
			return false;
		}

		$template_vars = [
			'site_url'   => site_url(),
			'api_key'    => $api_key,
			'qr_src'     => $qrcode_base64_src,
			'nonce'      => wp_create_nonce( $connector->get_nonce_key() ),
			'action_key' => $connector->get_ajax_action_key(),
		];

		/** @var Tribe__Tickets__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets.admin.views' );

		tribe_asset_enqueue( 'tec-tickets-qr-connector' );

		return $admin_views->template( 'settings/etp-app-banner', $template_vars, false );
	}

	/**
	 * The option key to store use QR code option value.
	 *
	 * @since 5.7.0
	 *
	 * @deprecated 5.8.0 In favor of `\TEC\Tickets\QR\Settings::get_enabled_option_slug()`
	 *
	 * @var string
	 */
	public static string $qr_code_enabled_option = 'tickets-enable-qr-codes';

	/**
	 * Get the saved API key string.
	 *
	 * @since 5.6.2
	 *
	 * @deprecated 5.8.0 In favor of `\TEC\Tickets\QR\Settings->get_api_key()`
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		_deprecated_function( __METHOD__, '5.8.0', '\TEC\Tickets\QR\Settings->get_api_key()' );

		return tribe( QR_Settings::class )->get_api_key();
	}

	/**
	 * Generate QR API Key.
	 *
	 * @since 4.7.5
	 *
	 * @deprecated 5.8.0 In favor of `\TEC\Tickets\QR\Settings->handle_ajax_generate_api_key()`
	 *
	 */
	public function generate_key() {
		_deprecated_function( __METHOD__, '5.8.0', '\TEC\Tickets\QR\Settings->handle_ajax_generate_api_key()' );
	}

	/**
	 * Check if the QR code feature is enabled.
	 *
	 * @since 5.7.0
	 *
	 * @deprecated 5.8.0 In favor of `\TEC\Tickets\QR\Settings->is_enabled()`
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		_deprecated_function( __METHOD__, '5.8.0', '\TEC\Tickets\QR\Settings->is_enabled()' );

		return tribe( QR_Settings::class )->is_enabled();
	}

	/**
	 * Generate a random number for the QR API Key
	 *
	 * @since 4.7.5
	 *
	 * @deprecated 5.8.0 No replacement.
	 *
	 * @return int $random a random number
	 */
	protected function generate_random_int() {
		_deprecated_function( __METHOD__, '5.8.0', 'No replacement.' );
	}

	/**
	 * Generate a hash key for QR API.
	 *
	 * @since 4.7.5
	 *
	 * @deprecated 5.8.0 In favor of `\TEC\Tickets\QR\Settings->generate_api_key()`
	 *
	 * @return string The QR API key.
	 */
	protected function generate_qr_api_key( $random ) {
		_deprecated_function( __METHOD__, '5.8.0', '\TEC\Tickets\QR\Settings->generate_api_key()' );

		return tribe( QR_Settings::class )->get_api_key();
	}

	/**
	 * Generate a random API key.
	 *
	 * @since 5.2.5
	 *
	 * @deprecated 5.8.0 In favor of `\TEC\Tickets\QR\Settings->generate_api_key()`
	 *
	 * @return string The QR API key.
	 */
	public function generate_new_api_key() {
		_deprecated_function( __METHOD__, '5.8.0', '\TEC\Tickets\QR\Settings->generate_api_key()' );

		return tribe( QR_Settings::class )->get_api_key();
	}

}
