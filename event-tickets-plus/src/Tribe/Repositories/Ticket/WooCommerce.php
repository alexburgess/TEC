<?php

use TEC\Tickets\Commerce\Ticket;
use Tribe__Tickets__Global_Stock as Global_Stock;
use Tribe__Tickets__Tickets_Handler as Tickets_Handler;
use Tribe__Tickets_Plus__Commerce__WooCommerce__Main as Woo_Provider;

/**
 * The ORM/Repository class for WooCommerce tickets.
 *
 * @since 4.10.5
 */
class Tribe__Tickets_Plus__Repositories__Ticket__WooCommerce extends Tribe__Tickets_Plus__Ticket_Repository {

	/**
	 * {@inheritdoc}
	 */
	public function __construct() {
		parent::__construct();

		/** @var Woo_Provider $woo_provider */
		$woo_provider = tribe( 'tickets-plus.commerce.woo' );
		/** @var Tickets_Handler $tickets_handler */
		$tickets_handler = tribe( 'tickets.handler' );

		$this->default_args['post_type']   = 'product';
		$this->default_args['post_status'] = 'publish';
		$this->create_args['post_status']  = 'publish';
		$this->create_args['post_type']    = 'product';

		// Add event specific aliases.
		$this->update_fields_aliases = array_merge(
			$this->update_fields_aliases,
			[
				'event_id'              => $woo_provider->event_key,
				'event'                 => $woo_provider->event_key,
				'show_description'      => $tickets_handler->key_show_description,
				'start_date'            => $tickets_handler->key_start_date,
				'end_date'              => $tickets_handler->key_end_date,
				'start_time'            => $tickets_handler->key_start_time,
				'end_time'              => $tickets_handler->key_end_time,
				'sku'                   => Ticket::$sku_meta_key,
				'stock'                 => Ticket::$stock_meta_key,
				'price'                 => Ticket::$price_meta_key,
				'sales'                 => Ticket::$sales_meta_key,
				'stock_mode'            => Global_Stock::TICKET_STOCK_MODE,
				'stock_status'          => Ticket::$stock_status_meta_key,
				'allow_backorders'      => Ticket::$allow_backorders_meta_key,
				'manage_stock'          => Ticket::$should_manage_stock_meta_key,
				'type'                  => Ticket::$type_meta_key,
				'sale_price'            => Ticket::$sale_price_key,
				'sale_price_start_date' => '_sale_price_dates_from',
				'sale_price_end_date'   => '_sale_price_dates_to',
				'sale_price_enabled'    => Ticket::$sale_price_checked_key,
				'capacity'              => $tickets_handler->key_capacity,
			]
		);

		$this->add_simple_meta_schema_entry( 'start_date', $tickets_handler->key_start_date );
		$this->add_simple_meta_schema_entry( 'end_date', $tickets_handler->key_end_date );
		$this->add_simple_meta_schema_entry( 'start_time', $tickets_handler->key_start_time );
		$this->add_simple_meta_schema_entry( 'end_time', $tickets_handler->key_end_time );
		$this->add_simple_meta_schema_entry( 'sku', Ticket::$sku_meta_key );
		$this->add_simple_meta_schema_entry( 'stock', Ticket::$stock_meta_key );
		$this->add_simple_meta_schema_entry( 'show_description', $tickets_handler->key_show_description );
		$this->add_simple_meta_schema_entry( 'price', Ticket::$price_meta_key );
		$this->add_simple_meta_schema_entry( 'sales', Ticket::$sales_meta_key );
		$this->add_simple_meta_schema_entry( 'stock_mode', Global_Stock::TICKET_STOCK_MODE );
		$this->add_simple_meta_schema_entry( 'stock_status', Ticket::$stock_status_meta_key );
		$this->add_simple_meta_schema_entry( 'allow_backorders', Ticket::$allow_backorders_meta_key );
		$this->add_simple_meta_schema_entry( 'manage_stock', Ticket::$should_manage_stock_meta_key );
		$this->add_simple_meta_schema_entry( 'type', Ticket::$type_meta_key );
		$this->add_simple_meta_schema_entry( 'sale_price', Ticket::$sale_price_key );
		$this->add_simple_meta_schema_entry( 'sale_price_start_date', '_sale_price_dates_from' );
		$this->add_simple_meta_schema_entry( 'sale_price_end_date', '_sale_price_dates_to' );
		$this->add_simple_meta_schema_entry( 'sale_price_enabled', Ticket::$sale_price_checked_key );
		$this->add_simple_meta_schema_entry( 'capacity', $tickets_handler->key_capacity );
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_types() {
		$types = parent::ticket_types();

		$types = [
			'woo' => $types['woo'],
		];

		return $types;
	}

	/**
	 * {@inheritdoc}
	 */
	public function ticket_to_event_keys() {
		$keys = parent::ticket_to_event_keys();

		$keys = [
			'woo' => $keys['woo'],
		];

		return $keys;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function format_item( $id ) {
		$formatted = null === $this->formatter
			? tec_wc_get_ticket( $id )
			: $this->formatter->format_item( $id );

		/**
		 * Filters a single formatted ticket result.
		 *
		 * @since 6.8.0
		 *
		 * @param mixed|WP_Post                $formatted  The formatted event result, usually a post object.
		 * @param int                          $id         The formatted post ID.
		 * @param Tribe__Repository__Interface $repository The current repository object.
		 */
		$formatted = apply_filters( 'tec_tickets_plus_woo_repository_ticket_format', $formatted, $id, $this );

		return $formatted;
	}
}
