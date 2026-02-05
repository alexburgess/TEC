<?php
/**
 * Handles the assets for the Ticket Presets feature.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */

namespace TEC\Tickets_Plus\Ticket_Presets;

use TEC\Common\Contracts\Provider\Controller;
use TEC\Common\Asset;
use TEC\Tickets\Admin\Tickets\Page;
use TEC\Tickets_Plus\Ticket_Presets\Admin\Tab;
use TEC\Tickets_Plus\Ticket_Presets\Admin\Form_Page;
use TEC\Common\StellarWP\Assets\Config;
use TEC\Tickets_Plus\Ticket_Presets\Repositories\Ticket_Presets;
use Tribe__Tickets_Plus__Main as ET_Plus;
use WP_Post;
/**
 * Class Assets.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets
 */
class Assets extends Controller {
	/**
	 * Register the hooks.
	 *
	 * @since 6.6.0
	 */
	public function do_register(): void {
		Config::add_group_path( 'tec-tickets-plus-presets', ET_Plus::instance()->plugin_path . 'build/', 'Presets/' );
		$this->register_admin_assets();

		// Load block editor assets.
		add_action( 'admin_init', [ $this, 'register_block_editor_assets' ] );
	}

	/**
	 * Unregister the hooks.
	 *
	 * @since 6.6.0
	 */
	public function unregister(): void {
		remove_action( 'admin_init', [ $this, 'register_block_editor_assets' ] );
	}

	/**
	 * Register block editor assets.
	 *
	 * @since 6.6.0
	 */
	public function register_block_editor_assets(): void {
		Asset::add(
			'tec-tickets-plus-presets-block-editor',
			'blockEditor.js',
			ET_Plus::VERSION
		)
		->add_to_group_path( 'tec-tickets-plus-presets' )
		->enqueue_on( 'enqueue_block_editor_assets' )
		->add_to_group( 'tec-tickets-presets' )
		->add_localize_script( 'tec.tickets_plus.presets', [ $this, 'get_preset_localize_data' ] )
		->register();

		Asset::add(
			'tec-tickets-plus-presets-block-editor-style',
			'style-blockEditor.css',
			ET_Plus::VERSION
		)
		->add_to_group_path( 'tec-tickets-plus-presets' )
		->enqueue_on( 'enqueue_block_editor_assets' )
		->add_to_group( 'tec-tickets-presets' )
		->register();
	}

