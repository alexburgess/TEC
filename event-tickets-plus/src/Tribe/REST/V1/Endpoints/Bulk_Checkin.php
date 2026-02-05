<?php
/**
 * Class Tribe\Tickets\Plus\REST\V1\Endpoints\Bulk_Checkin
 *
 * @since 6.7.0
 *
 * @package Tribe\Tickets\REST\V1\Endpoints
 */

namespace Tribe\Tickets\Plus\REST\V1\Endpoints;

use TEC\Tickets_Plus\Checkin\Constants as Checkin_Constants;
use Tribe\Utils\Date_I18n_Immutable;
use Tribe__Documentation__Swagger__Provider_Interface;
use Tribe__Tickets__REST__V1__Endpoints__QR;
use Tribe__Tickets__REST__V1__Validator__Base;
use Tribe__Tickets__Tickets as Tickets;
use Tribe__Timezones;
use DateTimeZone;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Tribe\Tickets\Plus\REST\V1\Endpoints\Bulk_Checkin.
 *
 * @since 6.7.0
 *
 * @package Tribe\Tickets\REST\V1\Endpoints
 */
class Bulk_Checkin extends Tribe__Tickets__REST__V1__Endpoints__QR implements Tribe__Documentation__Swagger__Provider_Interface { // phpcs:ignore StellarWP.Classes.ValidClassName.NotSnakeCase, PEAR.NamingConventions.ValidClassName.Invalid, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace

	/**
	 * The REST API endpoint path.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	protected $path = 'bulk-checkins';

	/**
	 * Device ID for the current request.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	protected $device_id;

	/**
	 * Counter for failed check-ins by event_id and reason.
	 *
	 * @example
	 * [
	 *     'event_id' => [
	 *         'count' => 1,
	 *         'DUPLICATE' => 1,
	 *         'SECURITY' => 1,
	 *         'CHECKOUT' => 1,
	 *     ],
	 * ]
	 *
	 * @since 6.7.0
	 *
	 * @var array
	 */
	protected $failed_checkin_counts = [];

	/**
	 * Set of unique attendees with duplicate check-ins by event_id.
	 *
	 * @example
	 * [
	 *     'event_id' => [
	 *         'attendee_id_1' => true,
	 *         'attendee_id_2' => true,
	 *     ],
	 * ]
	 *
	 * @since 6.7.0
	 *
	 * @var array
	 */
	protected $duplicate_attendees = [];

	/**
	 * An instance of the Tribe__Tickets__REST__V1__Validator__Base handler.
	 *
	 * @since 6.7.0
	 *
	 * @var Tribe__Tickets__REST__V1__Validator__Base
	 */
	protected $validator;

	/**
	 * Constructor.
	 *
	 * @since 6.7.0
	 *
	 * @param Tribe__Tickets__REST__V1__Validator__Base $validator Validator instance.
	 */
	public function __construct( Tribe__Tickets__REST__V1__Validator__Base $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Gets the Endpoint path for this route.
	 *
	 * @since 6.7.0
	 *
	 * @return string
	 */
	public function get_endpoint_path() {
		return $this->path;
	}

	/**
	 * Register the actual endpoint on WP Rest API.
	 *
	 * @since 6.7.0
	 */
	public function register() {
		register_rest_route(
			tribe( 'tickets-plus.rest-v1.main' )->get_events_route_namespace(),
			$this->get_endpoint_path(),
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'args'                => $this->bulk_check_in_args(),
				'callback'            => [ $this, 'bulk_check_in' ],
				'permission_callback' => [ $this, 'can_access' ],
			]
		);

		/** @var Tribe__Documentation__Swagger__Builder_Interface $endpoint */
		$endpoint = tribe( 'tickets.rest-v1.endpoints.documentation' );

		$endpoint->register_documentation_provider( $this->get_endpoint_path(), $this );
	}

