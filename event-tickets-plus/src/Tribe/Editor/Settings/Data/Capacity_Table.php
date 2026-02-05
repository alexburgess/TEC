<?php
namespace Tribe\Tickets\Plus\Editor\Settings\Data;

use Tribe__Tickets__Tickets;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets__Global_Stock as Global_Stock;

/**
 * Class Capacity_Table
 *
 * @since 5.9.0
 *
 * @package Tribe\Tickets\Plus\Editor\Settings\Data
 */
class Capacity_Table {
	/**
	 * The ID of the event.
	 *
	 * @since 5.9.0
	 *
	 * @var int
	 */
	protected int $post_id;

	/**
	 * The tickets for the current event.
	 *
	 * @since 5.9.0
	 *
	 * @var Ticket_Object[]
	 */
	protected array $tickets = [];

	/**
	 * The tickets by type.
	 *
	 * @since 5.9.0
	 *
	 * @var array<string, array>
	 */
	protected array $tickets_by_types = [];

	/**
	 * The capacity by type.
	 *
	 * @since 5.9.0
	 *
	 * @var array<string, array>
	 */
	protected array $capacity_by_types = [];

	/**
	 * The total capacity.
	 *
	 * @since 5.9.0
	 *
	 * @var int
	 */
	protected int $total_capacity = 0;

	/**
	 * Capacity_Table constructor.
	 *
	 * @since 5.9.0
	 *
	 * @param int $post_id The ID of the event.
	 */
	public function __construct( int $post_id ) {
		$this->post_id = $post_id;
		$this->build_data();
	}

	/**
	 * Build the data for the capacity table.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	public function build_data(): void {
		foreach ( $this->get_tickets() as $ticket ) {
			$type   = $ticket->type();
			$mode   = $ticket->global_stock_mode();
			$shared = ! empty( $mode ) && Global_Stock::OWN_STOCK_MODE !== $mode;
			$capacity = is_null( $ticket->capacity ) || $ticket->capacity === -1 ? 'unlimited' : $ticket->capacity;

			$ticket_data = [
				'ticket'   => $ticket,
				'type'     => $type,
				'shared'   => $shared,
				'capacity' => $capacity,
				'mode'     => empty( $mode ) ? 'own' : $mode,
			];

			$this->tickets_by_types[ $type ][] = $ticket_data;
		}

		$this->process_capacity_by_type();
		$this->calculate_total_capacity();
	}

	/**
	 * Get the tickets for the current event.
	 *
	 * @since 5.9.0
	 *
	 * @return Ticket_Object[] A list of the Tickets to be displayed.
	 */
	public function get_tickets(): array {
		$this->tickets = Tribe__Tickets__Tickets::get_event_tickets( $this->post_id );

		/**
		 * Filters the tickets to be displayed on the capacity table.
		 *
		 * @since 5.9.0
		 *
		 * @param Ticket_Object[] $tickets The tickets to be displayed.
		 * @param int $post_id The ID of the event.
		 * @param Capacity_Table $capacity_table The instance of the capacity table.
		 */
		return apply_filters( 'tec_tickets_plus_editor_capacity_table_tickets', $this->tickets, $this->post_id, $this );
	}

	/**
	 * Get the tickets by type.
	 *
	 * @since 5.9.0
	 *
	 * @return array<string, array> A map from ticket types to the tickets of that type.
	 */
	public function get_tickets_by_types(): array {
		/**
		 * Filters the tickets by type to be displayed on the capacity table.
		 *
		 * @since 5.9.0
		 *
		 * @param array<string, array> $tickets_by_types The tickets by type to be displayed.
		 * @param int $post_id The ID of the event.
		 * @param Capacity_Table $capacity_table The instance of the capacity table.
		 */
		return apply_filters( 'tec_tickets_plus_editor_capacity_table_tickets_by_type', $this->tickets_by_types, $this->post_id, $this );
	}

