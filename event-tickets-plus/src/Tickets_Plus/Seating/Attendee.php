<?php
/**
 * Handle Attendee related seating actions.
 */

namespace TEC\Tickets_Plus\Seating;

use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;
use Tribe__Utils__Array as Arr;

/**
 * Class Attendee.
 *
 * @since 6.1.0
 *
 * @package TEC/Tickets_Plus/Seating
 */
class Attendee {
	/**
	 * Filter pdf pass template context to include seating label with ticket name.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string,mixed> $context Template context.
	 *
	 * @return array<string,mixed> Filtered Template context.
	 */
	public function filter_pdf_pass_template_context( array $context ): array {
		$attendee = Arr::get( $context, [ 'attendee' ], false );

		if ( ! $attendee ) {
			return $context;
		}

		$ticket_title = Arr::get( $attendee, [ 'ticket' ], false );
		$seat_label   = Arr::get( $attendee, [ 'seat_label' ], false );

		if ( empty( $ticket_title ) || empty( $seat_label ) ) {
			return $context;
		}

		$context['attendee']['ticket'] = sprintf( '%s | %s', $seat_label, $ticket_title );

		return $context;
	}

	/**
	 * Filter pdf pass sample template context to include seating label with ticket name.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string,mixed> $context Template context.
	 *
	 * @return array<string,mixed> Filtered Template context.
	 */
	public function filter_pdf_pass_sample_data( array $context ): array {
		$attendee = Arr::get( $context, [ 'attendee' ], false );

		if ( ! $attendee ) {
			return $context;
		}

		$ticket_title = Arr::get( $attendee, [ 'ticket' ], false );
		$seat_label   = 'F-35'; // Sample seat label.

		if ( empty( $ticket_title ) ) {
			return $context;
		}

		$context['attendee']['ticket'] = sprintf( '%s | %s', $seat_label, $ticket_title );

		return $context;
	}

	/**
	 * Filter apple pass data.
	 *
	 * @param array<string,mixed> $data The pass data.
	 * @param Pass                $pass The pass object.
	 *
	 * @return array
	 */
	public function filter_apple_pass_data( array $data, Pass $pass ): array {
		$attendee = $pass->get_attendee();

		if ( empty( $attendee ) || ! isset( $data['auxiliary'] ) ) {
			return $data;
		}

		$ticket_title = Arr::get( $attendee, [ 'ticket' ], false );
		$seat_label   = Arr::get( $attendee, [ 'seat_label' ], false );

		if ( empty( $ticket_title ) || empty( $seat_label ) ) {
			return $data;
		}

		foreach ( $data['auxiliary'] as $key => $info ) {
			if ( 'ticket_title' === $info['key'] ) {
				$data['auxiliary'][ $key ]['value'] = wp_specialchars_decode( sprintf( '%s | %s', $seat_label, $ticket_title ), ENT_QUOTES );
			}
		}

		return $data;
	}

	/**
	 * Filter apple pass preview data.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string,mixed> $data The pass data.
	 *
	 * @return array<string,mixed> The filtered pass data.
	 */
	public function filter_apple_pass_preview_data( array $data ): array {
		if ( ! isset( $data['auxiliary'] ) ) {
			return $data;
		}

		foreach ( $data['auxiliary'] as $key => $info ) {
			if ( 'ticket_title' === $info['key'] || 'ticket_name' === $info['key'] ) {
				$data['auxiliary'][ $key ]['value'] = sprintf( '%s | %s', 'F-15', $data['auxiliary'][ $key ]['value'] );
			}
		}

		return $data;
	}
}
