<?php
namespace Tribe\Tickets\Plus\Commerce\WooCommerce\Orders\Data;

use Tribe__Tickets__Status__Manager;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report as Woo_Report;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Table as Woo_Table;
use \TEC\Tickets\Commerce\Reports\Data\Order_Summary as Order_Summary_Base;

/**
 * Class Order_Summary.
 *
 * @since 5.8.0
 *
 * @package Tribe\Tickets\Plus\Commerce\WooCommerce\Orders\Data
 */
class Order_Summary extends Order_Summary_Base {
	/**
	 * @inheritDoc
	 */
	protected function format_price( string $price ): string {
		return strip_tags( wc_price( $price ) );
	}

	/**
	 * @inheritDoc
	 */
	protected function build_data(): void {
		foreach ( $this->get_tickets() as $ticket ) {
			$status_counts = array_filter( Woo_Report::get_total_sales_per_productby_status( $ticket->ID ) );

			$quantities = array_reduce(
				array_keys( $status_counts ),
				function ( $carry, $status ) use ( $status_counts ) {
					$status_name           = strtolower( wc_get_order_status_name( $status ) );
					$carry[ $status_name ] = $status_counts[ $status ][0]->_qty;
					return $carry;
				},
				[]
			);

			// Process the event sales data.
			$this->process_event_sales_data( $status_counts, $ticket );

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
	 * @inheritdoc
	 */
	protected function process_event_sales_data( array $quantity_by_status, Ticket_Object $ticket ): void {
		foreach ( $quantity_by_status as $status_slug => $item ) {
			$data = $item[0];
			if ( ! isset( $this->event_sales_by_status[ $status_slug ] ) ) {
				// This is the first time we've seen this status, so initialize it.
				$this->event_sales_by_status[ $status_slug ] = [
					'label'              => wc_get_order_status_name( $status_slug ),
					'qty_sold'           => 0,
					'total_sales_amount' => 0,
					'total_sales_price'  => 0,
				];
			}
			$this->event_sales_by_status[ $status_slug ]['qty_sold']           += $data->_qty;
			$this->event_sales_by_status[ $status_slug ]['total_sales_amount'] += $data->_line_total;
			$this->event_sales_by_status[ $status_slug ]['total_sales_price']  = $this->format_price( $this->event_sales_by_status[ $status_slug ]['total_sales_amount'] );

			// process the total ordered data.
			$this->total_ordered['qty']    += $data->_qty;
			$this->total_ordered['amount'] += $data->_line_total;
			$this->total_ordered['price']  = $this->format_price( $this->total_ordered['amount'] );

			/** @var Tribe__Tickets__Status__Manager $status */
			$status            = tribe( 'tickets.status' );
			$complete_statuses = $status->get_completed_status_by_provider_name( $ticket->provider_class );
			// Only completed orders should be counted in the total sales.
			if ( in_array( $status_slug, $complete_statuses, true ) ) {
				$this->total_sales['qty']    += $data->_qty;
				$this->total_sales['amount'] += $data->_line_total;
				$this->total_sales['price']  = $this->format_price( $this->total_sales['amount'] );
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	public function build_event_sales_data(): void {
		$this->event_sales_data = [
			'by_status'       => $this->event_sales_by_status,
			'total_sales'     => $this->total_sales,
			'total_ordered'   => $this->total_ordered,
			'total_discounts' => Woo_Table::event_discounts( $this->post_id ),
		];
	}
}