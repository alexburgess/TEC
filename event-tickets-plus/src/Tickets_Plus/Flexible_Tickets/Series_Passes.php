<?php
/**
 * Handles the integration of Series Passes in the context of Event Tickets Plus.
 *
 * @since 5.9.0
 *
 * @package TEC\Tickets_Plus\Flexible_Tickets;
 */

namespace TEC\Tickets_Plus\Flexible_Tickets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Series_Passes as Base_Series_Passes;
use TEC\Tickets\Flexible_Tickets\Series_Passes\Base as Series_Passes_Base_Controller;
use Tribe\Tickets\Plus\Editor\Settings\Data\Capacity_Table;
use Tribe__Events__Main as TEC;
use Tribe__Template as Template;
use Tribe__Tickets__Tickets as Tickets;

/**
 * Class Series_Passes.
 *
 * @since 5.9.0
 *
 * @package TEC\Tickets_Plus\Flexible_Tickets;
 */
class Series_Passes extends Controller {

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter(
			'tribe_template_pre_html:tickets-plus/admin-views/manual-attendees/add',
			[
				$this,
				'redirect_manual_attendee_add_to_series',
			],
			10,
			5
		);
		add_filter( 'tec_tickets_plus_editor_capacity_table_label_for_type', [ $this, 'filter_capacity_table_label_for_type' ], 10, 3 );
		add_filter( 'tec_tickets_plus_editor_capacity_table_capacity_by_type', [ $this, 'filter_series_passes_as_default_type_on_series_edit' ], 10, 2 );
		add_filter( 'tec_tickets_get_event_capacity', [ $this, 'filter_event_capacity_including_other_type_capacity' ], 10, 3 );
		add_filter( 'tec_tickets_plus_my_tickets_order_list_ticket_type_titles', [ $this, 'include_series_type_label_for_my_tickets' ] );

