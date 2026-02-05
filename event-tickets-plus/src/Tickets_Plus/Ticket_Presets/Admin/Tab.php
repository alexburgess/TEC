<?php
/**
 * Handles the registration and rendering of the Ticket Presets tab.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */

namespace TEC\Tickets_Plus\Ticket_Presets\Admin;

use TEC\Tickets\Admin\Tickets\Page;

/**
 * Class Tab
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets\Admin
 */
class Tab {
	/**
	 * The presets table instance.
	 *
	 * @since 6.6.0
	 *
	 * @var Presets_Table|null
	 */
	private ?Presets_Table $presets_table = null;

	/**
	 * The slug of the tab.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public static string $slug = 'presets';

	/**
	 * Checks if the current tab is the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 *
	 * @return bool True if the current tab is the Ticket Presets tab, false otherwise.
	 */
	public static function is_on_tab(): bool {
		return tec_get_request_var( 'tab' ) === self::$slug;
	}

	/**
	 * Prepares the table before headers are sent.
	 *
	 * @since 6.6.0
	 */
	public function prepare_table(): void {
		if ( ! is_admin() || tec_get_request_var( 'page' ) !== 'tec-tickets-admin-tickets' || tec_get_request_var( 'tab' ) !== self::$slug ) {
			return;
		}

		$this->get_presets_table()->prepare_items();
	}

	/**
	 * Gets the presets table instance.
	 *
	 * @since 6.6.0
	 *
	 * @return Presets_Table The presets table instance.
	 */
	public function get_presets_table(): Presets_Table {
		if ( null === $this->presets_table ) {
			$this->presets_table = new Presets_Table();
		}

		return $this->presets_table;
	}

	/**
	 * Registers the hooks for the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 */
	public function register(): void {
		add_action( 'admin_init', [ $this, 'prepare_table' ] );
		add_action( 'tec_tickets_admin_tickets_page_after_register_tabs', [ $this, 'register_tab' ] );
		add_filter( 'tec_tickets_admin_tickets_table_columns', [ $this, 'filter_table_columns' ] );
		add_filter( 'set-screen-option', [ Presets_Table::class, 'store_custom_per_page_option' ], 10, 3 );
		add_action( 'current_screen', [ $this, 'set_screen_id_for_tab' ] );
		add_filter( 'screen_options_show_screen', [ $this, 'filter_screen_options_show_screen' ], 10, 2 );
		add_action( 'load-tickets_page_tec-tickets-admin-tickets', [ $this, 'add_screen_options' ] );
	}

	/**
	 * Registers the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 *
	 * @param Page $page The current page instance.
	 */
	public function register_tab( Page $page ): void {
		$page->add_tab(
			self::$slug,
			__( 'Ticket Presets', 'event-tickets-plus' ),
			[
				'visible'         => true,
				'capability'      => $page->required_capability(),
				'render_callback' => [ $this, 'render_tab_content' ],
			]
		);
	}

	/**
	 * Renders the Ticket Presets tab content.
	 *
	 * @since 6.6.0
	 *
	 * @param string $current_tab The current tab slug.
	 */
	public function render_tab_content( string $current_tab ): void {
		if ( self::$slug !== $current_tab ) {
			return;
		}

		$presets_table = $this->get_presets_table();
		include dirname( __DIR__ ) . '/Admin/views/presets.php';
	}

	/**
	 * Filters the table columns for the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 *
	 * @param array<string,string> $columns The current table columns.
	 *
	 * @return array<string,string> The filtered table columns.
	 */
	public function filter_table_columns( array $columns ): array {
		if ( ! static::is_on_tab() ) {
			return $columns;
		}

		return tribe( Presets_Table::class )->get_columns();
	}

	/**
	 * Filters the screen options for the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 *
	 * @param bool   $status  The current status of the screen option.
	 * @param string $option  The screen option name.
	 * @param string $value   The screen option value.
	 *
	 * @return bool The filtered status of the screen option.
	 */
	public function filter_set_screen_options( $status, $option, $value ): bool {
		if ( ! static::is_on_tab() ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Filters the screen ID for the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 *
	 * @param \WP_Screen $screen The current screen instance.
	 */
	public function set_screen_id_for_tab( \WP_Screen $screen ): void {
		if ( ! static::is_on_tab() ) {
			return;
		}

		if ( 'tickets_page_tec-tickets-admin-tickets' === $screen->id ) {
			$screen->id = 'tickets_page_tec-tickets-admin-tickets-presets';
		}
	}

	/**
	 * Filters the screen options show screen for the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 *
	 * @param bool       $show Whether to show the screen options.
	 * @param \WP_Screen $screen The screen object.
	 *
	 * @return bool Whether to show the screen options.
	 */
	public function filter_screen_options_show_screen( $show, $screen ): bool {
		if ( 'tickets_page_tec-tickets-admin-tickets-presets' === $screen->id ) {
			return true;
		}

		return $show;
	}

	/**
	 * Adds screen options for the Ticket Presets tab.
	 *
	 * @since 6.6.0
	 */
	public function add_screen_options(): void {
		if ( ! static::is_on_tab() ) {
			return;
		}

		$screen = get_current_screen();

		$screen->add_option(
			'per_page',
			[
				'label'   => __( 'Number of entries per page:', 'event-tickets-plus' ),
				'default' => 20,
				'option'  => Presets_Table::get_per_page_option_name(),
			]
		);
	}
}
