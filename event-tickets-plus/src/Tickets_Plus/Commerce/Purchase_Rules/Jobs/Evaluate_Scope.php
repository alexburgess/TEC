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

use InvalidArgumentException;
use TEC\Common\StellarWP\DB\DB;
use TEC\Common\StellarWP\Shepherd\Abstracts\Task_Abstract;
use TEC\Common\StellarWP\Shepherd\Exceptions\ShepherdTaskException;
use TEC\Tickets\Commerce\Ticket as Ticket_Data;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Listeners;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables\Relationships as Relationships_Table;
use Tribe__Events__Repositories__Event;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Controller;
use function TEC\Common\StellarWP\Shepherd\shepherd;

// phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found

/**
 * Evaluate the scope of a purchase rule.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Jobs;
 */
class Evaluate_Scope extends Task_Abstract {
	/**
	 * The evaluate scope job's constructor.
	 *
	 * @since 6.9.0
	 *
	 * @param ?int $because_of_rule_id   The rule ID.
	 * @param ?int $because_of_ticket_id The ticket ID.
	 * @param ?int $because_of_post_id   The post ID.
	 * @param int  $offset               The offset.
	 *
	 * @throws InvalidArgumentException If the email task's arguments are invalid.
	 */
	public function __construct( ?int $because_of_rule_id = null, ?int $because_of_ticket_id = null, ?int $because_of_post_id = null, int $offset = 0 ) {
		parent::__construct( $because_of_rule_id, $because_of_ticket_id, $because_of_post_id, $offset );
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
		$because_of_rule_id   = $args[0] ?? null;
		$because_of_ticket_id = $args[1] ?? null;
		$because_of_post_id   = $args[2] ?? null;

		$offset = $args[3] ?? 0;

		if ( $because_of_ticket_id ) {
			$this->handle_ticket_change_scope_evaluation( $because_of_ticket_id, $offset );
			return;
		}

		if ( $because_of_post_id ) {
			$this->handle_post_change_scope_evaluation( $because_of_post_id, $offset );
			return;
		}

		if ( $because_of_rule_id ) {
			$rule = Rule::find( $because_of_rule_id );
			if ( ! $rule ) {
				return;
			}

			$this->handle_rule_change_scope_evaluation( $rule, $offset );
			return;
		}

		$rules_handled = 0;

		$batch_size = self::get_batch_size();

		$rules_query = Rule::query()->where( 'status', Rule::ACTIVE_STATUS )->offset( $offset )->limit( $batch_size )->orderBy( 'id' );
		foreach ( (array) $rules_query->getAll() as $rule ) {
			$this->handle_rule_change_scope_evaluation( $rule );
			++$rules_handled;
		}

		if ( $rules_handled < $batch_size ) {
			return;
		}

		shepherd()->dispatch( new self( $because_of_rule_id, $because_of_ticket_id, $because_of_post_id, $offset + $batch_size ) );
	}

	/**
	 * Handle the rule change scope evaluation.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule $rule   The rule.
	 * @param int  $offset The offset.
	 *
	 * @return void
	 */
	protected function handle_rule_change_scope_evaluation( Rule $rule, int $offset = 0 ): void {
		if ( Rule::ACTIVE_STATUS !== $rule->get_status() ) {
			DB::table( Relationships_Table::table_name( false ) )->where( 'rule_id', $rule->getPrimaryValue() )->delete();
			return;
		}

		$batch_size = self::get_batch_size();

		/**
		 * Consider very well what you add here.
		 *
		 * For example think about start date. Its not added ON PURPOSE.
		 *
		 * We don't want to listen for the change of the start date field.
		 *
		 * The more arguments you add here, the more listeners you got to create.
		 */
		$args = [
			'post_status'       => 'any', // This enables us to display which rules apply to non published events and also to not "listen" for status transitions in order to evaluate scope application again.
			'ticketed'          => true,
			'posts_per_page'    => $batch_size,
			'tec_events_ignore' => true,
		];

		/** @var Tribe__Events__Repositories__Event $orm */
		$orm          = tribe_events();
		$events_query = $orm->by_args( $args )->offset( $offset )->order_by( 'ID', 'DESC' );

		$events_processed = 0;
		$entries          = [];

		/** @var Listeners $listeners */
		$listeners = tribe( Listeners::class );

		foreach ( $events_query->get_ids() as $event_id ) {
			++$events_processed;

			$post = get_post( $event_id );
			if ( ! $post ) {
				continue;
			}

			if ( ! $listeners->does_rule_apply_to_post( $rule, $post ) ) {
				continue;
			}

			$entries[] = [
				'rule_id' => 3000000000 + $rule->getPrimaryValue(),
				'post_id' => $event_id,
			];

			Rule::remove_rule_from_enabled( $event_id, $rule->getPrimaryValue() );
		}

		if ( ! empty( $entries ) ) {
			Relationships_Table::insert_many( $entries );
		}

		if ( $events_processed < $batch_size ) {
			DB::table( Relationships_Table::table_name( false ) )->where( 'rule_id', $rule->getPrimaryValue() )->delete();
			DB::table( Relationships_Table::table_name( false ) )->where( 'rule_id', 3000000000 + $rule->getPrimaryValue() )->update( [ 'rule_id' => $rule->getPrimaryValue() ] );
			return;
		}

		shepherd()->dispatch( new self( $rule->getPrimaryValue(), null, null, $offset + $batch_size ) );
	}

