<?php

class Tribe__Tickets_Plus__REST__V1__Post_Repository extends Tribe__Tickets__REST__V1__Post_Repository {
	/**
	 * Returns an array representation of an attendee.
	 *
	 * @since 4.7.5
	 * @deprecated 5.8.0
	 *
	 * @param int    $attendee_id A attendee post ID.
	 * @param string $context  Context of data.
	 *
	 * @return array|WP_Error Either the array representation of an attendee or an error object.
	 *
	 */
	public function get_qr_data( $attendee_id, $context = '' ) {
		_deprecated_function( __METHOD__, '5.8.0', 'Method moved to Tribe__Tickets__REST__V1__Post_Repository' );

		return [];
	}

}

