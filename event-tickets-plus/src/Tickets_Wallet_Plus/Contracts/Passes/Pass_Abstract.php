<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Passes;

use Tribe__Utils__Array as Arr;

use WP_Error;

/**
 * Class Pass_Abstract
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Contracts\Pass
 */
abstract class Pass_Abstract implements Pass_Interface {
	/**
	 * Stores the attendee ID.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var int The attendee ID.
	 */
	protected int $attendee_id = 0;

	/**
	 * Stores the attendee data.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var ?array The attendee data.
	 */
	protected ?array $attendee = null;

	/**
	 * @inheritDoc
	 */
	public static function from_attendee( $attendee_id ): self {
		$pass = new static();
		$pass->attendee_id( $attendee_id );

		return $pass;
	}

	/**
	 * Sets the attendee ID.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string|int $attendee_id The attendee ID.
	 *
	 * @return $this
	 */
	protected function attendee_id( $attendee_id ): self {
		$this->attendee_id = (int) $attendee_id;

		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function attendee_exists(): bool {
		return $this->attendee_id !== 0; // @todo this verification can be better, maybe check all the possible post types.
	}

	/**
	 * @inheritDoc
	 */
	public function get_attendee_id(): int {
		return $this->attendee_id;
	}

	/**
	 * Gets the attendee data, and stores locally to avoid having to do it again.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return ?array
	 */
	public function get_attendee(): ?array {
		if ( ! $this->attendee_exists() ) {
			return null;
		}

		if ( is_array( $this->attendee ) ) {
			return $this->attendee;
		}

		/** @var \Tribe__Tickets__Data_API $data_api */
		$data_api = tribe( 'tickets.data_api' );

		/** @var \Tribe__Tickets__Tickets $provider */
		$provider = $data_api->get_ticket_provider( $this->get_attendee_id() );

		if ( empty( $provider ) ) {
			// The attendee post does exist but it does not make sense on the server, server error.
			return null;
		}

		// The return value of this function will always be an array even if we only want one object.
		$attendee = $provider->get_all_attendees_by_attendee_id( $this->get_attendee_id() );

		if ( empty( $attendee ) ) {
			// The attendee post does exist but it does not make sense on the server, server error.
			return null;
		}

		if ( $attendee instanceof WP_Error ) {
			$this->attendee = null;
		} else {
			$this->attendee = reset( $attendee );
		}

		return $this->attendee;
	}

	/**
	 * @inheritDoc
	 */
	public function get_event_id(): int {
		if ( ! $this->attendee_exists() ) {
			return 0;
		}

		$attendee = $this->get_attendee();

		if ( empty( $attendee ) ) {
			return 0;
		}

		return Arr::get( $attendee, 'post_id', Arr::get( $attendee, 'event_id', 0 ) );
	}

	/**
	 * @inheritDoc
	 */
	abstract public function create();

	/**
	 * @inheritDoc
	 */
	abstract public function get_url(): ?string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_filename(): ?string;
}
