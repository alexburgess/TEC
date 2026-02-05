<?php
/**
 * The Waitlist_Subscribers table schema.
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
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\Column_Types;
use TEC\Common\StellarWP\Schema\Columns\Text_Column;
use TEC\Common\StellarWP\Schema\Columns\Created_At;

/**
 * Waitlist_Subscribers table schema.
 *
 * The table is used as the permanent storage for waitlist subscribers.
 * Thats why we don't have their connection to the waitlist object here since its not important.
 * We do link them with a post though.
 *
 * @since 6.2.0
 *
 * @package TEC\Tickets_Plu\Waitlist\Tables;
 */
class Waitlist_Subscribers extends Table {
	/**
	 * The schema version.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.1-dev';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_waitlist_subscribers';

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
	protected static $schema_slug = 'tec-tickets-plus-waitlist-subscribers';

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
	 * @since 6.9.0
	 *
	 * @var string[]
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'waitlist_user_id' );
				$columns[] = new Referenced_ID( 'post_id' );
				$columns[] = ( new Referenced_ID( 'wp_user_id' ) )->set_nullable( true );
				$columns[] = ( new Integer_Column( 'status' ) )->set_type( Column_Types::TINYINT )->set_length( 1 )->set_default( 0 );
				$columns[] = ( new String_Column( 'fullname' ) )->set_length( 255 )->set_nullable( true )->set_searchable( true );
				$columns[] = ( new String_Column( 'email' ) )->set_length( 255 )->set_nullable( true )->set_searchable( true );
				$columns[] = ( new Text_Column( 'meta' ) )->set_type( Column_Types::LONGTEXT )->set_nullable( true );
				$columns[] = new Created_At( 'created' );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}
}
