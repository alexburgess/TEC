<?php
/**
 * Class that handles integration with duplicating Ticket Feature.
 *
 * @since 6.0.5
 *
 * @package TEC\Tickets_Plus\Integrations\Event_Tickets
 */

namespace TEC\Tickets_Plus\Integrations\Event_Tickets;

use TEC\Common\Contracts\Service_Provider;
use Tribe\Tickets\Plus\Attendee_Registration\IAC;
use Tribe__Tickets_Plus__Meta as Tickets_Plus_Meta;

/**
 * Duplicate Post Provider
 *
 * @since 6.0.5
 *
 * @package TEC\Tickets_Plus\Integrations
 */
class Duplicate_Ticket_Provider extends Service_Provider {
	/**
	 * Register filters
	 *
	 * @since 6.0.5
	 */
	public function register() {
		add_action( 'tec_tickets_ticket_duplicated', [ $this, 'add_metadata_to_duplicated_ticket' ], 10, 2 );
	}

	/**
	 * Add metadata to duplicated ticket
	 *
	 * @since 6.0.5
	 *
	 * @param int $new_ticket_id      Duplicated ticket ID.
	 * @param int $original_ticket_id Original ticket ID.
	 */
	public function add_metadata_to_duplicated_ticket( $new_ticket_id, $original_ticket_id ) {
		update_post_meta(
			$new_ticket_id,
			tribe( IAC::class )->get_iac_setting_ticket_meta_key(),
			get_post_meta( $original_ticket_id, tribe( IAC::class )->get_iac_setting_ticket_meta_key(), true )
		);

		update_post_meta(
			$new_ticket_id,
			Tickets_Plus_Meta::ENABLE_META_KEY,
			get_post_meta( $original_ticket_id, Tickets_Plus_Meta::ENABLE_META_KEY, true )
		);

		update_post_meta(
			$new_ticket_id,
			Tickets_Plus_Meta::META_KEY,
			get_post_meta( $original_ticket_id, Tickets_Plus_Meta::META_KEY, true )
		);
	}
}
