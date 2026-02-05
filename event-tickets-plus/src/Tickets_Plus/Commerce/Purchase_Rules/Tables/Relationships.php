<?php
/**
 * The Purchase Rules relationships table schema.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables;
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\Referenced_ID;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;

/**
 * Purchase Rules relationships table schema.
 *
 * The table is used to store the relationships between purchase rules and posts.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables;
 */
class Relationships extends Table {
	/**
	 * The schema version.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.1';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_purchase_rules_relationships';

	/**
	 * The table group.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	protected static $group = 'tec_tickets_plus_purchase_rules';

	/**
	 * The slug used to identify the custom table.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	protected static $schema_slug = 'tec-tickets-plus-purchase-rules-relationships';

	/**
	 * An array of all the columns in the table.
	 *
	 * @since 6.9.0
	 *
	 * @return array<string, array<string, bool|int|string>>
	 */
	public static function get_schema_history(): array {
		$table_name = self::table_name();

		return [
			self::SCHEMA_VERSION => function () use ( $table_name ) {
				$columns   = new Column_Collection();
				$columns[] = new ID( 'id' );
				$columns[] = new Referenced_ID( 'rule_id' );
				$columns[] = new Referenced_ID( 'post_id' );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}
}
