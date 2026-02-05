<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets_Plus\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets-plus.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets_Plus\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets-plus.hooks' ), 'some_method' ] );
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus
 */

namespace TEC\Tickets_Plus;

use Tribe__Template;
use Tribe__Tickets__Commerce__Orders_Tabbed_View;

/**
 * Class Hooks.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus
 */
class Hooks extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.3.0
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets component.
	 *
	 * @since 5.3.0
	 */
	protected function add_actions() {
		add_action( 'tribe_tickets_attendees_page_inside', tribe_callback( Tribe__Tickets__Commerce__Orders_Tabbed_View::class, 'render' ) );
	}

	/**
	 * Adds the filters required by each Tickets component.
	 *
	 * @since 5.3.0
	 */
	protected function add_filters() {
		add_filter( 'tribe_template_path_list', [ $this, 'filter_template_path_list' ], 15, 2 );
		add_filter( 'tribe_template_origin_namespace_map', [ $this, 'filter_add_template_origin_namespace' ], 15, 3 );
		add_filter( 'tec_tickets_commerce_settings_top_level', [ $this, 'filter_tc_settings' ] );

		$this->container->register_on_action(
			'tec_tickets_cache_controller_registered',
			Ticket_Cache_Controller::class
		);
	}

	/**
	 * Filters the list of folders that will be looked over to find templates and add the Event Tickets Plus.
	 *
	 * @since 5.3.0
	 *
	 * @param array           $folders  The current list of folders that will be searched template files.
	 * @param Tribe__Template $template Which template instance we are dealing with.
	 *
	 * @return array The filtered list of folders that will be searched for the templates.
	 */
	public function filter_template_path_list( array $folders, Tribe__Template $template ) {
		/** @var Plugin $main */
		$main = tribe( 'tickets-plus.main' );

		$path = (array) rtrim( $main->plugin_path, '/' );

		// Pick up if the folder needs to be added to the public template path.
		$folder = $template->get_template_folder();

		if ( ! empty( $folder ) ) {
			$path = array_merge( $path, $folder );
		}

		$folders['event-tickets-plus'] = [
			'id'        => 'event-tickets-plus',
			'namespace' => $main->template_namespace,
			'priority'  => 16,
			'path'      => implode( DIRECTORY_SEPARATOR, $path ),
		];

		return $folders;
	}

	/**
	 * Includes Event Tickets Plus into the path namespace mapping, allowing for a better namespacing when loading files.
	 *
	 * @since 5.3.0
	 *
	 * @param array           $namespace_map Indexed array containing the namespace as the key and path to `strpos`.
	 * @param string          $path          Path we will do the `strpos` to validate a given namespace.
	 * @param Tribe__Template $template      Current instance of the template class.
	 *
	 * @return array  Namespace map after adding Pro to the list.
	 */
	public function filter_add_template_origin_namespace( $namespace_map, $path, $template ) {
		/** @var Plugin $main */
		$main = tribe( 'tickets-plus.main' );

		$namespace_map[ $main->template_namespace ] = $main->plugin_path;

		return $namespace_map;
	}

	/**
	 * Remove the promotional wording from TC settings since plus is already installed.
	 *
	 * @since 5.5.2
	 *
	 * @param array $settings Associative array of setting from Tickets Commerce.
	 *
	 * @return array  Filtered settings.
	 */
	public function filter_tc_settings( $settings ) {
		// If setting doesn't exist, bail.
		if ( ! isset( $settings['tickets-commerce-description'] ) ) {
			return $settings;
		}

		$kb_link = sprintf(
			'<a href="https://evnt.is/1axt" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Learn more', 'event-tickets' )
		);

		$new_description = sprintf(
			// Translators: %1$s: The Tickets Commerce knowledgebase article link.
			esc_html_x( 'Tickets Commerce provides a simple and flexible eCommerce checkout for purchasing tickets. Just choose your payment gateway and configure checkout options and you\'re all set. %1$s.', 'about Tickets Commerce', 'event-tickets-plus' ),
			$kb_link
		);

		$settings['tickets-commerce-description']['html'] = '<p>' . $new_description . '</p>';
		return $settings;
	}
}
