<?php
/**
 * The rule model.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Models
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Models;

use TEC\Common\StellarWP\SchemaModels\SchemaModel;
use TEC\Common\StellarWP\Schema\Tables\Contracts\Table as Table_Interface;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables\Rules as Rules_Table;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Date_Time;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;
use TEC\Common\REST\TEC\V1\Parameter_Types\Definition_Parameter;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Tables\Relationships;
use TEC\Common\StellarWP\SchemaModels\Relationships\ManyToManyWithPosts;
use TEC\Common\StellarWP\DB\DB;
use WP_Post;
use RuntimeException;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Scope_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Config_Definition;
use DateTimeInterface;

/**
 * Class Rule.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Models
 *
 * @method int get_id()
 * @method string get_name()
 * @method array get_config()
 * @method ?array get_scope()
 * @method string get_status()
 * @method string get_type()
 * @method DateTimeInterface get_updated_at()
 * @method void set_id( int $id )
 * @method void set_name( string $name )
 * @method void set_config( array $config )
 * @method void set_scope( ?array $scope )
 * @method void set_status( string $status )
 * @method void set_type( string $type )
 * @method void set_updated_at( DateTimeInterface $updated_at )
 */
class Rule extends SchemaModel {
	/**
	 * The manually enabled meta key.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const MANUALLY_ENABLED_META_KEY = 'tec_tickets_plus_purchase_rules_enabled';

	/**
	 * The manually disabled meta key.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const MANUALLY_DISABLED_META_KEY = 'tec_tickets_plus_purchase_rules_disabled';

	/**
	 * The delete action.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const DELETE_ACTION = 'tec_tickets_plus_delete_purchase_rule';

	/**
	 * The toggle status action.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const TOGGLE_STATUS_ACTION = 'tec_tickets_plus_toggle_purchase_rule_status';

	/**
	 * The discount type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const ORDER_DISCOUNT_TYPE = 'order-discount';

	/**
	 * The ticket discount type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const TICKET_DISCOUNT_TYPE = 'ticket-discount';

	/**
	 * The event purchase limit type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const EVENT_PURCHASE_LIMIT_TYPE = 'event-purchase-limit';

	/**
	 * The ticket purchase limit type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const TICKET_PURCHASE_LIMIT_TYPE = 'ticket-purchase-limit';

	/**
	 * The event ticket purchase min type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const EVENT_PURCHASE_MIN_TYPE = 'event-purchase-min';

	/**
	 * The ticket purchase min type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const TICKET_PURCHASE_MIN_TYPE = 'ticket-purchase-min';

	/**
	 * The user role restriction type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const USER_ROLE_RESTRICTION_TYPE = 'user-role-restriction';

	/**
	 * The combined purchase type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const COMBINED_PURCHASE_TYPE = 'combined-purchase';

	/**
	 * The ticket types.
	 *
	 * @since 6.9.0
	 *
	 * @var array
	 */
	public const TICKET_TYPES = [
		self::TICKET_DISCOUNT_TYPE       => true,
		self::TICKET_PURCHASE_LIMIT_TYPE => true,
		self::TICKET_PURCHASE_MIN_TYPE   => true,
		self::COMBINED_PURCHASE_TYPE     => true,
	];

	/**
	 * The promotional types.
	 *
	 * @since 6.9.0
	 *
	 * @var array
	 */
	public const PROMOTIONAL_TYPES = [
		self::ORDER_DISCOUNT_TYPE  => true,
		self::TICKET_DISCOUNT_TYPE => true,
	];

	/**
	 * The all types.
	 *
	 * @since 6.9.0
	 *
	 * @var array
	 */
	public const ALL_TYPES = [
		self::ORDER_DISCOUNT_TYPE,
		self::TICKET_DISCOUNT_TYPE,
		self::EVENT_PURCHASE_LIMIT_TYPE,
		self::TICKET_PURCHASE_LIMIT_TYPE,
		self::EVENT_PURCHASE_MIN_TYPE,
		self::TICKET_PURCHASE_MIN_TYPE,
		self::USER_ROLE_RESTRICTION_TYPE,
		self::COMBINED_PURCHASE_TYPE,
	];