	/**
	 * Register admin assets.
	 *
	 * @since 6.6.0
	 */
	protected function register_admin_assets(): void {
		$plugin_path = plugin_dir_path( EVENT_TICKETS_PLUS_FILE );

		// Add table styles.
		Asset::add(
			'tec-tickets-plus-presets-list-table-css',
			'presets/tec-tickets-plus-presets-list-table.css',
			ET_Plus::VERSION
		)
		->add_to_group_path( ET_Plus::class )
		->add_to_group( 'tec-tickets-plus-presets-list' )
		->add_to_group( 'tec-tickets-plus-presets' )
		->enqueue_on( 'admin_enqueue_scripts' )
		->set_condition( [ $this, 'tec_tickets_plus_preset_admin_page' ] )
		->register();

		// Add table script.
		Asset::add(
			'tec-tickets-plus-presets-list-table-js',
			'presets/tec-tickets-plus-presets-list-table.js',
			ET_Plus::VERSION
		)
		->add_to_group_path( ET_Plus::class )
		->add_to_group( 'tec-tickets-plus-presets-list' )
		->add_to_group( 'tec-tickets-plus-presets' )
		->enqueue_on( 'admin_enqueue_scripts' )
		->set_condition( [ $this, 'tec_tickets_plus_preset_admin_page' ] )
		->register();

		// Add form script.
		Asset::add(
			'tec-tickets-plus-presets-form-js',
			'presets/tec-tickets-plus-presets-form.js',
			ET_Plus::VERSION
		)
		->set_dependencies( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'jquery-ui-accordion', 'event-tickets-admin-accordion-js', 'event-tickets-plus-meta-js' )
		->add_to_group_path( ET_Plus::class )
		->add_to_group( 'tec-tickets-plus-presets-form' )
		->add_to_group( 'tec-tickets-plus-presets' )
		->enqueue_on( 'admin_enqueue_scripts' )
		->set_condition( [ $this, 'tec_tickets_plus_preset_form_page' ] )
		->add_localize_script(
			'tecTicketsPlusPresets',
			[
				'nonce' => wp_create_nonce( 'tribe_ticket_attendee_info_nonce' ),
			]
		)
		->register();

		// Add form styles.
		Asset::add(
			'tec-tickets-plus-presets-form-css',
			'presets/tec-tickets-plus-presets-form.css',
			ET_Plus::VERSION
		)
		->set_dependencies( 'event-tickets-admin-css', 'event-tickets-plus-meta-admin-css' )
		->add_to_group_path( ET_Plus::class )
		->add_to_group( 'tec-tickets-plus-presets-form' )
		->add_to_group( 'tec-tickets-plus-presets' )
		->enqueue_on( 'admin_enqueue_scripts' )
		->set_condition( [ $this, 'tec_tickets_plus_preset_form_page' ] );

		// Add editor styles.
		Asset::add(
			'tec-tickets-plus-presets-editor-css',
			'presets/tec-tickets-plus-presets-editor.css',
			ET_Plus::VERSION
		)
		->add_to_group_path( ET_Plus::class )
		->add_to_group( 'tec-tickets-plus-presets-editor' )
		->enqueue_on( 'admin_enqueue_scripts' )
		->set_condition( [ $this, 'is_editing_post' ] );

		// Add editor script.
		Asset::add(
			'tec-tickets-plus-presets-editor-js',
			'presets/tec-tickets-plus-presets-editor.js',
			ET_Plus::VERSION
		)
		->set_dependencies( 'jquery', 'jquery-ui-core', 'jquery-ui-accordion', 'event-tickets-plus-meta-js' )
		->add_to_group_path( ET_Plus::class )
		->add_to_group( 'tec-tickets-plus-presets-editor' )
		->enqueue_on( 'admin_enqueue_scripts' )
		->set_condition( [ $this, 'is_editing_post' ] )
		->add_localize_script(
			'tecTicketsPlusPresetEditor',
			[
				'nonce'          => wp_create_nonce( 'tec-tickets-plus_apply-preset' ),
				'duplicateNonce' => wp_create_nonce( 'tec-tickets-plus_duplicate-preset' ),
				'createNonce'    => wp_create_nonce( 'tec-tickets-plus_create-preset' ),
				'unlimited'      => __( 'Unlimited', 'event-tickets-plus' ),
				'available'      => __( 'available', 'event-tickets-plus' ),
			]
		)
		->register();
	}

	/**
	 * Condition to check if we're on the presets list page.
	 *
	 * @since 6.6.0
	 *
	 * @return bool
	 */
	public function tec_tickets_plus_preset_admin_page(): bool {
		$page       = tribe( Page::class );
		$is_on_tab  = Tab::is_on_tab();
		$is_on_page = $page->is_on_page();

		return $is_on_tab && $is_on_page;
	}

	/**
	 * Condition to check if we're on the presets form page.
	 *
	 * @since 6.6.0
	 *
	 * @return bool
	 */
	public function tec_tickets_plus_preset_form_page(): bool {
		return tribe( Form_Page::class )->is_on_page();
	}

	/**
	 * Get the localize data for the presets block editor.
	 *
	 * @since 6.6.0
	 *
	 * @return array<string,mixed> The localize data.
	 */
	public function get_preset_localize_data(): array {
		$presets = tribe( Ticket_Presets::class )->get_all( ARRAY_A );

		if ( empty( $presets ) ) {
			return [ 'items' => [] ];
		}

		$items = [];
		foreach ( $presets as $preset ) {
			$data = json_decode( $preset['data'], true );
			if ( ! is_array( $data ) ) {
				continue;
			}

			$items[] = array_merge(
				[
					'id'          => $preset['id'],
					'slug'        => $preset['slug'],
					'meta_fields' => (array) maybe_unserialize( $data['iac'] ?? [] ),
				],
				$data
			);
		}

		return [
			'items' => $items,
		];
	}

	/**
	 * Condition to check if we're on the post editor page.
	 *
	 * @since 6.6.0
	 *
	 * @return bool
	 */
	public function is_editing_post() {
		// Ensure we have a post.
		$post = get_post();

		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		// Ensure we are on a page for a ticketable post type.
		$ticketable_post_types = (array) tribe_get_option( 'ticket-enabled-post-types', [] );

		if ( empty( $ticketable_post_types ) ) {
			return false;
		}

		return in_array( $post->post_type, $ticketable_post_types, true );
	}
}
