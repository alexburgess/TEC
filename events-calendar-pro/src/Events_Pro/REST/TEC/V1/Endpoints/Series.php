<?php
/**
 * Archive Events Pro series endpoint for the TEC REST API V1.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Endpoints
 */

declare( strict_types=1 );

namespace TEC\Events_Pro\REST\TEC\V1\Endpoints;

use TEC\Common\REST\TEC\V1\Abstracts\Post_Entity_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\Readable_Endpoint;
use TEC\Events_Pro\REST\TEC\V1\Tags\Events_Pro_Tag;
use TEC\Events_Pro\Custom_Tables\V1\Series\Post_Type as Series_Post_Type;
use TEC\Events_Pro\REST\TEC\V1\Traits\With_Series_ORM;
use TEC\Common\Rest\TEC\V1\Traits\Read_Archive_Response;
use TEC\Common\REST\TEC\V1\Collections\HeadersCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Documentation\OpenAPI_Schema;
use TEC\Common\REST\TEC\V1\Parameter_Types\Array_Of_Type;
use RuntimeException;
use TEC\Common\REST\TEC\V1\Parameter_Types\URI;
use TEC\Common\REST\TEC\V1\Collections\QueryArgumentCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;
use TEC\Common\REST\TEC\V1\Parameter_Types\Boolean;
use TEC\Events_Pro\REST\TEC\V1\Documentation\Series_Definition;
use TEC\Common\REST\TEC\V1\Endpoints\OpenApiDocs;
use TEC\Events_Pro\Custom_Tables\V1\Models\Series_Post_Type_Model;

/**
 * Archive Events Pro series endpoint for the TEC REST API V1.
 *
 * @since 7.7.11
 *
 * @package TEC\Events_Pro\REST\TEC\V1\Endpoints
 */
class Series extends Post_Entity_Endpoint implements Readable_Endpoint {
	use With_Series_ORM;
	use Read_Archive_Response;

	/**
	 * Formats a collection of series into a collection of series entities.
	 *
	 * @since 7.7.11
	 *
	 * @param array $posts The posts to format.
	 *
	 * @return array
	 */
	protected function format_entity_collection( array $posts ): array {
		return array_map( [ $this, 'get_formatted_entity' ], $posts );
	}

	/**
	 * Returns the schema for the endpoint.
	 *
	 * @since 7.7.11
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'series',
			'type'    => 'array',
			'items'   => [
				'$ref' => tribe( OpenApiDocs::class )->get_url() . '#/components/schemas/Series',
			],
		];
	}

	/**
	 * Returns the Series model class.
	 *
	 * @since 7.7.11
	 *
	 * @return string
	 */
	public function get_model_class(): string {
		return Series_Post_Type_Model::class;
	}

	/**
	 * Returns the base path of the endpoint.
	 *
	 * @since 7.7.11
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return '/series';
	}

	/**
	 * Returns the post type of the endpoint.
	 *
	 * @since 7.7.11
	 *
	 * @return string
	 */
	public function get_post_type(): string {
		return Series_Post_Type::POSTTYPE;
	}

