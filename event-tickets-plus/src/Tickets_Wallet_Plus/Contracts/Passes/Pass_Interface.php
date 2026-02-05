<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Passes;

/**
 * Interface Pass_Interface
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Contracts\Pass
 */
interface Pass_Interface {
	/**
	 * Creates a new instance of the pass from an attendee ID.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string|int $attendee_id
	 *
	 * @return self
	 */
	public static function from_attendee( $attendee_id ): self;

	/**
	 * Checks if the attendee exists.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool
	 */
	public function attendee_exists(): bool;

	/**
	 * Gets the attendee ID.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return int
	 */
	public function get_attendee_id(): int;

	/**
	 * Gets the event id this pass's attendee is associated with.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return int
	 */
	public function get_event_id(): int;

	/**
	 * Create the Pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function create();

	/**
	 * Get pass URL.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return mixed
	 */
	public function get_url(): ?string;

	/**
	 * Get pass File name.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The pass filename.
	 */
	public function get_filename(): ?string;
}
