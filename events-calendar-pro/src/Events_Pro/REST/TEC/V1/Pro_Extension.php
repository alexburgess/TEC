<?php
/**
 * The REST API extension for the Events Pro plugin.
 *
 * @since 7.7.0
 *
 * @package TEC\Events_Pro\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST\TEC\V1;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Events\REST\TEC\V1\Endpoints\Events;
use TEC\Events\REST\TEC\V1\Endpoints\Venues;
use TEC\Common\REST\TEC\V1\Collections\QueryArgumentCollection;
use TEC\Common\REST\TEC\V1\Collections\PropertiesCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Number;
use TEC\Common\REST\TEC\V1\Parameter_Types\Boolean;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;
use TEC\Common\REST\TEC\V1\Parameter_Types\URI;
use TEC\Common\REST\TEC\V1\Parameter_Types\Array_Of_Type;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Integer;
use TEC\Events_Pro\Custom_Tables\V1\Models\Provisional_Post;
use TEC\Events_Pro\Custom_Tables\V1\Models\Occurrence;

/**
 * The REST API extension for the Events Pro plugin.
 *
 * @since 7.7.0
 *
 * @package TEC\Events_Pro\REST\TEC\V1
 */
class Pro_Extension extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 7.7.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_events_rest_v1_events_read_params', [ $this, 'filter_events_read_params' ], 10, 2 );
		add_filter( 'tec_events_rest_v1_venues_read_params', [ $this, 'filter_venues_read_params' ], 10, 2 );
		add_filter( 'tec_rest_swagger_event_request_body_definition', [ $this, 'filter_event_request_body_definition' ] );
		add_filter( 'tec_rest_swagger_venue_request_body_definition', [ $this, 'filter_venue_request_body_definition' ] );
		add_filter( 'tec_rest_swagger_event_definition', [ $this, 'filter_event_definition' ] );
		add_filter( 'tec_rest_v1_tribe_events_transform_entity', [ $this, 'filter_event_transform_entity' ], 5 );
	}

	/**
	 * Unregisters the filters and actions hooks added by the controller.
	 *
	 * @since 7.7.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_events_rest_v1_events_read_params', [ $this, 'filter_events_read_params' ], 10 );
		remove_filter( 'tec_events_rest_v1_venues_read_params', [ $this, 'filter_venues_read_params' ], 10 );
		remove_filter( 'tec_rest_swagger_event_request_body_definition', [ $this, 'filter_event_request_body_definition' ] );
		remove_filter( 'tec_rest_swagger_venue_request_body_definition', [ $this, 'filter_venue_request_body_definition' ] );
		remove_filter( 'tec_rest_swagger_event_definition', [ $this, 'filter_event_definition' ] );
		remove_filter( 'tec_rest_swagger_venue_definition', [ $this, 'filter_venue_definition' ] );
		remove_filter( 'tec_rest_v1_tribe_events_transform_entity', [ $this, 'filter_event_transform_entity' ], 5 );
	}

	/**
	 * Filters the arguments for the events read request.
	 *
	 * @since 7.7.0
	 *
	 * @param QueryArgumentCollection $collection The collection of arguments.
	 * @param Events                  $endpoint   The events endpoint.
	 *
	 * @return QueryArgumentCollection The filtered collection of arguments.
	 */
	public function filter_events_read_params( QueryArgumentCollection $collection, Events $endpoint ): QueryArgumentCollection {
		foreach ( $this->get_geo_related_props() as $prop ) {
			$collection[] = $prop;
		}

		$collection[] = ( new Boolean(
			'in_series',
			fn() => __( 'Filter by events that are part of a series', 'tribe-events-calendar-pro' ),
		) )->set_example( true );

		$collection[] = ( new Array_Of_Type(
			'series',
			fn() => __( 'Filter by events that are part of specified series IDs', 'tribe-events-calendar-pro' ),
			Positive_Integer::class,
		) )->set_example( [ 1, 2, 3 ] );

		$collection[] = ( new Positive_Integer(
			'related_to',
			fn() => __( 'Filter by events that are related to a specific event ID.', 'tribe-events-calendar-pro' ),
		) )->set_example( 123 );

		$collection[] = ( new Boolean(
			'recurring',
			fn() => __( 'Filter by events that are recurring', 'tribe-events-calendar-pro' ),
		) )->set_example( true );

		$collection[] = ( new Boolean(
			'virtual',
			fn() => __( 'Filter by events that are virtual', 'tribe-events-calendar-pro' ),
		) )->set_example( false );

		return $collection;
	}

	/**
	 * Filters the arguments for the venues read request.
	 *
	 * @since 7.7.0
	 *
	 * @param QueryArgumentCollection $collection The collection of arguments.
	 * @param Venues                  $endpoint   The venues endpoint.
	 *
	 * @return QueryArgumentCollection The filtered collection of arguments.
	 */
	public function filter_venues_read_params( QueryArgumentCollection $collection, Venues $endpoint ): QueryArgumentCollection {
		foreach ( $this->get_geo_related_props( __( 'venue', 'tribe-events-calendar-pro' ), __( 'venues', 'tribe-events-calendar-pro' ) ) as $prop ) {
			$collection[] = $prop;
		}

		return $collection;
	}

	/**
	 * Filters the event request body definition.
	 *
	 * @since 7.7.0
	 *
	 * @param array $documentation An associative PHP array in the format supported by Swagger.
	 *
	 * @return array The filtered documentation.
	 */
	public function filter_event_request_body_definition( array $documentation ): array {
		$pro_properties = new PropertiesCollection();

		$pro_properties[] = ( new Boolean(
			'virtual',
			fn() => __( 'Whether the event is virtual', 'tribe-events-calendar-pro' ),
		) )->set_example( false );

		$pro_properties[] = (
			new Number(
				'lat',
				fn() => __( 'The latitude of the event', 'tribe-events-calendar-pro' ),
			)
		)->set_example( 37.774929 );

		$pro_properties[] = (
			new Number(
				'lng',
				fn() => __( 'The longitude of the event', 'tribe-events-calendar-pro' ),
			)
		)->set_example( -122.419416 );

		foreach ( $documentation['allOf'] as &$value ) {
			unset(
				$value['title'],
				$value['description'],
			);
		}

		$documentation['allOf'][] = [
			'type'        => 'object',
			'title'       => 'Pro: Event Request Body',
			'description' => __( 'The request body for the event endpoint, with Pro-specific properties', 'tribe-events-calendar-pro' ),
			'properties'  => $pro_properties,
		];

		return $documentation;
	}

	/**
	 * Filters the venue request body definition.
	 *
	 * @since 7.7.0
	 *
	 * @param array $documentation An associative PHP array in the format supported by Swagger.
	 *
	 * @return array The filtered documentation.
	 */
	public function filter_venue_request_body_definition( array $documentation ): array {
		$pro_properties = new PropertiesCollection();

		$pro_properties[] = (
			new Number(
				'lat',
				fn() => __( 'The latitude of the venue', 'tribe-events-calendar-pro' ),
			)
		)->set_example( 37.774929 );

		$pro_properties[] = (
			new Number(
				'lng',
				fn() => __( 'The longitude of the venue', 'tribe-events-calendar-pro' ),
			)
		)->set_example( -122.419416 );

		foreach ( $documentation['allOf'] as &$value ) {
			unset(
				$value['title'],
				$value['description'],
			);
		}

		$documentation['allOf'][] = [
			'type'        => 'object',
			'title'       => 'Pro: Venue Request Body',
			'description' => __( 'The request body for the venue endpoint, with Pro-specific properties', 'tribe-events-calendar-pro' ),
			'properties'  => $pro_properties,
		];

		return $documentation;
	}

	/**
	 * Filters the venue definition.
	 *
	 * @since 7.7.0
	 *
	 * @param array $documentation An associative PHP array in the format supported by Swagger.
	 *
	 * @return array The filtered documentation.
	 */
	public function filter_venue_definition( array $documentation ): array {
		$pro_properties = new PropertiesCollection();

		$pro_properties[] = (
			new Number(
				'latitude',
				fn() => __( 'The latitude of the venue', 'tribe-events-calendar-pro' ),
			)
		)->set_example( 37.774929 );

		$pro_properties[] = (
			new Number(
				'longitude',
				fn() => __( 'The longitude of the venue', 'tribe-events-calendar-pro' ),
			)
		)->set_example( -122.419416 );

		foreach ( $documentation['allOf'] as &$value ) {
			unset(
				$value['title'],
				$value['description'],
			);
		}

		$documentation['allOf'][] = [
			'type'        => 'object',
			'title'       => 'Pro: Venue',
			'description' => __( 'The venue definition, with Pro-specific properties', 'tribe-events-calendar-pro' ),
			'properties'  => $pro_properties,
		];

		return $documentation;
	}

	/**
	 * Filters the event definition.
	 *
	 * @since 7.7.0
	 *
	 * @param array $documentation An associative PHP array in the format supported by Swagger.
	 *
	 * @return array The filtered documentation.
	 */
	public function filter_event_definition( array $documentation ): array {
		$pro_properties = new PropertiesCollection();

		$pro_properties[] = (
			new Integer(
				'occurrence_id',
				fn() => __( 'The occurrence ID of the event, if the event is a recurring event occurrence', 'tribe-events-calendar-pro' ),
			)
		)->set_example( 0 );

		$pro_properties[] = (
			new Boolean(
				'is_virtual',
				fn() => __( 'Whether the event is virtual', 'tribe-events-calendar-pro' ),
			)
		)->set_example( false );

		$pro_properties[] = (
			new Text(
				'video_source',
				fn() => __( 'The video source for the virtual event', 'tribe-events-calendar-pro' ),
			)
		)->set_example( 'zoom' )->set_nullable( true );

		$pro_properties[] = (
			new URI(
				'virtual_url',
				fn() => __( 'The URL for the virtual event', 'tribe-events-calendar-pro' ),
			)
		)->set_example( 'https://zoom.us/j/123456789' )->set_nullable( true );

		foreach ( $documentation['allOf'] as &$value ) {
			unset(
				$value['title'],
				$value['description'],
			);
		}

		$documentation['allOf'][] = [
			'type'        => 'object',
			'title'       => 'Pro: Event',
			'description' => __( 'The event definition, with Pro-specific properties', 'tribe-events-calendar-pro' ),
			'properties'  => $pro_properties,
		];

		return $documentation;
	}

	/**
	 * Returns the geo-related properties for the events read request.
	 *
	 * @since 7.7.0
	 *
	 * @param string $singular_entity The singular entity name.
	 * @param string $plural_entity   The plural entity name.
	 *
	 * @return array The geo-related properties.
	 */
	protected function get_geo_related_props( string $singular_entity = '', string $plural_entity = '' ): array {
		if ( ! $singular_entity ) {
			$singular_entity = __( 'event', 'tribe-events-calendar-pro' );
			$plural_entity   = __( 'events', 'tribe-events-calendar-pro' );
		}

		$props = [
			( new Number(
				'geoloc_lat',
				// translators: %s is the singular entity name.
				fn() => sprintf( __( 'The latitude of the %s', 'tribe-events-calendar-pro' ), $singular_entity ),
			) )->set_example( 37.774929 ),
			( new Number(
				'geoloc_lng',
				// translators: %s is the singular entity name.
				fn() => sprintf( __( 'The longitude of the %s', 'tribe-events-calendar-pro' ), $singular_entity ),
			) )->set_example( -122.419416 ),
			( new Number(
				'distance',
				// translators: %s is the plural entity name.
				fn() => sprintf( __( 'The distance of the %s relative to the geolocation', 'tribe-events-calendar-pro' ), $plural_entity ),
			) )->set_example( 10 ),
			( new Boolean(
				'has_geoloc',
				// translators: %s is the plural entity name.
				fn() => sprintf( __( 'Filter by %s that have geolocation data', 'tribe-events-calendar-pro' ), $plural_entity ),
			) )->set_example( true ),
			( new Text(
				'near',
				// translators: %s is the plural entity name.
				fn() => sprintf( __( 'Filter by %s near an address', 'tribe-events-calendar-pro' ), $plural_entity ),
			) )->set_example( 'San Francisco, CA' ),
		];

		return $props;
	}

	/**
	 * Filters the event entity transformation to normalize occurrence IDs to real post IDs.
	 *
	 * @since 7.7.0
	 *
	 * @param array $entity The entity data.
	 * @param mixed $endpoint The endpoint instance.
	 *
	 * @return array The filtered entity data.
	 */
	public function filter_event_transform_entity( array $entity, $endpoint = null ): array {
		// Only process if we have an ID.
		if ( ! isset( $entity['id'] ) ) {
			return $entity;
		}

		$id = $entity['id'];

		$entity['occurrence_id'] = 0;

		// Check if this is a provisional ID.
		$provisional_post = tribe( Provisional_Post::class );
		if ( ! $provisional_post || ! method_exists( $provisional_post, 'is_provisional_post_id' ) ) {
			return $entity;
		}

		if ( ! $provisional_post->is_provisional_post_id( $id ) ) {
			// Not a provisional ID, return as-is.
			return $entity;
		}

		// This is a provisional ID for a recurring event occurrence.
		// Get the real post ID from the occurrence and replace the main ID.
		$occurrence_model = tribe( Occurrence::class );
		if ( $occurrence_model && method_exists( $occurrence_model, 'normalize_occurrence_post_id' ) ) {
			$real_post_id = $occurrence_model->normalize_occurrence_post_id( $id );
			// Store the occurrence ID for reference and replace the main ID with the real post ID.
			$entity['occurrence_id'] = $id;
			$entity['id']            = $real_post_id;
		}

		return $entity;
	}
}
