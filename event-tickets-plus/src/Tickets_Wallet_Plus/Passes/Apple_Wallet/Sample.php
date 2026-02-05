<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use TEC\Tickets\QR\Settings as QR_Settings;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Qr_Codes_Setting;

/**
 * Class Sample
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Sample extends Pass {
	/**
	 * Generates the sample pass data for an Apple Wallet Pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array The generated pass data.
	 */
	protected function get_data(): array {
		// Initialize pass data.
		$data = parent::get_data();

		$data['description'] = __( 'Sample description for a General Admission Ticket.', 'event-tickets-plus' );
		$data['auxiliary'][] = [
			'key'   => 'ticket_name',
			'label' => esc_html__( 'Ticket', 'event-tickets-plus' ),
			'value' => esc_html__( 'General Admission', 'event-tickets-plus' ),
		];

		$data['header'][] = [
			'key'   => 'attendee_id_header',
			'label' => 'ID',
			'value' => '#123456',
		];

		$data['primary'][] = [
			'key'   => 'ticket_holder',
			'label' => esc_html__( 'Full Name', 'event-tickets-plus' ),
			'value' => esc_html__( 'John Doe', 'event-tickets-plus' ),
		];

		$qr_data = home_url();

		if (
			! empty( $qr_data )
			&& ! empty( tribe( Qr_Codes_Setting::class )->get_value() )
			&& ! empty( tribe( QR_Settings::class )->is_enabled() )
		) {
			$data['barcode'] = [
				'format'          => 'PKBarcodeFormatQR',
				'message'         => $qr_data,
				'messageEncoding' => 'iso-8859-1',
			];
		}

		/**
		 * Filter the preview apple pass data.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array<string,mixed> $pass_data The preview Apple Pass data.
		 * @param Sample              $sample The Sample object.
		 */
		return apply_filters( 'tec_tickets_wallet_plus_apple_preview_pass_data', $data, $this );
	}
}
