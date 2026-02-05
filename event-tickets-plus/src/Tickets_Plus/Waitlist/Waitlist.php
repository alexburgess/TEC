<?php
/**
 * The factory class to interact with a single Waitlist.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\StellarWP\DB\DB;
use InvalidArgumentException;
use RuntimeException;
use TEC\Tickets_Plus\Waitlist\Tables\Waitlists as Waitlists_Table;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Tickets\Ticket_Data;

/**
 * Class Waitlist.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Waitlist {
	/**
	 * The valid keys for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected const VALID_KEYS = [
		'waitlist_id' => true,
		'post_id'     => true,
		'enabled'     => true,
		'conditional' => true,
		'type'        => true,
	];

	/**
	 * The difference of minutes for which we consider a ticket's date to be "about to" start/end.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public const ABOUT_TO_MINUTES = 20;

	/**
	 * Conditional ENUM for when the waitlist is always active.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const ALWAYS_CONDITIONAL = 'always';

	/**
	 * Conditional ENUM for when the tickets are before their sale date.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const BEFORE_SALE_CONDITIONAL = 'before-sale';

	/**
	 * Conditional ENUM for when the tickets are sold out.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const ON_SOLD_OUT_CONDITIONAL = 'on-sold-out';

	/**
	 * The valid conditionals for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected const VALID_CONDITIONALS = [
		self::ALWAYS_CONDITIONAL      => 3,
		self::BEFORE_SALE_CONDITIONAL => 2,
		self::ON_SOLD_OUT_CONDITIONAL => 1,
	];

	/**
	 * The ticket type for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public const TICKET_TYPE = 0;

	/**
	 * The RSVP type for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @var int
	 */
	public const RSVP_TYPE = 1;

	/**
	 * The "live" data of the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected array $data = [];

	/**
	 * The original data of the waitlist.
	 *
	 * Compared against the "live" data to determine what update is needed if any.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected array $original_data = [];

	/**
	 * The ticket stats for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @var array
	 */
	protected array $ticket_stats = [];

	/**
	 * Waitlist constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param array $waitlist_array The waitlist data.
	 * @throws InvalidArgumentException If the key is invalid.
	 */
	public function __construct( array $waitlist_array = [] ) {
		foreach ( $waitlist_array as $key => $value ) {
			if ( ! $this->is_valid_key( $key ) ) {
				throw new InvalidArgumentException( 'Invalid key: ' . $key );
			}

			$this->original_data[ $key ] = $value;
		}

		$this->data = $this->original_data;

		// Normalize the conditional.
		if ( isset( $this->original_data['conditional'] ) && ! is_numeric( $this->original_data['conditional'] ) ) {
			$this->set_conditional( $this->original_data['conditional'] );
			$this->original_data['conditional'] = $this->data['conditional'];
		}

		// Normalize the type.
		if ( isset( $this->original_data['type'] ) ) {
			$this->set_type( $this->original_data['type'] );
			$this->original_data['type'] = $this->data['type'];
		}
	}

	/**
	 * Get the ID of the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return int
	 */
	public function get_id(): int {
		return (int) ( $this->data['waitlist_id'] ?? 0 );
	}

	/**
	 * Get the post ID of the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return int|null
	 */
	public function get_post_id(): ?int {
		$id = $this->data['post_id'] ?? null;

		return $id && $id > 0 ? (int) $id : null;
	}

	/**
	 * Get the type of the waitlist.
	 *
	 * 0 for tickets, 1 for RSVP.
	 *
	 * @since 6.2.0
	 *
	 * @return ?int
	 */
	public function get_type(): ?int {
		$type = $this->data['type'] ?? null;
		return null !== $type ? (int) $type : null;
	}

	/**
	 * Get whether the waitlist is enabled or not.
	 *
	 * @since 6.2.0
	 *
	 * @return bool|null
	 */
	public function is_enabled(): ?bool {
		return isset( $this->data['enabled'] ) ? (bool) $this->data['enabled'] : null;
	}

	/**
	 * Check if the waitlist has ended.
	 *
	 * @since 6.2.0
	 *
	 * @return bool
	 */
	public function has_ended(): bool {
		$ticket_stats = $this->get_ticket_stats();

		if ( empty( $ticket_stats['ticket_count'] ) ) {
			// We evaluate the waitlist for an event with no tickets as not started.
			return false;
		}

		/**
		 * Filter whether the waitlist has ended.
		 *
		 * @since 6.2.0
		 *
		 * @param bool     $has_ended    Whether the waitlist has ended.
		 * @param array    $ticket_stats The ticket stats.
		 * @param Waitlist $waitlist  The waitlist.
		 */
		return (bool) apply_filters(
			'tec_tickets_plus_waitlist_has_ended',
			count( $ticket_stats['tickets_have_ended_sales'] ) === $ticket_stats['ticket_count'],
			$ticket_stats,
			$this
		);
	}

	/**
	 * Check if the waitlist is active.
	 *
	 * @since 6.2.0
	 *
	 * @return bool
	 * @throws RuntimeException If the conditional is invalid.
	 */
	public function is_active(): bool {
		if ( ! $this->is_enabled() ) {
			// Is forced disabled.
			return false;
		}

		$ticket_stats = $this->get_ticket_stats();

		if ( empty( $ticket_stats['ticket_count'] ) ) {
			// No tickets, so no waitlist.
			return false;
		}

		if ( $this->has_ended() ) {
			// The event has ended, no need for a waitlist.
			return false;
		}

		if ( $this->has_tickets_on_sale() ) {
			// There are tickets on sale, no need for a waitlist.
			return false;
		}

		$conditional = $this->get_conditional();

		switch ( $conditional ) {
			case static::BEFORE_SALE_CONDITIONAL:
				// The result is whether all the tickets are in their pre-sale state.
				$result = count( $ticket_stats['tickets_have_not_started_sales'] ) === $ticket_stats['ticket_count'];
				break;
			case static::ON_SOLD_OUT_CONDITIONAL:
				// No availability is our indicator for this conditional.
				$result = empty( $ticket_stats['availability'] );
				break;
			case static::ALWAYS_CONDITIONAL:
				// The waitlist should be active whenever there are no tickets on sale! We are here then.
				$result = true;
				break;
			default:
				throw new RuntimeException( 'Invalid conditional for waitlist.' );
		}

		/**
		 * Filter whether the waitlist is active.
		 *
		 * @since 6.2.0
		 *
		 * @param bool     $result       Whether the waitlist is active.
		 * @param string   $conditional  The conditional.
		 * @param array    $ticket_stats The ticket stats.
		 * @param Waitlist $waitlist     The waitlist.
		 */
		return (bool) apply_filters( 'tec_tickets_plus_waitlist_is_active', $result, $conditional, $ticket_stats, $this );
	}

	/**
	 * Get the conditional for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return string|null
	 */
	public function get_conditional(): ?string {
		$flipped = array_flip( self::VALID_CONDITIONALS );

		return $flipped[ $this->data['conditional'] ?? null ] ?? null;
	}

	/**
	 * Get the label for the conditional.
	 *
	 * @since 6.2.0
	 *
	 * @return string
	 */
	public function get_conditional_label(): string {
		$conditional = $this->get_conditional();
		$type        = $this->get_type();

		if ( self::TICKET_TYPE === $type ) {
			switch ( $conditional ) {
				case static::BEFORE_SALE_CONDITIONAL:
					$label = __( 'Shows until tickets sale start date', 'event-tickets-plus' );
					break;
				case static::ON_SOLD_OUT_CONDITIONAL:
					$label = __( 'Shows when ticket capacity runs out', 'event-tickets-plus' );
					break;
				case static::ALWAYS_CONDITIONAL:
				default:
					$label = __( 'Shows before tickets go on sale and when tickets are sold out.', 'event-tickets-plus' );
					break;
			}
		} else {
			switch ( $conditional ) {
				case static::BEFORE_SALE_CONDITIONAL:
					$label = __( 'Shows until RSVP start date', 'event-tickets-plus' );
					break;
				case static::ON_SOLD_OUT_CONDITIONAL:
					$label = __( 'Shows when RSVP capacity runs out', 'event-tickets-plus' );
					break;
				case static::ALWAYS_CONDITIONAL:
				default:
					$label = __( 'Shows before RSVP start date and after RSVP reaches maximum capacity', 'event-tickets-plus' );
					break;
			}
		}


		/**
		 * Filter the label for the waitlist conditional.
		 *
		 * @since 6.2.0
		 *
		 * @param string   $label      The label for the conditional.
		 * @param string   $conditional The conditional.
		 * @param int      $type       The type of the waitlist.
		 * @param Waitlist $waitlist    The waitlist.
		 *
		 * @return string
		 */
		return (string) apply_filters( 'tec_tickets_plus_waitlist_conditional_label', $label, $conditional, $type, $this );
	}

	/**
	 * Delete the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 * @throws RuntimeException If the delete fails.
	 */
	public function delete(): void {
		if ( ! $this->get_id() ) {
			return;
		}

		/**
		 * Fires before deleting a waitlist.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The waitlist.
		 */
		do_action( 'tec_tickets_plus_waitlist_before_delete', $this );

		$result = DB::delete(
			Waitlists_Table::table_name( true ),
			[ 'waitlist_id' => $this->get_id() ],
			[ '%d' ]
		);

		if ( ! $result ) {
			throw new RuntimeException( 'Failed to delete waitlist.' );
		}

		/**
		 * Fires after deleting a waitlist.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The waitlist.
		 */
		do_action( 'tec_tickets_plus_waitlist_after_delete', $this );
	}

	/**
	 * Sets the waitlist as enabled or disabled.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $enabled Whether the waitlist is enabled or not.
	 *
	 * @return Waitlist
	 */
	public function set_enabled( bool $enabled ): self {
		$this->data['enabled'] = (int) $enabled;
		return $this;
	}

	/**
	 * Enable the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return Waitlist
	 */
	public function enable(): self {
		$this->data['enabled'] = 1;
		return $this;
	}

	/**
	 * Disable the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return Waitlist
	 */
	public function disable(): self {
		$this->data['enabled'] = 0;
		return $this;
	}

	/**
	 * Set the conditional for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param string $value The conditional to set.
	 *
	 * @return Waitlist
	 * @throws InvalidArgumentException If the conditional is invalid.
	 */
	public function set_conditional( string $value ): self {
		if ( ! $this->is_valid_conditional( $value ) ) {
			throw new InvalidArgumentException( 'Invalid conditional for waitlist.' );
		}
		$this->data['conditional'] = self::VALID_CONDITIONALS[ $value ];
		return $this;
	}

	/**
	 * Set the type of the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param int $type The type to set.
	 *
	 * @return Waitlist
	 * @throws InvalidArgumentException If the type is invalid.
	 */
	public function set_type( int $type ): self {
		if ( ! in_array( $type, [ self::TICKET_TYPE, self::RSVP_TYPE ], true ) ) {
			throw new InvalidArgumentException( 'Invalid type for waitlist.' );
		}

		$this->data['type'] = $type;
		return $this;
	}

	/**
	 * Set the post ID of the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param int $post_id The post ID to set.
	 *
	 * @return Waitlist
	 * @throws InvalidArgumentException If the post ID is invalid.
	 */
	public function set_post_id( int $post_id ): self {
		if ( $post_id < 1 ) {
			throw new InvalidArgumentException( 'Invalid post ID for waitlist.' );
		}

		$this->data['post_id'] = $post_id;
		return $this;
	}

	/**
	 * Convert the waitlist to an array.
	 *
	 * @since 6.2.0
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'waitlist_id' => $this->get_id(),
			'post_id'     => $this->get_post_id(),
			'enabled'     => $this->is_enabled(),
			'conditional' => $this->get_conditional(),
			'type'        => $this->get_type(),
		];
	}

	/**
	 * Check if the waitlist has tickets.
	 *
	 * @since 6.2.0
	 *
	 * @return bool
	 */
	public function has_tickets(): bool {
		$ticket_stats = $this->get_ticket_stats();

		return ! empty( $ticket_stats['ticket_count'] );
	}

	/**
	 * Check if the waitlist has tickets about to go on sale.
	 *
	 * @since 6.2.0
	 *
	 * @return bool
	 */
	public function has_tickets_about_to_go_on_sale(): bool {
		return ! empty( $this->get_tickets_about_to_go_on_sale() );
	}

	/**
	 * Get the tickets on sale for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return array
	 */
	public function get_tickets_on_sale(): array {
		$ticket_stats = $this->get_ticket_stats();

		return $ticket_stats['tickets_on_sale'];
	}

	/**
	 * Get the tickets about to go on sale for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return array
	 */
	public function get_tickets_about_to_go_on_sale(): array {
		$ticket_stats = $this->get_ticket_stats();

		return $ticket_stats['tickets_about_to_go_to_sale'];
	}

	/**
	 * Check if the waitlist has tickets on sale.
	 *
	 * @since 6.2.0
	 *
	 * @return bool
	 */
	public function has_tickets_on_sale(): bool {
		return ! empty( $this->get_tickets_on_sale() );
	}

	/**
	 * Save the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $force Whether to force the save or not.
	 *
	 * @return int The ID of the waitlist.
	 */
	public function save( bool $force = false ): int {
		$method = $this->get_id() ? 'update' : 'insert';

		$this->{$method}( $force );

		$this->original_data = $this->data;

		return $this->get_id();
	}

	/**
	 * Update the waitlist by post ID.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $force         Whether to force the update or not.
	 * @param bool $fail_silently Whether to fail silently or not.
	 *
	 * @return void
	 * @throws RuntimeException If the update fails.
	 * @throws DatabaseQueryException If the query fails while not failing silently.
	 */
	public function update_by_post_id( $force = false, $fail_silently = false ): void {
		$this->validate( $force );
		$to_be_updated = $force ? $this->data : array_diff_assoc( $this->data, $this->original_data );

		unset( $to_be_updated['waitlist_id'], $to_be_updated['post_id'], $to_be_updated['type'] );

		if ( empty( $to_be_updated ) ) {
			return;
		}

		try {
			$result = DB::update(
				Waitlists_Table::table_name( true ),
				$to_be_updated,
				[
					'post_id' => $this->get_post_id(),
					'type'    => $this->get_type(),
				],
				array_fill( 0, count( $to_be_updated ), '%d' ),
				[ '%d', '%d' ]
			);
		} catch ( DatabaseQueryException $e ) {
			if ( ! $fail_silently ) {
				throw $e;
			}
			return;
		}

		if ( ! ( $result || $fail_silently ) ) {
			throw new RuntimeException( 'Failed to update waitlist.' );
		}
	}

	/**
	 * Insert the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 * @throws RuntimeException If the insert fails.
	 */
	protected function insert(): void {
		$this->validate();

		unset( $this->data['waitlist_id'] );

		$result = DB::insert(
			Waitlists_Table::table_name( true ),
			$this->data,
			[
				'%d',
				'%d',
				'%d',
			],
		);

		if ( ! $result ) {
			throw new RuntimeException( 'Failed to insert waitlist.' );
		}

		$this->data['waitlist_id'] = (int) DB::last_insert_id();
	}

	/**
	 * Update the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $force Whether to force the update or not.
	 *
	 * @return void
	 * @throws RuntimeException If the update fails.
	 */
	protected function update( $force = false ): void {
		$this->validate( $force );

		if ( ! $this->get_id() ) {
			throw new RuntimeException( 'Cannot update a waitlist without an ID.' );
		}

		$id = $this->get_id();

		$to_be_updated = $force ? $this->data : array_diff_assoc( $this->data, $this->original_data );

		unset( $to_be_updated['waitlist_id'] );

		if ( empty( $to_be_updated ) ) {
			return;
		}

		$result = DB::update(
			Waitlists_Table::table_name( true ),
			$to_be_updated,
			[ 'waitlist_id' => $id ],
			array_fill( 0, count( $to_be_updated ), '%d' ),
			[ '%d' ]
		);

		if ( ! $result ) {
			throw new RuntimeException( 'Failed to update waitlist.' );
		}
	}

	/**
	 * Validate the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param bool $force Whether this is a force update validation.
	 *
	 * @return void
	 * @throws InvalidArgumentException If the waitlist is invalid.
	 */
	protected function validate( $force = false ) {
		if ( ! $this->get_post_id() ) {
			throw new InvalidArgumentException( 'Post ID is required to create a waitlist.' );
		}

		if ( ! in_array( $this->get_type(), [ self::TICKET_TYPE, self::RSVP_TYPE ], true ) ) {
			throw new InvalidArgumentException( 'Invalid type for waitlist.' );
		}

		if ( ! $force && ! isset( $this->data['enabled'] ) ) {
			throw new InvalidArgumentException( 'Waitlist must be enabled or disabled to be created.' );
		}

		if ( ! $force || isset( $this->data['enabled'] ) ) {
			// Normalize the data.
			$this->data['enabled'] = (int) $this->is_enabled();
		}

		if ( ! $force && empty( $this->data['conditional'] ) ) {
			throw new InvalidArgumentException( 'Waitlist must have a conditional to be created.' );
		}

		if ( ! empty( $this->data['conditional'] ) && ! in_array( $this->data['conditional'], array_values( self::VALID_CONDITIONALS ), true ) ) {
			throw new InvalidArgumentException( 'Invalid conditional for waitlist.' );
		}
	}

	/**
	 * Check if a key is valid for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param string $key The key to check.
	 *
	 * @return bool
	 */
	protected function is_valid_key( string $key ): bool {
		return isset( self::VALID_KEYS[ $key ] );
	}

	/**
	 * Check if a conditional is valid for the waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @param string $value The conditional to check.
	 *
	 * @return bool
	 */
	protected function is_valid_conditional( string $value ) {
		return isset( self::VALID_CONDITIONALS[ $value ] );
	}

	/**
	 * Get the ticket stats for the waitlist.
	 *
	 * @since 6.2.0
	 * @since 6.5.1 Updated to use the Ticket_Data class.
	 *
	 * @return array
	 */
	protected function get_ticket_stats(): array {
		if ( ! empty( $this->ticket_stats ) ) {
			return $this->ticket_stats;
		}

		$ticket_data = tribe( Ticket_Data::class );

		$waitlists = tribe( Waitlists::class );

		$waitlists->add_about_to_seconds_hook();

		$this->ticket_stats = self::TICKET_TYPE === $this->get_type() ? $ticket_data->get_posts_tickets_data( $this->get_post_id() ) : $ticket_data->get_posts_rsvps_data( $this->get_post_id() );

		$waitlists->remove_about_to_seconds_hook();

		return $this->ticket_stats;
	}
}
