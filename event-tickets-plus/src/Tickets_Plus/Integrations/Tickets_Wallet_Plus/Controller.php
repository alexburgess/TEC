<?php

namespace TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus;

use TEC\Common\Integrations\Traits\Plugin_Integration;
use TEC\Tickets_Plus\Integrations\Integration_Abstract;
use TEC\Tickets_Wallet_Plus\Controller as Tickets_Wallet_Plus;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;
use Tribe__Template;

/**
 * Class Controller
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Integrations
 */
class Controller extends Integration_Abstract {
	use Plugin_Integration;

	/**
	 * {@inheritdoc}
	 */
	public static function get_slug(): string {
		return 'event-tickets-wallet-plus';
	}

	/**
	 * {@inheritdoc}
	 */
	public function load_conditionals(): bool {
		return tribe( Tickets_Wallet_Plus::class )->is_active();
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(): void {
		$this->register_actions();
		$this->register_filters();
	}

	/**
	 * Register actions.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function register_actions(): void {
		add_action( 'tribe_template_after_include:tickets-plus/tickets-wallet-plus/pdf/pass/styles', [ $this, 'add_styles_to_pdf' ], 10, 3 );
		add_action( 'tribe_template_after_include:tickets-plus/tickets-wallet-plus/pdf/pass/body/ticket-info/attendee-details', [ $this, 'add_attendee_fields_to_pdf' ], 10, 3 );
	}

	/**
	 * Register filters.
	 *
	 * @since 5.8.0
	 *
	 * @return void
	 */
	public function register_filters(): void {
		add_filter( 'tec_tickets_wallet_plus_pdf_settings_fields', [ $this, 'add_attendee_registration_fields_setting' ] );
		add_filter( 'tec_tickets_wallet_plus_pdf_sample_template_context', [ $this, 'add_attendee_meta_to_sample_pdf' ] );

		add_filter( 'tec_tickets_wallet_plus_apple_settings_fields', [ $this, 'add_attendee_registration_fields_apple_wallet_setting' ], 10, 2 );
		add_filter( 'tec_tickets_wallet_plus_apple_pass_data', [ $this, 'add_attendee_registration_fields_apple_pass_data' ], 10, 2 );
		add_filter( 'tec_tickets_wallet_plus_apple_preview_pass_data', [ $this, 'add_attendee_meta_to_sample_apple_wallet_pass' ], 10, 2 );
	}

	/**
	 * Add styles to PDF.
	 *
	 * @since 5.8.0
	 *
	 * @param string          $file     Path to the file.
	 * @param string          $name     Name of the file.
	 * @param Tribe__Template $template Template instance.
	 *
	 * @return void
	 */
	public function add_styles_to_pdf( $file, $name, $template ) {
		$this->container->make( Passes\Pdf::class )->add_styles( $file, $name, $template );
	}

	/**
	 * Add attendee fields to PDF.
	 *
	 * @since 5.8.0
	 *
	 * @param string          $file     Path to the file.
	 * @param string          $name     Name of the file.
	 * @param Tribe__Template $template Template instance.
	 *
	 * @return void
	 */
	public function add_attendee_fields_to_pdf( $file, $name, $template ) {
		if ( ! $this->container->make( Passes\Pdf\Attendee_Registration_Fields_Setting::class )->get_value() ) {
			return;
		}
		$this->container->make( Passes\Pdf::class )->add_attendee_fields( $file, $name, $template );
	}

	/**
	 * Add attendee meta to Sample PDF.
	 *
	 * @since 5.8.0
	 *
	 * @param array $context Path to the file.
	 *
	 * @return array
	 */
	public function add_attendee_meta_to_sample_pdf( $context ): array {
		return $this->container->make( Passes\Pdf::class )->add_attendee_meta_to_sample( $context );
	}


	/**
	 * Add attendee registration field setting to PDF settings.
	 *
	 * @since 5.8.0
	 *
	 * @param array $fields The fields.
	 */
	public function add_attendee_registration_fields_setting( $fields ) {
		return $this->container->make( Passes\Pdf::class )->add_attendee_registration_fields_setting( $fields );
	}

	/**
	 * Add attendee registration field setting to Apple Wallet passes settings.
	 *
	 * @since 5.8.0
	 *
	 * @param array $fields The fields.
	 *
	 * @return array
	 */
	public function add_attendee_registration_fields_apple_wallet_setting( $fields ): array {
		return $this->container->make( Passes\Apple_Wallet\Settings::class )->add_attendee_registration_fields_setting( $fields );
	}

	/**
	 * Add attendee registration fields to the Apple Wallet Pass.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $data The Apple Pass data.
	 * @param Pass  $pass The Apple Pass object.
	 *
	 * @return array
	 */
	public function add_attendee_registration_fields_apple_pass_data( $data, $pass ): array {
		return $this->container->make( Passes\Apple_Wallet\Attendee_Registration_Fields_Data::class )->add_attendee_registration_fields_apple_pass_data( $data, $pass );
	}

	/**
	 * Add attendee meta to Sample Apple Wallet Pass.
	 *
	 * @since 5.8.0
	 *
	 * @param array<string,mixed> $data The Apple Pass data.
	 * @param Pass  $pass The Apple Pass object.
	 *
	 * @return array
	 */
	public function add_attendee_meta_to_sample_apple_wallet_pass( $data, $pass ): array {
		return $this->container->make( Passes\Apple_Wallet\Attendee_Registration_Fields_Data::class )->add_attendee_meta_to_sample( $data, $pass );
	}
}
