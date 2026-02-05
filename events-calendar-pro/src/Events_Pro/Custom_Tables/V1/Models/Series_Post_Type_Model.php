<?php
/**
 * Models a Series.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\Custom_Tables\V1\Models
 */

namespace TEC\Events_Pro\Custom_Tables\V1\Models;

use Tribe\Models\Post_Types\Base;
use Tribe\Utils\Lazy_String;
use Tribe\Utils\Post_Thumbnail;

/**
 * Class Series_Post_Type_Model
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\Custom_Tables\V1\Models
 */
class Series_Post_Type_Model extends Base {
	/**
	 * {@inheritDoc}
	 */
	protected function build_properties( $filter ) {
		try {
			$cache_this = $this->get_caching_callback( $filter );
			$post_id    = $this->post->ID;
			$sticky     = get_post_field( 'menu_order', $post_id ) === - 1;

			$properties = [
				'sticky'    => $sticky,
				'excerpt'   => (
					new Lazy_String(
						static function () use ( $post_id ) {
							return tribe_events_get_the_excerpt( $post_id, wp_kses_allowed_html( 'post' ), true );
						},
						false
					)
				)->on_resolve( $cache_this ),
				'thumbnail' => ( new Post_Thumbnail( $post_id ) )->on_resolve( $cache_this ),
				'permalink' => ( new Lazy_String(
					static function () use ( $post_id ) {
						$permalink = get_permalink( $post_id );
						return (string) ( empty( $permalink ) ? '' : $permalink );
					},
					false
				) )->on_resolve( $cache_this ),
				'title'     => ( new Lazy_String(
					static function () use ( $post_id ) {
						$title = get_the_title( $post_id );
						return (string) ( empty( $title ) ? '' : $title );
					},
					false
				) )->on_resolve( $cache_this ),
			];
		} catch ( \Exception $e ) {
			return [];
		}

		return $properties;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_cache_slug() {
		return 'series';
	}

	/**
	 * Returns the properties to add to the event.
	 *
	 * @since 7.7.11
	 *
	 * @return array<string,bool>
	 */
	public static function get_properties_to_add(): array {
		/**
		 * Filters the properties to add to the event.
		 *
		 * @since 7.7.11
		 *
		 * @param array<string,bool> $properties The properties to add to the event.
		 *
		 * @return array<string,bool>
		 */
		return (array) apply_filters(
			'tec_rest_series_properties_to_add',
			[
				'sticky'    => true,
				'permalink' => true,
			]
		);
	}
}
