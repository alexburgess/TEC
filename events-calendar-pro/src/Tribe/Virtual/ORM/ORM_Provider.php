<?php
/**
 * Registers the filters and functions needed to extend The Events Calendar ORM to support
 * Virtual functionality.
 *
 * @since 7.0.0 Migrated to Events Pro from Events Virtual.
 * @package Tribe\Events\Virtual\Service_Providers;
 */

namespace Tribe\Events\Virtual\ORM;

use Tribe\Events\Virtual\Repositories\Event;
use TEC\Common\Contracts\Service_Provider;

/**
 * Class ORM
 *
 * @since 7.0.0 Migrated to Events Pro from Events Virtual.
 * @package Tribe\Events\Virtual\ORM;
 */
class ORM_Provider extends Service_Provider {

	/**
	 * Binds and sets up implementations and registers the required filters.
	 *
	 * @since 7.0.0 Migrated to Events Pro from Events Virtual.
	 */
	public function register() {
		$this->container->singleton( 'events-virtual.orm', $this );
		$this->container->singleton( static::class, $this );

		// Not bound as a singleton to leverage the repository instance properties and to allow decoration and injection.
		$this->container->bind( 'events-virtual.event-repository', Event::class );

		add_filter( 'tribe_events_event_repository_map', array( $this, 'filter_event_repository_map' ), 12 );
		add_filter( 'tec_rest_event_properties_to_add', [ $this, 'filter_event_properties_to_add' ], 12 );
	}

	/**
	 * Filters the repository resolution map to replace the base TEC repository with the Virtual one.
	 *
	 * @since 7.0.0 Migrated to Events Pro from Events Virtual.
	 *
	 * @param array $map A map that associates the repository types to their implementations.
	 *
	 * @return array The modified repository map.
	 */
	public function filter_event_repository_map( array $map ) {
		$map['default'] = 'events-virtual.event-repository';

		return $map;
	}

	/**
	 * Filters the properties to add to the event REST API response.
	 *
	 * @since 7.7.0
	 *
	 * @param array $properties The properties to add to the event REST API response.
	 *
	 * @return array The modified properties.
	 */
	public function filter_event_properties_to_add( array $properties ) {
		$properties['virtual']              = true;
		$properties['virtual_video_source'] = true;
		$properties['virtual_url']          = true;

		return $properties;
	}
}
