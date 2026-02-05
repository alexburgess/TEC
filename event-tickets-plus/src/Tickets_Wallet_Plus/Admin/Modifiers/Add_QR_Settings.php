<?php

namespace TEC\Tickets_Wallet_Plus\Admin\Modifiers;

use TEC\Tickets\QR\Settings as QR_Settings;
use TEC\Tickets\QR\Connector as QR_Connector;
use TEC\Common\QR\Controller as QR_Controller;
use TEC\Common\QR\Notices as QR_Notices;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;

/**
 * Class Add_QR_Settings
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Admin\Modifiers
 */
class Add_QR_Settings {
	use Generic_Template;

	/**
	 * Stores the template folder that will be used by the Generic Template Trait.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The template folder.
	 */
	protected string $template_folder = 'src/admin-views/tickets-wallet-plus';

	/**
	 * Append global Event Tickets Plus settings section to tickets settings tab
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $settings_fields
	 *
	 * @return array
	 */
	public function add_settings( array $settings_fields ) {
		$extra_settings = $this->additional_settings();

		if ( isset( $settings_fields[ QR_Settings::get_enabled_option_slug() ] ) ) {
			return $settings_fields;
		}

		return \Tribe__Main::array_insert_before_key( 'tribe-form-content-end', $settings_fields, $extra_settings );
	}

	/**
	 * Adds the general ticket QR code settings to the Events ‣ Settings ‣ Tickets screen.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function additional_settings( array $settings = [] ) {
		$ticket_label_plural_lower = esc_html( tribe_get_ticket_label_plural_lowercase( 'check_in_app' ) );

		$qr_settings = [
			'tickets-plus-qr-options-title'        => [
				'type' => 'html',
				'html' => '<h3>' . esc_html__( 'Event Tickets Plus App', 'event-tickets-plus' ) . '</h3>',
			],
			'tickets-plus-qr-options-intro'        => [
				'type' => 'html',
				'html' => $this->get_intro_html(),
			],
			'tickets-plus-qr-options-app-banner'   => [
				'type' => 'html',
				'html' => $this->get_app_banner(),
			],
			QR_Settings::get_enabled_option_slug() => [
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
			if ( in_array( \Tribe__Events__Main::POSTTYPE, $enabled_post_types ) ) {
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

		return \Tribe__Main::array_insert_before_key(
			'tribe-form-content-end',
			$settings,
			$qr_settings
		);
	}

	/**
	 * Get the QR setting intro html content.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
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
				       esc_html__(
					       'Our Event Tickets Plus app makes on-site ticket validation and attendee management a breeze. Available for mobile devices through the iOS %1$s and %2$s
store. Learn more about the app in our %3$s.', 'event-tickets-wallet-plus'
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
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return false|string Settings banner html.
	 */
	protected function get_app_banner(): string {
		if ( ! tribe( QR_Controller::class )->can_use() ) {
			return '';
		}

		$qr_settings       = tribe( QR_Settings::class );
		$connector         = tribe( QR_Connector::class );
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
		tribe_asset_enqueue( 'tec-tickets-qr-connector' );

		return $this->get_template()->template( 'settings/etp-app-banner', $template_vars, false );
	}
}
