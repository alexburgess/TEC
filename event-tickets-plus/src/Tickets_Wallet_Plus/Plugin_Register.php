<?php
/**
 * Handles the Event Tickets Wallet Plus plugin dependency manifest registration.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus
 */

namespace TEC\Tickets_Wallet_Plus;

use Tribe__Abstract_Plugin_Register as Abstract_Plugin_Register;

/**
 * Class Plugin_Register.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus
 *
 * @see Tribe__Abstract_Plugin_Register For the plugin dependency manifest registration.
 */
class Plugin_Register extends Abstract_Plugin_Register {
	/**
	 * The version of the plugin.
	 * Replaced the Plugin::VERSION constant, which now is an alias to this one.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public const VERSION = '1.0.4';

	/**
	 * The dependencies of the plugin.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var array[] The dependencies of the plugin.
	 */
	protected $dependencies = [
		'parent-dependencies' => [
			'Tribe__Tickets__Main' => '5.9.1-dev',
		],
	];

	/**
	 * Configures the base_dir property which is the path to the plugin bootstrap file.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $file Which is the path to the plugin bootstrap file.
	 */
	public function set_base_dir( string $file ): void {
		$this->base_dir = $file;
	}

	/**
	 * Gets the previously configured base_dir property.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	public function get_base_dir(): string {
		return $this->base_dir;
	}

	/**
	 * Gets the main class of the Plugin, stored on the main_class property.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	public function get_plugin_class(): string {
		return $this->main_class;
	}

	/**
	 * File path to the main class of the plugin.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The path to the main class of the plugin.
	 */
	protected $base_dir;

	/**
	 * Alias to the VERSION constant.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The version of the plugin.
	 */
	protected $version = self::VERSION;

	/**
	 * Fully qualified name of the main class of the plugin.
	 * Do not use the Plugin::class constant here, we need this value without loading the Plugin class.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The main class of the plugin.
	 */
	protected $main_class = '\TEC\Tickets_Wallet_Plus\Plugin';
}
