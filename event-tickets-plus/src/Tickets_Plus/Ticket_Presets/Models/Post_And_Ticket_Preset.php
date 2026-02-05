<?php
/**
 * Post and Ticket Preset relationship model.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Models;

use TEC\Common\StellarWP\Models\Contracts\ModelPersistable;
use TEC\Common\StellarWP\Models\ModelQueryBuilder;
use TEC\Tickets\Flexible_Tickets\Models\Post_And_Ticket_Group;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Posts_And_Ticket_Presets;
use TEC\Tickets_Plus\Ticket_Presets\Enums\Ticket_Preset_To_Post_Relationship_Keys as Keys;

/**
 * Class Post_And_Ticket_Preset.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 *
 * @property int    $id       The Post and Ticket Preset relationship ID.
 * @property int    $post_id  The Post ID.
 * @property int    $group_id The Ticket Preset ID.
 * @property string $type     The relationship type.
 */
class Post_And_Ticket_Preset extends Post_And_Ticket_Group implements ModelPersistable {
	/**
	 * Finds a model by its ID.
	 *
	 * @since 6.6.0
	 *
	 * @param int $id The model ID.
	 *
	 * @return Post_And_Ticket_Preset|null The model instance, or null if not found.
	 */
	public static function find( $id ): ?self {
		return tribe( Posts_And_Ticket_Presets::class )->find_by_id( $id );
	}

	/**
	 * Creates a new model and saves it to the database.
	 *
	 * @since 6.6.0
	 *
	 * @param array<string,mixed> $attributes The model attributes.
	 *
	 * @return Post_And_Ticket_Preset The model instance.
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
	 * @return Post_And_Ticket_Preset The model instance.
	 */
	public function save(): self {
		if ( $this->id ) {
			return tribe( Posts_And_Ticket_Presets::class )->update( $this );
		}

		$this->id = tribe( Posts_And_Ticket_Presets::class )->insert( $this )->id;

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
		return tribe( Posts_And_Ticket_Presets::class )->delete( $this );
	}

	/**
	 * Returns the query builder for the model.
	 *
	 * @since 6.6.0
	 *
	 * @return ModelQueryBuilder The query builder instance.
	 */
	public static function query(): ModelQueryBuilder {
		return tribe( Posts_And_Ticket_Presets::class )->query();
	}

	/**
	 * Validate the post_id.
	 *
	 * @param string $value The value to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_post_id( $value ): bool {
		if ( empty( $value ) ) {
			throw new \Exception( __( 'post_id is required.', 'event-tickets-plus' ) );
		}

		$post = get_post( $value );

		if ( ! $post ) {
			throw new \Exception( __( 'The post_id must be a valid post (event) ID.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the group_id.
	 *
	 * @param string $value The value to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_group_id( $value ): bool {
		if ( empty( $value ) ) {
			throw new \Exception( __( 'group_id is required.', 'event-tickets-plus' ) );
		}

		return true;
	}

	/**
	 * Validate the relationship type.
	 *
	 * @param string $value The value to be validated.
	 *
	 * @throws \Exception If value is empty.
	 *
	 * @return bool
	 */
	public function validate_type( $value ): bool {
		if ( empty( $value ) ) {
			throw new \Exception( __( 'The type is required.', 'event-tickets-plus' ) );
		}

		$allowed_keys = Keys::all();

		if ( ! in_array( $value, $allowed_keys ) ) {
			throw new \Exception(
				sprintf(
					/* Translators: %1$s: Comma separated list of allowed keys. */
					__( 'The type must be one of: %1$s.', 'event-tickets-plus' ),
					implode( ', ', $allowed_keys )
				)
			);
		}

		return true;
	}
}
