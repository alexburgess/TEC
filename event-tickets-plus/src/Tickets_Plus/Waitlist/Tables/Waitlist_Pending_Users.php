<?php
/**
 * The Waitlist_Pending_Users table schema.
 *
 * @since 6.2.0
 *
 * @package TEC\Tickets_Plu\Waitlist\Tables;
 */

namespace TEC\Tickets_Plus\Waitlist\Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Common\StellarWP\Schema\Columns\Created_At;

/**
 * Waitlist_Pending_Users table schema.
 *
 * The table is used as the temporary storage for waitlist subscribers.
 *
 * Whenever a user is notified, we will delete them from here.
 *
 * @since 6.2.0
 *
 * @package TEC\Tickets_Plu\Waitlist\Tables;
 */
class Waitlist_Pending_Users extends Table {
	/**
	 * The schema version.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.2-dev';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_waitlist_pending_users';

	/**
	 * The table group.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected static $group = 'tec_tickets_plus_waitlist';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-tickets-plus-waitlist-pending-users';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected static $uid_column = 'waitlist_user_id';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since 6.2.0
	 *
	 * @var string[]
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = ( new Integer_Column( 'waitlist_user_id' ) )->set_signed( false )->set_is_primary_key( true );
				$columns[] = new Referenced_ID( 'waitlist_id' );
				$columns[] = ( new Referenced_ID( 'wp_user_id' ) )->set_nullable( true );
				$columns[] = ( new String_Column( 'fullname' ) )->set_length( 255 )->set_nullable( true );
				$columns[] = ( new String_Column( 'email' ) )->set_length( 255 )->set_nullable( true );
				$columns[] = new Created_At( 'created' );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}
}
