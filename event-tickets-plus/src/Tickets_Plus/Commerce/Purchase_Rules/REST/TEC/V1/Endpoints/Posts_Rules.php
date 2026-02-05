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
use TEC\Common\REST\TEC\V1\Contracts\Creatable_Endpoint;
use TEC\Tickets_Plus\REST\TEC\V1\Tags\Tickets_Plus_Tag;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Traits\With_Rules_ORM;
use RuntimeException;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule as Rule_Model;
use TEC\Common\REST\TEC\V1\Collections\PathArgumentCollection;
use TEC\Common\REST\TEC\V1\Documentation\OpenAPI_Schema;
use TEC\Common\REST\TEC\V1\Endpoints\OpenApiDocs;
use TEC\Common\REST\TEC\V1\Collections\RequestBodyCollection;
use TEC\Common\REST\TEC\V1\Parameter_Types\Positive_Integer;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\RuleToggle_Definition;
use TEC\Common\REST\TEC\V1\Parameter_Types\Array_Of_Type;
use WP_REST_Response;

/**
 * Archive purchase rules endpoint for the TEC REST API V1.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints
 */
class Posts_Rules extends Custom_Entity_Endpoint implements Creatable_Endpoint {
	use With_Rules_ORM;

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
		return '/purchase-rules/posts/%s';
	}

	/**
	 * Creates a new rule toggle for a post.
	 *
	 * @since 6.9.0
	 *
	 * @param array $params The parameters for the request.
	 *
	 * @return WP_REST_Response
	 */
	public function create( array $params = [] ): WP_REST_Response {
		$post_id = (int) ( $params['id'] ?? null );

		if ( ! $post_id ) {
			return new WP_REST_Response(
				[ 'error' => __( 'The post ID is required.', 'event-tickets-plus' ) ],
				400
			);
		}

		if ( ! in_array( get_post_type( $post_id ), (array) tribe_get_option( 'ticket-enabled-post-types', [] ), true ) ) {
			return new WP_REST_Response(
				[ 'error' => __( 'The post is not a ticket enabled post.', 'event-tickets-plus' ) ],
				400
			);
		}

		$toggles = $params['toggles'] ?? [];

		if ( empty( $toggles ) ) {
			return new WP_REST_Response(
				[ 'error' => __( 'The toggles specifications are required.', 'event-tickets-plus' ) ],
				400
			);
		}

		foreach ( $toggles as $toggle ) {
			$rule = Rule_Model::find( $toggle['rule_id'] );

			if ( ! $rule ) {
				return new WP_REST_Response(
					[
						'error' => sprintf(
							/* translators: %d: The rule ID. */
							__( 'The rule with ID %d does not exist.', 'event-tickets-plus' ),
							$toggle['rule_id']
						),
					],
					404
				);
			}

			Rule_Model::manage_rule_relationship_with_post( $rule->getPrimaryValue(), $post_id, $toggle['enabled'] );
		}

		return new WP_REST_Response(
			[],
			201
		);
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
			fn() => __( 'The ID of the post', 'event-tickets-plus' ),
		);

		return $collection;
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
				'$ref' => tribe( OpenApiDocs::class )->get_url() . '#/components/schemas/RuleToggle',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function create_params(): RequestBodyCollection {
		$collection = new RequestBodyCollection();

		$definition   = new RuleToggle_Definition();
		$collection[] = new Array_Of_Type( 'toggles', fn() => __( 'The rule toggles to create', 'event-tickets-plus' ), get_class( $definition ) );

		$example = $definition->get_example();

		return $collection
			->set_description_provider( fn() => __( 'The rule toggles to create.', 'event-tickets-plus' ) )
			->set_required( true )
			->set_example( [ $example, $example ] );
	}

	/**
	 * @inheritDoc
	 */
	public function create_schema(): OpenAPI_Schema {
		$schema = new OpenAPI_Schema(
			fn() => __( 'Create a RuleToggle', 'event-tickets-plus' ),
			fn() => __( 'Create a new rule', 'event-tickets-plus' ),
			$this->get_operation_id( 'create' ),
			$this->get_tags(),
			$this->get_path_parameters(),
			null,
			$this->create_params(),
			true
		);

		$schema->add_response(
			201,
			fn() => __( 'Your request was processed successfully.', 'event-tickets-plus' ),
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
			case 'create':
				return 'LinkRulesToPost';
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
