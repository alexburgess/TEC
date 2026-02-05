<?php
/**
 * Plugin Register.
 *
 * @since 4.10.0
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase, PEAR.NamingConventions.ValidClassName.Invalid

/**
 * Class Tribe__Tickets__Plugin_Register
 */
class Tribe__Tickets_Plus__Plugin_Register extends Tribe__Abstract_Plugin_Register {

	/**
	 * @var string
	 */
	protected $main_class = 'Tribe__Tickets_Plus__Main';

	/**
	 * @var array
	 */
	protected $dependencies = [
		'parent-dependencies' => [
			'Tribe__Tickets__Main' => '5.27.0-dev',
		],
		'addon-dependencies'  => [
			'TEC\Tickets_Wallet_Plus\Plugin' => '2.0.0-dev',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->base_dir = EVENT_TICKETS_PLUS_FILE;
		$this->version  = Tribe__Tickets_Plus__Main::VERSION;

		$this->register_plugin();
	}
}
