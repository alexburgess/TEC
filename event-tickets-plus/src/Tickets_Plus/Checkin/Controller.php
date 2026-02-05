<?php
/**
 * Controller for managing check-in related functionality.
 *
 * This file contains the controller class for managing check-in features,
 * including displaying duplicate check-in notices and logs in the admin interface.
 *
 * @since 6.7.0
 *
 * @package TEC\Tickets_Plus\Checkin;
 */

namespace TEC\Tickets_Plus\Checkin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets_Plus\Checkin\Constants as Checkin_Constants;

/**
 * Class Controller.
 *
 * Manages check-in related functionality for Event Tickets Plus,
 * including displaying duplicate check-in notices and logs in the admin interface.
 *
 * @since 6.7.0
 *
 * @package TEC\Tickets_Plus\Checkin;
 */
class Controller extends Controller_Contract {

	/**
	 * Registers the actions and filters for the controller.
	 *
	 * Sets up the singleton instance and registers all hooks required
	 * for displaying duplicate check-in notices and logs.
	 *
	 * @since 6.7.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		$this->container->singleton( static::class, $this );
	}

	/**
	 * Unregisters all actions and filters registered by this controller.
	 *
	 * Removes hooks for displaying duplicate check-in notices and logs.
	 *
	 * @since 6.7.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		// These are not currently being added above but will be in a future iteration.
		remove_action( 'tribe_tickets_attendees_event_details_list_top', [ $this, 'add_duplicate_checkin_notice' ], 24 );
		remove_filter( 'tec_tickets_attendees_table_column_check_in', [ $this, 'add_duplicate_checkin_info' ], 10, 2 );
	}

	/**
	 * Adds a notice about duplicate check-ins to the event details.
	 *
	 * Displays the total count of duplicate check-ins for the specified event
	 * at the top of the attendees list if any duplicates exist.
	 *
	 * @since 6.7.0
	 *
	 * @param int $event_id The ID of the event to display check-in notices for.
	 */
	public function add_duplicate_checkin_notice( $event_id ) {
		$duplicate_checkin_count = $this->get_duplicate_checkin_count( $event_id );
		$duplicate_attendees     = $this->get_duplicate_checkin_attendee_count( $event_id );

		if ( $duplicate_checkin_count < 1 ) {
			return;
		}

		echo '
			<li class="checkin-log-notice">
				<strong>' . esc_html__( 'Duplicate Checkins', 'event-tickets-plus' ) . ': </strong><br/>'
				. sprintf(
					/* translators: 1: number of attendees with duplicate check-ins, 2: total number of duplicate check-ins */
					esc_html__( '%1$d attendees for a total of %2$d duplicate check-ins.', 'event-tickets-plus' ),
					absint( $duplicate_attendees ),
					absint( $duplicate_checkin_count )
				)
				. '<br/>'
				. '<i>'
				. esc_html__( 'See individual attendees for checkin logs.', 'event-tickets-plus' )
				. '</i>'
			. '</li>';
	}

