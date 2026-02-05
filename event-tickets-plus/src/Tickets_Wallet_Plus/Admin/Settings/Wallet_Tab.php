<?php
namespace TEC\Tickets_Wallet_Plus\Admin\Settings;

use Tribe\Tickets\Admin\Settings as Plugin_Settings;
use TEC\Tickets_Wallet_Plus\Plugin;
use TEC\Tickets_Wallet_Plus\Template;
use Tribe__Settings_Tab;
use Tribe__Template;

/**
 * Class Wallet_Tab
 *
 * @package TEC\Tickets_Wallet_Plus\Admin\Settings
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 */
class Wallet_Tab {

	/**
	 * Slug for the tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public static $slug = 'wallet';

	/**
	 * Key to use in GET variable for currently selected section.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public static $key_current_section_get_var = 'tab';

	/**
	 * Stores the instance of the template engine that we will use for rendering different elements.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var Tribe__Template
	 */
	protected $template;

	/**
	 * Stores the instance of the settings tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var Tribe__Settings_Tab
	 */
	protected $settings_tab;

	/**
	 * Register the Tab.
	 *
	 * @param string $admin_page Admin page id.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 * @sinece 6.5.1 Added child tabs.
	 */
	public function register_tab( $admin_page ) {
		if ( ! empty( $admin_page ) && Plugin_Settings::$settings_page_id !== $admin_page ) {
			return;
		}

		$tab_settings = [
			'priority'  => 30,
			'fields'    => $this->get_fields(),
			'show_save' => true,
		];

		/**
		 * Filter the tab settings options.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array Key value pairs of setting options.
		 */
		$tab_settings = apply_filters( 'tec_tickets_wallet_plus_tab_settings', $tab_settings );

		// Create the parent tab.
		$this->settings_tab = new Tribe__Settings_Tab( static::$slug, esc_html__( 'Wallet & PDF', 'event-tickets-plus' ), $tab_settings );

		// Add child tabs based on available sections.
		$this->get_child_tabs( $this->settings_tab );
	}

	/**
	 * Creates and adds child tabs for each available section.
	 *
	 * @since 6.5.0
	 *
	 * @param Tribe__Settings_Tab $parent_tab The parent tab instance to add children to.
	 *
	 * @return void
	 */
	protected function get_child_tabs( Tribe__Settings_Tab $parent_tab ): void {
		$sections = $this->get_sections();
		if ( empty( $sections ) ) {
			return;
		}

		// Get existing child tab slugs to avoid duplicates.
		$existing_slugs = [];
		if ( method_exists( $parent_tab, 'get_children' ) ) {
			foreach ( $parent_tab->get_children() as $child ) {
				if ( method_exists( $child, 'get_slug' ) ) {
					$existing_slugs[ $child->get_slug() ] = true;
				}
			}
		}

		// Create child tabs for each valid section.
		foreach ( $sections as $section_data ) {
			if ( isset( $existing_slugs[ $section_data['slug'] ] ) ) {
				continue;
			}

			$fields = $this->get_fields();

			$parent_tab->add_child(
				new Tribe__Settings_Tab(
					$section_data['slug'],
					$section_data['text'],
					[
						'fields'    => $fields,
						'show_save' => true,
					]
				)
			);
		}
	}


	/**
	 * Gets the settings tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return Tribe__Settings_Tab
	 */
	public function get_settings_tab() {
		return $this->settings_tab;
	}

	/**
	 * Register tab ID for network mode support.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $tabs Array of tabs IDs for the Events settings page.
	 *
	 * @return array
	 */
	public function register_tab_id( array $tabs ): array {
		$tabs[] = static::$slug;
		return $tabs;
	}

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return Tribe__Template
	 */
	public function get_template() {
		if ( empty( $this->template ) ) {
			$this->template = new Template();
			$this->template->set_template_origin( tribe( Plugin::class ) );
			$this->template->set_template_folder( 'src/admin-views/tickets-wallet-plus/settings' );
			$this->template->set_template_context_extract( true );
		}

		return $this->template;
	}

