<?php
/**
 * Post and Ticket Preset relationship Data Transfer Object.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Data_Transfer_Objects
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Data_Transfer_Objects;

use TEC\Tickets\Flexible_Tickets\Data_Transfer_Objects\Post_And_Ticket_Group_DTO;
use TEC\Tickets_Plus\Ticket_Presets\Models\Post_And_Ticket_Preset;

/**
 * Class Post_And_Ticket_Preset_DTO.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Data_Transfer_Objects
 */
class Post_And_Ticket_Preset_DTO extends Post_And_Ticket_Group_DTO {
	/**
	 * Creates a new model from the DTO.
	 *
	 * @since 6.6.0
	 *
	 * @return Post_And_Ticket_Preset The model instance.
	 */
	public function toModel(): Post_And_Ticket_Preset {
		return new Post_And_Ticket_Preset(
			[
				'id'       => $this->id,
				'post_id'  => $this->post_id,
				'group_id' => $this->group_id,
				'type'     => $this->type,
			]
		);
	}
}
