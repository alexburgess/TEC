<?php
/**
 * Ticket Preset model.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Models;

use TEC\Common\StellarWP\Arrays\Arr;
use TEC\Common\StellarWP\Models\Contracts\ModelPersistable;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Models\Ticket_Group;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;
use TEC\Tickets_Plus\Ticket_Presets\Meta;

/**
 * Class Ticket_Preset.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 *
 * @property int    $id            The Ticket Preset ID.
 * @property string $slug          The Ticket Preset slug.
 * @property string $data          The Ticket Preset data in JSON format.
 * @property string $cost          The cost of the ticket.
 * @property int    $capacity      The capacity of the ticket.
 * @property string $name          The name of the ticket.
 */
class Ticket_Preset extends Ticket_Group implements ModelPersistable {
	/**
	 * The Ticket Preset name.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public string $name;

	/**
	 * The Ticket Preset cost.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public string $cost;

	/**
	 * The Ticket Preset capacity.
	 *
	 * @since 6.6.0
	 *
	 * @var int
	 */
	public int $capacity;

	/**
	 * @inheritDoc
	 *
	 * @var array<string,string|int|array>
	 */
	protected static array $properties = [
		'id'       => 'int',
		'slug'     => 'string',
		'data'     => 'string',
		'name'     => 'string',
		'cost'     => 'string',
		'capacity' => 'int',
	];

	/**
	 * Properties expected in the data array.
	 * Note the typing is *after* decoding the JSON string.
	 *
	 * @since 6.6.0
	 *
	 * @var array<string,string|int|array>
	 */
	protected array $data_properties = [
		'capacity'         => 'array',
		'cost'             => 'string|decimal|float|int',
		'description'      => 'string',
		'iac_setting'      => 'string',
		'iac'              => 'string|array',
		'name'             => 'string',
		'sale_end_logic'   => 'array',
		'sale_start_logic' => 'array',
		'ticket_name'      => 'string',
		'ticket_type'      => 'string',
	];

	/**
	 * Required properties.
	 *
	 * @since 6.6.0
	 *
	 * @var array<string>
	 */
	protected array $required_properties = [
		'capacity',
		'cost',
		'iac_setting',
		'name',
		'sale_end_logic',
		'sale_start_logic',
		'ticket_name',
		'ticket_type',
	];

	/**
	 * Properties expected in the capacity array.
	 *
	 * Example (100 tickets):
	 * {
	 *    'amount': 100,    // The number of tickets available for sale.
	 *    'type': 'own' // 'own' or 'unlimited' are the only allowed types for now.
	 * }
	 *
	 * @since 6.6.0
	 *
	 * @var array<string,string|int>
	 */
	protected array $capacity_properties = [
		'amount' => 'int',
		'type'   => 'string',
	];

	/**
	 * Properties expected in the sale_start_logic array.
	 * This defines the offset we use to determine when the sale starts.
	 *
	 * Example (two hours after the event starts):
	 * {
	 *     'type': 'relative', // The type of sale logic (published, start, or relative)
	 *     'relative_to': 'start', // The date the offset is relative to (only for relative type)
	 *     'direction': 'after',   // The direction of the offset (only for relative type)
	 *     'period': 'hour',       // The time period of the offset (only for relative type)
	 *     'length': 2             // The length of the offset (only for relative type)
	 * }
	 *
	 * @since 6.6.0
	 *
	 * @var array<string,string|int>
	 */
	protected array $sale_date_logic_properties = [
		'type' => 'string',
	];

	/**
	 * Properties required for relative type sale logic.
	 *
	 * @since 6.6.0
	 *
	 * @var array<string,string|int>
	 */
	protected array $relative_sale_logic_properties = [
		'relative_to' => 'string',
		'direction'   => 'string',
		'period'      => 'string',
		'length'      => 'int',
	];

	/**
	 * Allowed values for sale logic type.
	 *
	 * @since 6.6.0
	 *
	 * @var array<string>
	 */
	protected array $allowed_types = [
		'published',
		'start',
		'relative',
	];

	/**
	 * Allowed values for relative_to.
	 *
	 * @since 6.6.0
	 *
	 * @var array<string>
	 */
	protected array $allowed_relative_to = [
		'start',
		'end',
		'published',
		'now',
	];

	/**
	 * Allowed values for direction.
	 *
	 * @since 6.6.0
	 *
	 * @var array<string>
	 */
	protected array $allowed_directions = [
		'before',
		'after',
	];

