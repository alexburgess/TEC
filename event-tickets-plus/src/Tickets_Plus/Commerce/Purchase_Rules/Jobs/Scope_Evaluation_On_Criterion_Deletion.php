<?php
/**
 * Evaluate the scope of a purchase rule.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Jobs
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Jobs;

use TEC\Common\StellarWP\Shepherd\Abstracts\Task_Abstract;
use TEC\Common\StellarWP\Shepherd\Exceptions\ShepherdTaskException;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use InvalidArgumentException;
use function TEC\Common\StellarWP\Shepherd\shepherd;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

/**
 * Evaluate the scope of a purchase rule on criterion deletion.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Jobs;
 */
class Scope_Evaluation_On_Criterion_Deletion extends Task_Abstract {
	/**
	 * The scope evaluation on criterion deletion job's constructor.
	 *
	 * @since 6.9.0
	 *
	 * @param int|null $because_of_tag_id    The tag ID.
	 * @param int|null $because_of_cat_id    The category ID.
	 * @param int|null $because_of_venue_id  The venue ID.
	 * @param int|null $because_of_series_id The series ID.
	 * @param int      $offset               The offset.
	 *
	 * @throws InvalidArgumentException If the email task's arguments are invalid.
	 */
	public function __construct( ?int $because_of_tag_id = null, ?int $because_of_cat_id = null, ?int $because_of_venue_id = null, ?int $because_of_series_id = null, int $offset = 0 ) {
		parent::__construct( $because_of_tag_id, $because_of_cat_id, $because_of_venue_id, $because_of_series_id, $offset );
	}

	/**
	 * Processes the evaluate scope job.
	 *
	 * @since 6.9.0
	 *
	 * @throws ShepherdTaskException If the evaluate scope job fails to process.
	 */
	public function process(): void {
		$args                 = $this->get_args();
		$because_of_tag_id    = $args[0] ?? null;
		$because_of_cat_id    = $args[1] ?? null;
		$because_of_venue_id  = $args[2] ?? null;
		$because_of_series_id = $args[3] ?? null;

		$offset = $args[4] ?? 0;

		$handled = 0;

		$batch_size = Evaluate_Scope::get_batch_size();

		$rules_query = Rule::query()->offset( $offset )->limit( $batch_size )->orderBy( 'id' );
		foreach ( $rules_query->getAll() as $rule ) {
			++$handled;

			$scope = $rule->get_scope();

			if ( $scope['connector'] === 'all' || $scope['connector'] === 'none' ) {
				continue;
			}

			$modified = false;

			foreach ( $scope['criteria'] as $offset => $criterion ) {
				if ( $because_of_tag_id && $criterion['term'] === 'tag' && $criterion['value'] === $because_of_tag_id ) {
					unset( $scope['criteria'][ $offset ] );
					if ( empty( $scope['criteria'] ) ) {
						$scope['connector'] = 'none';
					}
					$rule->set_scope( $scope );
					$modified = true;
					continue;
				}

				if ( $because_of_cat_id && $criterion['term'] === 'category' && $criterion['value'] === $because_of_cat_id ) {
					unset( $scope['criteria'][ $offset ] );
					if ( empty( $scope['criteria'] ) ) {
						$scope['connector'] = 'none';
					}
					$rule->set_scope( $scope );
					$modified = true;
					continue;
				}

				if ( $because_of_venue_id && $criterion['term'] === 'venue' && $criterion['value'] === $because_of_venue_id ) {
					unset( $scope['criteria'][ $offset ] );
					if ( empty( $scope['criteria'] ) ) {
						$scope['connector'] = 'none';
					}
					$rule->set_scope( $scope );
					$modified = true;
					continue;
				}

				if ( $because_of_series_id && $criterion['term'] === 'series' && $criterion['value'] === $because_of_series_id ) {
					unset( $scope['criteria'][ $offset ] );
					if ( empty( $scope['criteria'] ) ) {
						$scope['connector'] = 'none';
					}
					$rule->set_scope( $scope );
					$modified = true;
				}
			}

			if ( $modified ) {
				// The save will trigger a scope re-evaluation.
				$rule->save();
			}
		}

		if ( $handled < $batch_size ) {
			return;
		}

		shepherd()->dispatch( new self( $because_of_tag_id, $because_of_cat_id, $because_of_venue_id, $because_of_series_id, $offset + $batch_size ) );
	}

	/**
	 * Gets the evaluate scope job's hook prefix.
	 *
	 * @since 6.9.0
	 *
	 * @return string The evaluate scope job's hook prefix.
	 */
	public function get_task_prefix(): string {
		return 'etp_prs_on_trm_';
	}

	/**
	 * Gets the maximum number of retries.
	 *
	 * @since 6.9.0
	 *
	 * @return int The maximum number of retries.
	 */
	public function get_max_retries(): int {
		return 9;
	}
}