		remove_filter(
			'tribe_template_pre_html:tickets/admin-views/editor/panel/settings-button',
			[
				tribe( Series_Passes_Base_Controller::class ),
				'remove_settings_button_from_classic_metabox',
			],
			10,
			5
		);
	}

	/**
	 * {@inheritDoc}
	 *
	 * @since 5.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter(
			'tribe_template_pre_html:tickets-plus/admin-views/manual-attendees/add',
			[
				$this,
				'redirect_manual_attendee_add_to_series',
			]
		);
		remove_filter( 'tec_tickets_plus_editor_capacity_table_label_for_type', [ $this, 'filter_capacity_table_label_for_type' ], 10, 3 );
		remove_filter( 'tec_tickets_plus_editor_capacity_table_capacity_by_type', [ $this, 'filter_series_passes_as_default_type_on_series_edit' ], 10, 2 );
		remove_filter( 'tec_tickets_get_event_capacity', [ $this, 'filter_event_capacity_including_other_type_capacity' ], 10, 3 );
		remove_filter( 'tec_tickets_plus_my_tickets_order_list_ticket_type_titles', [ $this, 'include_series_type_label_for_my_tickets' ] );

		add_filter(
			'tribe_template_pre_html:tickets/admin-views/editor/panel/settings-button',
			[
				tribe( Series_Passes_Base_Controller::class ),
				'remove_settings_button_from_classic_metabox',
			],
			10,
			5
		);
	}

	/**
	 * Filters the template used to render the manual attendee add modal to redirect the user to the Series
	 * when trying to add Attendees to an Event that has no own tickets.
	 *
	 * @since 5.9.0
	 *
	 * @param string              $html     The HTML to filter.
	 * @param string              $file     The template file being filtered.
	 * @param string|string[]     $name     The name of the template to filter.
	 * @param Template            $template A reference to the template object.
	 * @param array<string,mixed> $context  The template parameters.
	 *
	 * @return string|null The filtered HTML, if required.
	 */
	public function redirect_manual_attendee_add_to_series( $html, $file, $name, $template, array $context ): ?string {
		if ( ! empty( $context['tickets'] ) ) {
			// The post has tickets, leave the message alone.
			return $html;
		}

		if ( ! (
			isset( $context['post_id'] )
			&& get_post_type( $context['post_id'] ) === TEC::POSTTYPE
			&& $series_id = tec_series()->where( 'event_post_id', $context['post_id'] )->first_id()
		) ) {
			// Not an Event or not related to a Series.
			return $html;
		}

		$edit_link        = get_edit_post_link( $series_id, 'admin' ) . '#tribetickets';
		$series_edit_link = sprintf(
			'<a href="%s" target="_blank" class="tribe-common-anchor--unstyle">%s</a>',
			$edit_link,
			get_post_field( 'post_title', $series_id )
		);

		$message =
			sprintf(
			// Translators: %1$s is "Series Pass", %2$s is the Series edit link.
				esc_html_x(
					'Add %1$s attendees from the %2$s Attendees page.',
					'The message to display when the user tries to add attendees to an Event that has no own tickets.',
					'event-tickets'
				),
				tec_tickets_get_series_pass_singular_uppercase(),
				$series_edit_link
			);

		return "<div class='tribe-dialog__content__wrapper tribe-dialog__content__wrapper--centered'><p>" .
				$message .
				'</p></div>';
	}

	/**
	 * Filters the label for the Series post type in the Capacity Table.
	 *
	 * @since 5.9.0
	 *
	 * @param string         $type                   The type of ticket.
	 * @param int            $post_id                   The ID of the post being displayed.
	 * @param Capacity_Table $capacity_table The instance of the capacity table.
	 *
	 * @return string The updated label.
	 */
	public function filter_capacity_table_label_for_type( string $type, int $post_id, Capacity_Table $capacity_table ): string {
		if ( Base_Series_Passes::TICKET_TYPE !== $type ) {
			return $type;
		}

		return tec_tickets_get_series_pass_plural_uppercase( 'ticket settings capacity table' );
	}

	/**
	 * Filters the capacity by type to set the Series Passes as the default type on the Series edit page.
	 *
	 * @since 5.9.0
	 *
	 * @param array<string, array> $capacity_by_types The capacity by type to be displayed.
	 * @param int                  $post_id The ID of the event.
	 *
	 * @return array<string, array> The updated capacity by type.
	 */
	public function filter_series_passes_as_default_type_on_series_edit( array $capacity_by_types, int $post_id ): array {
		if ( get_post_type( $post_id ) !== Series_Post_Type::POSTTYPE ) {
			return $capacity_by_types;
		}

		if ( ! isset( $capacity_by_types[ Base_Series_Passes::TICKET_TYPE ] ) ) {
			return $capacity_by_types;
		}

		// Set the Series Passes as the default type.
		$capacity_by_types['default'] = $capacity_by_types[ Base_Series_Passes::TICKET_TYPE ];

		// Remove the Series Passes from the other types.
		unset( $capacity_by_types[ Base_Series_Passes::TICKET_TYPE ] );

		return $capacity_by_types;
	}

	/**
	 * Filters the event capacity to include the capacity of other ticket types.
	 *
	 * @since 5.9.0
	 *
	 * @param int          $capacity          The event capacity.
	 * @param int          $event_id          The event ID.
	 * @param bool|Tickets $provider The provider.
	 *
	 * @return int The updated event capacity.
	 */
	public function filter_event_capacity_including_other_type_capacity( $capacity, $event_id, $provider ) {
		// Check if event is part of a series.
		$series_id = tec_series()->where( 'event_post_id', $event_id )->first_id();

		if ( ! $series_id ) {
			return $capacity;
		}

		$capacity_table = new Capacity_Table( $event_id );

		return $capacity_table->get_total_capacity();
	}

	/**
	 * Filters the ticket type labels for the My Tickets order list view to include the Series Passes.
	 *
	 * @since 5.9.0
	 *
	 * @param array<string,string> $labels List of ticket type labels.
	 *
	 * @return array<string,string> The updated list of ticket type labels.
	 */
	public function include_series_type_label_for_my_tickets( array $labels ): array {
		$labels[ Base_Series_Passes::TICKET_TYPE ] = tec_tickets_get_series_pass_plural_uppercase( 'order list view' );
		return $labels;
	}
}
