<?php
/**
 * Endpoints Controller class.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1;

use TEC\Common\REST\TEC\V1\Contracts\Definition_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Endpoint_Interface;
use TEC\Common\REST\TEC\V1\Contracts\Tag_Interface;
use TEC\Common\REST\TEC\V1\Abstracts\Endpoints_Controller;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Rule_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Rule_Request_Body_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Scope_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Config_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\Requirements_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Documentation\RuleToggle_Definition;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints\Rules;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints\Rule;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1\Endpoints\Posts_Rules;

/**
 * Endpoints Controller class.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\REST\TEC\V1
 */
class Endpoints extends Endpoints_Controller {
	/**
	 * Returns the endpoints to register.
	 *
	 * @since 6.9.0
	 *
	 * @return Endpoint_Interface[]
	 */
	public function get_endpoints(): array {
		return [
			Rules::class,
			Rule::class,
			Posts_Rules::class,
		];
	}

	/**
	 * Returns the tags to register.
	 *
	 * @since 6.9.0
	 *
	 * @return Tag_Interface[]
	 */
	public function get_tags(): array {
		return [];
	}

	/**
	 * Returns the definitions to register.
	 *
	 * @since 6.9.0
	 *
	 * @return Definition_Interface[]
	 */
	public function get_definitions(): array {
		return [
			Rule_Definition::class,
			Rule_Request_Body_Definition::class,
			Scope_Definition::class,
			Config_Definition::class,
			Requirements_Definition::class,
			RuleToggle_Definition::class,
		];
	}
}
