<?php
namespace Tribe\Tickets\Plus\Integrations\Elementor;

use Elementor\Plugin as Elementor_Plugin;
use Tribe\Tickets\Plus\Integrations\Elementor\Widgets\Widget_RSVP;
use Tribe\Tickets\Plus\Integrations\Elementor\Widgets\Widget_Tickets;

/**
 * Class Widgets_Manager
 *
 * @since 5.4.4
 *
 * @package Tribe\Tickets\Plus\Integrations\Elementor
 */
class Widgets_Manager extends Manager_Abstract {
	/**
	 * {@inheritdoc}
	 */
	protected $type = 'widgets';

	/**
	 * Constructor
	 *
	 * @since 5.4.4
	 */
	public function __construct() {
		$this->objects = [
			Widget_Tickets::get_slug() => Widget_Tickets::class,
			Widget_RSVP::get_slug()    => Widget_RSVP::class
		];
	}

	/**
	 * Registers the widgets with Elementor.
	 *
	 * @since 5.4.4
	 */
	public function register() {
		$widgets = $this->get_registered_objects();

		foreach ( $widgets as $slug => $widget_class ) {
			Elementor_Plugin::instance()->widgets_manager->register( tribe( $widget_class ) );
		}
	}
}