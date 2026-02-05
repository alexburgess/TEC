<?php
/**
 * Plugin Register
 */

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase, PEAR.NamingConventions.ValidClassName.Invalid
/**
 * Class Tribe__Events__Pro__Plugin_Register
 */
class Tribe__Events__Pro__Plugin_Register extends Tribe__Abstract_Plugin_Register {

	/**
	 * The main class of the plugin.
	 *
	 * @var string
	 */
	protected $main_class   = 'Tribe__Events__Pro__Main';

	/**
	 * The dependencies of the plugin.
	 *
	 * @var array
	 */
	protected $dependencies = [
		'parent-dependencies' => [
			'Tribe__Events__Main' => '6.15.12-dev',
		],
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->base_dir = EVENTS_CALENDAR_PRO_FILE;
		$this->version  = Tribe__Events__Pro__Main::VERSION;

		$this->register_plugin();
	}
}
