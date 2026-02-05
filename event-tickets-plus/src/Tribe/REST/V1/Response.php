<?php
/**
 * Class Tribe__Tickets_Plus__REST__V1__Response
 *
 * Filters the REST API v1 response to add fields and information managed by Event Tickets Plus.
 *
 * @since 4.8
 */
class Tribe__Tickets_Plus__REST__V1__Response {
	/**
	 * Filters the data that will be returned for a single ticket.
	 *
	 * @since 4.8
	 *
	 * @param array|WP_Error  $data             The ticket data or a WP_Error if the request
	 *                                          generated errors.
	 *
	 * @return array|WP_Error  The modified ticket data.
	 */
	public function filter_single_ticket_data( $data ) {
		if ( $data instanceof WP_Error ) {
			return $data;
		}

		if ( ! isset( $data['id'] ) ) {
			return $data;
		}

		$ticket_id            = $data['id'];
		$ticket_meta_enabled  = tribe_is_truthy( get_post_meta( $ticket_id, Tribe__Tickets_Plus__Meta::ENABLE_META_KEY, true ) );
		$ticket_meta          = (array) get_post_meta( $ticket_id, Tribe__Tickets_Plus__Meta::META_KEY, true );
		$required_ticket_meta = wp_list_filter( $ticket_meta, [ 'required' => 'on' ] );

		$supports_attendee_information         = $ticket_meta_enabled && count( $ticket_meta ) > 0;
		$data['supports_attendee_information'] = $supports_attendee_information;
		$data['requires_attendee_information'] = $data['supports_attendee_information'] && count( $required_ticket_meta ) > 0;
		$data['attendee_information_fields']   = $supports_attendee_information
			? array_filter( array_map( [ $this, 'build_field_information' ], $ticket_meta ) )
			: [];

		return $data;
	}

	/**
	 * Builds an attendee meta field information.
	 *
	 * @since 4.8
	 *
	 * @param array|stdClass $field The attendee field information.
	 *
	 * @return array|bool The attendee meta field information if valid, `false` otherwise.
	 */
	protected function build_field_information( $field ) {
		$field = (array) $field;

		if ( ! isset( $field['slug'], $field['type'], $field['label'] ) ) {
			return false;
		}

		$field_data = [
			'slug'     => $field['slug'],
			'type'     => $field['type'],
			'required' => tribe_is_truthy( Tribe__Utils__Array::get( $field, 'required', false ) ),
			'label'    => $field['label'],
			'extra'    => Tribe__Utils__Array::get( $field, 'extra', [] ),
		];

		return $field_data;
	}

	/**
	 * Filters the data that will be returned for a single attendee.
	 *
	 * @since 4.8
	 *
	 * @param array|WP_Error $data             The attendee data or a WP_Error if the request
	 *                                         generated errors.
	 *
	 * @return array|WP_Error  The modified attendee data.
	 */
	public function filter_single_attendee_data( $data ) {
		if ( $data instanceof WP_Error ) {
			return $data;
		}

		if ( ! tribe( 'tickets.rest-v1.main' )->request_has_manage_access() ) {
			return $data;
		}

		if ( ! isset( $data['id'] ) ) {
			return $data;
		}

		/** @var \Tribe__Tickets_Plus__Meta $meta */
		$meta      = tribe( 'tickets-plus.meta' );
		$ticket_id = $data['ticket_id'];
		$fields    = $meta->get_meta_fields_by_ticket( $ticket_id );

		if ( empty( $fields ) ) {
			return $data;
		}

		$attendee_id   = $data['id'];
		$attendee_meta = $meta->get_attendee_meta_values( $ticket_id, $attendee_id );

		$data['information'] = empty( $attendee_meta ) ? [] : (array) $attendee_meta;

		return $data;
	}

	/**
	 * Filter and validate attendee meta data for update.
	 *
	 * @since 5.4.2
	 *
	 * @param array           $data Data that needs to be updated.
	 * @param WP_REST_Request $request Request object.
	 * @param array           $attendee Attendee data that will be updated.
	 *
	 * @return array | WP_Error
	 */
	public function filter_single_attendee_update_data( $data, $request, $attendee ) {
		if ( ! isset( $data['attendee_meta'] ) ) {
			return $data;
		}

		return $this->validate_attendee_meta( $attendee['product_id'], $data );
	}

	/**
	 * Filter and validate attendee meta data for create.
	 *
	 * @since 5.4.2
	 *
	 * @param array           $data    Data that needs to be updated.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array | WP_Error
	 */
	public function filter_single_attendee_create_data( $data, $request ) {
		if ( ! isset( $data['attendee_meta'] ) ) {
			return $data;
		}

		return $this->validate_attendee_meta( $data['ticket_id'], $data );
	}

	/**
	 * Validate attendee data.
	 *
	 * @since 5.4.2
	 *
	 * @param int   $ticket_id Ticket ID.
	 * @param array $data      Ticket Data.
	 *
	 * @return mixed|WP_Error
	 */
	public function validate_attendee_meta( $ticket_id, $data ) {
		if ( ! tribe_tickets_has_meta_fields( $ticket_id ) ) {
			return new WP_Error( 'invalid-meta-fields', __( 'This attendee has no meta fields associated with it.', 'event-tickets-plus' ), [ 'status' => 400 ] );
		}

		$meta_fields   = (array) get_post_meta( $ticket_id, Tribe__Tickets_Plus__Meta::META_KEY, true );
		$attendee_meta = $data['attendee_meta'];

		foreach ( $meta_fields as $field ) {
			if ( 'on' === $field['required'] && ! isset( $attendee_meta[ $field['slug'] ] ) ) {
				return new WP_Error(
					'missing-required-meta-fields',
					__( 'Some required attendee data is missing.', 'event-tickets-plus' ),
					[
						'status'        => 400,
						'attendee_meta' => $meta_fields,
					]
				);
			}
		}

		return $data;
	}
}