	/**
	 * Allowed values for period.
	 *
	 * @since 6.6.0
	 *
	 * @var array<string>
	 */
	protected array $allowed_periods = [
		'minute',
		'hour',
		'day',
		'week',
		'month',
	];

	/**
	 * The allowed values for the capacity type property.
	 *
	 * @since 6.6.0
	 *
	 * @var string[]
	 */
	protected array $allowed_capacity_types = [
		'own',
		'unlimited',
	];

	/**
	 * Finds a model by its ID.
	 *
	 * @since 6.6.0
	 *
	 * @param int $id The model ID.
	 *
	 * @return Ticket_Preset|null The model instance, or null if not found.
	 */
	public static function find( $id ): ?self {
		return tribe( Ticket_Presets::class )->find_by_id( $id );
	}

	/**
	 * Creates a new model and saves it to the database.
	 *
	 * @since 6.6.0
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return Ticket_Preset The model instance.
	 */
	public static function create( array $attributes ): self {
		$model = new self( $attributes );
		$model->save();

		return $model;
	}

	/**
	 * Saves the model to the database.
	 *
	 * @since 6.6.0
	 *
	 * @return Ticket_Preset The model instance.
	 */
	public function save(): self {
		$data = json_decode( $this->data, true );

		// Set the properties from data.
		$capacity_type    = Arr::get( $data, [ 'capacity', 'type' ], 'own' );
		$capacity         = 'unlimited' === $capacity_type ? - 1 : Arr::get( $data, [ 'capacity', 'amount' ], 1 );
		$this->capacity ??= $capacity;
		$this->name     ??= $this->name ?? $data['name'] ?? '';
		$this->cost     ??= (string) $data['cost'] ?? '0';

		if ( $this->id ) {
			return tribe( Ticket_Presets::class )->update( $this );
		}

		$this->id = tribe( Ticket_Presets::class )->insert( $this )->id;

		return $this;
	}

	/**
	 * Deletes the model from the database.
	 *
	 * @since 6.6.0
	 *
	 * @return bool Whether the model was deleted.
	 */
	public function delete(): bool {
		return tribe( Ticket_Presets::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since 6.6.0
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Ticket_Presets::class )->query();
	}

	/**
	 * Validate the data array.
	 *
	 * @since 6.6.0
	 *
	 * @param string $data The data array to validate.
	 *
	 * @throws \Exception If data is invalid.
	 *
	 * @return bool
	 */
	public function validate_data( string $data ): bool {
		$data = json_decode( $data, true );

		if ( empty( $data ) ) {
			throw new \Exception( __( 'Data is required.', 'event-tickets-plus' ) );
		}

		foreach ( $this->data_properties as $key => $type ) {
			if ( ! isset( $data[ $key ] ) && in_array( $key, $this->required_properties, true ) ) {
				throw new \Exception(
					sprintf(
						/* Translators: %1$s is the key of the data property that is required. */
						__( '%1$s is required.', 'event-tickets-plus' ),
						$key
					)
				);
			}

			if ( 'array' === $type && ! is_array( $data[ $key ] ) ) {
				throw new \Exception(
					sprintf(
						/* Translators: %1$s is the key of the data property that must be an array. */
						__( '%1$s must be an array.', 'event-tickets-plus' ),
						$key
					)
				);
			}

			$validation = 'validate_' . $key;
			if ( 'capacity' === $key ) {
				$validation = 'validate_capacity_data';
			}

			if ( is_callable( [ $this, $validation ] ) ) {
				$this->$validation( $data[ $key ] );
			}
		}

		// Validate sale start logic data.
		if ( isset( $data['sale_start_logic'] ) && is_array( $data['sale_start_logic'] ) ) {
			$this->validate_sale_date_logic( $data['sale_start_logic'] );
		}

		// Validate sale end logic data.
		if ( isset( $data['sale_end_logic'] ) && is_array( $data['sale_end_logic'] ) ) {
			$this->validate_sale_date_logic( $data['sale_end_logic'] );
		}

		return true;
	}