	/**
	 * The quantity requirement.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const QUANTITY_REQUIREMENT = 'quantity';

	/**
	 * The total requirement.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const TOTAL_REQUIREMENT = 'total';

	/**
	 * The all requirements.
	 *
	 * @since 6.9.0
	 *
	 * @var array
	 */
	public const ALL_REQUIREMENTS = [
		self::QUANTITY_REQUIREMENT,
		self::TOTAL_REQUIREMENT,
	];

	/**
	 * The percentage discount type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const PERCENTAGE_DISCOUNT_TYPE = 'percentage';

	/**
	 * The flat discount type.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const FLAT_DISCOUNT_TYPE = 'flat';

	/**
	 * The all discount types.
	 *
	 * @since 6.9.0
	 *
	 * @var array
	 */
	public const ALL_DISCOUNT_TYPES = [
		self::PERCENTAGE_DISCOUNT_TYPE,
		self::FLAT_DISCOUNT_TYPE,
	];

	/**
	 * The matched required quantity.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const MATCHED_REQUIRED_QUANTITY = 'matched';

	/**
	 * The specific required quantity.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const SPECIFIC_REQUIRED_QUANTITY = 'specific';

	/**
	 * The all required quantities.
	 *
	 * @since 6.9.0
	 *
	 * @var array
	 */
	public const ALL_REQUIRED_QUANTITIES = [
		self::MATCHED_REQUIRED_QUANTITY,
		self::SPECIFIC_REQUIRED_QUANTITY,
	];

	/**
	 * The active status.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const ACTIVE_STATUS = 'active';

	/**
	 * The inactive status.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public const INACTIVE_STATUS = 'inactive';

	/**
	 * The all statuses.
	 *
	 * @since 6.9.0
	 *
	 * @var array
	 */
	public const ALL_STATUS = [
		self::ACTIVE_STATUS,
		self::INACTIVE_STATUS,
	];

	/**
	 * Gets the label for a key.
	 *
	 * @since 6.9.0
	 *
	 * @param string $key The key.
	 *
	 * @return string The label.
	 */
	public static function get_label( string $key ): string {
		$translations = [
			self::ACTIVE_STATUS              => __( 'Active', 'event-tickets-plus' ),
			self::INACTIVE_STATUS            => __( 'Inactive', 'event-tickets-plus' ),
			self::ORDER_DISCOUNT_TYPE        => __( 'Order discount', 'event-tickets-plus' ),
			self::TICKET_DISCOUNT_TYPE       => __( 'Ticket discount', 'event-tickets-plus' ),
			self::EVENT_PURCHASE_LIMIT_TYPE  => __( 'Event purchase limit', 'event-tickets-plus' ),
			self::TICKET_PURCHASE_LIMIT_TYPE => __( 'Ticket purchase limit', 'event-tickets-plus' ),
			self::EVENT_PURCHASE_MIN_TYPE    => __( 'Event purchase minimum', 'event-tickets-plus' ),
			self::TICKET_PURCHASE_MIN_TYPE   => __( 'Ticket purchase minimum', 'event-tickets-plus' ),
			self::USER_ROLE_RESTRICTION_TYPE => __( 'User role restriction', 'event-tickets-plus' ),
			self::COMBINED_PURCHASE_TYPE     => __( 'Combined purchase', 'event-tickets-plus' ),
		];

		/**
		 * Filters the translations for the rule.
		 *
		 * @since 6.9.0
		 *
		 * @param array  $translations The translations.
		 * @param string $key          The key.
		 *
		 * @return array The translations.
		 */
		$translations = (array) apply_filters( 'tec_tickets_plus_purchase_rules_rule_translations', $translations, $key );

		return $translations[ $key ] ?? $key;
	}