	/**
	 * Whether the current request can access the endpoint.
	 *
	 * @since 6.7.0
	 *
	 * @param WP_REST_Request $request The request instance.
	 *
	 * @return bool Whether the current request can access the endpoint.
	 */
	public function can_access( $request ) {
		$request_params = $request->get_params();

		// Ensure required params are present.
		if (
			empty( $request_params['api_key'] )
			|| empty( $request_params['device_id'] )
		) {
			return false;
		}

		$qr_arr = [ 'api_key' => $request_params['api_key'] ];

		if ( $this->has_api( $qr_arr ) ) {
			$this->device_id = $request_params['device_id'];

			return true;
		}

		return false;
	}

	/**
	 * Additional arguments for the bulk check-in endpoint.
	 *
	 * @since 6.7.0
	 *
	 * @return array
	 */
	public function bulk_check_in_args() {
		return [
			'api_key'   => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_string' ],
				'type'              => 'string',
				'description'       => __( 'The API key to authorize checkin or checkout.', 'event-tickets-plus' ),
			],
			'device_id' => [
				'required'          => true,
				'validate_callback' => [ $this->validator, 'is_string' ],
				'type'              => 'string',
				'description'       => __( 'The device id for the checkin or checkout.', 'event-tickets-plus' ),
			],
		];
	}

	/**
	 * Process bulk check-ins
	 *
	 * @since 6.7.0
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @return WP_REST_Response
	 */
	public function bulk_check_in( WP_REST_Request $request ) {
		$request_body = $request->get_body();
		$check_ins    = json_decode( $request_body, true );

		// Validate we have check-ins to process.
		if ( empty( $check_ins ) || ! is_array( $check_ins ) ) {
			$response = new WP_REST_Response(
				[
					'msg'   => __( 'No check-in data provided', 'event-tickets-plus' ),
					'error' => 'no_checkin_data',
				]
			);
			$response->set_status( 400 );

			return $response;
		}

		/**
		 * Filters the maximum number of check-ins that can be processed in bulk.
		 *
		 * @since 6.7.0
		 *
		 * @param int $limit The maximum number of check-ins allowed in a bulk operation.
		 *
		 * @return int The filtered maximum number of check-ins.
		 */
		$check_in_limit = apply_filters( 'tec_tickets_plus_bulk_checkin_limit', 50 );

		// Enforce the entries limit.
		if ( count( $check_ins ) > $check_in_limit ) {
			$response = new WP_REST_Response(
				[
					'msg'   => __( 'Too many check-in requests. Maximum is 50.', 'event-tickets-plus' ),
					'error' => 'too_many_checkins',
				]
			);
			$response->set_status( 400 );

			return $response;
		}

		// Organize data by attendee ID and sort by timestamp.
		$organized_data = $this->organize_check_in_data( $check_ins );

		// Process the organized data.
		$this->process_check_in_data( $organized_data );

		// Update event meta with failed check-in counts.
		$this->update_event_checkin_counts();

		// Return only status 200 with no message for successful operations.
		$response = new WP_REST_Response();
		$response->set_status( 200 );

		return $response;
	}

	/**
	 * Organize check-in data by attendee ID and sort by timestamp.
	 *
	 * @since 6.7.0
	 *
	 * @param array $check_ins The check-in data.
	 *
	 * @return array Organized data by attendee ID, sorted by timestamp (oldest first).
	 */
	private function organize_check_in_data( $check_ins ) {
		$organized = [];

		// Organize data by attendee_id.
		foreach ( $check_ins as $check_in ) {
			$attendee_id = $check_in['attendee_id'];
			if ( ! isset( $organized[ $attendee_id ] ) ) {
				$organized[ $attendee_id ] = [];
			}

			// Convert timestamp to site datetime for better sorting.
			$check_in['timestamp']       = $this->convert_to_site_timezone( $check_in['timestamp'] );
			$organized[ $attendee_id ][] = $check_in;
		}

		// Sort each attendee's check-ins by timestamp, oldest first.
		foreach ( $organized as $attendee_id => $attendee_checkins ) {
			usort(
				$attendee_checkins,
				function ( $a, $b ) {
					$time_a = strtotime( $a['timestamp'] );
					$time_b = strtotime( $b['timestamp'] );

					// Sort ascending (oldest first).
					return $time_a - $time_b;
				}
			);

			$organized[ $attendee_id ] = $attendee_checkins;
		}

		return $organized;
	}

	/**
	 * Process the organized check-in data.
	 *
	 * @since 6.7.0
	 *
	 * @param array $organized_data The organized check-in data.
	 */
	private function process_check_in_data( $organized_data ) {
		/** @var Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		// Process each attendee's check-ins.
		foreach ( $organized_data as $attendee_id => $attendee_checkins ) {
			$attendee_id = (int) $attendee_id;

			// Process each check-in chronologically.
			foreach ( $attendee_checkins as $check_in ) {
				$event_id              = (int) $check_in['event_id'];
				$security_code         = $check_in['security_code'];
				$status                = (bool) $check_in['status'];
				$check_in['device_id'] = (string) $this->device_id;

				// Get ticket provider for the attendee.
				$ticket_provider = $data_api->get_ticket_provider( $attendee_id );

				// Validate security code.
				if (
					$security_code !== null // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
					&&
						(
							empty( $ticket_provider->security_code )
							|| get_post_meta( $attendee_id, $ticket_provider->security_code, true ) !== $security_code
						)
				) {
					$this->log_failed_checkin( $check_in, Checkin_Constants::CHECKIN_LOGGING_SECURITY_FLAG );
					continue;
				}

				// Check if checked in.
				$is_checked_in = $this->is_attendee_checked_in( $attendee_id, $ticket_provider );

				// Process check-in or check-out based on status.
				// phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
				if ( $status === true ) { // Check-in.
					if ( $is_checked_in ) {
						// Already checked in, skip.
						$this->log_failed_checkin( $check_in, Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_FLAG );
						continue;
					}

					$this->do_check_in( $attendee_id, $event_id, $ticket_provider, $check_in );
				} else { // Check-out.
					if ( ! $is_checked_in ) {
						// Not checked in, can't check out.
						$this->log_failed_checkin( $check_in, Checkin_Constants::CHECKIN_LOGGING_CHECKOUT_FLAG );
						continue;
					}

					$this->do_uncheckin( $attendee_id, $ticket_provider );
				}
			}
		}
	}

	/**
	 * Log failed check-in attempts and increment the counter by event_id.
	 *
	 * @since 6.7.0
	 *
	 * @param array  $check_in The check-in data.
	 * @param string $reason   The reason for failure.
	 */
	private function log_failed_checkin( $check_in, $reason ) {
		// Add log entry for the failed check-in.
		add_post_meta(
			$check_in['attendee_id'],
			Checkin_Constants::CHECKIN_LOGGING_META_KEY,
			array_merge(
				[ 'reason' => $reason ],
				[ 'label' => $this->get_reason_label( $reason ) ],
				$check_in
			)
		);

		// Create or increment counters for this event_id and reason.
		$event_id = (int) $check_in['event_id'];

		// Total count.
		if ( ! isset( $this->failed_checkin_counts[ $event_id ]['count'] ) ) {
			$this->failed_checkin_counts[ $event_id ]['count'] = 0;
		}

		++$this->failed_checkin_counts[ $event_id ]['count'];

		// Reason counts.
		if ( ! isset( $this->failed_checkin_counts[ $event_id ][ $reason ] ) ) {
			$this->failed_checkin_counts[ $event_id ][ $reason ] = 0;
		}

		++$this->failed_checkin_counts[ $event_id ][ $reason ];

		// Track unique attendees with duplicate check-ins.
		if ( $reason === Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_FLAG ) {
			$attendee_id = (int) $check_in['attendee_id'];

			if ( ! isset( $this->duplicate_attendees[ $event_id ] ) ) {
				$this->duplicate_attendees[ $event_id ] = [];
			}

			$this->duplicate_attendees[ $event_id ][ $attendee_id ] = true;
		}
	}

	/**
	 * Check if an attendee is already checked in.
	 *
	 * @since 6.7.0
	 *
	 * @param int    $attendee_id     The attendee ID.
	 * @param object $ticket_provider The ticket provider.
	 *
	 * @return bool
	 */
	private function is_attendee_checked_in( $attendee_id, $ticket_provider ) {
		return (bool) get_post_meta( $attendee_id, $ticket_provider->checkin_key, true );
	}

	/**
	 * Checks in an attendee for an event using the specified ticket provider.
	 *
	 * This method handles the actual check-in process, recording the check-in
	 * details including timestamp and device information.
	 *
	 * @since 6.7.0
	 *
	 * @param int                 $attendee_id     The attendee ID to check in.
	 * @param int                 $event_id        The ID of the ticketable post the Attendee is being checked into.
	 * @param Tickets             $ticket_provider The Attendee ticket provider.
	 * @param array<string|mixed> $details         Check-out details including timestamp and device_id information.
	 *
	 * @return boolean Whether the check in was successful or not.
	 */
	private function do_check_in( $attendee_id, $event_id, $ticket_provider, $details ) {
		if ( empty( $ticket_provider ) ) {
			return false;
		}

		// Set parameter to true for the QR app - it is false for the original url so that the message displays.
		$success = $ticket_provider->checkin( $attendee_id, true, $event_id, $details );
		if ( $success ) {
			return $success;
		}

		return false;
	}

	/**
	 * Check out attendee with timestamp and device_id details for tracking purposes.
	 *
	 * @since 6.7.0
	 *
	 * @param int     $attendee_id     The attendee ID.
	 * @param Tickets $ticket_provider The Attendee ticket provider.
	 *
	 * @return boolean Whether the check out was successful or not.
	 */
	private function do_uncheckin( $attendee_id, $ticket_provider ) {
		if ( empty( $ticket_provider ) ) {
			return false;
		}

		$success = $ticket_provider->uncheckin( $attendee_id, true );
		if ( $success ) {
			return $success;
		}

		return false;
	}

	/**
	 * Update event meta with the count of failed check-ins
	 *
	 * @since 6.7.0
	 */
	protected function update_event_checkin_counts() {
		// Only proceed if we have failed check-ins to log.
		if ( empty( $this->failed_checkin_counts ) ) {
			return;
		}

		// Update each event's meta count.
		foreach ( $this->failed_checkin_counts as $event_id => $data ) {
			// Total count.
			$existing_count = (int) get_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_COUNT_META_KEY, true );
			$new_count      = $existing_count + $data['count'];

			update_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_COUNT_META_KEY, (int) $new_count, $existing_count );

			// Update each reason's meta count.
			foreach ( $data as $reason => $count ) {
				// Skip the 'count' key as it's handled above.
				if ( $reason === 'count' ) {
					continue;
				}

				$key                   = $this->get_reason_key( $reason );
				$existing_reason_count = (int) get_post_meta( $event_id, $key, true );
				$new_reason_count      = $existing_reason_count + $count;

				update_post_meta( $event_id, $key, (int) $new_reason_count, $existing_reason_count );
			}

			// Update unique attendees with duplicate check-ins count.
			if ( isset( $this->duplicate_attendees[ $event_id ] ) ) {
				$existing_attendee_count = (int) get_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_ATTENDEES_META_KEY, true );
				$new_attendee_count      = $existing_attendee_count + count( $this->duplicate_attendees[ $event_id ] );

				update_post_meta( $event_id, Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_ATTENDEES_META_KEY, (int) $new_attendee_count, $existing_attendee_count );
			}
		}
	}

	/**
	 * Convert timestamp to the site's local timezone.
	 *
	 * @since 6.7.0
	 *
	 * @param string $gmt_time RFC 1123 formatted GMT timestamp (e.g. 'Wed, 14 Jun 2017 07:00:00 GMT').
	 *
	 * @return string Formatted datetime in site timezone (Y-m-d H:i:s), or empty string if invalid.
	 */
	protected function convert_to_site_timezone( string $gmt_time ): string {
		// Parse the input as UTC.
		$utc_datetime = Date_I18n_Immutable::createFromFormat( DATE_RFC1123, $gmt_time, new DateTimeZone( 'UTC' ) );

		if ( ! $utc_datetime ) {
			return '';
		}

		// Get the site's timezone string using Tribe's helper.
		$site_timezone_string = Tribe__Timezones::wp_timezone_string();

		// Normalize the string (in case it's a UTC offset).
		$site_timezone_string = Tribe__Timezones::generate_timezone_string_from_utc_offset( $site_timezone_string );

		// Set the correct timezone.
		$datetime_in_site_tz = $utc_datetime->setTimezone( new DateTimeZone( $site_timezone_string ) );

		return $datetime_in_site_tz->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Returns the documentation for the bulk check-in endpoint.
	 *
	 * @since 6.7.0
	 *
	 * @return array
	 */
	public function get_documentation() {
		return [
			'post' => [
				'consumes'   => [ 'application/json' ],
				'parameters' => [
					[
						'name'        => 'check_ins',
						'in'          => 'body',
						'description' => __( 'Array of check-in requests (max 50)', 'event-tickets-plus' ),
						'required'    => true,
						'schema'      => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'event_id'      => [
										'type'        => 'integer',
										'description' => __( 'The event ID', 'event-tickets-plus' ),
									],
									'attendee_id'   => [
										'type'        => 'integer',
										'description' => __( 'The attendee ID', 'event-tickets-plus' ),
									],
									'security_code' => [
										'type'        => 'string',
										'description' => __( 'Security code for verification', 'event-tickets-plus' ),
									],
									'timestamp'     => [
										'type'        => 'string',
										'description' => __( 'Timestamp of the check-in request', 'event-tickets-plus' ),
									],
									'status'        => [
										'type'        => 'boolean',
										'description' => __( 'Check-in status: true for check-in, false for check-out', 'event-tickets-plus' ),
									],
								],
							],
						],
					],
				],
				'responses'  => [
					'200' => [
						'description' => __( 'Successfully processed check-ins/check-outs. Returns empty response with 200 status code.', 'event-tickets-plus' ),
					],
					'400' => [
						'description' => __( 'Invalid request format or too many check-in requests', 'event-tickets-plus' ),
						'schema'      => [
							'type'       => 'object',
							'properties' => [
								'msg'   => [
									'type'        => 'string',
									'description' => __( 'Error message', 'event-tickets-plus' ),
								],
								'error' => [
									'type'        => 'string',
									'description' => __( 'Error code', 'event-tickets-plus' ),
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Get the meta key for the reason based on the passed constant.
	 *
	 * @since 6.7.0
	 *
	 * @param string $reason The reason meta key.
	 *
	 * @return string
	 */
	protected function get_reason_key( $reason ) {
		switch ( $reason ) {
			case Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_FLAG:
				return Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_META_KEY;
			case Checkin_Constants::CHECKIN_LOGGING_SECURITY_FLAG:
				return Checkin_Constants::CHECKIN_LOGGING_SECURITY_META_KEY;
			case Checkin_Constants::CHECKIN_LOGGING_CHECKOUT_FLAG:
				return Checkin_Constants::CHECKIN_LOGGING_CHECKOUT_META_KEY;
			default:
				return Checkin_Constants::CHECKIN_LOGGING_META_KEY;
		}
	}

	/**
	 * Get the translated label for the reason.
	 *
	 * @since 6.7.0
	 *
	 * @param string $reason The reason.
	 *
	 * @return string The translated label for the reason.
	 */
	public function get_reason_label( $reason ) {
		switch ( $reason ) {
			case Checkin_Constants::CHECKIN_LOGGING_DUPLICATE_FLAG:
				return __( 'Attendee already checked in', 'event-tickets-plus' );
			case Checkin_Constants::CHECKIN_LOGGING_SECURITY_FLAG:
				return __( 'Invalid security code', 'event-tickets-plus' );
			case Checkin_Constants::CHECKIN_LOGGING_CHECKOUT_FLAG:
				return __( 'Attendee not checked in, cannot check out', 'event-tickets-plus' );
			default:
				return __( 'Unknown reason', 'event-tickets-plus' );
		}
	}
}
