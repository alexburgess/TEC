<?php
namespace Tribe\Tickets\Plus\Integrations\Elementor;

/**
 * Class Manager_Abstract
 *
 * @since 5.4.4
 *
 * @package Tribe\Tickets\Plus\Integrations\Elementor
 */
abstract class Manager_Abstract {
	/**
	 * @var string Type of object.
	 */
	protected $type;

	/**
	 * @var array Collection of objects to register.
	 */
	protected $objects;

	/**
	 * Returns an associative array of objects to be registered.
	 *
	 * @since 5.4.4
	 *
	 * @return array An array in the shape `[ <slug> => <class> ]`.
	 */
	public function get_registered_objects() {
		/**
		 * Filters the list of objects available and registered.
		 *
		 * Both classes and built objects can be associated with a slug; if bound in the container the classes
		 * will be built according to the binding rules; objects will be returned as they are.
		 *
		 * @since 5.4.4
		 *
		 * @param array $widgets An associative array of objects in the shape `[ <slug> => <class> ]`.
		 */
		return (array) apply_filters( "tribe_event_tickets_plus_elementor_registered_{$this->type}", $this->objects );
	}

	/**
	 * Registers the objects with Elementor.
	 *
	 * @since 5.4.4
	 */
	abstract public function register();
}
