<?php

namespace TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes;

use TEC\Tickets_Plus\Admin\Tabs\Attendee_Registration;
use Tribe__Template;
use Tribe__Tickets_Plus__Main as Tickets_Plus;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings\Qr_Codes_Setting;

/**
 * Class Pdf
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes
 */
class Pdf {

	/**
	 * Template instance.
	 *
	 * @since 5.8.0
	 *
	 * @var Tribe__Template
	 */
	private $template;

	/**
	 * Get the template.
	 *
	 * @since 5.8.0
	 *
	 * @return Tribe__Template
	 */
	public function get_template(): Tribe__Template {
		if ( empty( $this->template ) ) {
			$template = new Tribe__Template();
			$template->set_template_origin( Tickets_Plus::instance() );
			$template->set_template_folder( 'src/views/integrations/event-tickets-wallet-plus/pdf' );
			$template->set_template_folder_lookup( true );
			$template->set_template_context_extract( true );
			$this->template = $template;
		}
		return $this->template;
	}

	/**
	 * Add styles.
	 *
	 * @since 5.8.0
	 *
	 * @param string          $file     Path to the file.
	 * @param string          $name     Name of the file.
	 * @param Tribe__Template $template Template instance.
	 *
	 * @return void
	 */
	public function add_styles( $file, $name, $template ): void {
		if ( ! $template instanceof Tribe__Template ) {
			return;
		}

		$this->get_template()->template( 'pass/tec-tickets-plus-styles', $template->get_local_values(), true );
	}

	/**
	 * Add attendee fields.
	 *
	 * @since 5.8.0
	 *
	 * @param string          $file     Path to the file.
	 * @param string          $name     Name of the file.
	 * @param Tribe__Template $template Template instance.
	 *
	 * @return void
	 */
	public function add_attendee_fields( $file, $name, $template ) {
		if ( ! $template instanceof Tribe__Template ) {
			return;
		}

		$args = $template->get_local_values();

		/** @var \Tribe__Tickets_Plus__Meta $meta */
		$meta        = tribe( 'tickets-plus.meta' );
		$attendee_id = $args['attendee']['attendee_id'];
		$ticket_id   = $args['attendee']['product_id'];

		$is_preview = ! empty( $args['is_preview'] );

		if ( ! $is_preview ) {
			$attendee_meta = $meta->get_attendee_meta_values( $ticket_id, $attendee_id );
			$args['attendee']['attendee_meta'] = ! empty( $attendee_meta ) ? $attendee_meta : [];
		}

		$this->get_template()->template( 'pass/body/ticket-info/attendee-fields', $args, true );
	}

	/**
	 * Add attendee meta to sample.
	 *
	 * @since 5.8.0
	 *
	 * @param array $context The context.
	 *
	 * @return array $context The modified context.
	 */
	public function add_attendee_meta_to_sample( $context ) {
		if ( empty( $context['attendee'] ) ) {
			return $context;
		}

		$context['is_preview'] = true;

		$context['attendee']['attendee_meta'] = [
			__( 'Age', 'event-tickets-plus' )              => __( '32', 'event-tickets-plus' ),
			__( 'T-Shirt Size', 'event-tickets-plus' )     => __( 'Medium', 'event-tickets-plus' ),
			__( 'Parking Required', 'event-tickets-plus' ) => __( 'Yes', 'event-tickets-plus' ),
			__( 'Phone', 'event-tickets-plus' )            => __( '555-555-5555', 'event-tickets-plus' ),
		];

		return $context;
	}

	/**
	 * Add attendee registration fields setting to PDF settings.
	 *
	 * @since 5.8.0
	 *
	 * @param array $fields PDF settings.
	 */
	public function add_attendee_registration_fields_setting( $fields ): array {
		$attendee_registration_fields_setting = tribe( Pdf\Attendee_Registration_Fields_Setting::class );
		$setting_key                          = $attendee_registration_fields_setting->get_key();
		$setting_definition                   = $attendee_registration_fields_setting->get_definition();
		$qr_code_key                          = tribe( Qr_Codes_Setting::class )->get_key();
		$arf_setting                          = [ $setting_key => $setting_definition ];

		// We want our Settings field after the QR code setting, leaving the credit one after it.
		$fields = \Tribe__Main::array_insert_after_key(
			$qr_code_key,
			$fields,
			$arf_setting
		);

		return $fields;
	}
}
