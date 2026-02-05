<?php
/**
 * Archive purchase rules endpoint for the TEC REST API V1.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints;

use TEC\Common\REST\TEC\V1\Abstracts\Custom_Entity_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\Readable_Endpoint;
use TEC\Common\REST\TEC\V1\Contracts\Creatable_Endpoint;
use TEC\Tickets_Plus\REST\TEC\V1\Tags\Tickets_Plus_Tag;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Traits\With_Rules_ORM;
use RuntimeException;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule as Rule_Model;
use TEC\Common\REST\TEC\V1\Traits\Read_Custom_Archive_Response;
use TEC\Common\REST\TEC\V1\Traits\Create_Entity_Response;
use TEC\Common\REST\TEC\V1\Documentation\OpenAPI_Schema;
use TEC\Common\REST\TEC\V1\Endpoints\OpenApiDocs;
use TEC\Common\REST\TEC\V1\Collections\QueryArgumentCollection;
use TEC\Common\REST\TEC\V1\Collections\HeadersCollection;
use TEC\Common\REST\TEC\V1\Collections\RequestBodyCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Common\REST\TEC\V1\Parameter_Types\Text;
use TEC\Common\REST\TEC\V1\Parameter_Types\Definition_Parameter;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Rule_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Rule_Request_Body_Definition;
use TEC\Common\REST\TEC\V1\Parameter_Types\Array_Of_Type;
use TEC\Common\REST\TEC\V1\Parameter_Types\URI;

/**
 * Archive purchase rules endpoint for the TEC REST API V1.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints
 */
class Rules extends Custom_Entity_Endpoint implements Readable_Endpoint, Creatable_Endpoint {
	use With_Rules_ORM;
	use Read_Custom_Archive_Response;
	use Create_Entity_Response;

	/**
	 * Returns the model class.
	 *
	 * @since 6.9.0
	 *
	 * @return string
	 */
	public function get_model_class(): string {
		return Rule_Model::class;
	}

	/**
	 * Determines if this is an experimental endpoint.
	 *
	 * @since 6.9.0
	 *
	 * @return bool
	 */
	public function is_experimental(): bool {
		return false;
	}

	/**
	 * Returns the base path of the endpoint.
	 *
	 * @since 6.9.0
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return '/purchase-rules';
	}

	/**
	 * Returns the schema for the endpoint.
	 *
	 * @since 6.9.0
	 *
	 * @return array
	 */
	public function get_schema(): array {
		return [
			'$schema' => 'http://json-schema.org/draft-04/schema#',
			'title'   => 'rules',
			'type'    => 'array',
			'items'   => [
				'$ref' => tribe( OpenApiDocs::class )->get_url() . '#/components/schemas/Rule',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function read_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Retrieve Rules', 'event-tickets-plus' ),
			fn() => __( 'Returns a list of rules', 'event-tickets-plus' ),
			$this->get_operation_id( 'read' ),
			$this->get_tags(),
			null,
			$this->read_params()
		);

		$headers_collection = new HeadersCollection();

		$headers_collection[] = new Positive_Integer(
			'X-WP-Total',
			fn() => __( 'The total number of rules matching the request.', 'event-tickets-plus' ),
			null,
			null,
			null,
			true
		);

		$headers_collection[] = new Positive_Integer(
			'X-WP-TotalPages',
			fn() => __( 'The total number of pages for the request.', 'event-tickets-plus' ),
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
				'event-tickets-plus'
			),
			URI::class,
		);

		$response = new Array_Of_Type(
			'Rule',
			null,
			Rule_Definition::class,
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the list of rules', 'event-tickets-plus' ),
			$headers_collection,
			'application/json',
			$response,
		);

		$schema->add_response(
			400,
			fn() => __( 'A required parameter is missing or an input parameter is in the wrong format', 'event-tickets-plus' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The requested page was not found', 'event-tickets-plus' ),
		);

		return $schema;
	}

	/**
	 * Returns the arguments for the read request.
	 *
	 * @since 6.9.0
	 *
	 * @return QueryArgumentCollection
	 */
	public function read_params(): QueryArgumentCollection {
		$collection = new QueryArgumentCollection();

		$collection[] = new Positive_Integer(
			'page',
			fn() => __( 'The collection page number.', 'event-tickets-plus' ),
			1,
			1
		);

		$collection[] = new Positive_Integer(
			'per_page',
			fn() => __( 'Maximum number of items to be returned in result set.', 'event-tickets-plus' ),
			$this->get_default_posts_per_page(),
			1,
			100,
		);

		$collection[] = new Text(
			'search',
			fn() => __( 'Limit results to those matching a string.', 'event-tickets-plus' ),
		);

		$collection[] = new Positive_Integer(
			'event',
			fn() => __( 'Limit result set to rules assigned matching an event.', 'event-tickets-plus' ),
		);

		$collection[] = new Text(
			'status',
			fn() => __( 'Sort collection by rule status.', 'event-tickets-plus' ),
			Rule_Model::ACTIVE_STATUS,
			array_merge( Rule_Model::ALL_STATUS, [ 'any' ] )
		);

		$collection[] = new Text(
			'type',
			fn() => __( 'Sort collection by rule type.', 'event-tickets-plus' ),
			Rule_Model::ORDER_DISCOUNT_TYPE,
			array_merge( Rule_Model::ALL_TYPES, [ 'any' ] )
		);

		return $collection;
	}

	/**
	 * @inheritDoc
	 */
	public function create_params(): RequestBodyCollection {
		$collection = new RequestBodyCollection();

		$definition   = new Rule_Request_Body_Definition();
		$collection[] = new Definition_Parameter( $definition );

		return $collection
			->set_description_provider( fn() => __( 'The rule data to create.', 'event-tickets-plus' ) )
			->set_required( true )
			->set_example( $definition->get_example() );
	}

	/**
	 * @inheritDoc
	 */
	public function create_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Create a Rule', 'event-tickets-plus' ),
			fn() => __( 'Create a new rule', 'event-tickets-plus' ),
			$this->get_operation_id( 'create' ),
			$this->get_tags(),
			null,
			null,
			$this->create_params(),
			true
		);

		$response = new Definition_Parameter(
			new Rule_Definition(),
			'rule'
		);

		$schema->add_response(
			201,
			fn() => __( 'Returns the created rule', 'event-tickets-plus' ),
			null,
			'application/json',
			$response,
		);

		$schema->add_response(
			400,
			fn() => __( 'A required parameter is missing or an input parameter is in the wrong format', 'event-tickets-plus' ),
		);

		$schema->add_response(
			401,
			fn() => __( 'The request was not authorized', 'event-tickets-plus' ),
		);

		$schema->add_response(
			500,
			fn() => __( 'Failed to create the rule', 'event-tickets-plus' ),
		);

		return $schema;
	}

	/**
	 * Returns the operation ID for the endpoint.
	 *
	 * @since 6.9.0
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
				return 'getPurchaseRules';
			case 'create':
				return 'createPurchaseRule';
		}

		throw new RuntimeException( 'Invalid operation.' );
	}

	/**
	 * Returns the tags for the endpoint.
	 *
	 * @since 6.9.0
	 *
	 * @return array
	 */
	public function get_tags(): array {
		return [ tribe( Tickets_Plus_Tag::class ) ];
	}
}