	/**
	 * Handle the post change scope evaluation.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $offset  The offset.
	 *
	 * @return void
	 */
	protected function handle_post_change_scope_evaluation( int $post_id, int $offset = 0 ): void {
		$normalized_post_id = Controller::maybe_normalize_provisional_post_id( $post_id );

		$post = get_post( $normalized_post_id );
		if ( ! $post ) {
			DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', $normalized_post_id )->delete();
			return;
		}

		/** @var Ticket_Data $ticket_data */
		$ticket_data = tribe( Ticket_Data::class );

		$has_at_least_one_ticket = false;
		foreach ( $ticket_data->get_posts_tickets( $normalized_post_id ) as $ticket ) {
			$has_at_least_one_ticket = true;
			break;
		}

		if ( ! $has_at_least_one_ticket ) {
			DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', $normalized_post_id )->delete();
			return;
		}

		$batch_size = self::get_batch_size();

		$rules_query = Rule::query()->where( 'status', Rule::ACTIVE_STATUS )->offset( $offset )->limit( $batch_size )->orderBy( 'id' );

		/** @var Listeners $listeners */
		$listeners = tribe( Listeners::class );

		$entries       = [];
		$rules_handled = 0;
		foreach ( (array) $rules_query->getAll() as $rule ) {
			if ( ! $listeners->does_rule_apply_to_post( $rule, $post ) ) {
				continue;
			}

			$entries[] = [
				'rule_id' => $rule->getPrimaryValue(),
				'post_id' => 5000000000 + $normalized_post_id,
			];

			Rule::remove_rule_from_enabled( $normalized_post_id, $rule->getPrimaryValue() );

			++$rules_handled;
		}

		if ( ! empty( $entries ) ) {
			Relationships_Table::insert_many( $entries );
		}

		if ( $rules_handled < $batch_size ) {
			DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', $normalized_post_id )->delete();
			DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', 5000000000 + $post_id )->update( [ 'post_id' => $normalized_post_id ] );
			return;
		}

		shepherd()->dispatch( new self( null, null, $normalized_post_id, $offset + $batch_size ) );
	}

	/**
	 * Handle the ticket change scope evaluation.
	 *
	 * @since 6.9.0
	 *
	 * @param int $ticket_id The ticket ID.
	 * @param int $offset    The offset.
	 *
	 * @return void
	 */
	protected function handle_ticket_change_scope_evaluation( int $ticket_id, int $offset = 0 ): void {
		$ticket = tribe( Ticket_Data::class )->load_ticket_object( $ticket_id );
		if ( ! ( $ticket instanceof Ticket_Object && $ticket->ID ) ) {
			return;
		}

		$event_id = $ticket->get_event_id();
		if ( ! $event_id ) {
			return;
		}

		$post = get_post( $event_id );
		if ( ! $post ) {
			DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', $event_id )->delete();
			return;
		}

		$this->handle_post_change_scope_evaluation( $event_id, $offset );
	}

	/**
	 * Gets the evaluate scope job's hook prefix.
	 *
	 * @since 6.9.0
	 *
	 * @return string The evaluate scope job's hook prefix.
	 */
	public function get_task_prefix(): string {
		return 'etp_prs_on_chg_';
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

	/**
	 * Gets the batch size for the scope evaluation.
	 *
	 * @since 6.9.0
	 *
	 * @return int The batch size.
	 */
	public static function get_batch_size(): int {
		/**
		 * The batch size for the scope evaluation.
		 *
		 * @since 6.9.0
		 *
		 * @param int $batch_size The batch size.
		 */
		return (int) apply_filters( 'tec_tickets_plus_purchase_rules_scope_evaluation_batch_size', 1000 );
	}
}
