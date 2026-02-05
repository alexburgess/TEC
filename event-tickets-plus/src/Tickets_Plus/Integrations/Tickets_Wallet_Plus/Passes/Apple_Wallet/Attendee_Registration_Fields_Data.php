<?php

namespace TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;

/**
 * Class Attendee_Registration_Fields_Data
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Integrations\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Attendee_Registration_Fields_Data {

	/**
	 * Add the Attendee Registration Fields data into the Apple Pass `back` data.
	 *
	 * @since 5.8.0
	 *
	 * @param array $data The Apple Pass data.
	 * @param Pass  $pass The Apple Pass object.
	 *
	 * @return array Modified pass data.
	 */
	public function add_attendee_registration_fields_apple_pass_data( array $data, Pass $pass ): array {
		if ( ! $this->check_arf_status() ) {
			return $data;
		}

		if ( ! $pass->attendee_exists() ) {
			return $data;
		}

		// If the 'back' key doesn't exist or isn't an array, initialize it.
		if ( ! isset( $data['back'] ) || ! is_array( $data['back'] ) ) {
			$data['back'] = [];
		}

		/** @var \Tribe__Tickets_Plus__Meta $meta */
		$meta      = tribe( 'tickets-plus.meta' );
		$attendee  = $pass->get_attendee();
		$ticket_id = $attendee['product_id'];
		$fields    = $meta->get_meta_fields_by_ticket( $ticket_id );

		if ( empty( $fields ) ) {
			return $data;
		}

		$attendee_meta = $meta->get_attendee_meta_values( $ticket_id, $pass->get_attendee_id() );
		$attendee_meta = empty( $attendee_meta ) ? [] : (array) $attendee_meta;

		// Merge the extra data into the 'back' section of the pass data.
		$data['back'] = array_merge( $data['back'], $this->generate_arf_data_format( $attendee_meta ) );

		return $data;
	}

	/**
	 * Check to see if the Attendee Registration Fields setting within Apple Passes is enabled.
	 *
	 * @since 5.8.0
	 *
	 * @return bool True if `Attendee Registration Fields` is enabled.
	 */
	public function check_arf_status(): bool {
		return tribe( Attendee_Registration_Fields_Setting::class )->get_value();
	}

	/**
	 * Generate the correct format for the Attendee Registration Fields data.
	 *
	 * @since 5.8.0
	 *
	 * @param array $attendee_meta Metadata for the attendee.
	 *
	 * @return array
	 */
	public function generate_arf_data_format( $attendee_meta ): array {
		$arf_data_display = [];
		// Loop through the original data to transform it.
		foreach ( $attendee_meta as $key => $value ) {
			$arf_data_display[] = [
				'key'   => sanitize_html_class( $key ),
				'label' => wp_kses_post( $key ),
				'value' => wp_kses_post( $value ),
			];
		}

		return $arf_data_display;
	}

	/**
	 * Generate the correct format for the Attendee Registration Fields data.
	 *
	 * @since 5.8.0
	 *
	 * @param array $data The Apple Pass data.
	 * @param Pass  $pass The Apple Pass object.
	 *
	 * @return array
	 */
	public function add_attendee_meta_to_sample( array $data, Pass $pass ) {
		$sample_data = [
			[
				'key'   => 'age',
				'label' => __( 'Age', 'event-tickets-plus' ),
				'value' => __( '32', 'event-tickets-plus' ),
			],
			[
				'key'   => 't_shirt_size',
				'label' => __( 'T-Shirt Size', 'event-tickets-plus' ),
				'value' => __( 'Medium', 'event-tickets-plus' ),
			],
			[
				'key'   => 'parking_required',
				'label' => __( 'Parking Required', 'event-tickets-plus' ),
				'value' => __( 'Yes', 'event-tickets-plus' ),
			],
			[
				'key'   => 'phone',
				'label' => __( 'Phone', 'event-tickets-plus' ),
				'value' => __( '555-555-5555', 'event-tickets-plus' ),
			],
		];


		if ( ! $this->check_arf_status() ) {
			return $data;
		}

		// If the 'back' key doesn't exist or isn't an array, initialize it.
		if ( ! isset( $data['back'] ) || ! is_array( $data['back'] ) ) {
			$data['back'] = [];
		}

		// Merge the extra data into the 'back' section of the pass data.
		$data['back'] = array_merge( $data['back'], $sample_data );

		return $data;

	}
}
