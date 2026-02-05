<?php
/**
 * Single purchase rule endpoint for the TEC REST API V1.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints;

use TEC\Common\REST\TEC\V1\Abstracts\Custom_Entity_Endpoint;
use TEC\Tickets_Plus\REST\TEC\V1\Tags\Tickets_Plus_Tag;
use TEC\Common\REST\TEC\V1\Contracts\RUD_Endpoint;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule as Rule_Model;
use TEC\Common\REST\TEC\V1\Traits\Read_Custom_Entity_Response;
use TEC\Common\REST\TEC\V1\Traits\Update_Custom_Entity_Response;
use TEC\Common\REST\TEC\V1\Traits\Delete_Custom_Entity_Response;
use TEC\Common\REST\TEC\V1\Endpoints\OpenApiDocs;
use TEC\Common\REST\TEC\V1\Collections\QueryArgumentCollection;
use TEC\Common\REST\TEC\V1\Collections\PathArgumentCollection;
use TEC\Common\REST\TEC\V1\Documentation\OpenAPI_Schema;
use TEC\Common\REST\TEC\V1\Collections\RequestBodyCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Rule_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Rule_Request_Body_Definition;
use TEC\Common\REST\TEC\V1\Parameter_Types\Definition_Parameter;
use RuntimeException;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Traits\With_Rules_ORM;

/**
 * Single purchase rule endpoint for the TEC REST API V1.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints
 */
class Rule extends Custom_Entity_Endpoint implements RUD_Endpoint {
	use Read_Custom_Entity_Response;
	use Update_Custom_Entity_Response;
	use Delete_Custom_Entity_Response;
	use With_Rules_ORM;

	/**
	 * Returns the base path for the endpoint.
	 *
	 * @since 6.9.0
	 *
	 * @return string
	 */
	public function get_base_path(): string {
		return '/purchase-rules/%s';
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
	 * Returns the path parameters for the endpoint.
	 *
	 * @since 6.9.0
	 *
	 * @return PathArgumentCollection
	 */
	public function get_path_parameters(): PathArgumentCollection {
		$collection = new PathArgumentCollection();

		$collection[] = new Positive_Integer(
			'id',
			fn() => __( 'The ID of the rule', 'event-tickets-plus' ),
		);

		return $collection;
	}

	/**
	 * Returns the arguments for the read request.
	 *
	 * @since 6.9.0
	 *
	 * @return QueryArgumentCollection
	 */
	public function read_params(): QueryArgumentCollection {
		return new QueryArgumentCollection();
	}

	/**
	 * @inheritDoc
	 */
	public function read_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Retrieve a Rule', 'event-tickets-plus' ),
			fn() => __( 'Retrieve a rule by ID', 'event-tickets-plus' ),
			$this->get_operation_id( 'read' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			$this->read_params()
		);

		$response = new Definition_Parameter(
			new Rule_Definition(),
			'rule'
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the rule', 'event-tickets-plus' ),
			null,
			'application/json',
			$response,
		);

		$schema->add_response(
			400,
			fn() => __( 'The rule ID is invalid', 'event-tickets-plus' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The rule does not exist', 'event-tickets-plus' ),
		);

		return $schema;
	}

	/**
	 * @inheritDoc
	 */
	public function update_params(): RequestBodyCollection {
		$definition = new Rule_Request_Body_Definition();

		$collection = new RequestBodyCollection();

		$collection[] = new Definition_Parameter( $definition );

		return $collection
			->set_description_provider( fn() => __( 'The rule data to update.', 'event-tickets-plus' ) )
			->set_required( true )
			->set_example( $definition->get_example() );
	}

	/**
	 * @inheritDoc
	 */
	public function update_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Update a Rule', 'event-tickets-plus' ),
			fn() => __( 'Update a rule by ID', 'event-tickets-plus' ),
			$this->get_operation_id( 'update' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			null,
			$this->update_params(),
			true
		);

		$response = new Definition_Parameter(
			new Rule_Definition(),
			'rule'
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the updated rule', 'event-tickets-plus' ),
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
			404,
			fn() => __( 'The rule does not exist', 'event-tickets-plus' ),
		);

		$schema->add_response(
			500,
			fn() => __( 'Failed to update the rule', 'event-tickets-plus' ),
		);

		return $schema;
	}

	/**
	 * @inheritDoc
	 */
	public function delete_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Delete a Rule', 'event-tickets-plus' ),
			fn() => __( 'Delete a rule by ID', 'event-tickets-plus' ),
			$this->get_operation_id( 'delete' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			$this->delete_params(),
			null,
			true
		);

		$response = new Definition_Parameter(
			new Rule_Definition(),
			'rule'
		);

		$schema->add_response(
			200,
			fn() => __( 'Returns the deleted rule', 'event-tickets-plus' ),
			null,
			'application/json',
			$response,
		);

		$schema->add_response(
			401,
			fn() => __( 'The request was not authorized', 'event-tickets-plus' ),
		);

		$schema->add_response(
			404,
			fn() => __( 'The rule does not exist', 'event-tickets-plus' ),
		);

		$schema->add_response(
			410,
			fn() => __( 'The rule has already been deleted', 'event-tickets-plus' ),
		);

		$schema->add_response(
			500,
			fn() => __( 'Failed to delete the rule', 'event-tickets-plus' ),
		);

		$schema->add_response(
			501,
			fn() => __( 'The rule does not support deletion. Set force=true to delete', 'event-tickets-plus' ),
		);

		return $schema;
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
			'title'   => 'rule',
			'type'    => 'object',
			'$ref'    => tribe( OpenApiDocs::class )->get_url() . '#/components/schemas/Rule',
		];
	}

	/**
	 * Returns the model class for the endpoint.
	 *
	 * @since 6.9.0
	 *
	 * @return string
	 */
	public function get_model_class(): string {
		return Rule_Model::class;
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
				return 'readPurchaseRule';
			case 'update':
				return 'updatePurchaseRule';
			case 'delete':
				return 'deletePurchaseRule';
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
