<?php

namespace Tribe\Tickets\Plus\Commerce\EDD\Orders\Data;

use TEC\Tickets\Commerce\Reports\Data\Order_Summary as Order_Summary_Base;
use Tribe__Tickets__Ticket_Object as Ticket_Object;

/**
 * Class Order_Summary.
 *
 * @since 5.8.0
 *
 * @package Tribe\Tickets\Plus\Commerce\EDD\Orders\Data
 */
class Order_Summary extends Order_Summary_Base {

	/**
	 * Get the quantity of tickets sold for each status.
	 *
	 * @since 5.8.0
	 *
	 * @param int $ticket_id The ticket ID.
	 *
	 * @return array
	 */
	private function get_status_quantity( int $ticket_id ): array {
		$quantities = [];

		$orders = edd_get_orders( [
			'product_id' => $ticket_id,
		] );

		foreach ( $orders as $order ) {
			if ( 'sale' !== $order->type ) {
				continue;
			}

			if ( ! isset( $quantities[ $order->status ] ) ) {
				$quantities[ $order->status ]['qty']      = 0;
				$quantities[ $order->status ]['amount']   = 0;
				$quantities[ $order->status ]['discount'] = 0;
			}

			foreach ( $order->get_items() as $item ) {
				if ( $ticket_id != $item->product_id ) {
					continue;
				}
				$quantities[ $order->status ]['qty']      += $item->quantity;
				$quantities[ $order->status ]['amount']   += $item->total;
				$quantities[ $order->status ]['discount'] += $item->discount;
			}
		}

		return $quantities;
	}

	/**
	 * @inheritDoc
	 */
	protected function format_price( string $price ): string {
		return edd_currency_filter( edd_format_amount( $price ) );
	}

	/**
	 * @inheritDoc
	 */
	protected function build_data(): void {

		foreach ( $this->get_tickets() as $ticket ) {
			$qty_counts = $this->get_status_quantity( $ticket->ID );
			$quantities = wp_list_pluck( $qty_counts, 'qty' );

			$this->process_event_sales_data( $qty_counts, $ticket );

			// We need to show the total available for each ticket type.
			$quantities = $this->add_available_data( $quantities, $ticket );

			$ticket_data = [
				'ticket'        => $ticket,
				'label'         => sprintf( '%1$s %2$s', $ticket->name, $this->format_price( $ticket->price ) ),
				'type'          => $ticket->type(),
				'qty_data'      => $quantities,
				'qty_by_status' => implode( ' | ', array_map( fn( $k, $v ) => "$v $k", array_keys( $quantities ), $quantities ) ),
			];

			$this->tickets_by_type[ $ticket->type ][] = $ticket_data;
		}
	}

	/**
	 * @inheritDoc
	 */
	protected function process_event_sales_data( array $quantity_by_status, Ticket_Object $ticket ): void {
		foreach ( $quantity_by_status as $status_slug => $counts ) {
			if ( ! isset( $this->event_sales_by_status[ $status_slug ] ) ) {
				// This is the first time we've seen this status, so initialize it.
				$this->event_sales_by_status[ $status_slug ] = [
					'label'              => edd_get_status_label( $status_slug ),
					'qty_sold'           => 0,
					'total_sales_amount' => 0,
					'total_sales_price'  => $this->format_price( 0 ),
				];
			}
			$this->event_sales_by_status[ $status_slug ]['qty_sold']           += $counts['qty'];
			$this->event_sales_by_status[ $status_slug ]['total_sales_amount'] += $counts['amount'];
			$this->event_sales_by_status[ $status_slug ]['total_sales_price']  = $this->format_price( $this->event_sales_by_status[ $status_slug ]['total_sales_amount'] );

			// process the total ordered data.
			$this->total_ordered['qty']    += $counts['qty'];
			$this->total_ordered['amount'] += $counts['amount'];
			$this->total_ordered['price']  = $this->format_price( $this->total_ordered['amount'] );

			// Only completed orders should be counted in the total sales.
			if ( in_array( $status_slug, edd_get_complete_order_statuses() ) ) {
				$this->total_sales['qty']    += $counts['qty'];
				$this->total_sales['amount'] += $counts['amount'];
				$this->total_sales['price']  = $this->format_price( $this->total_sales['amount'] );
			}
		}
	}
}