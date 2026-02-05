<?php
/**
 * The Purchase Rules table schema.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables;
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables;

use TEC\Common\StellarWP\Schema\Tables\Contracts\Table;
use TEC\Common\StellarWP\Schema\Collections\Column_Collection;
use TEC\Common\StellarWP\Schema\Columns\ID;
use TEC\Common\StellarWP\Schema\Columns\String_Column;
use TEC\Common\StellarWP\Schema\Columns\Text_Column;
use TEC\Common\StellarWP\Schema\Columns\Column_Types;
use TEC\Common\StellarWP\Schema\Tables\Table_Schema;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use InvalidArgumentException;
use TEC\Common\StellarWP\Schema\Columns\Last_Changed;
use TEC\Common\StellarWP\Schema\Columns\PHP_Types;

/**
 * Purchase Rules table schema.
 *
 * The table is used to store the purchase rules.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables;
 */
class Rules extends Table {
	/**
	 * The schema version.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	const SCHEMA_VERSION = '0.0.3';

	/**
	 * The base table name, without the table prefix.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	protected static $base_table_name = 'tec_purchase_rules';

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
	protected static $schema_slug = 'tec-tickets-plus-purchase-rules';

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
				$columns[] = ( new String_Column( 'type' ) )->set_length( 191 )->set_is_index( true );
				$columns[] = ( new String_Column( 'name' ) )->set_length( 191 )->set_searchable( true )->set_is_index( true );
				$columns[] = ( new Text_Column( 'config' ) )->set_type( Column_Types::LONGTEXT )->set_php_type( PHP_Types::JSON );
				$columns[] = ( new Text_Column( 'scope' ) )->set_type( Column_Types::LONGTEXT )->set_php_type( PHP_Types::JSON )->set_nullable( true );
				$columns[] = ( new String_Column( 'status' ) )->set_length( 191 )->set_default( 'active' )->set_is_index( true );
				$columns[] = new Last_Changed( 'updated_at' );

				return new Table_Schema( $table_name, $columns );
			},
		];
	}

	/**
	 * Gets a rule from an array.
	 *
	 * @since 6.9.0
	 *
	 * @param array<string, mixed> $rule_array The rule array.
	 *
	 * @return Rule The rule.
	 *
	 * @throws InvalidArgumentException If a method does not exist on the model.
	 */
	public static function transform_from_array( array $rule_array ): Rule {
		return Rule::fromData( $rule_array );
	}
}
