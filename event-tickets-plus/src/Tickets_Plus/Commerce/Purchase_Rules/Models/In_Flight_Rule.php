<?php
/**
 * The in-flight rule model.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Models;
 */

declare( strict_types=1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Models;

use DateTimeInterface;

// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid

/**
 * Class In_Flight_Rule.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Models;
 */
class In_Flight_Rule {
	/**
	 * The rule.
	 *
	 * @since 6.9.0
	 *
	 * @var Rule
	 */
	private Rule $rule;

	/**
	 * Constructs the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @param Rule $rule The rule.
	 */
	public function __construct( Rule $rule ) {
		$this->rule = $rule;
	}

	/**
	 * Constructs the in-flight rule from data.
	 *
	 * @since 6.9.0
	 *
	 * @param array $data The data.
	 *
	 * @return ?self The in-flight rule.
	 */
	public static function fromData( array $data ): ?self {
		$rule = Rule::find( $data['id'] );
		return $rule ? new self( $rule ) : null;
	}

	/**
	 * Gets the ID of the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @return int The ID.
	 */
	public function get_id(): int {
		return $this->rule->getPrimaryValue();
	}

	/**
	 * Gets the name of the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @return string The name.
	 */
	public function get_name(): string {
		return $this->rule->get_name();
	}

	/**
	 * Gets the type of the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @return string The type.
	 */
	public function get_type(): string {
		return $this->rule->get_type();
	}

	/**
	 * Gets the config of the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @return array The config.
	 */
	public function get_config(): array {
		return $this->rule->get_config();
	}

	/**
	 * Gets the scope of the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @return ?array The scope.
	 */
	public function get_scope(): ?array {
		return $this->rule->get_scope();
	}

	/**
	 * Gets the status of the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @return string The status.
	 */
	public function get_status(): string {
		return $this->rule->get_status();
	}

	/**
	 * Gets the updated at date of the in-flight rule.
	 *
	 * @since 6.9.0
	 *
	 * @return DateTimeInterface The updated at date.
	 */
	public function get_updated_at(): DateTimeInterface {
		return $this->rule->get_updated_at();
	}

	/**
	 * Gets the in-flight rule as an array.
	 *
	 * @since 6.9.0
	 *
	 * @return array The in-flight rule as an array.
	 */
	public function toArray(): array {
		return $this->rule->toArray();
	}
}