	/**
	 * Gets the labels for the rule types.
	 *
	 * @since 6.9.0
	 *
	 * @return array<string, string> The labels.
	 */
	public static function get_rule_type_labels(): array {
		return array_combine( self::ALL_TYPES, array_map( fn( $type ) => self::get_label( $type ), self::ALL_TYPES ) );
	}

	/**
	 * Gets the descriptions for the rule.
	 *
	 * @since 6.9.0
	 *
	 * @return string[] The descriptions.
	 */
	public static function get_rule_descriptions(): array {
		$descriptions = [
			self::ORDER_DISCOUNT_TYPE        => __( 'Apply a discount to an order.', 'event-tickets-plus' ),
			self::TICKET_DISCOUNT_TYPE       => __( 'Apply a discount to specific tickets.', 'event-tickets-plus' ),
			self::EVENT_PURCHASE_LIMIT_TYPE  => __( 'Limit the total number of tickets per order.', 'event-tickets-plus' ),
			self::TICKET_PURCHASE_LIMIT_TYPE => __( 'Limit the number of specific tickets per order.', 'event-tickets-plus' ),
			self::EVENT_PURCHASE_MIN_TYPE    => __( 'Require a minimum ticket quantity or total value per order.', 'event-tickets-plus' ),
			self::TICKET_PURCHASE_MIN_TYPE   => __( 'Require a minimum number of specific tickets per order.', 'event-tickets-plus' ),
			self::USER_ROLE_RESTRICTION_TYPE => __( 'Restrict purchases to specific user roles.', 'event-tickets-plus' ),
			self::COMBINED_PURCHASE_TYPE     => __( 'Require tickets to be purchased in specific combinations.', 'event-tickets-plus' ),
		];

		/**
		 * Filters the descriptions for the rule.
		 *
		 * @since 6.9.0
		 *
		 * @param string[] $descriptions The descriptions.
		 *
		 * @return string[] The descriptions.
		 */
		return (array) apply_filters( 'tec_tickets_plus_purchase_rules_rule_descriptions', $descriptions );
	}

	/**
	 * Gets the description for a rule.
	 *
	 * @since 6.9.0
	 *
	 * @param string $key The key.
	 *
	 * @return string The description.
	 *
	 * @throws RuntimeException If the key is invalid.
	 */
	public static function get_rule_description( string $key ): string {
		$descriptions = self::get_rule_descriptions();

		if ( ! isset( $descriptions[ $key ] ) ) {
			throw new RuntimeException( sprintf( 'Invalid key: %s', $key ) );
		}

		return $descriptions[ $key ]();
	}

	/**
	 * Constructs the rule relationships.
	 *
	 * @since 6.9.0
	 */
	protected static function relationships(): array {
		return [
			'events' => ( new ManyToManyWithPosts( 'events' ) )
				->setTableInterface( Relationships::class )
				->setThisEntityColumn( 'rule_id' )
				->setOtherEntityColumn( 'post_id' )
				->setHydrateWith( fn( int $post_id ) => get_post( $post_id ) )
				->setValidateSanitizeRelationshipWith(
					function ( $post_or_post_id ): ?int {
						$p = get_post( $post_or_post_id );
						return $p instanceof WP_Post ? $p->ID : null;
					}
				),
		];
	}