	/**
	 * Validate the IAC setting.
	 *
	 * @since 6.6.0
	 *
	 * @param string $value The IAC setting to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	protected function validate_iac_setting( string $value ): bool {
		$allowed_values = tribe( Meta::class )->iac->get_iac_setting_options();

		if ( ! isset( $allowed_values[ $value ] ) ) {
			throw new \Exception( __( 'Invalid IAC setting.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the sale date logic array.
	 *
	 * @since 6.6.0
	 *
	 * @param array<string,mixed> $logic The sale date logic array to validate.
	 *
	 * @throws \Exception If sale date logic data is invalid.
	 *
	 * @return bool
	 */
	protected function validate_sale_date_logic( array $logic ): bool {
		// Validate type.
		if ( ! isset( $logic['type'] ) ) {
			throw new \Exception( __( 'Sale date logic type is required.', 'event-tickets-plus' ) );
		}

		if ( ! in_array( $logic['type'], $this->allowed_types, true ) ) {
			throw new \Exception(
				sprintf(
					/* Translators: %1$s is a comma-separated list of allowed type values. */
					__( 'Invalid type value. Allowed values are: %1$s', 'event-tickets-plus' ),
					implode( ', ', $this->allowed_types )
				)
			);
		}

		// If type is not relative, we're done.
		if ( 'relative' !== $logic['type'] ) {
			return true;
		}

		// Validate relative date fields.
		foreach ( $this->relative_sale_logic_properties as $key => $type ) {
			if ( ! isset( $logic[ $key ] ) ) {
				throw new \Exception(
					sprintf(
						/* Translators: %1$s is the key of the sale date logic property that is required. */
						__( 'Sale date logic %1$s is required for relative type.', 'event-tickets-plus' ),
						$key
					)
				);
			}
		}

		if ( ! in_array( $logic['relative_to'], $this->allowed_relative_to, true ) ) {
			throw new \Exception(
				sprintf(
					/* Translators: %1$s is a comma-separated list of allowed relative_to values. */
					__( 'Invalid relative_to value. Allowed values are: %1$s', 'event-tickets-plus' ),
					implode( ', ', $this->allowed_relative_to )
				)
			);
		}

		if ( ! in_array( $logic['direction'], $this->allowed_directions, true ) ) {
			throw new \Exception(
				sprintf(
					/* Translators: %1$s is a comma-separated list of allowed direction values. */
					__( 'Invalid direction value. Allowed values are: %1$s', 'event-tickets-plus' ),
					implode( ', ', $this->allowed_directions )
				)
			);
		}

		if ( ! in_array( $logic['period'], $this->allowed_periods, true ) ) {
			throw new \Exception(
				sprintf(
					/* Translators: %1$s is a comma-separated list of allowed period values. */
					__( 'Invalid period value. Allowed values are: %1$s', 'event-tickets-plus' ),
					implode( ', ', $this->allowed_periods )
				)
			);
		}

		if ( ! is_numeric( $logic['length'] ) ) {
			throw new \Exception( __( 'Length must be a number.', 'event-tickets-plus' ) );
		}

		if ( $logic['length'] <= 0 ) {
			throw new \Exception( __( 'Length must be greater than 0.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the name.
	 *
	 * @since 6.6.0
	 *
	 * @param string $value The name to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_name( string $value ): bool {
		if ( empty( $value ) ) {
			throw new \Exception( __( 'Name is required.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the slug.
	 *
	 * @since 6.6.0
	 *
	 * @param string $value The slug to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_slug( string $value ): bool {
		if ( empty( $value ) ) {
			throw new \Exception( __( 'Slug is required.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the ticket name.
	 *
	 * @since 6.6.0
	 *
	 * @param string $value The ticket name to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_ticket_name( string $value ): bool {
		if ( empty( $value ) ) {
			throw new \Exception( __( 'Ticket name is required.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the ticket type.
	 *
	 * @since 6.6.0
	 *
	 * @param string $value The ticket type to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_ticket_type( string $value ): bool {
		if ( empty( $value ) ) {
			throw new \Exception( __( 'Ticket type is required.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Get the number of decimal places for presets currency.
	 *
	 * @since 6.6.0
	 *
	 * @return int The number of decimal places.
	 */
	protected function get_decimal_places(): int {
		$places = tribe_get_option( 'tickets-commerce-currency-number-of-decimals', 2 );

		/**
		 * Filters the number of decimal places for presets currency.
		 *
		 * @since 6.6.0
		 *
		 * @param int $places The number of decimal places.
		 */
		return (int) apply_filters( 'tribe_tickets_plus_presets_decimal_places', $places );
	}

	/**
	 * Get the decimal separator for presets currency.
	 *
	 * @since 6.6.0
	 *
	 * @return string The decimal separator.
	 */
	protected function get_decimal_separator(): string {
		$separator = tribe_get_option( 'tickets-commerce-currency-decimal-separator', '.' );

		/**
		 * Filters the decimal separator for presets currency.
		 *
		 * @since 6.6.0
		 *
		 * @param string $separator The decimal separator.
		 */
		return (string) apply_filters( 'tribe_tickets_plus_presets_decimal_separator', $separator );
	}

	/**
	 * Get the thousands separator for presets currency.
	 *
	 * @since 6.6.0
	 *
	 * @return string The thousands separator.
	 */
	protected function get_thousands_separator(): string {
		$separator = tribe_get_option( 'tickets-commerce-currency-thousands-separator', ',' );

		/**
		 * Filters the thousands separator for presets currency.
		 *
		 * @since 6.6.0
		 *
		 * @param string $separator The thousands separator.
		 */
		return (string) apply_filters( 'tribe_tickets_plus_presets_thousands_separator', $separator );
	}

	/**
	 * Validate the cost.
	 *
	 * @since 6.6.0
	 *
	 * @param string|float $value The cost to be validated. Must be a number.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_cost( $value ): bool {
		$decimal_places = $this->get_decimal_places();

		// Convert to float and check decimal places.
		$float_value = (float) $value;
		$parts       = explode( '.', (string) $float_value );

		if ( ! is_numeric( $float_value ) ) {
			throw new \Exception( __( 'Cost must be a number.', 'event-tickets-plus' ) );
		}

		if ( isset( $parts[1] ) && strlen( $parts[1] ) > $decimal_places ) {
			throw new \Exception(
				sprintf(
					/* Translators: %d is the number of decimal places allowed. */
					__( 'Cost must have no more than %d decimal places.', 'event-tickets-plus' ),
					$decimal_places
				)
			);
		}

		return true;
	}

	/**
	 * Validate the capacity value.
	 *
	 * @since 6.6.0
	 *
	 * @param int $value The capacity value to validate.
	 *
	 * @throws \Exception If capacity value is invalid.
	 *
	 * @return bool
	 */
	public function validate_capacity( int $value ): bool {
		if ( -1 > $value || 0 === $value ) {
			throw new \Exception( __( 'Capacity must be -1 for unlimited or greater than 0.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the capacity data.
	 *
	 * @since 6.6.0
	 *
	 * @param array<string,mixed> $capacity The capacity array to validate.
	 *
	 * @throws \Exception If capacity data is invalid.
	 *
	 * @return bool
	 */
	protected function validate_capacity_data( array $capacity ): bool {
		foreach ( $this->capacity_properties as $key => $type ) {
			if ( ! isset( $capacity[ $key ] ) ) {
				throw new \Exception(
					sprintf(
						/* Translators: %1$s is the key of the capacity property that is required. */
						__( 'Capacity %1$s is required.', 'event-tickets-plus' ),
						$key
					)
				);
			}
		}

		if ( ! in_array( $capacity['type'], $this->allowed_capacity_types, true ) ) {
			throw new \Exception(
				sprintf(
					/* Translators: %1$s is a comma-separated list of allowed capacity types. */
					__( 'Invalid capacity type. Allowed types are: %1$s', 'event-tickets-plus' ),
					implode( ', ', $this->allowed_capacity_types )
				)
			);
		}

		if ( 'own' === $capacity['type'] && 0 >= $capacity['amount'] ) {
			throw new \Exception( __( 'Capacity amount must be greater than 0.', 'event-tickets-plus' ) );
		}

		if ( 'unlimited' === $capacity['type'] && -1 !== $capacity['amount'] ) {
			throw new \Exception( __( 'Capacity amount must be -1 for unlimited.', 'event-tickets-plus' ) );
		}

		$this->validate_capacity( $capacity['amount'] );

		return true;
	}

	/**
	 * Returns the object as an array.
	 *
	 * @since 6.6.0
	 *
	 * @return array<string,mixed> The object as an array.
	 */
	public function to_array(): array {
		$data = json_decode( $this->data, true );

		return [
			'id'   => $this->id,
			'slug' => $this->slug,
			'data' => $data,
		];
	}

	/**
	 * Get the IAC fields.
	 *
	 * @since 6.6.0
	 *
	 * @return string The IAC fields as an HTML string.
	 */
	public function get_iac(): array {
		$data = json_decode( $this->data, true );
		return (array) maybe_unserialize( $data['iac'] ?? [] );
	}

	/**
	 * Get the IAC setting.
	 *
	 * @since 6.6.0
	 *
	 * @return string The IAC setting.
	 */
	public function get_iac_setting(): string {
		$data = json_decode( $this->data, true );
		return $data['iac_setting'] ?? '';
	}
}
