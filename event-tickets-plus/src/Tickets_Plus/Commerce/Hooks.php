<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets_Plus\Commerce\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets-plus.commerce.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets_Plus\Commerce\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets-plus.commerce.hooks' ), 'some_method' ] );
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus\Commerce
 */

namespace TEC\Tickets_Plus\Commerce;


/**
 * Class Hooks.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus\Commerce
 */
class Hooks extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.3.0
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets component.
	 *
	 * @since 5.3.0
	 */
	protected function add_actions() {
	}

	/**
	 * Adds the filters required by each Tickets component.
	 *
	 * @since 5.3.0
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_metabox_capacity_file', [ $this, 'filter_metabox_capacity_file' ], 15 );
	}

	/**
	 * Filters the absolute path to the capacity metabox for Tickets Commerce.
	 *
	 * @since 5.3.0
	 *
	 * @return string
	 */
	public function filter_metabox_capacity_file() {
		return \Tribe__Tickets_Plus__Main::instance()->plugin_path . 'src/admin-views/commerce/metabox/capacity.php';
	}
}