	/**
	 * @inheritDoc
	 */
	public function read_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Retrieve Series', 'tribe-events-calendar-pro' ),
			fn() => __( 'Returns a list of series', 'tribe-events-calendar-pro' ),
			$this->get_operation_id( 'read' ),
			$this->get_tags(),
			null,
			$this->read_params()
		);

		$headers_collection = new HeadersCollection();

		$headers_collection[] = new Positive_Integer(
			'X-WP-Total',
			fn() => __( 'The total number of series matching the request.', 'tribe-events-calendar-pro' ),
			null,
			null,
			null,
			true
		);

		$headers_collection[] = new Positive_Integer(
			'X-WP-TotalPages',
			fn() => __( 'The total number of pages for the request.', 'tribe-events-calendar-pro' ),
			null,
			null,
			null,
			true
		);

		$headers_collection[] = new Array_Of_Type(
			'Link',
			fn() => __(
				'RFC 5988 Link header for pagination. Contains navigation links with relationships:
				`rel="next"` for the next page (if not on last page),
				`rel="prev"` for the previous page (if not on first page).
				Header is omitted entirely if there\'s only one page',
				'tribe-events-calendar-pro'
			),
			URI::class,
		);

		$response = new Array_Of_Type(
			'Series',
			null,
			Series_Definition::class,
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the list of series', 'tribe-events-calendar-pro' ),
			$headers_collection,
			'application/json',
			$response,
		);

		$schema->add_response(
			400,
			fn() => __( 'A required parameter is missing or an input parameter is in the wrong format', 'tribe-events-calendar-pro' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The requested page was not found', 'tribe-events-calendar-pro' ),
		);

		return $schema;
	}

	/**
	 * Returns the arguments for the read request.
	 *
	 * @since 7.7.11
	 *
	 * @return QueryArgumentCollection
	 */
	public function read_params(): QueryArgumentCollection {
		$collection = new QueryArgumentCollection();

		$collection[] = new Positive_Integer(
			'page',
			fn() => __( 'The collection page number.', 'tribe-events-calendar-pro' ),
			1,
			1
		);

		$collection[] = new Array_Of_Type(
			'event_post_id',
			fn() => __( 'Limit result set to series assigned to specific event post IDs.', 'tribe-events-calendar-pro' ),
			Positive_Integer::class,
		);

		$collection[] = new Positive_Integer(
			'per_page',
			fn() => __( 'Maximum number of items to be returned in result set.', 'tribe-events-calendar-pro' ),
			$this->get_default_posts_per_page(),
			1,
			100,
		);

		$collection[] = new Text(
			'search',
			fn() => __( 'Limit results to those matching a string.', 'tribe-events-calendar-pro' ),
		);

		$collection[] = new Array_Of_Type(
			'status',
			fn() => __( 'Limit result set to events with specific status.', 'tribe-events-calendar-pro' ),
			Text::class,
			self::ALLOWED_STATUS,
			[ 'publish' ],
			fn( $value ) => $this->validate_status( $value )
		);

		$collection[] = new Boolean(
			'ticketed',
			fn() => __( 'Limit result set to events with tickets.', 'tribe-events-calendar-pro' ),
		);

		$collection[] = new Text(
			'orderby',
			fn() => __( 'Sort collection by event attribute.', 'tribe-events-calendar-pro' ),
			'event_date',
			[ 'date', 'event_date', 'title', 'menu_order', 'modified' ],
		);

		$collection[] = new Text(
			'order',
			fn() => __( 'Order sort attribute ascending or descending.', 'tribe-events-calendar-pro' ),
			'ASC',
			[ 'ASC', 'DESC' ],
		);

		/**
		 * Filters the arguments for the series read request.
		 *
		 * @since 7.7.11
		 *
		 * @param QueryArgumentCollection $collection The collection of arguments.
		 * @param Series                  $this       The series endpoint.
		 */
		return apply_filters( 'tec_events_pro_rest_v1_series_read_params', $collection, $this );
	}

	/**
	 * Returns the operation ID for the endpoint.
	 *
	 * @since 7.7.11
	 *
	 * @param string $operation The operation to get the ID for.
	 *
	 * @return string
	 *
	 * @throws RuntimeException If the operation is invalid.
	 */
	public function get_operation_id( string $operation ): string {
		switch ( $operation ) {
			case 'read':
				return 'getSeries';
			case 'create':
				return 'createSeries';
		}

		throw new RuntimeException( 'Invalid operation.' );
	}

	/**
	 * Returns the tags for the endpoint.
	 *
	 * @since 7.7.11
	 *
	 * @return array
	 */
	public function get_tags(): array {
		return [ tribe( Events_Pro_Tag::class ) ];
	}
}
