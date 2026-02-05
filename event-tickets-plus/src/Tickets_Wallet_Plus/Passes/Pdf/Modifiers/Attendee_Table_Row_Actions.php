<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Pass;

/**
 * Class Attendee_Table_Row_Actions
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers
 */
class Attendee_Table_Row_Actions extends Modifier_Abstract {

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'pdf/attendee_table_row_actions';
	}

	/**
	 * @inheritDoc
	 */
	protected function add_filters(): void {
		add_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'include_link' ], 0, 2 );
	}

	/**
	 * @inheritDoc
	 */
	protected function remove_filters(): void {
		remove_filter( 'event_tickets_attendees_table_row_actions', [ $this, 'include_link' ], 0 );
	}

	/**
	 * Add a link to each ticket's PDF ticket on the wp-admin Attendee List.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $row_actions The row actions.
	 * @param array $item        The attendee data.
	 *
	 * @return array $row_actions The modified row actions.
	 */
	public function include_link( $row_actions, $item ): array {
		$pdf_link      = Pass::from_attendee( $item['attendee_id'] )->get_url();
		$row_actions[] = '<a href="' . esc_url( $pdf_link ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Download PDF', 'event-tickets-plus' ). '</a>';

		return $row_actions;
	}
}