	/**
	 * Process the capacity by type.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	public function process_capacity_by_type(): void {
		$types = $this->get_tickets_by_types();

		// Add the default type if not already available.
		$types = isset( $types['default'] ) ? $types : [ 'default' => [] ] + $types;

		$capacity_counts = [];
		foreach ( $types as $type => $items ) {
			$counts = [
				'shared'      => [ 'capacity' => 0, 'labels' => [] ],
				'independent' => [],
				'unlimited'   => [],
			];

			foreach ( $items as $ticket_data ) {
				$capacity = $ticket_data['capacity'];
				$shared   = $ticket_data['shared'];

				if ( $shared ) {
					if ( $capacity > (int) $counts['shared']['capacity'] ) {
						// Only use the value if the ticket is not capped or if we do not already have a global capacity.
						$counts['shared']['capacity'] = $capacity;
					}

					$counts['shared']['labels'][] = $ticket_data['ticket']->name;
					continue;
				}

				if ( $capacity === 'unlimited' ) {
					$counts['unlimited'][] = $ticket_data['ticket']->name;
					continue;
				}

				$counts['independent'][] = [
					'capacity' => $capacity,
					'label' => $ticket_data['ticket']->name,
				];
			}

			$capacity_counts[ $type ] = $counts;
		}

		$this->capacity_by_types = $capacity_counts;
	}

	/**
	 * Get the capacity by type.
	 *
	 * @since 5.9.0
	 *
	 * @return array<string, array> A map from ticket types to the capacity data for each ticket type.
	 */
	public function get_capacity_by_type(): array {
		/**
		 * Filters the capacity by type for the capacity table.
		 *
		 * @since 5.9.0
		 *
		 * @param array<string, array> $capacity_by_types The capacity by type to be displayed.
		 * @param int $post_id The ID of the event.
		 * @param Capacity_Table $capacity_table The instance of the capacity table.
		 */
		return apply_filters( 'tec_tickets_plus_editor_capacity_table_capacity_by_type', $this->capacity_by_types, $this->post_id, $this );
	}

	/**
	 * Get the default ticket capacity.
	 *
	 * @since 5.9.0
	 *
	 * @return array<string, array> The capacity components for the default ticket type.
	 */
	public function get_default_ticket_capacity(): array {
		$capacity_types = $this->get_capacity_by_type();
		return $capacity_types['default'];
	}

	/**
	 * Get the default RSVP capacity.
	 *
	 * @since 5.9.0
	 *
	 * @return array The capacity components for the default rsvp type.
	 */
	public function get_default_rsvp_type_capacity(): array {
		$capacity_types = $this->get_capacity_by_type();

		if ( isset( $capacity_types['rsvp'] ) ) {
			unset( $capacity_types['rsvp']['shared'] );
		}

		return $capacity_types['rsvp'] ?? [];
	}

	/**
	 * Get other type tickets except for default and rsvp.
	 *
	 * @since 5.9.0
	 *
	 * @return array The capacity components for the other type tickets.
	 */
	public function get_other_type_capacity(): array {
		$capacity_types = $this->get_capacity_by_type();
		return array_filter( $capacity_types, function( $key ) {
			return $key !== 'default' && $key !== 'rsvp';
		}, ARRAY_FILTER_USE_KEY );
	}

	/**
	 * Calculate the total capacity.
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	private function calculate_total_capacity(): void {
		$capacity_types = $this->get_capacity_by_type();

		$has_unlimited_ticket = false;
		foreach ( $capacity_types as $key => $type ) {
			$shared_capacity      = $type['shared']['capacity'];
			$independent_capacity = array_sum( array_filter( array_column( $type['independent'], 'capacity'), fn( $value ) => $value >= 0) );
			$total_capacity       = $shared_capacity + $independent_capacity;
			$this->total_capacity += $total_capacity;

			if ( ! empty( $type['unlimited'] ) ) {
				$has_unlimited_ticket = true;
			}
		}

		// Assign -1 when unlimited capacity tickets are present.
		if ( $has_unlimited_ticket ) {
			$this->total_capacity = -1;
		}
	}

	/**
	 * Get the total capacity.
	 *
	 * @since 5.9.0
	 *
	 * @return int The total capacity.
	 */
	public function get_total_capacity(): int {
		/**
		 * Filters the total capacity for the capacity table.
		 *
		 * @since 5.9.0
		 *
		 * @param int $total_capacity The total capacity to be displayed.
		 * @param int $post_id The ID of the event.
		 * @param Capacity_Table $capacity_table The instance of the capacity table.
		 */
		return apply_filters( 'tec_tickets_plus_editor_capacity_table_total_capacity', $this->total_capacity, $this->post_id, $this );
	}

	/**
	 * Get the label for the type.
	 *
	 * @since 5.9.0
	 *
	 * @param string $type The type to be displayed.
	 *
	 * @return string The label for the type.
	 */
	public function get_label_for_type( string $type ): string {
		/**
		 * Filters the label for the type for the capacity table.
		 *
		 * @since 5.9.0
		 *
		 * @param string $type The type to be displayed.
		 * @param int $post_id The ID of the event.
		 * @param Capacity_Table $capacity_table The instance of the capacity table.
		 */
		return apply_filters( 'tec_tickets_plus_editor_capacity_table_label_for_type', $type, $this->post_id, $this );
	}
}
