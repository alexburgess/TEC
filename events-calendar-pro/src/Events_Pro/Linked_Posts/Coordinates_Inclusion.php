<?php
/**
 * Adds coordinates (latitude and longitude) to Venue linked post objects.
 *
 * @since 7.7.0
 *
 * @package TEC\Events_Pro\Linked_Posts
 */

namespace TEC\Events_Pro\Linked_Posts;

use WP_Post;

/**
 * Class Coordinates_Inclusion
 *
 * @since 7.7.0
 */
class Coordinates_Inclusion {

	/**
	 * Latitude property slug.
	 *
	 * @since 7.7.0
	 *
	 * @var string
	 */
	public const LATITUDE_SLUG = 'latitude';

	/**
	 * Longitude property slug.
	 *
	 * @since 7.7.0
	 *
	 * @var string
	 */
	public const LONGITUDE_SLUG = 'longitude';

	/**
	 * Include the coordinates in a Venue object.
	 *
	 * @since 7.7.0
	 *
	 * @param WP_Post $post The venue post object, decorated with a set of custom properties.
	 *
	 * @return WP_Post
	 */
	public function include_coordinates( WP_Post $post ): WP_Post {
		$latitude  = null;
		$longitude = null;

		if ( ! function_exists( 'tribe_get_coordinates' ) ) {
			$post->{self::LATITUDE_SLUG}  = $latitude;
			$post->{self::LONGITUDE_SLUG} = $longitude;

			return $post;
		}

		$coordinates = (array) tribe_get_coordinates( $post->ID );

		if ( ! empty( $coordinates['lat'] ) ) {
			$latitude = (float) $coordinates['lat'];
		}

		if ( ! empty( $coordinates['lng'] ) ) {
			$longitude = (float) $coordinates['lng'];
		}

		$post->{self::LATITUDE_SLUG}  = $latitude;
		$post->{self::LONGITUDE_SLUG} = $longitude;

		return $post;
	}

	/**
	 * Adds latitude and longitude to the REST Venue properties to include.
	 *
	 * @since 7.7.0
	 *
	 * @param array<string,bool> $properties The properties to add to the venue.
	 *
	 * @return array<string,bool> The updated properties.
	 */
	public function filter_rest_properties( array $properties ): array {
		$properties[ self::LATITUDE_SLUG ]  = true;
		$properties[ self::LONGITUDE_SLUG ] = true;

		return $properties;
	}
}
