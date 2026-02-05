<?php
/**
 * The custom tables controller.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */

namespace TEC\Tickets_Plus\Ticket_Presets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Schema\Config as Schema_Config;
use TEC\Common\StellarWP\Schema\Register as Schema_Register;
use TEC\Common\StellarWP\Models\Config as Model_Config;
use TEC\Tickets_Plus\Ticket_Presets\Custom_Tables\Posts_And_Ticket_Presets;
use TEC\Tickets_Plus\Ticket_Presets\Custom_Tables\Ticket_Presets;

/**
 * Class Custom_Tables.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */
class Custom_Tables extends Controller {
	/**
	 * {@inheritDoc}
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function do_register(): void {
		Schema_Config::set_container( $this->container );
		Schema_Config::set_db( DB::class );
		Model_Config::reset();
		Model_Config::setHookPrefix( 'tec-tickets-ticket-presets' );

		if ( did_action( 'tribe_plugins_loaded' ) ) {
			$this->register_tables();
		} else {
			add_action( 'tribe_plugins_loaded', [ $this, 'register_tables' ] );
		}
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_plugins_loaded', [ $this, 'register_tables' ] );
	}

	/**
	 * Registers the custom tables and makes them available in the container as singletons.
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function register_tables(): void {
		$this->container->singleton( Ticket_Presets::class, Schema_Register::table( Ticket_Presets::class ) );
		$this->container->singleton( Posts_And_Ticket_Presets::class, Schema_Register::table( Posts_And_Ticket_Presets::class ) );
	}

	/**
	 * Drops the custom tables.
	 *
	 * @since 6.6.0
	 *
	 * @return int The number of tables dropped.
	 */
	public function drop_tables(): int {
		$dropped = 0;

		DB::query( 'SET FOREIGN_KEY_CHECKS = 0' );
		foreach (
			[
				Ticket_Presets::table_name(),
			] as $table
		) {
			$dropped += DB::query( "DROP TABLE IF EXISTS $table" );
		}
		DB::query( 'SET FOREIGN_KEY_CHECKS = 1' );

		return $dropped;
	}

	/**
	 * Truncates the custom tables.
	 *
	 * @since 6.6.0
	 *
	 * @return int The number of tables truncated.
	 */
	public function truncate_tables(): int {
		$truncated = 0;

		DB::query( 'SET FOREIGN_KEY_CHECKS = 0' );
		foreach (
			[
				Ticket_Presets::table_name(),
				Posts_And_Ticket_Presets::table_name(),
			] as $table
		) {
			// Check if the table exists before attempting to truncate it.
			$table_exists = DB::query( "SHOW TABLES LIKE '$table'" );
			if ( ! $table_exists ) {
				continue;
			}

			$truncated += DB::query( "TRUNCATE TABLE $table" );
		}
		DB::query( 'SET FOREIGN_KEY_CHECKS = 1' );

		return $truncated;
	}
}
