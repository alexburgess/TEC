<?php
// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( class_exists( 'Tribe__Tickets_Plus__PUE' ) ) {
	return;
}

/**
 * Registers Event Tickets Plus with the Plugin Update Engine.
 */
class Tribe__Tickets_Plus__PUE {
	/**
	 * This string must match the plugin slug as set in the PUE plugin library.
	 *
	 * @since 5.6.6 Make the property static.
	 *
	 * @var string
	 */
	private static $pue_slug = 'event-tickets-plus';

	/**
	 * @var string
	 */
	private $update_url = 'http://theeventscalendar.com/';

	/**
	 * @var Tribe__PUE__Checker
	 */
	private $pue_instance;

	/**
	 * Setup plugin update checks.
	 */
	public function __construct() {
		$this->load_plugin_update_engine();
		register_activation_hook( EVENT_TICKETS_PLUS_FILE, [ $this, 'register_uninstall_hook' ] );
	}

	/**
	 * Gets the PUE instance used for validation.
	 *
	 * @since 4.15.0
	 *
	 * @return Tribe__PUE__Checker
	 */
	public function get_pue() {
		return $this->pue_instance;
	}

	/**
	 * If the PUE Checker class exists, go ahead and create a new instance to handle
	 * update checks for this plugin.
	 */
	public function load_plugin_update_engine() {
		/**
		 * Whether PUE checks should run.
		 *
		 * @var bool   $enable_pue
		 * @var string $pue_slug
		 */
		if ( ! class_exists( 'Tribe__PUE__Checker' ) || ! apply_filters( 'tribe_enable_pue', true, self::$pue_slug ) ) {
			return;
		}

		$this->pue_instance = new Tribe__PUE__Checker(
			$this->update_url,
			self::$pue_slug,
			array(),
			plugin_basename( EVENT_TICKETS_PLUS_FILE )
		);

		if ( ! has_filter( 'default_option_' . $this->pue_instance->get_license_option_key(), [ $this, 'filter_include_baked_license' ] ) ) {
			add_filter( 'default_option_' . $this->pue_instance->get_license_option_key(), [ $this, 'filter_include_baked_license' ], 15, 3 );
		}
	}

	/**
	 * FIlter the default PUE key to make sure it exists.
	 *
	 * @since 5.4.4.1
	 *
	 * @param mixed $default
	 * @param string $option
	 * @param mixed $passed_default
	 *
	 * @return mixed|string
	 */
	public function filter_include_baked_license( $default, $option, $passed_default ) {
		if ( $this->pue_instance->get_license_option_key() !== $option ) {
			return $default;
		}

		if ( ! defined( 'Tribe__Tickets_Plus__PUE__Helper::DATA' ) ) {
			return $default;
		}

		return Tribe__Tickets_Plus__PUE__Helper::DATA;
	}

	/**
	 * Register the uninstall hook on activation.
	 *
	 * @since 5.6.6 Make the method static.
	 */
	public function register_uninstall_hook() {
		register_uninstall_hook( EVENT_TICKETS_PLUS_FILE, [ __CLASS__, 'uninstall' ] );
	}

	/**
	 * Plugin has been uninstalled: clean up by purging various options from the database.
	 */
	public static function uninstall() {
		$slug = str_replace( '-', '_', self::$pue_slug );
		delete_option( 'pue_install_key_' . $slug );
		delete_option( 'pu_dismissed_upgrade_' . $slug );
	}

	/**
	 * Checks the value of the temporary key status transient.
	 *
	 * @since 5.4.0
	 *
	 * @param bool $revalidate whether to submit a new validation API request.
	 *
	 * @return bool
	 */
	public function is_current_license_valid( $revalidate = false ) {

		if ( empty( $this->pue_instance ) || ! $this->pue_instance instanceof Tribe__PUE__Checker ) {
			return false;
		}

		if ( true === $revalidate ) {
			$this->revalidate_key();
		}

		return $this->pue_instance->is_key_valid();
	}

	/**
	 * Request key revalidation.
	 *
	 * @since 5.4.0
	 */
	private function revalidate_key() {
		$license = get_option( $this->pue_instance->get_license_option_key() );
		$this->pue_instance->validate_key( $license );
	}
}