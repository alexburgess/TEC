<?php
/**
 * Handles the Ticket Presets form page.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Admin;

use TEC\Common\Admin\Abstract_Admin_Page;
use TEC\Tickets_Plus\Ticket_Presets\Meta;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;

/**
 * Class Form_Page
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */
class Form_Page extends Abstract_Admin_Page {
	/**
	 * The page slug.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public static string $page_slug = 'tec-tickets-preset-form';

	/**
	 * Whether the page has a header.
	 *
	 * @since 6.6.0
	 *
	 * @var bool
	 */
	public static bool $has_header = true;

	/**
	 * Whether the page has a logo.
	 *
	 * @since 6.6.0
	 *
	 * @var bool
	 */
	public static bool $has_logo = false;

	/**
	 * Whether the page has a footer.
	 *
	 * @since 6.6.0
	 *
	 * @var boolean
	 */
	public static bool $has_footer = false;

	/**
	 * Whether the page has a sidebar.
	 *
	 * @since 6.6.0
	 *
	 * @var bool
	 */
	public static bool $has_sidebar = false;
	
	/**
	 * The position of the submenu in the menu.
	 *
	 * @since 6.6.0
	 *
	 * @var int|float
	 */
	public float $menu_position = 2;

	/**
	 * Get the page title.
	 *
	 * @since 6.6.0
	 */
	public function get_the_page_title(): string {
		$action = tec_get_request_var( 'action' ) ?? 'create';

		if ( 'edit' === $action ) {
			return __( 'Edit Ticket Preset', 'event-tickets-plus' );
		}

		return __( 'Add New Ticket Preset', 'event-tickets-plus' );
	}

	/**
	 * Get the menu title.
	 *
	 * @since 6.6.0
	 */
	public function get_the_menu_title(): string {
		return __( 'Presets', 'event-tickets-plus' );
	}

	/**
	 * Get the parent page slug.
	 *
	 * @since 6.6.0
	 */
	public function get_parent_page_slug(): string {
		return self::is_on_page() ? 'tec-tickets' : 'tec-tickets-admin-tickets';
	}

	/**
	 * Whether to show this page in the admin menu.
	 *
	 * @since 6.6.0
	 */
	protected function show_in_menu(): bool {
		return false;
	}

	/**
	 * Render the main content of the page.
	 *
	 * @since 6.6.0
	 */
	public function admin_page_main_content(): void {
		$template  = tribe( Admin_Template::class );
		$preset_id = tec_get_request_var( 'id', 0 );
		
		$template->template(
			'preset-form',
			$this->get_preset_form_data( $preset_id )
		);
	}
	
	/**
	 * Get the data for the preset form.
	 *
	 * @since 6.6.0
	 *
	 * @param int $preset_id The preset ID.
	 *
	 * @return array<string,mixed> The data for the preset form.
	 */
	public function get_preset_form_data( int $preset_id ): array {
		$meta        = tribe( Meta::class );
		$preset      = tribe( Ticket_Presets::class )->find_by_id( $preset_id );
		$form_action = tec_get_request_var( 'action', 'create' );
		
		// Default template data.
		$data = [
			'active_meta'   => [],
			'enable_meta'   => false,
			'fieldset_form' => false,
			'meta_object'   => $meta,
			'meta'          => [],
			'page_title'    => $this->get_the_page_title(),
			'preset'        => null,
			'preset_id'     => $preset_id,
			'preset_data'   => [],
			'templates'     => [],
			'form_action'   => $form_action,
		];
		
		$has_submission = tec_get_request_var( 'key' );
		
		if ( $has_submission ) {
			$submission_data     = get_transient( $has_submission );
			$data['preset_data'] = $submission_data ?? [];
		}
		
		// If no preset is found, return default data.
		if ( null === $preset ) {
			return $data;
		}
		
		$preset_array = $preset->to_array();
		
		// Update data with preset-specific values.
		$data['active_meta'] = $meta->get_iac_by_preset( $preset_id );
		$data['enable_meta'] = $meta->preset_has_meta( $preset_id );
		$data['preset']      = $preset;
		$data['preset_data'] = $preset_array['data'] ?? [];
		$data['templates']   = $meta->meta_fieldset()->get_fieldsets();
		
		return $data;
	}

	/**
	 * Get the URL to this page.
	 *
	 * @since 6.6.0
	 *
	 * @param array $args Additional query args to add to the URL.
	 * @return string The URL to this page.
	 */
	public function get_url( array $args = [] ): string {
		$defaults = [
			'page' => static::$page_slug,
		];

		$args = wp_parse_args( $args, $defaults );
		return add_query_arg( $args, admin_url( 'admin.php' ) );
	}

	/**
	 * Render the sidebar content.
	 *
	 * @since 6.6.0
	 */
	public function admin_page_sidebar_content(): void {
		// No sidebar content needed.
	}

	/**
	 * Render the footer content.
	 *
	 * @since 6.6.0
	 */
	public function admin_page_footer_content(): void {
		// No footer content needed.
	}
}
