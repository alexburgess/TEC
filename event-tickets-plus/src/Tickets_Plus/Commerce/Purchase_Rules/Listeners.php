<?php
/**
 * Listeners for the purchase rules.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Listeners
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use WP_Post;
use Tribe__Events__Main as Events_Main;
use RuntimeException;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Jobs\Evaluate_Scope;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Jobs\Scope_Evaluation_On_Criterion_Deletion;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables\Relationships as Relationships_Table;
use TEC\Common\StellarWP\DB\DB;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Relationship;
use function TEC\Common\StellarWP\Shepherd\shepherd;

/**
 * Listeners for the purchase rules.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Listeners
 */
class Listeners extends Controller_Contract {
	/**
	 * Registers the listeners.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$rule_class = Rule::class;
		// Handle pre and post update of a ticket-able post.
		add_action( 'pre_post_update', [ $this, 'store_post_state_before_update' ] );
		add_action( 'save_post', [ $this, 'schedule_scope_evaluation_on_post_save' ], 10, 2 );
		add_action( 'set_object_terms', [ $this, 'schedule_scope_evaluation_on_post_terms_change' ], 10, 6 );
		add_action( 'tec_events_custom_tables_v1_update_post_after', [ $this, 'check_for_series_changes' ] );

		// Handle pre and post update of a ticket.
		add_action( 'tec_tickets_ticket_pre_save', [ $this, 'store_ticket_state_before_update' ], 10, 2 );
		add_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_scope_evaluation_on_ticket_save' ], 10, 2 );

		// Handle pre and post update of a rule.
		add_action( "stellarwp_schema_models_pre_save_{$rule_class}", [ $this, 'store_rule_state_before_update' ] );
		add_action( "stellarwp_schema_models_post_save_{$rule_class}", [ $this, 'schedule_scope_evaluation_on_rule_save' ] );

		add_action( 'pre_delete_term', [ $this, 'evaluate_scope_on_tag_or_category_deletion' ], 10, 2 );

		add_action( 'wp_trash_post', [ $this, 'evaluate_scope_on_venue_or_series_trash_or_delete' ] );

		/**
		 * We don't handle trashing. Why ? Because we don't want to handle the case where a post is trashed and then restored.
		 *
		 * A trashed post can retain its relationships with rules. We don't mind. A trashed post cant have its tickets purchased after all.
		 *
		 * The above applies to Tickets and all the ticket-able post types. For venues and series we do handle trashing and restoring.
		 */
		add_action( 'before_delete_post', [ $this, 'remove_all_relationships_on_delete' ] );

