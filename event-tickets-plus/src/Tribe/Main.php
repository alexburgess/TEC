<?php
// Don't load directly.

use TEC\Tickets_Wallet_Plus\Plugin;
use TEC\Common\StellarWP\Assets\Config as Assets_Config;

if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! class_exists( 'Tribe__Tickets_Plus__Main' ) ) {
	class Tribe__Tickets_Plus__Main {

		/**
		 * Current version of this plugin.
		 */
		const VERSION = '6.9.1';

		/**
		 * Used to store the version history.
		 *
		 * @since 4.11.0
		 *
		 * @var string
		 */
		public $version_history_slug = 'previous_event_tickets_plus_versions';

		/**
		 * Used to store the latest version.
		 *
		 * @since 4.11.0
		 *
		 * @var string
		 */
		public $latest_version_slug = 'latest_event_tickets_plus_version';

		/**
		 * Directory of the plugin
		 *
		 * @var string
		 */
		public $plugin_dir;

		/**
		 * Path of the plugin
		 *
		 * @var string
		 */
		public $plugin_path;

		/**
		 * URL of the plugin
		 *
		 * @since 4.6
		 *
		 * @var string
		 */
		public $plugin_url;

		/**
		 * Holds an instance of Tribe__Tickets_Plus__PUE
		 *
		 * @var Tribe__Tickets_Plus__PUE
		 */
		public $pue;

		/**
		 * @var Tribe__Tickets_Plus__Commerce__Attendance_Totals
		 */
		protected $attendance_totals;

		/**
		 * Holds an instance of Tribe__Tickets_Plus__Commerce__Loader
		 *
		 * @var Tribe__Tickets_Plus__Commerce__Loader
		 */
		protected static $commerce_loader;

		/**
		 * Holds an instance of Tribe__Tickets_Plus__QR
		 *
		 * @var Tribe__Tickets_Plus__QR
		 */
		protected static $qr;

		/**
		 * @deprecated 5.1.0 Use `tribe( 'tickets-plus.meta' )` instead.
		 *
		 * @var Tribe__Tickets_Plus__Meta
		 */
		protected static $meta;

		/**
		 * Holds an instance of Tribe__Tickets_Plus__APM.
		 *
		 * @var Tribe__Tickets_Plus__APM
		 */
		protected static $apm_filters;

		/**
		 * Where in the themes we will look for templates.
		 *
		 * @since 5.1.0
		 *
		 * @var string
		 */
		public $template_namespace = 'tickets-plus';

		/**
		 * Get (and instantiate, if necessary) the instance of the class.
		 *
		 * @static
		 * @return Tribe__Tickets_Plus__Main
		 */
		public static function instance() {
			try {
				return tribe( 'tickets-plus.main' );
			} catch ( RuntimeException $exception ) {
				return null;
			}
		}

		public function __construct() {
			$this->plugin_path = trailingslashit( EVENT_TICKETS_PLUS_DIR );
			$this->plugin_dir  = trailingslashit( basename( $this->plugin_path ) );
			$this->plugin_url  = plugins_url() . '/' . $this->plugin_dir;

			Tribe__Tickets_Plus__Commerce__WooCommerce__Main::set_compatibility_checks();

			/** @see \Tribe__Events__Pro__Main::init_apm_filters() Is on priority 10. */
			add_action( 'plugins_loaded', [ $this, 'apm_filters' ], 5 );

			add_action( 'tribe_extension_is_disallowed', [ $this, 'disallowed_extensions' ], 10, 2 );

			add_action( 'init', [ $this, 'init' ], 5 );

			// CSV import needs to happen before P10@init but after P5@init.
			add_action( 'init', [ $this, 'csv_import_support' ], 6 );
			add_filter( 'tribe_support_registered_template_systems', [ $this, 'add_template_updates_check' ] );
			add_action( 'tribe_events_tickets_attendees_event_details_top', [ $this, 'setup_attendance_totals' ], 5 );

			// Unique ticket identifiers.
			add_action( 'event_tickets_rsvp_attendee_created', [ Tribe__Tickets_Plus__Meta__Unique_ID::instance(), 'assign_unique_id' ], 10, 2 );
			add_action( 'event_ticket_woo_attendee_created', [ Tribe__Tickets_Plus__Meta__Unique_ID::instance(), 'assign_unique_id' ], 10, 2 );
			add_action( 'event_ticket_edd_attendee_created', [ Tribe__Tickets_Plus__Meta__Unique_ID::instance(), 'assign_unique_id' ], 10, 2 );

			add_action( 'admin_init', [ $this, 'run_updates' ], 10, 0 );

			add_filter( 'tribe-events-save-options', [ $this, 'retro_attendee_page_option' ] );

			// Force Woo cart functionality over REST API.
			add_action( 'before_woocommerce_init', tribe_callback( Tribe__Tickets_Plus__Commerce__WooCommerce__Cart::class, 'force_woo_cart_for_rest_api' ) );

			add_action( 'init', [ $this, 'load_tickets_wallet_plus' ] );
			add_action( 'wp', [ $this, 'load_deprecated' ], 20 );
		}

		/**
		 * Bootstrap of the Plugin on Init
		 *
		 * @since 4.10
		 * @since 4.12.1 Added Shortcodes service provider and deactivation of incompatible extensions.
		 * @since 6.5.0 Added Tyson group paths.
		 */
		public function init() {
			/*
			* Register the `/build` directory assets as a different group to ensure back-compatibility.
			* This needs to happen early in the plugin bootstrap routine.
			*/
			Assets_Config::add_group_path(
				self::class,
				$this->plugin_path,
				'build/',
				true
			);

			/*
			* Register the `/build` directory as root for packages.
			* The difference from the group registration above is that packages are not expected to use prefix directories
			* like `/js` or `/css`.
			*/
			Assets_Config::add_group_path(
				self::class . '-packages',
				$this->plugin_path,
				'build/',
				false
			);

			tec_svg_register_namespace( '@ticketsPlus', $this->plugin_path . 'src/resources/icons/' );

			// Setup Main Service Provider.
			tribe_register_provider( 'Tribe__Tickets_Plus__Service_Provider' );

			// REST API v1.
			tribe_register_provider( 'Tribe__Tickets_Plus__REST__V1__Service_Provider' );

			// Promoter.
			tribe_register_provider( Tribe__Tickets_Plus__Service_Providers__Promoter::class );

			// Meta.
			tribe_register_provider( Tribe\Tickets\Plus\Meta\Service_Provider::class );

			// Attendee Registration.
			tribe_register_provider( Tribe\Tickets\Plus\Attendee_Registration\Service_Provider::class );

			// @todo Refactor this in the future to move to ET+ entirely, using same backcompat ET slugs.
			tribe_register_provider( Tribe__Tickets__Attendee_Registration__Service_Provider::class );

			// Manual Attendees.
			tribe_register_provider( Tribe\Tickets\Plus\Manual_Attendees\Service_Provider::class );

			// Shortcodes.
			tribe_register_provider( Tribe\Tickets\Plus\Service_Providers\Shortcode::class );

			// Views v2 compatibility.
			tribe_register_provider( Tribe\Tickets\Plus\Views\V2\Service_Provider::class );

			// WooCommerce Enhanced Templates.
			tribe_register_provider( Tribe\Tickets\Plus\Commerce\WooCommerce\Enhanced_Templates\Service_Provider::class );

			// Attendee List Resend Tickets Handler.
			tribe_register_provider( Tribe\Tickets\Plus\Service_Providers\Resend_Tickets_Handler::class );

			$this->commerce_loader();
			$this->bind_implementations();

			tribe( 'tickets-plus.privacy' );

			$this->meta();
			$this->tickets_view();
			$this->qr();
			$this->attendees_list();

			$this->apm_filters();
			$this->maybe_set_event_tickets_plus_version();

			/**
			 * Fires when Event Tickets Plus is fully loaded.
			 *
			 * @since 6.4.0
			 */
			do_action( 'tec_tickets_plus_fully_loaded' );
		}

		/**
		 * Method executed on `plugins_loaded`.
		 *
		 * @since 5.6.8
		 *
		 * @deprecated 6.1.3 This used to load the Resend Tickets Handler - we do that elsewhere now.
		 *
		 * @return void
		 */
		public function tec_tickets_plus_plugins_loaded() {
			_deprecated_function( __METHOD__, '6.1.3' );
		}

		/**
		 * Deactivate incompatible extension plugins.
		 *
		 * @since 4.12.1.
		 *
		 * @param bool   $is_disallowed        False by default.
		 * @param string $extension_class_name This extension's class name string
		 *                                     (without initial forward slash for namespaced classes).
		 *
		 * @return bool
		 */
		public function disallowed_extensions( $is_disallowed, $extension_class_name ) {
			// Without leading forward slash.
			$disallowed_extensions = [
				'Tribe\Extensions\Tickets\Shortcodes\Main', // Event Tickets Extension: Shortcodes.
			];

			if (
				empty( $is_disallowed )
				&& in_array( $extension_class_name, $disallowed_extensions, true )
			) {
				return true;
			}

			return $is_disallowed;
		}

		/**
		 * Registers the implementations in the container
		 *
		 * @since 4.7.6
		 */
		public function bind_implementations() {
			// Privacy.
			tribe_singleton( 'tickets-plus.privacy', 'Tribe__Tickets_Plus__Privacy', [ 'hook' ] );
			tribe_singleton( Tribe__Tickets_Plus__PUE::class, Tribe__Tickets_Plus__PUE::class );

			// Blocks editor.
			tribe_register_provider( 'Tribe__Tickets_Plus__Editor__Provider' );

			// Load integrations.
			Tribe\Tickets\Plus\Integrations\Manager::instance()->load_integrations();

			/**
			 * Fires after the Tickets Plus attendee bind implementations have been registered.
			 *
			 * @since 6.0.3
			 */
			do_action( 'tec_tickets_plus_attendee_bind_implementations' );
		}

		/**
		 * Registers this plugin as being active for other tribe plugins and extensions
		 *
		 * @deprecated 4.10
		 *
		 * @return bool Indicates if Tribe Common wants the plugin to run
		 */
		public function register_active_plugin() {
			_deprecated_function( __METHOD__, '4.10', '4.10' );

			if ( ! function_exists( 'tribe_register_plugin' ) ) {
				return true;
			}

			return tribe_register_plugin( EVENT_TICKETS_PLUS_FILE, __CLASS__, self::VERSION );
		}


		/**
		 * @deprecated 4.6
		 */
		public function register_resources() {
			_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets_Plus__Assets:enqueue_scripts ' );
		}

		/**
		 * @deprecated 4.6
		 */
		public function enqueue_scripts() {
			_deprecated_function( __METHOD__, '4.6', 'Tribe__Tickets_Plus__Assets:enqueue_scripts' );
		}

		/**
		 * Creates the Tickets FrontEnd facing View class
		 *
		 * This will happen on `plugins_loaded` by default
		 *
		 * @return Tribe__Tickets_Plus__Tickets_View
		 */
		public function tickets_view() {
			return Tribe__Tickets_Plus__Tickets_View::hook();
		}

		/**
		 * Object accessor method for the Commerce Loader
		 *
		 * @return Tribe__Tickets_Plus__Commerce__Loader
		 */
		public function commerce_loader() {

			if ( ! self::$commerce_loader ) {
				self::$commerce_loader = new Tribe__Tickets_Plus__Commerce__Loader();
			}

			return self::$commerce_loader;
		}

		/**
		 * Object accessor method for QR codes
		 *
		 * @return Tribe__Tickets_Plus__QR
		 */
		public function qr() {
			if ( ! self::$qr ) {
				self::$qr = new Tribe__Tickets_Plus__QR();
			}

			return self::$qr;
		}

		/**
		 * Object accessor method for APM filters.
		 *
		 * @return null|Tribe__Tickets_Plus__APM
		 */
		public function apm_filters() {
			if ( ! class_exists( 'Tribe_APM' ) ) {
				return null;
			}

			if ( ! self::$apm_filters ) {
				self::$apm_filters = new Tribe__Tickets_Plus__APM();
			}

			return self::$apm_filters;
		}

		/**
		 * Set the Event Tickets version in the options table if it's not already set.
		 *
		 * @since 4.11.0
		 */
		public function maybe_set_event_tickets_plus_version() {
			if ( version_compare( Tribe__Settings_Manager::get_option( $this->latest_version_slug ), self::VERSION, '<' ) ) {
				$previous_versions   = Tribe__Settings_Manager::get_option( $this->version_history_slug ) ?: [];
				$previous_versions[] = Tribe__Settings_Manager::get_option( $this->latest_version_slug ) ?: '0';

				Tribe__Settings_Manager::set_option( $this->version_history_slug, $previous_versions );
				Tribe__Settings_Manager::set_option( $this->latest_version_slug, self::VERSION );
			}
		}

		/**
		 * Object accessor method for Ticket meta
		 *
		 * @deprecated 5.1.0 Use `tribe( 'tickets-plus.meta' )` instead.
		 *
		 * @return Tribe__Tickets_Plus__Meta
		 */
		public function meta() {
			return tribe( 'tickets-plus.meta' );
		}

		protected static $attendees_list;

		public function attendees_list() {
			if ( ! self::$attendees_list ) {
				self::$attendees_list = Tribe__Tickets_Plus__Attendees_List::hook();
			}

			return self::$attendees_list;
		}

		/**
		 * Adds ticket attendance totals to the summary box of the attendance
		 * screen.
		 *
		 * Expects to fire during 'tribe_tickets_attendees_page_inside', ie
		 * before the attendee screen is rendered.
		 *
		 * @since 4.2.4
		 * @since 5.9.2 Added the `$event_id` parameter.
		 * @since 6.7.0 Added logic to prevent duplicate totals from being displayed.
		 *
		 * @param int $event_id The event ID to set up the totals for.
		 */
		public function setup_attendance_totals( $event_id = null ) {
			static $totals_displayed = false;

			// Only display totals once.
			if ( $totals_displayed ) {
				return;
			}

			$totals_displayed = true;

			$this->attendance_totals( $event_id )->integrate_with_attendee_screen();
		}

		/**
		 * Returns the attendance totals object.
		 *
		 * @since 4.2.4
		 * @since 5.9.2 Added the `$event_id` parameter.
		 *
		 * @param int $event_id The event ID to set up the totals for.
		 *
		 * @return Tribe__Tickets_Plus__Commerce__Attendance_Totals The attendance totals object.
		 */
		public function attendance_totals( $event_id = null ) {
			if ( empty( $this->attendance_totals ) ) {
				$this->attendance_totals = new Tribe__Tickets_Plus__Commerce__Attendance_Totals();
			}

			$this->attendance_totals->set_event_id( $event_id );

			return $this->attendance_totals;
		}

		/**
		 * Setup integration with The Events Calendar's CSV import facilities.
		 *
		 * Expects to run during the init action - we don't want to set this up
		 * too early otherwise the commerce loader may not be able to reliably
		 * determine the version numbers of any active ecommerce plugins.
		 */
		public function csv_import_support() {
			// CSV import is not a concern unless The Events Calendar is also running.
			if ( ! class_exists( 'Tribe__Events__Main' ) ) {
				return;
			}

			$commerce_loader = $this->commerce_loader();

			if ( ! $commerce_loader->has_commerce_providers() ) {
				return;
			}

			$column_names_filter  = Tribe__Tickets_Plus__CSV_Importer__Column_Names::instance( $commerce_loader );
			$importer_rows_filter = Tribe__Tickets_Plus__CSV_Importer__Rows::instance( $commerce_loader );

			add_filter( 'tribe_events_import_options_rows', [ $importer_rows_filter, 'filter_import_options_rows' ] );
			add_filter( 'tribe_aggregator_csv_post_types', [ $importer_rows_filter, 'filter_csv_post_types' ] );

			if ( $commerce_loader->is_woocommerce_active() ) {
				Tribe__Tickets_Plus__CSV_Importer__Woo::instance();

				add_filter( 'tribe_event_import_product_column_names', [ $column_names_filter, 'filter_tickets_woo_column_names' ] );

				add_filter( 'tribe_events_import_tickets_woo_importer', [ 'Tribe__Tickets_Plus__CSV_Importer__Tickets_Importer', 'woo_instance' ], 10, 2 );
				add_filter( 'tribe_event_import_tickets_woo_column_names', [ $column_names_filter, 'filter_tickets_woo_column_names' ] );
			}

			add_filter( 'tribe_events_import_type_titles_map', [ $column_names_filter, 'filter_import_type_titles_map' ] );
		}

		/**
		 * Register Event Tickets Plus with the template update checker.
		 *
		 * @since 5.1.0 Updated template path structure.
		 *
		 * @param array $plugins
		 *
		 * @return array
		 */
		public function add_template_updates_check( $plugins ) {
			// ET+ views can be in one of a range of different subdirectories (eddtickets, wootickets
			// etc) so we will tell the template checker to simply look in tribe/tickets-plus and work
			// things out from there.
			$plugins[ __( 'Event Tickets Plus', 'event-tickets-plus' ) ] = [
				self::VERSION,
				$this->plugin_path . 'src/views',
				trailingslashit( get_stylesheet_directory() ) . 'tribe/tickets-plus',
			];

			$plugins[ __( 'Event Tickets Plus - Legacy', 'event-tickets-plus' ) ] = [
				self::VERSION,
				$this->plugin_path . 'src/views',
				trailingslashit( get_stylesheet_directory() ) . 'tribe-events',
			];

			return $plugins;
		}

		/**
		 * Gets the view from the plugin's folder, or from the user's theme if found.
		 *
		 * @param string $template The template.
		 *
		 * @return mixed|void
		 */
		public function get_template_hierarchy( $template ) {

			if ( substr( $template, - 4 ) != '.php' ) {
				$template .= '.php';
			}

			if ( $theme_file = locate_template( [ 'tribe-events/' . $template ] ) ) {
				$file = $theme_file;
			} else {
				$file = $this->plugin_path . 'src/views/' . $template;
			}

			return apply_filters( 'tribe_events_tickets_template_' . $template, $file );
		}

		/**
		 * Handle converting an old key to a new one, in this case
		 * ticket-attendee-page-slug -> ticket-attendee-page-id
		 *
		 * @since 4.10.4
		 *
		 * @todo Move this to common at some point for better utility?
		 *
		 * @param array $options List of options to save.
		 * @return array Modified list of options to save.
		 */
		public function retro_attendee_page_option( $options ) {
			// Don't migrate option if old option is not set.
			if ( empty( $options['ticket-attendee-page-slug'] ) ) {
				return $options;
			}

			$slug = $options['ticket-attendee-page-slug'];
			unset( $options['ticket-attendee-page-slug'] );

			// ID is already set, just return $options without the slug.
			if ( ! empty( $options['ticket-attendee-page-id'] ) ) {
				return $options;
			}

			$page = get_page_by_path( $slug, OBJECT );

			// Slug does not match any pages or it may have changed, just return $options without the slug.
			if ( empty( $page ) ) {
				return $options;
			}

			// Set ID to the slug page's ID  and return $options without the slug.
			$options['ticket-attendee-page-id'] = $page->ID;
			return $options;
		}

		/**
		 * Filters the list of ticket login requirements, making it possible to require that users
		 * be logged in before purchasing tickets.
		 *
		 * @param array $options
		 *
		 * @return array
		 */
		public function register_login_setting( array $options ) {
			$options['event-tickets-plus_all'] = __( 'Require users to log in before they purchase tickets', 'event-tickets-plus' );
			return $options;
		}

		/**
		 * Make necessary database updates on admin_init
		 *
		 * @since 4.7.1
		 */
		public function run_updates() {
			if ( ! class_exists( 'Tribe__Updater' ) ) {
				return;
			}

			$updater = new Tribe__Tickets_Plus__Updater( self::VERSION );
			if ( $updater->update_required() ) {
				$updater->do_updates();
			}
		}

		/**
		 * Loading the Service Provider
		 *
		 * @deprecated 4.10
		 *
		 * @since 4.6
		 */
		public function on_load() {
			_deprecated_function( __METHOD__, '4.10', '' );

			tribe_register_provider( 'Tribe__Tickets_Plus__Service_Provider' );
		}


		/**
		 * Finalize the initialization of this plugin
		 *
		 * @deprecated 4.10
		 *
		 * @since 4.7.6
		 */
		public function plugins_loaded() {
			_deprecated_function( __METHOD__, '4.10', '' );

			$this->bind_implementations();

			tribe( 'tickets-plus.privacy' );
		}

		/**
		 * Load Event Tickets Wallet Plus features.
		 *
		 * @since 6.0.0
		 */
		public function load_tickets_wallet_plus() {
			// Boot Tickets Wallet Plus plugin.
			Plugin::boot();

			/**
			 * Fires when Event Tickets Wallet Plus is fully loaded.
			 *
			 * @since 6.4.0
			 */
			do_action( 'tec_tickets_wallet_plus_fully_loaded' );
		}

		/**
		 * Load deprecated assets.
		 *
		 * @since 6.0.0
		 */
		public function load_deprecated() {
			// Load deprecated assets.
			require_once EVENT_TICKETS_PLUS_DIR . '/src/deprecated/constants.php';
		}
	}
}
