<?php
/**
 * The Uplink controller
 *
 * @since 6.1.0
 *
 * @package TEC\Tickets_Plus\Libraries\Uplink;
 */

namespace TEC\Tickets_Plus\Libraries;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Uplink\Register;
use Tribe__Tickets_Plus__Main as Main;

/**
 * Controller for setting up the stellarwp/uplink library.
 *
 * @since 6.1.0
 *
 * @package TEC\TicketsPlus\Libraries\Uplink
 */
class Uplink_Controller extends Controller_Contract {
	/**
	 * Register the controller.
	 *
	 * @since 6.1.0
	 */
	public function do_register(): void {
		$this->add_actions();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_actions();
	}

	/**
	 * Add the action hooks.
	 *
	 * @since 6.1.0
	 */
	public function add_actions(): void {
		add_action( 'init', [ $this, 'register_plugin' ] );
	}

	/**
	 * Remove the action hooks.
	 *
	 * @since 6.1.0
	 */
	public function remove_actions(): void {
		remove_action( 'init', [ $this, 'register_plugin' ] );
	}

	/**
	 * Register the plugin in the uplink library.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function register_plugin(): void {
		$main = tribe( Main::class );
		Register::plugin(
			'event-tickets-plus',
			'Event Tickets Plus',
			Main::VERSION,
			"{$main->plugin_dir}/event-tickets-plus.php",
			Main::class,
			\Tribe__Tickets_Plus__PUE__Helper::class
		);
	}
}