	/**
	 * Gets the properties of the rule.
	 *
	 * @since 6.9.0
	 *
	 * @return PropertiesCollection The properties of the rule.
	 */
	public function get_properties(): PropertiesCollection {
		$properties = new PropertiesCollection();

		$properties[] = (
			new Positive_Integer(
				'id',
				fn() => __( 'Unique identifier for the rule.', 'event-tickets-plus' ),
			)
		)->set_example( 12345 )->set_read_only( true );

		$properties[] = (
			new Text(
				'type',
				fn() => __( 'The type of the rule.', 'event-tickets-plus' ),
				self::ORDER_DISCOUNT_TYPE,
				self::ALL_TYPES
			)
		)->set_example( self::ORDER_DISCOUNT_TYPE );

		$properties[] = (
			new Text(
				'name',
				fn() => __( 'The name of the rule.', 'event-tickets-plus' ),
			)
		)->set_example( 'Early Bird Discount' );

		$properties[] = new Definition_Parameter( new Config_Definition(), 'config' );

		$properties[] = new Definition_Parameter( new Scope_Definition(), 'scope' );

		$properties[] = (
			new Text(
				'status',
				fn() => __( 'A named status for the entity.', 'event-tickets-plus' ),
				self::ACTIVE_STATUS,
				self::ALL_STATUS
			)
		)->set_example( self::ACTIVE_STATUS );

		$properties[] = (
			new Date_Time(
				'start_date',
				fn() => __( 'The start date of the rule.', 'event-tickets-plus' ),
			)
		)->set_example( '2025-06-05 12:00:00' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' );

		$properties[] = (
			new Date_Time(
				'end_date',
				fn() => __( 'The end date of the rule.', 'event-tickets-plus' ),
			)
		)->set_example( '2025-01-01 00:00:00' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' );

		$properties[] = (
			new Date_Time(
				'updated_at',
				fn() => __( 'The updated at date of the rule.', 'event-tickets-plus' ),
			)
		)->set_example( '2025-01-01 00:00:00' )->set_pattern( '^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$' )->set_read_only( true );

		return $properties;
	}

	/**
	 * Gets the table interface for the rule.
	 *
	 * @since 6.9.0
	 *
	 * @return Table_Interface The table interface.
	 */
	public static function getTableInterface(): Table_Interface {
		return tribe( Rules_Table::class );
	}

	/**
	 * Gets the ticket keyword for the rule.
	 *
	 * @since 6.9.0
	 *
	 * @return string[] The ticket keywords.
	 *
	 * @throws RuntimeException If the type is invalid.
	 */
	public function get_ticket_keywords(): array {
		$config = $this->get_config();

		switch ( $this->get_type() ) {
			case self::TICKET_DISCOUNT_TYPE:
				return $config['requirements'] ?? [];
			case self::TICKET_PURCHASE_LIMIT_TYPE:
			case self::TICKET_PURCHASE_MIN_TYPE:
				return [ $config['limitedTicket'] ?? '' ];
			case self::COMBINED_PURCHASE_TYPE:
				return [ $config['restrictedTicket'] ?? '' ];
			default:
				throw new RuntimeException( sprintf( 'Invalid type for ticket keywords: %s', $this->get_type() ) );
		}
	}

	/**
	 * Gets the ticket keyword for the rule.
	 *
	 * @since 6.9.0
	 *
	 * @return string[] The ticket keywords.
	 *
	 * @throws RuntimeException If the type is invalid.
	 */
	public function get_original_ticket_keywords(): array {
		$config = $this->getOriginal( 'config' );

		switch ( $this->get_type() ) {
			case self::TICKET_DISCOUNT_TYPE:
				return $config['requirements'] ?? [];
			case self::TICKET_PURCHASE_LIMIT_TYPE:
			case self::TICKET_PURCHASE_MIN_TYPE:
				return [ $config['limitedTicket'] ?? '' ];
			case self::COMBINED_PURCHASE_TYPE:
				return [ $config['restrictedTicket'] ?? '' ];
			default:
				throw new RuntimeException( sprintf( 'Invalid type for ticket keywords: %s', $this->get_type() ) );
		}
	}

	/**
	 * Links a rule to a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int  $rule_id The rule ID.
	 * @param int  $post_id The post ID.
	 * @param bool $enabled The enabled status.
	 */
	public static function manage_rule_relationship_with_post( int $rule_id, int $post_id, bool $enabled ): void {
		$rule_details = self::get_rule_details_for_post( $post_id );

		[ 'auto' => $automatic_rules, 'manual' => $manually_enabled_rules, 'disabled' => $manually_disabled_rules ] = $rule_details;

		$automatic_rules_flipped         = array_flip( $automatic_rules );
		$manually_enabled_rules_flipped  = array_flip( $manually_enabled_rules );
		$manually_disabled_rules_flipped = array_flip( $manually_disabled_rules );

		if ( isset( $automatic_rules_flipped[ $rule_id ] ) ) {
			if ( $enabled ) {
				self::remove_rule_from_disabled( $post_id, $rule_id );
			} else {
				self::add_rule_to_disabled( $post_id, $rule_id );
			}
		}

		if ( ! $enabled && isset( $manually_enabled_rules_flipped[ $rule_id ] ) ) {
			self::remove_rule_from_enabled( $post_id, $rule_id );
		}

		if ( $enabled && ! isset( $manually_disabled_rules_flipped[ $rule_id ] ) && ! isset( $automatic_rules_flipped[ $rule_id ] ) ) {
			self::add_rule_to_enabled( $post_id, $rule_id );
		}
	}

	/**
	 * Gets the rule data for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int  $post_id          The post ID.
	 * @param bool $include_disabled Whether to include disabled rules.
	 *
	 * @return array The rule data.
	 */
	private static function get_rule_data_for_post( int $post_id, bool $include_disabled = true ): array {
		$rule_details = self::get_rule_details_for_post( $post_id );

		[ 'auto' => $automatic_rules, 'manual' => $manually_enabled_rules, 'disabled' => $manually_disabled_rules ] = $rule_details;

		if ( ! $include_disabled ) {
			$automatic_rules        = array_diff( $automatic_rules, $manually_disabled_rules );
			$manually_enabled_rules = array_diff( $manually_enabled_rules, $manually_disabled_rules );
		}

		$automatic_rules = array_map(
			function ( $rule_id ) use ( $manually_disabled_rules ) {
				$rule = Rule::find( $rule_id );

				if ( ! $rule ) {
					return null;
				}

				$manually_disabled_rules_flipped = array_flip( $manually_disabled_rules );

				return array_merge( Rule::find( $rule_id )->toArray(), [ 'enabled' => ! isset( $manually_disabled_rules_flipped[ $rule_id ] ) ] );
			},
			$automatic_rules
		);

		return [
			'auto'   => array_values( array_filter( $automatic_rules, fn( $rule ) => $rule !== null ) ),
			'manual' => array_values(
				array_filter(
					array_map(
						function ( $rule_id ) {
							$rule = Rule::find( $rule_id );

							if ( ! $rule ) {
								return null;
							}

							return array_merge( $rule->toArray(), [ 'enabled' => true ] );
						},
						$manually_enabled_rules
					),
					fn( $rule ) => $rule !== null
				),
			),
		];
	}

	/**
	 * Gets the rules for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Rule[] The rules.
	 */
	public static function get_active_rules_for_post_backend( int $post_id ): array {
		$rule_data = self::get_rule_data_for_post( $post_id );

		[ 'auto' => $automatic_rules, 'manual' => $manually_enabled_rules ] = $rule_data;

		$rule_is_valid_callback = fn( $rule ) => ! empty( $rule['id'] ) && ! empty( $rule['status'] ) && isset( $rule['enabled'] ) && $rule['status'] === self::ACTIVE_STATUS;

		return [
			'auto'   => array_values( array_filter( array_filter( $automatic_rules ), $rule_is_valid_callback ) ),
			'manual' => array_values( array_filter( array_filter( $manually_enabled_rules ), $rule_is_valid_callback ) ),
		];
	}

	/**
	 * Gets the rules for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Rule[] The rules.
	 */
	public static function get_active_rules_for_post( int $post_id ): array {
		$rule_data = self::get_rule_data_for_post( $post_id, false );

		[ 'auto' => $automatic_rules, 'manual' => $manually_enabled_rules ] = $rule_data;

		$merged_rules = array_map( fn( $rule ) => self::find( $rule['id'] ), array_filter( array_merge( $automatic_rules, $manually_enabled_rules ) ) );

		return array_filter( $merged_rules, fn( $rule ) => $rule instanceof Rule && $rule->get_status() === self::ACTIVE_STATUS );
	}

	/**
	 * Gets the active promotional rules for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Rule[] The rules.
	 */
	public static function get_active_promotional_rules_for_post( int $post_id ): array {
		$rules = self::get_active_rules_for_post( $post_id );
		return array_filter( $rules, fn( Rule $rule ) => isset( self::PROMOTIONAL_TYPES[ $rule->get_type() ] ) );
	}

	/**
	 * Gets the active non-promotional rules for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return Rule[] The rules.
	 */
	public static function get_active_non_promotional_rules_for_post( int $post_id ): array {
		$rules = self::get_active_rules_for_post( $post_id );
		return array_filter( $rules, fn( Rule $rule ) => ! isset( self::PROMOTIONAL_TYPES[ $rule->get_type() ] ) );
	}

	/**
	 * Gets the rule details for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array The rule details.
	 */
	private static function get_rule_details_for_post( int $post_id ): array {
		$automatic_rules = wp_list_pluck( DB::table( Relationships::table_name( false ) )->select( 'rule_id' )->where( 'post_id', $post_id )->getAll(), 'rule_id' );

		$manually_enabled_rules  = (array) get_post_meta( $post_id, self::MANUALLY_ENABLED_META_KEY, true );
		$manually_disabled_rules = (array) get_post_meta( $post_id, self::MANUALLY_DISABLED_META_KEY, true );

		$ensure_integer = fn ( $v ) => (int) $v;

		return [
			'auto'     => array_map( $ensure_integer, $automatic_rules ),
			'manual'   => array_map( $ensure_integer, $manually_enabled_rules ),
			'disabled' => array_map( $ensure_integer, $manually_disabled_rules ),
		];
	}

	/**
	 * Removes a rule from the disabled list.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $rule_id The rule ID.
	 */
	private static function remove_rule_from_disabled( int $post_id, int $rule_id ): void {
		$disabled_rules = get_post_meta( $post_id, self::MANUALLY_DISABLED_META_KEY, true );
		if ( ! is_array( $disabled_rules ) ) {
			$disabled_rules = [];
		}
		$disabled_rules = array_diff( $disabled_rules, [ $rule_id ] );
		update_post_meta( $post_id, self::MANUALLY_DISABLED_META_KEY, array_values( array_filter( array_unique( $disabled_rules ) ) ) );
	}

	/**
	 * Adds a rule to the disabled list.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $rule_id The rule ID.
	 */
	private static function add_rule_to_disabled( int $post_id, int $rule_id ): void {
		$disabled_rules = get_post_meta( $post_id, self::MANUALLY_DISABLED_META_KEY, true );
		if ( ! is_array( $disabled_rules ) ) {
			$disabled_rules = [];
		}
		$disabled_rules[] = $rule_id;
		update_post_meta( $post_id, self::MANUALLY_DISABLED_META_KEY, array_values( array_filter( array_unique( $disabled_rules ) ) ) );
	}

	/**
	 * Removes a rule from the enabled list.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $rule_id The rule ID.
	 */
	public static function remove_rule_from_enabled( int $post_id, int $rule_id ): void {
		$enabled_rules = get_post_meta( $post_id, self::MANUALLY_ENABLED_META_KEY, true );
		if ( ! is_array( $enabled_rules ) ) {
			$enabled_rules = [];
		}
		$enabled_rules = array_diff( $enabled_rules, [ $rule_id ] );
		update_post_meta( $post_id, self::MANUALLY_ENABLED_META_KEY, array_values( array_filter( array_unique( $enabled_rules ) ) ) );
	}

	/**
	 * Adds a rule to the enabled list.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 * @param int $rule_id The rule ID.
	 */
	private static function add_rule_to_enabled( int $post_id, int $rule_id ): void {
		$enabled_rules = get_post_meta( $post_id, self::MANUALLY_ENABLED_META_KEY, true );
		if ( ! is_array( $enabled_rules ) ) {
			$enabled_rules = [];
		}
		$enabled_rules[] = $rule_id;
		update_post_meta( $post_id, self::MANUALLY_ENABLED_META_KEY, array_values( array_filter( array_unique( $enabled_rules ) ) ) );
	}
}
