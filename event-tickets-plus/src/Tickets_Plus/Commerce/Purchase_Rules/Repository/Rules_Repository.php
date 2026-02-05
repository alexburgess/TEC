<?php
/**
 * The rules repository.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Repository
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Repository;

use TEC\Common\Abstracts\Custom_Table_Repository;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;

/**
 * The rules repository.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Repository
 */
class Rules_Repository extends Custom_Table_Repository {
	/**
	 * Constructor.
	 *
	 * @since 6.9.0
	 */
	public function __construct() {
		parent::__construct();

		$current_callback = $this->get_schema()['type'] ?? null;
		$this->add_schema_entry(
			'type',
			function ( $value ) use ( $current_callback ) {
				if ( 'any' !== $value ) {
					return $current_callback( $value );
				}

				return [];
			}
		);
	}

	/**
	 * The model class.
	 *
	 * @since 6.9.0
	 *
	 * @return string The model class.
	 */
	public function get_model_class(): string {
		return Rule::class;
	}
}
