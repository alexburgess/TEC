<?php
/**
 * Ticket Groups repository.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Repositories;

use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Models\Contracts\Model;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets_Plus\Ticket_Presets\Custom_Tables\Ticket_Presets as Preset_Table;
use TEC\Tickets_Plus\Ticket_Presets\Models\Ticket_Preset;
use TEC\Tickets\Flexible_Tickets\Repositories\Ticket_Groups;
use TEC\Tickets\Flexible_Tickets\Custom_Tables\Ticket_Groups as Table;

/**
 * Class Ticket_Presets.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */
class Ticket_Presets extends Ticket_Groups {
	/**
	 * {@inheritDoc}
	 */
	public function prepareQuery(): ModelQueryBuilder {
		$builder = new ModelQueryBuilder( Ticket_Preset::class );
		$builder = $builder->from( Preset_Table::table_name( false ) );

		// Handle search.
		$search = tec_get_request_var( 's', '' );
		if ( ! empty( $search ) ) {
			$builder->where( 'name', "%{$search}%", 'LIKE' );
		}

		// Get sort parameters using tribe helper.
		$orderby = tec_get_request_var( 'orderby', 'name' );
		$order   = tec_get_request_var( 'order', 'ASC' );

		// If orderby is a valid column, add it to the query.
		if ( in_array( $orderby, [ 'name', 'cost' ], true ) ) {
			$builder->orderBy( $orderby, $order );
		} elseif ( 'capacity' === $orderby ) {
			$builder->orderBy( 'CASE WHEN capacity = -1 THEN ' . PHP_INT_MAX . ' ELSE capacity END', $order );
		}

		return $builder;
	}

	/**
	 * Gets all presets.
	 *
	 * @since 6.6.0
	 * @param string $output Output type.
	 *
	 * @return ?array<Ticket_Preset> The list of presets.
	 */
	public function get_all( $output = OBJECT ): ?array {
		$builder = $this->prepareQuery();
		$sql     = $builder->getSQL();

		return DB::get_results( $sql, $output );
	}

	/**
	 * Counts all existing presets.
	 *
	 * @since 6.6.0
	 *
	 * @return int The number of presets.
	 */
	public function count_all(): int {
		$presets = $this->get_all();

		return count( $presets );
	}

	/**
	 * {@inheritDoc}
	 */
	public function insert( Model $model ): Ticket_Preset {
		DB::insert(
			Table::table_name(),
			[
				'slug'     => $model->slug,
				'data'     => $model->data,
				'name'     => $model->name,
				'cost'     => $model->cost,
				'capacity' => $model->capacity,
			],
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
			]
		);

		$model->id = DB::last_insert_id();

		return $model;
	}
}
