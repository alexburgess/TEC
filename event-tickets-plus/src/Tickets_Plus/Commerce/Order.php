<?php

namespace TEC\Tickets_Plus\Commerce;

use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;

/**
 * Class Order
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus\Commerce
 */
class Order {

	/**
	 * Modify the order based on the IAC fields.
	 *
	 * @since 5.3.0
	 *
	 * @param array             $args
	 * @param Gateway_Interface $gateway
	 *
	 * @return array
	 */
	public function modify_iac_item_extra( $args, $gateway ) {
		if ( empty( $args['items'] ) ) {
			return $args;
		}

		$args['items'] = array_map( static function( $item ) {
			if ( empty( $item['extra']['iac'] ) ) {
				$item['extra']['iac'] = tribe( 'tickets-plus.attendee-registration.iac' )->get_iac_setting_for_ticket( $item['ticket_id'] );
			}
			return $item;
		}, $args['items'] );

		return $args;
	}
}