		add_action( 'tec_shutdown', [ $this, 'schedule_periodic_scope_evaluation' ] );
	}

	/**
	 * Unregisters the listeners.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$rule_class = Rule::class;
		remove_action( 'pre_post_update', [ $this, 'store_post_state_before_update' ] );
		remove_action( 'save_post', [ $this, 'schedule_scope_evaluation_on_post_save' ] );
		remove_action( 'set_object_terms', [ $this, 'schedule_scope_evaluation_on_post_terms_change' ] );
		remove_action( 'tec_events_custom_tables_v1_update_post_after', [ $this, 'check_for_series_changes' ] );
		remove_action( 'tec_tickets_ticket_pre_save', [ $this, 'store_ticket_state_before_update' ] );
		remove_action( 'tec_tickets_ticket_upserted', [ $this, 'schedule_scope_evaluation_on_ticket_save' ] );
		remove_action( "stellarwp_schema_models_pre_save_{$rule_class}", [ $this, 'store_rule_state_before_update' ] );
		remove_action( "stellarwp_schema_models_post_save_{$rule_class}", [ $this, 'schedule_scope_evaluation_on_rule_save' ] );
		remove_action( 'pre_delete_term', [ $this, 'evaluate_scope_on_tag_or_category_deletion' ] );
		remove_action( 'wp_trash_post', [ $this, 'evaluate_scope_on_venue_or_series_trash_or_delete' ] );
		remove_action( 'before_delete_post', [ $this, 'remove_all_relationships_on_delete' ] );
		remove_action( 'tec_shutdown', [ $this, 'schedule_periodic_scope_evaluation' ] );
	}

	/**
	 * Evaluate the scope on tag or category deletion.
	 *
	 * @since 6.9.0
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $taxonomy The taxonomy.
	 *
	 * @return void
	 */
	public function evaluate_scope_on_tag_or_category_deletion( $term_id, $taxonomy ): void {
		if ( ! in_array( $taxonomy, [ Events_Main::TAXONOMY, 'post_tag' ], true ) ) {
			return;
		}

		shepherd()->dispatch(
			new Scope_Evaluation_On_Criterion_Deletion(
				'post_tag' === $taxonomy ? $term_id : null,
				'post_tag' !== $taxonomy ? $term_id : null
			)
		);
	}

	/**
	 * Evaluate the scope on venue or series trash.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function evaluate_scope_on_venue_or_series_trash_or_delete( int $post_id ): void {
		$post_type_array = did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) ? [ Events_Main::VENUE_POST_TYPE, Series_Post_Type::POSTTYPE ] : [ Events_Main::VENUE_POST_TYPE ];

		$post_type = get_post_type( $post_id );
		if ( ! in_array( $post_type, $post_type_array, true ) ) {
			return;
		}

		shepherd()->dispatch(
			new Scope_Evaluation_On_Criterion_Deletion(
				null,
				null,
				$post_type === Events_Main::VENUE_POST_TYPE ? $post_id : null,
				$post_type !== Events_Main::VENUE_POST_TYPE ? $post_id : null
			)
		);
	}

	/**
	 * Store the post state before update.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function store_post_state_before_update( $post_id ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! in_array( get_post_type( $post_id ), (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return;
		}

		$post_id = Controller::maybe_normalize_provisional_post_id( $post_id );

		/** @var Ticket $ticket_data */
		$ticket_data = tribe( Ticket::class );

		$has_at_least_one_ticket = false;
		foreach ( $ticket_data->get_posts_tickets( $post_id ) as $ticket ) {
			$has_at_least_one_ticket = true;
			break;
		}

		if ( ! $has_at_least_one_ticket ) {
			return;
		}

		tribe_cache()[ 'purchase_rules_post_state_' . $post_id ] = $this->build_state( $post_id );
	}

	/**
	 * Check for series changes.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function check_for_series_changes( int $post_id ): void {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$this->schedule_scope_evaluation_on_post_save( $post_id, $post );
	}

	/**
	 * Schedule the scope evaluation on post terms change.
	 *
	 * @since 6.9.0
	 *
	 * @param int    $post_id    The post ID.
	 * @param array  $terms      The terms.
	 * @param array  $tt_ids     The term taxonomy IDs.
	 * @param string $taxonomy   The taxonomy.
	 * @param bool   $append     Whether to append the terms.
	 * @param array  $old_tt_ids The old term taxonomy IDs.
	 *
	 * @return void
	 */
	public function schedule_scope_evaluation_on_post_terms_change( int $post_id, array $terms, array $tt_ids, string $taxonomy, bool $append, array $old_tt_ids ): void {
		if ( ! in_array( $taxonomy, [ Events_Main::TAXONOMY, 'post_tag' ], true ) ) {
			return;
		}

		if ( $tt_ids === $old_tt_ids ) {
			return;
		}

		if ( ! tribe_is_event( $post_id ) ) {
			return;
		}

		shepherd()->dispatch( new Evaluate_Scope( null, null, $post_id ) );
	}

	/**
	 * Schedule the sync on save.
	 *
	 * @since 6.9.0
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post object.
	 *
	 * @return void
	 */
	public function schedule_scope_evaluation_on_post_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( ! in_array( $post->post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return;
		}

		$post_id = Controller::maybe_normalize_provisional_post_id( $post_id );

		/** @var Ticket $ticket_data */
		$ticket_data = tribe( Ticket::class );

		$has_at_least_one_ticket = false;
		foreach ( $ticket_data->get_posts_tickets( $post_id ) as $ticket ) {
			$has_at_least_one_ticket = true;
			break;
		}

		if ( ! $has_at_least_one_ticket && empty( tribe_cache()[ 'purchase_rules_post_state_' . $post_id ] ) ) {
			return;
		}

		if ( ! $has_at_least_one_ticket ) {
			// Tickets were removed! Remove all the rules from the post.
			DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', $post_id )->delete();
			return;
		}

		if ( ! $this->state_changed( $post_id ) ) {
			return;
		}

		shepherd()->dispatch( new Evaluate_Scope( null, null, $post_id ) );
	}

	/**
	 * Store the ticket state before update.
	 *
	 * @since 6.9.0
	 *
	 * @param int           $post_id The post ID.
	 * @param Ticket_Object $ticket  The ticket object.
	 *
	 * @return void
	 */
	public function store_ticket_state_before_update( int $post_id, Ticket_Object $ticket ): void {
		$cache = tribe_cache();
		if ( empty( $ticket->ID ) ) {
			$cache['purchase_rules_ticket_state_store_new-ticket'] = true;
			return;
		}

		$current_state_ticket = tribe( Ticket::class )->load_ticket_object( $ticket->ID );

		$cache[ 'purchase_rules_ticket_state_store_' . $ticket->ID ] = $current_state_ticket->name;
	}

	/**
	 * Schedule the ticket sync.
	 *
	 * @since 6.9.0
	 *
	 * @param int  $ticket_id The ticket ID.
	 * @param ?int $parent_id The parent ID. Null when the event has been deleted...
	 *
	 * @return void
	 */
	public function schedule_scope_evaluation_on_ticket_save( int $ticket_id, ?int $parent_id = null ): void {
		if ( ! $parent_id ) {
			// The parent event has been deleted.
			return;
		}

		$ticket_object = tribe( Ticket::class )->load_ticket_object( $ticket_id );
		if ( ! $ticket_object instanceof Ticket_Object ) {
			return;
		}

		$cache = tribe_cache();

		if ( empty( $cache['purchase_rules_ticket_state_store_new-ticket'] ) && ! empty( $cache[ 'purchase_rules_ticket_state_store_' . $ticket_id ] ) && $ticket_object->name === $cache[ 'purchase_rules_ticket_state_store_' . $ticket_id ] ?? '' ) {
			return;
		}

		unset( $cache['purchase_rules_ticket_state_store_new-ticket'] );

		shepherd()->dispatch( new Evaluate_Scope( null, $ticket_id ) );
	}

	/**
	 * Store the rule state before update.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule $rule The rule.
	 *
	 * @return void
	 */
	public function store_rule_state_before_update( Rule $rule ): void {
		$cache = tribe_cache();
		if ( ! $rule->getPrimaryValue() ) {
			$cache['purchase_rules_rule_state_store_new-rule'] = true;
			return;
		}

		try {
			$original_ticket_keywords = $rule->get_original_ticket_keywords();
			$current_ticket_keywords  = $rule->get_ticket_keywords();
		} catch ( RuntimeException $e ) {
			$original_ticket_keywords = '';
			$current_ticket_keywords  = '';
		}

		if ( $original_ticket_keywords === $current_ticket_keywords && ! $rule->isDirty( 'scope' ) ) {
			return;
		}

		$cache[ 'purchase_rules_rule_state_store_' . $rule->getPrimaryValue() ] = true;
	}

	/**
	 * Schedule the scope evaluation on rule save.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule $rule The rule.
	 *
	 * @return void
	 */
	public function schedule_scope_evaluation_on_rule_save( Rule $rule ): void {
		$cache = tribe_cache();
		if ( empty( $cache['purchase_rules_rule_state_store_new-rule'] ) && empty( $cache[ 'purchase_rules_rule_state_store_' . $rule->getPrimaryValue() ] ) ) {
			return;
		}

		unset( $cache['purchase_rules_rule_state_store_new-rule'] );

		shepherd()->dispatch( new Evaluate_Scope( $rule->getPrimaryValue() ) );
	}

	/**
	 * Remove all relationships on post delete.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return void
	 */
	public function remove_all_relationships_on_delete( int $post_id ): void {
		$post_type_array = did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) ? [ Events_Main::VENUE_POST_TYPE, Series_Post_Type::POSTTYPE ] : [ Events_Main::VENUE_POST_TYPE ];

		$post_type = get_post_type( $post_id );

		if ( in_array( $post_type, $post_type_array, true ) ) {
			$this->evaluate_scope_on_venue_or_series_trash_or_delete( $post_id );
			return;
		}

		if ( Ticket::POSTTYPE === $post_type ) {
			/** @var Ticket $ticket_data */
			$ticket_data   = tribe( Ticket::class );
			$ticket_object = $ticket_data->load_ticket_object( $post_id );

			if ( ! $ticket_object instanceof Ticket_Object ) {
				return;
			}

			$event_id = $ticket_object->get_event_id();
			if ( ! $event_id ) {
				return;
			}

			$has_at_least_one_other_ticket = false;

			foreach ( $ticket_data->get_posts_tickets( $event_id ) as $ticket ) {
				if ( ! $ticket instanceof Ticket_Object ) {
					continue;
				}

				if ( $ticket->ID === $post_id ) {
					continue;
				}

				$has_at_least_one_other_ticket = true;
				break;
			}

			if ( ! $has_at_least_one_other_ticket ) {
				DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', $event_id )->delete();
				return;
			}

			shepherd()->dispatch( new Evaluate_Scope( null, null, $event_id ) );
			return;
		}

		if ( ! in_array( $post_type, (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return;
		}

		DB::table( Relationships_Table::table_name( false ) )->where( 'post_id', $post_id )->delete();
	}

	/**
	 * Checks if a rule applies to a post.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule    $rule The rule.
	 * @param WP_Post $post The post.
	 *
	 * @return bool Whether the rule applies to the post.
	 *
	 * @throws RuntimeException If the criterion term is invalid.
	 */
	public function does_rule_apply_to_post( Rule $rule, WP_Post $post ): bool {
		/** @var Ticket $ticket_data */
		$ticket_data = tribe( Ticket::class );

		$event_tickets = iterator_to_array( $ticket_data->get_posts_tickets( $post->ID ) );

		if ( empty( $event_tickets ) ) {
			// No tickets, no purchase rules can apply.
			return false;
		}

		if ( isset( Rule::TICKET_TYPES[ $rule->get_type() ] ) ) {
			if ( ! $this->does_posts_tickets_apply_to_rule( $rule, $event_tickets ) ) {
				return false;
			}
		}


		return $this->does_rules_scope_apply_to_post( $rule, $post );
	}

	/**
	 * Checks if a rule scope applies to a post.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule    $rule The rule.
	 * @param WP_Post $post The post.
	 *
	 * @return bool Whether the rule scope applies to the post.
	 *
	 * @throws RuntimeException If the criterion term is invalid.
	 */
	private function does_rules_scope_apply_to_post( Rule $rule, WP_Post $post ): bool {
		$rule_scope = $rule->get_scope();
		$connector  = $rule_scope['connector'] ?? 'all';
		if ( 'all' === $connector ) {
			return true;
		}

		if ( 'none' === $connector ) {
			return false;
		}

		$match_any = 'or' === $connector;

		$criteria = $rule_scope['criteria'] ?? [];

		$matched_one = false;

		$cache = tribe_cache();

		foreach ( $criteria as $criterion ) {
			if ( empty( $criterion['term'] ) || empty( $criterion['value'] ) ) {
				continue;
			}

			switch ( $criterion['term'] ) {
				case 'category':
					if ( empty( $cache[ 'purchase_rules_event_categories_' . $post->ID ] ) ) {
						$cache[ 'purchase_rules_event_categories_' . $post->ID ] = get_the_terms( $post->ID, Events_Main::TAXONOMY );
					}

					if ( is_wp_error( $cache[ 'purchase_rules_event_categories_' . $post->ID ] ) || empty( $cache[ 'purchase_rules_event_categories_' . $post->ID ] ) ) {
						continue 2;
					}

					if ( in_array( (int) $criterion['value'], wp_list_pluck( $cache[ 'purchase_rules_event_categories_' . $post->ID ], 'term_id' ), true ) ) {
						if ( $match_any ) {
							return true;
						} else {
							$matched_one = true;
						}
					} elseif ( ! $match_any ) {
						return false;
					}

					break;
				case 'title':
					if ( strstr( strtolower( $post->post_title ), strtolower( $criterion['value'] ) ) !== false ) {
						if ( $match_any ) {
							return true;
						} else {
							$matched_one = true;
						}
					} elseif ( ! $match_any ) {
						return false;
					}

					break;
				case 'tag':
					if ( empty( $cache[ 'purchase_rules_event_tags_' . $post->ID ] ) ) {
						$cache[ 'purchase_rules_event_tags_' . $post->ID ] = get_the_terms( $post->ID, 'post_tag' );
					}

					if ( empty( $cache[ 'purchase_rules_event_tags_' . $post->ID ] ) ) {
						$cache[ 'purchase_rules_event_tags_' . $post->ID ] = get_the_terms( $post->ID, 'post_tag' );
					}

					if ( is_wp_error( $cache[ 'purchase_rules_event_tags_' . $post->ID ] ) || empty( $cache[ 'purchase_rules_event_tags_' . $post->ID ] ) ) {
						continue 2;
					}

					if ( in_array( (int) $criterion['value'], wp_list_pluck( $cache[ 'purchase_rules_event_tags_' . $post->ID ], 'term_id' ), true ) ) {
						if ( $match_any ) {
							return true;
						} else {
							$matched_one = true;
						}
					} elseif ( ! $match_any ) {
						return false;
					}

					break;
				case 'venue':
					if ( empty( $cache[ 'purchase_rules_event_venues_' . $post->ID ] ) ) {
						$cache[ 'purchase_rules_event_venues_' . $post->ID ] = array_map( 'intval', tec_get_venue_ids( $post->ID ) );
					}

					if ( in_array( (int) $criterion['value'], $cache[ 'purchase_rules_event_venues_' . $post->ID ], true ) ) {
						if ( $match_any ) {
							return true;
						} else {
							$matched_one = true;
						}
					} elseif ( ! $match_any ) {
						return false;
					}

					break;

				case 'series':
					if ( ! did_action( 'tec_events_pro_custom_tables_v1_fully_activated' ) ) {
						break;
					}

					$relationship = Series_Relationship::where( 'series_post_id', '=', $criterion['value'] )->where( 'event_post_id', '=', $post->ID )->first();

					if ( ! empty( $relationship->relationship_id ) ) {
						if ( $match_any ) {
							return true;
						} else {
							$matched_one = true;
						}
					} elseif ( ! $match_any ) {
						return false;
					}

					break;

				default:
					throw new RuntimeException( sprintf( 'Invalid criterion term: %s', $criterion['term'] ) );
			}
		}

		return $match_any ? false : $matched_one;
	}

	/**
	 * Checks if a rule applies to a posts tickets.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule  $rule    The rule.
	 * @param array $tickets The tickets.
	 *
	 * @return bool Whether the rule applies to the posts tickets.
	 *
	 * @throws RuntimeException If the rule type is invalid.
	 */
	private function does_posts_tickets_apply_to_rule( Rule $rule, array $tickets ): bool {
		if ( ! isset( Rule::TICKET_TYPES[ $rule->get_type() ] ) ) {
			throw new RuntimeException( 'Invalid rule type for posts tickets: ' . $rule->get_type() );
		}

		$ticket_keywords = $rule->get_ticket_keywords();

		if ( empty( $ticket_keywords ) ) {
			// No ticket keywords, no purchase rules can apply.
			return false;
		}

		if ( $rule->get_type() === Rule::COMBINED_PURCHASE_TYPE ) {
			$required_tickets = $rule->get_config()['requiredTickets'] ?? '';
			if ( ! empty( $required_tickets ) ) {
				foreach ( $required_tickets as $required_ticket ) {
					$required_ticket_found = false;
					foreach ( $tickets as $event_ticket ) {
						if ( $this->does_ticket_keyword_apply_to_ticket( $required_ticket, $event_ticket ) ) {
							$required_ticket_found = true;
							break;
						}
					}
					if ( ! $required_ticket_found ) {
						return false;
					}
				}
			}
		}

		foreach ( $ticket_keywords as $ticket_keyword ) {
			if ( empty( $ticket_keyword ) ) {
				continue;
			}

			$matched = false;

			foreach ( $tickets as $event_ticket ) {
				if ( ! is_string( $ticket_keyword ) && ! is_array( $ticket_keyword ) ) {
					continue;
				}

				if ( is_array( $ticket_keyword ) ) {
					if ( empty( $ticket_keyword['ticket'] ) ) {
						continue;
					}

					if ( ! $this->does_ticket_keyword_apply_to_ticket( $ticket_keyword['ticket'], $event_ticket ) ) {
						continue;
					}

					$matched = true;
					break;
				} else {
					if ( ! $this->does_ticket_keyword_apply_to_ticket( $ticket_keyword, $event_ticket ) ) {
						continue;
					}

					$matched = true;
					break;
				}
			}

			if ( ! $matched ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Schedule the periodic scope evaluation.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function schedule_periodic_scope_evaluation(): void {
		/**
		 * The interval for the periodic scope evaluation.
		 *
		 * @since 6.9.0
		 *
		 * @param int $interval The interval in seconds.
		 */
		$interval = (int) apply_filters( 'tec_tickets_plus_purchase_rules_periodic_scope_evaluation_interval', 12 * HOUR_IN_SECONDS );

		if ( ! $interval ) {
			return;
		}

		shepherd()->dispatch(
			new Evaluate_Scope(),
			$interval
		);
	}

	/**
	 * Checks if the state of a post has changed.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return bool Whether the state of the post has changed.
	 */
	private function state_changed( int $post_id ): bool {
		$current_state  = $this->build_state( $post_id );
		$previous_state = tribe_cache()[ 'purchase_rules_post_state_' . $post_id ] ?? [];
		return $current_state !== $previous_state;
	}

	/**
	 * Build the state for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array The state.
	 */
	private function build_state( int $post_id ): array {
		$series = function_exists( 'tec_event_series' ) ? tec_event_series( $post_id ) : null;
		$series = $series instanceof WP_Post ? $series->ID : null;

		$tags = get_the_tags( $post_id );
		$cats = get_the_terms( $post_id, Events_Main::TAXONOMY );

		return [
			'tags'   => is_array( $tags ) ? wp_list_pluck( $tags, 'term_id' ) : [],
			'cats'   => is_array( $cats ) ? wp_list_pluck( $cats, 'term_id' ) : [],
			'venues' => tec_get_venue_ids( $post_id ),
			'series' => $series,
			'title'  => get_the_title( $post_id ),
		];
	}

	/**
	 * Checks if a ticket keyword applies to a ticket.
	 *
	 * @since 6.9.0
	 *
	 * @param string        $ticket_keyword The ticket keyword.
	 * @param Ticket_Object $ticket         The ticket.
	 *
	 * @return bool Whether the ticket keyword applies to the ticket.
	 */
	private function does_ticket_keyword_apply_to_ticket( string $ticket_keyword, Ticket_Object $ticket ): bool {
		return false !== strstr( strtolower( $ticket->name ), strtolower( $ticket_keyword ) );
	}
}
