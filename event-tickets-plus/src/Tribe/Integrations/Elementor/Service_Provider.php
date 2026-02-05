<?php
/**
 * Handles the integration with Elementor.
 *
 * @since 5.4.4
 *
 * @package Tribe\Tickets\Plus\Integrations\Elementor
 */

namespace Tribe\Tickets\Plus\Integrations\Elementor;

use Elementor\Elements_Manager;

/**
 * Class Service_Provider
 *
 * @since 5.4.4
 *
 * @package Tribe\Tickets\Plus\Integrations\Elementor
 */
class Service_Provider extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Registers the bindings and hooks the filters required for the Elementor integration to work.
	 *
	 * @since 5.4.4
	 */
	public function register() {

		// Register the hooks related to this integration.
		$this->register_hooks();
	}

	/**
	 * Register the hooks for Elementor integration.
	 *
	 * @since 5.4.4
	 */
	public function register_hooks() {
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_elementor_category' ] );
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets_manager_registration' ] );
	}

	/**
	 * Registers widgets for Elementor.
	 *
	 * @since 5.4.4
	 */
	public function register_widgets_manager_registration() {
		return $this->container->make( Widgets_Manager::class )->register();
	}

	/**
	 * Registers widget categories for Elementor.
	 *
	 * @since 5.4.4
	 *
	 * @param Elements_Manager $elements_manager Elementor Manager instance.
	 */
	public function register_elementor_category( $elements_manager ) {
		$elements_manager->add_category(
			'event-tickets',
			[
				'title' => __( 'Event Tickets', 'event-tickets-plus' ),
			]
		);
	}
}
