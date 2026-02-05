<?php
namespace TEC\Tickets_Plus\Emails\Email;

use TEC\Tickets\QR\Connector;

/**
 * Class Components.
 * Contains the common components for the emails.
 *
 * @since 5.7.0
 */
abstract class Components {

	/**
	 * Add attendee meta data.
	 *
	 * @since 5.7.0
	 *
	 * @param array $args Array of arguments.
	 *
	 * @return array $args Modified array of arguments.
	 */
	public function add_attendee_meta_to_args( array $args ): array {
		// Add dummy meta data for preview.
		if ( ! empty( $args['is_preview'] ) && tribe_is_truthy( $args['is_preview'] ) ) {
			$args['ticket']['attendee_meta'] = [
				__( 'Age', 'event-tickets-plus' )              => __( '32', 'event-tickets-plus' ),
				__( 'T-Shirt Size', 'event-tickets-plus' )     => __( 'Medium', 'event-tickets-plus' ),
				__( 'Parking Required', 'event-tickets-plus' ) => __( 'Yes', 'event-tickets-plus' ),
				__( 'Phone', 'event-tickets-plus' )            => __( '555-555-5555', 'event-tickets-plus' ),
			];

			return $args;
		}

		/** @var \Tribe__Tickets_Plus__Meta $meta */
		$meta          = tribe( 'tickets-plus.meta' );
		$attendee_id   = $args['ticket']['attendee_id'];
		$ticket_id     = $args['ticket']['product_id'];
		$attendee_meta = $meta->get_attendee_meta_values( $ticket_id, $attendee_id );

		$args['ticket']['attendee_meta'] = ! empty( $attendee_meta ) ? $attendee_meta : [];
		return $args;
	}

	/**
	 * Render the Attendee Registration fields template.
	 *
	 * @since 5.7.0
	 *
	 * @param array $args Array of arguments.
	 */
	public function render_ar_fields_template( array $args ): void {
		$args = $this->add_attendee_meta_to_args( $args );

		/** @var \Tribe__Tickets_Plus__Template $template */
		$template = tribe( 'tickets-plus.template' );
		$template->template( 'emails/template-parts/body/ticket/ar-fields', $args, true );
	}

	/**
	 * Render the Attendee Registration styles template.
	 *
	 * @since 5.7.0
	 *
	 * @param array $args Array of arguments.
	 */
	public function render_ar_field_styles( array $args ): void {
		/** @var \Tribe__Tickets_Plus__Template $template */
		$template = tribe( 'tickets-plus.template' );
		$template->template( 'emails/template-parts/header/head/ar-styles', $args, true );
	}

	/**
	 * Render the QR code template.
	 *
	 * @since 5.7.0
	 *
	 * @param array $args Array of arguments.
	 */
	public function render_qr_code_template( array $args ): void {
		if ( ! tribe_is_truthy( $args['include_qr'] ) ) {
			return;
		}

		$args['qr'] = $args['preview'] ?
			esc_url( plugins_url( '/event-tickets-plus/src/resources/images/example-qr.png' ) ):
			tribe( Connector::class )->get_image_url_from_ticket_data( $args['ticket'] );

		/** @var \Tribe__Tickets_Plus__Template $template */
		$template = tribe( 'tickets-plus.template' );
		$template->template( 'emails/template-parts/body/ticket/qr-image', $args, true );
	}

	/**
	 * Render the styles for Tickets Emails features.
	 *
	 * @since 5.7.3
	 *
	 * @param array $args Array of arguments.
	 */
	public function render_styles( array $args ): void {
		/** @var \Tribe__Tickets_Plus__Template $template */
		$template = tribe( 'tickets-plus.template' );
		$template->template( 'emails/template-parts/header/head/etp-styles', $args, true );
	}
}
