<?php
/**
 * A pseudo-enum class to store the values that relate Tickets Presets to Posts (tickets).
 * Note these are Ticket-centric, the relationsships are based on the Ticket, not the Preset.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Enums;

/**
 * Class Ticket_Preset_To_Post_Relationship_Keys.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */
class Ticket_Preset_To_Post_Relationship_Keys {

	/**
	 * The meta value relating a Ticket as a child of a Preset.
	 * Indicates the ticket was created from a Preset.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public const IS_CHILD = 'tec_tickets_ticket_from_preset';

	/**
	 * The meta value relating a Preset as a child of a Ticket.
	 * Indicates the preset was based on a Ticket.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public const IS_PARENT = 'tec_tickets_ticket_to_preset';

	/**
	 * Returns the list of all the meta values that relate Tickets to Presets.
	 *
	 * @since 6.6.0
	 *
	 * @return array<string>
	 */
	public static function all(): array {
		return [
			// Ticket is parent of Preset.
			self::IS_PARENT,
			// Ticket is child of Preset.
			self::IS_CHILD,
		];
	}
}
