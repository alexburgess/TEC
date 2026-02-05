<?php
/**
 * Exception when a purchase restriction is not met.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Exceptions;
 */

declare( strict_vars = 1 );

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Exceptions;

use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\In_Flight_Rule;
use Exception;

// phpcs:disable StellarWP.Classes.ValidClassName.NotSnakeCase

/**
 * Class RestrictionNotMetException.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules\Exceptions;
 */
class RestrictionNotMetException extends Exception {
	/**
	 * The post ID.
	 *
	 * @since 6.9.0
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * The rule.
	 *
	 * @since 6.9.0
	 *
	 * @var In_Flight_Rule
	 */
	private In_Flight_Rule $rule;

	/**
	 * Set the post ID.
	 *
	 * @since 6.9.0
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return self
	 */
	public function set_post_id( int $post_id ): self {
		$this->post_id = $post_id;
		return $this;
	}

	/**
	 * Get the post ID.
	 *
	 * @since 6.9.0
	 *
	 * @return int
	 */
	public function get_post_id(): int {
		return $this->post_id ?? 0;
	}

	/**
	 * Set the rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule The rule.
	 *
	 * @return self
	 */
	public function set_rule( In_Flight_Rule $rule ): self {
		$this->rule = $rule;
		return $this;
	}

	/**
	 * Get the rule.
	 *
	 * @since 6.9.0
	 *
	 * @return In_Flight_Rule
	 */
	public function get_rule(): In_Flight_Rule {
		return $this->rule;
	}

	/**
	 * Create a new RestrictionNotMetException from a rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule The rule.
	 *
	 * @return RestrictionNotMetException
	 */
	public static function fromRule( In_Flight_Rule $rule ): self {
		$message = $rule->get_config()['message'] ?? '';

		if ( empty( $message ) ) {
			$message = __( 'This purchase does not meet the requirements for this event.', 'event-tickets-plus' );
		}

		return ( new self( $message ) )->set_rule( $rule );
	}
}
