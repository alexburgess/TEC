<?php
/**
 * The Waitlists table schema.
 *
 * @since 6.2.0
 *
 * @package TEC\Tickets_Plu\Waitlist\Tables;
 */

namespace TEC\Tickets_Plus\Waitlist\Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\Integer_Column;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\Column_Types;
use TEC\Common\StellarWP\Schema\Columns\Boolean_Column;

/**
 * Waitlists table schema.
 *
 * The table is used to link a waitlist object with a ticket-able post.
 *
 * @since 6.2.0
 *
 * @package TEC\Tickets_Plu\Waitlist\Tables;
 */
class Waitlists extends Table {
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
	protected static $base_table_name = 'tec_waitlists';

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
	protected static $schema_slug = 'tec-tickets-plus-waitlist';

	/**
	 * The field that uniquely identifies a row in the table.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	protected static $uid_column = 'waitlist_id';

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
				$columns[] = new ID( 'waitlist_id' );
				$columns[] = new Referenced_ID( 'post_id' );
				$columns[] = ( new Boolean_Column( 'enabled' ) )->set_default( false );
				$columns[] = ( new Integer_Column( 'conditional' ) )->set_type( Column_Types::TINYINT )->set_length( 1 )->set_default( 0 );
				$columns[] = ( new Integer_Column( 'type' ) )->set_type( Column_Types::TINYINT )->set_length( 1 )->set_default( 0 )->set_is_index( true );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}
}