	/**
	 * Adds a modal with check-in log information to the check-in column.
	 *
	 * Retrieves check-in logs for attendees and creates a modal button
	 * to display them when clicked.
	 *
	 * @since 6.7.0
	 *
	 * @param string $html The existing HTML for the check-in column.
	 * @param array  $item The current attendee item data.
	 *
	 * @return string Modified HTML with the check-in log modal button added.
	 */
	public function add_duplicate_checkin_info( $html, $item ) {
		// Check if there are check-in logs for this attendee.
		$attendee_id = absint( $item['attendee_id'] );
		$raw_logs    = get_post_meta(
			$attendee_id,
			Checkin_Constants::CHECKIN_LOGGING_META_KEY,
			false
		);

		if ( empty( $raw_logs ) ) {
			return $html;
		}

		// Process the logs - each item might be serialized.
		$checkin_logs = [];
		foreach ( $raw_logs as $log ) {
			// If it's a string, try to unserialize it.
			if ( is_string( $log ) ) {
				$unserialized = maybe_unserialize( $log );
				if ( $unserialized !== $log ) {
					$checkin_logs[] = $unserialized;
				} else {
					// If unserialization failed, keep the original string.
					$checkin_logs[] = $log;
				}
			} else {
				// If it's already an array or other type, keep it as is.
				$checkin_logs[] = $log;
			}
		}

		// Create context for the template.
		$template_context = [
			'checkin_logs' => $checkin_logs,
			'attendee_id'  => $attendee_id,
		];

		/** @var \Tribe__Tickets_Plus__Admin__Views $admin_views */
		$admin_views = tribe( 'tickets-plus.admin.views' );

		// Generate the modal content from template.
		$modal_content = $admin_views->template( 'attendees/modal/checkin-log', $template_context, false );

		// Create a unique ID for this button.
		$btn_id = uniqid();

		// Button args for the modal.
		$button_args = [
			'id'                      => 'tec-tickets__admin-checkin-log-' . $btn_id,
			'append_target'           => '#tec-tickets__admin-checkin-details-' . $btn_id,
			'button_classes'          => [ 'button', 'button-secondary', 'tec-tickets__admin-checkin-history' ],
			'button_attributes'       => [ 'data-modal-title' => esc_html__( 'Duplicate Check In Log', 'event-tickets-plus' ) ],
			'button_display'          => true,
			'button_id'               => 'tec-tickets__admin-checkin-details-' . $btn_id,
			'button_name'             => 'tec-tickets-checkin-details',
			'button_text'             => '<span class="dashicons dashicons-info-outline" title="' . esc_attr__( 'View Duplicate Check In Log', 'event-tickets-plus' ) . '"></span>',
			'button_type'             => 'button',
			'content_wrapper_classes' => 'tribe-dialog__wrapper event-tickets tribe-common',
			'title'                   => esc_html__( 'Duplicate Check In Log', 'event-tickets-plus' ),
			'title_classes'           => [
				'tribe-dialog__title',
				'tribe-modal__title',
				'tribe-common-h5',
			],
		];

		// Add the modal button to HTML.
		$html .= '<div class="tribe-common">' . tribe( 'dialog.view' )->render_modal( $modal_content, $button_args, null, false ) . '</div>';

		return $html;
	}

	/**
	 * Get the failed check-in counts.
	 *
	 * @since 6.7.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return array The failed check-ins.
	 */
	public function get_failed_checkins( $event_id ) {
		return [
			'total'               => $this->get_failed_checkin_count( $event_id ),
			'duplicate'           => $this->get_duplicate_checkin_count( $event_id ),
			'duplicate_attendees' => $this->get_duplicate_checkin_attendee_count( $event_id ),
			'security'            => $this->get_security_checkin_count( $event_id ),
			'checkout'            => $this->get_cant_checkout_count( $event_id ),
		];
	}

	/**
	 * Get the failed check-in count for an event.
	 *
	 * @since 6.7.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The total failed check-in count.
	 */
	public function get_failed_checkin_count( $event_id ) {
		return (int) get_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_COUNT_META_KEY, true );
	}

	/**
	 * Get the duplicate check-in count for an event.
	 *
	 * @since 6.7.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The duplicate check-in count.
	 */
	public function get_duplicate_checkin_count( $event_id ) {
		return (int) get_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_META_KEY, true );
	}

	/**
	 * Get the count of attendees that have duplicate check-ins for an event.
	 *
	 * @since 6.7.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The count of attendees that have duplicate check-ins.
	 */
	public function get_duplicate_checkin_attendee_count( $event_id ) {
		return (int) get_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_ATTENDEES_META_KEY, true );
	}

	/**
	 * Get the security failure check-in count for an event.
	 *
	 * @since 6.7.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The security failure check-in count.
	 */
	public function get_security_checkin_count( $event_id ) {
		return (int) get_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_SECURITY_META_KEY, true );
	}

	/**
	 * Get the count of attendees that cannot be checked out for an event.
	 *
	 * @since 6.7.0
	 *
	 * @param int $event_id The event ID.
	 *
	 * @return int The count of attendees that cannot be checked out.
	 */
	public function get_cant_checkout_count( $event_id ) {
		return (int) get_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_CHECKOUT_META_KEY, true );
	}
}
