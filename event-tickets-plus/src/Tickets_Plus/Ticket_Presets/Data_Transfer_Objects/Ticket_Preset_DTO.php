<?php
/**
 * Ticket Preset Data Transfer Object.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Data_Transfer_Objects
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Data_Transfer_Objects;

use TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects\Ticket_Group_DTO;
use TEC\Tickets_Plus\Ticket_Presets\Models\Ticket_Preset;

/**
 * Class Ticket_Preset_DTO.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Data_Transfer_Objects
 */
class Ticket_Preset_DTO extends Ticket_Group_DTO {
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
	 * @var string
	 */
	public int $capacity;

	/**
	 * Creates a new model from the DTO.
	 *
	 * @since 6.6.0
	 *
	 * @return Ticket_Preset The model instance.
	 */
	public function toModel(): Ticket_Preset {
		return new Ticket_Preset(
			[
				'id'       => $this->id,
				'slug'     => $this->slug,
				'data'     => $this->data,
				'name'     => $this->name ?? $this->data['name'] ?? '',
				'cost'     => $this->cost ?? $this->data['cost'] ?? '',
				'capacity' => $this->capacity ?? $this->data['capacity']['amount'] ?? 0,
			]
		);
	}

	//phpcs:disable Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound
	/**
	 * Creates a new DTO from an object.
	 *
	 * @since 6.6.0
	 *
	 * @param object $object The object to create the DTO from.
	 * @return self The DTO instance.
	 */
	public static function fromObject( $object ): self {
		$self           = new self();
		$self->id       = $object->id;
		$self->slug     = $object->slug;
		$self->data     = $object->data;
		$self->name     = $object->name ?? $object->data['name'] ?? '';
		$self->cost     = $object->cost ?? $object->data['cost'] ?? '';
		$self->capacity = $object->capacity ?? $object->data['capacity']['amount'] ?? 0;

		return $self;
	}
	//phpcs:enable Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound
}