	/**
	 * Gets the URL for the Wallet Tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $args Which query args we are adding.
	 *
	 * @return string
	 */
	public function get_url( array $args = [] ): string {
		// Force the wallet tab.
		$args['tab'] = static::$slug;

		// Use the settings page get_url to build the URL.
		return tribe( Plugin_Settings::class )->get_url( $args );
	}

	/**
	 * Get the settings.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array[] Key value pair for setting options.
	 */
	public function get_fields(): array {
		$fields  = [];
		$section = $this->get_current_section();

		/**
		 * Filter the settings fields for the Wallet tab.
		 * Each section can hook into this filter to add its specific fields.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array  $fields  The fields array.
		 * @param string $section The current section (e.g. 'apple-wallet', 'pdf-tickets').
		 */
		return apply_filters( 'tec_tickets_wallet_plus_settings_fields', $fields, $section );
	}

	/**
	 * Gets an array of all the sections.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 * @since 6.5.0 Added logic to remove duplicate sections.
	 *
	 * @return array[]
	 */
	public function get_sections(): array {
		$sections = [];

		/**
		 * Filters the sections available on the Wallet Tab.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array[] $sections Current sections.
		 */
		$filtered_sections = (array) apply_filters( 'tec_tickets_wallet_plus_settings_sections', $sections );

		// Remove any sections with empty slugs, deduplicate.
		$unique_sections = [];
		foreach ( $filtered_sections as $section ) {
			if ( empty( $section['slug'] ) ) {
				continue;
			}

			$unique_sections[ $section['slug'] ] = $section;
		}

		return array_values( $unique_sections );
	}

	/**
	 * Gets the current section.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 * @since 6.5.0 Added support for legacy wallet tab.
	 *
	 * @return string $section The current section.
	 */
	public function get_current_section(): string {
		$tab = tribe_get_request_var( static::$key_current_section_get_var, '' );

		// If we're on the legacy wallet tab, return the first child tab's slug.
		if ( $tab === static::$slug ) {
			$sections = $this->get_sections();
			if ( ! empty( $sections ) ) {
				return $sections[0]['slug'];
			}
		}

		return $tab;
	}

	/**
	 * Returns the settings item for the section menu at the top of the Wallet settings tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array[]
	 */
	public function get_section_menu(): array {
		$template_vars = [
			'sections'         => $this->get_sections(),
			'selected_section' => tribe_get_request_var( static::$key_current_section_get_var, '' ),
		];

		return [
			'event-tickets-wallet-plus-sections' => [
				'type' => 'html',
				'html' => $this->get_template()->template( 'menu', $template_vars, false ),
			],
		];
	}

	/**
	 * Determines if the current admin page is the specific settings page and tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool True if on the specified page and tab, false otherwise.
	 */
	public function is_current_page() {
		// Wallet tab only exists in Admin.
		if ( ! is_admin() ) {
			return false;
		}

		$tab  = tribe_get_request_var( 'tab' );
		$page = tribe_get_request_var( 'page' );

		// If you are not on the correct page or tab then bail.
		if ( Plugin_Settings::$settings_page_id !== $page || static::$slug !== $tab ) {
			return false;
		}

		return true;
	}

	/**
	 * Filters the redirect URL to include section, if applicable.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $url URL of redirection.
	 *
	 * @return string
	 */
	public function filter_redirect_url( $url ) {
		if ( ! is_admin() ) {
			return $url;
		}

		$tab  = tribe_get_request_var( 'tab' );
		$page = tribe_get_request_var( 'page' );

		if ( empty( $tab ) || empty( $page ) ) {
			return $url;
		}

		if ( empty( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
			return $url;
		}

		if ( \Tribe\Tickets\Admin\Settings::$settings_page_id !== $page ) {
			return $url;
		}

		if ( static::$slug !== $tab ) {
			return $url;
		}

		$section = $this->get_current_section();

		// In the main section we don't need to do anything.
		if ( empty( $section ) || 'main' === $section ) {
			return $url;
		}

		return add_query_arg( static::$key_current_section_get_var, esc_attr( $section ), $url );
	}
}
