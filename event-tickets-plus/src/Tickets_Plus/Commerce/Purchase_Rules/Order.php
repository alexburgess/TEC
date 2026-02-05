<?php
/**
 * The purchase rules order controller.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Ticket;
use Tribe__Template as Base_Template;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use TEC\Tickets\Commerce\Cart;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\In_Flight_Rule;
use RuntimeException;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Exceptions\RestrictionNotMetException;
use WP_Post;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Values\Currency_Value;
use TEC\Tickets\Commerce\Values\Integer_Value;
use TEC\Tickets\Commerce\Gateways\Square\Syncs\Remote_Objects;

/**
 * Class Order.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules
 */
class Order extends Controller_Contract {
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_plus_purchase_rules_order_registered';

	/**
	 * The discount rules in the cart.
	 *
	 * @since 6.9.0
	 *
	 * @var array<int, In_Flight_Rule[]>
	 */
	protected array $discount_rules_in_cart = [];

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tec_tickets_commerce_checkout_page_parse_request', [ $this, 'ensure_rules_are_honored_on_checkout' ] );
		add_filter( 'tec_tickets_commerce_cart_add_full_item_params', [ $this, 'add_purchase_rules_discount_data_to_cart' ], 10, 3 );
		add_action( 'tec_tickets_commerce_checkout_cart_before_footer_quantity', [ $this, 'display_purchase_rules_discount_section' ], 20, 3 );
		add_filter( 'tec_tickets_commerce_create_order_from_cart_items', [ $this, 'add_discount_items_to_order' ] );
		add_filter( 'tribe_template_before_include_html:tickets/admin-views/commerce/orders/single/order-items-extras', [ $this, 'show_purchase_rules_discount_section_in_admin_single_order' ], 10, 4 );
		add_filter( 'tribe_template_before_include_html:tickets/emails/template-parts/body/order/ticket-totals/fees-row', [ $this, 'show_purchase_rules_discount_section_in_emails' ], 10, 4 );
		add_filter( 'tec_tickets_commerce_square_order_payload', [ $this, 'add_purchase_rules_discount_data_to_square_order' ], 10, 2 );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tec_tickets_commerce_checkout_page_parse_request', [ $this, 'ensure_rules_are_honored_on_checkout' ] );
		remove_filter( 'tec_tickets_commerce_cart_add_full_item_params', [ $this, 'add_purchase_rules_discount_data_to_cart' ] );
		remove_action( 'tec_tickets_commerce_checkout_cart_before_footer_quantity', [ $this, 'display_purchase_rules_discount_section' ], 20 );
		remove_filter( 'tec_tickets_commerce_create_order_from_cart_items', [ $this, 'add_discount_items_to_order' ] );
		remove_filter( 'tribe_template_before_include_html:tickets/admin-views/commerce/orders/single/order-items-extras', [ $this, 'show_purchase_rules_discount_section_in_admin_single_order' ], 10, 4 );
		remove_filter( 'tribe_template_before_include_html:tickets/emails/template-parts/body/order/ticket-totals/fees-row', [ $this, 'show_purchase_rules_discount_section_in_emails' ], 10, 4 );
		remove_filter( 'tec_tickets_commerce_square_order_payload', [ $this, 'add_purchase_rules_discount_data_to_square_order' ], 10, 2 );
	}

	/**
	 * Adds the purchase rules discount data to the square order payload.
	 *
	 * @since 6.9.0
	 *
	 * @param array   $payload The payload.
	 * @param WP_Post $order   The order.
	 *
	 * @return array The payload.
	 */
	public function add_purchase_rules_discount_data_to_square_order( array $payload, WP_Post $order ): array {
		$discounts = array_filter( $order->items ?? [], fn( $item ) => ! empty( $item['type'] ) && 'discount' === $item['type'] );

		if ( empty( $discounts ) ) {
			return $payload;
		}

		if ( ! isset( $payload['discounts'] ) || ! is_array( $payload['discounts'] ) ) {
			$payload['discounts'] = [];
		}

		$remote_objects = tribe( Remote_Objects::class );

		foreach ( $discounts as $discount ) {
			$discount = $remote_objects->get_discount( $discount, $order );

			if ( empty( $discount ) ) {
				continue;
			}

			$payload['discounts'][] = $discount;
		}

		return $payload;
	}

	/**
	 * Shows the purchase rules discount section in emails.
	 *
	 * @since 6.9.0
	 *
	 * @param string        $html The HTML.
	 * @param string        $file The file.
	 * @param array         $name The name.
	 * @param Base_Template $template The template.
	 *
	 * @return string The HTML.
	 */
	public function show_purchase_rules_discount_section_in_emails( string $html, string $file, array $name, Base_Template $template ): string {
		$context = $template->get_values();

		if ( empty( $context['order'] ) ) {
			return $html;
		}

		$order = $context['order'];

		$discounts = array_filter( $order->items ?? [], fn( $item ) => ! empty( $item['type'] ) && 'discount' === $item['type'] );

		if ( empty( $discounts ) ) {
			return $html;
		}

		$total_discount = Precision_Value::sum(
			...array_map(
				fn( $discount ) => new Precision_Value( $discount['sub_total'] ),
				array_filter(
					$discounts,
					fn( $discount ) => isset( $discount['sub_total'] )
				)
			)
		);

		return tribe( Template::class )->template(
			'email',
			[
				'total_discount' => Currency_Value::create( $total_discount ),
			],
			false
		);
	}

	/**
	 * Shows the purchase rules discount section in the admin single order.
	 *
	 * @since 6.9.0
	 *
	 * @param string        $html The HTML.
	 * @param string        $file The file.
	 * @param array         $name The name.
	 * @param Base_Template $template The template.
	 *
	 * @return string The HTML.
	 */
	public function show_purchase_rules_discount_section_in_admin_single_order( string $html, string $file, array $name, Base_Template $template ): string {
		$context = $template->get_values();

		if ( empty( $context['order'] ) ) {
			return $html;
		}

		$order = $context['order'];

		$discounts = array_filter( $order->items ?? [], fn( $item ) => ! empty( $item['type'] ) && 'discount' === $item['type'] );

		if ( empty( $discounts ) ) {
			return $html;
		}

		$discounts = array_map(
			function ( $discount ) {
				$discount['sub_total'] = Currency_Value::create( new Precision_Value( $discount['sub_total'] ) );
				return $discount;
			},
			$discounts
		);

		return tribe( Template::class )->template(
			'admin/single-order',
			[
				'discounts' => $discounts,
			],
			false
		);
	}

	/**
	 * Adds the discount items to the order.
	 *
	 * @since 6.9.0
	 *
	 * @param array $items The items.
	 *
	 * @return array The items.
	 */
	public function add_discount_items_to_order( $items ): array {
		if ( empty( $items ) ) {
			return $items;
		}

		if ( $this->contains_discounts( $items ) ) {
			return $items;
		}

		/** @var Cart $cart */
		$cart      = tribe( Cart::class );
		$cart_hash = $cart->get_cart_hash();
		$discounts = $cart->get_items_in_cart( true, 'discount' );

		$discount_items = [];

		foreach ( $discounts as $discount ) {
			$rule_id = $discount['extra']['rule']['id'] ?? '';

			if ( ! $rule_id ) {
				continue;
			}

			$name  = $discount['extra']['rule']['name'] ?? '';
			$value = $discount['extra']['value'] ?? null;

			if ( ! $value instanceof Precision_Value ) {
				continue;
			}

			$discount_items[] = [
				'id'           => "rule_{$cart_hash}_{$rule_id}",
				'type'         => 'discount',
				'price'        => $discount['extra']['value']->get(),
				'sub_total'    => $discount['extra']['value']->multiply_by_integer( new Integer_Value( $discount['quantity'] ?? 1 ) )->get(),
				'rule_id'      => $rule_id,
				'display_name' => $name,
				'ticket_id'    => '0',
				'event_id'     => '0',
				'quantity'     => $discount['quantity'] ?? 1,
				'data'         => $discount['extra']['rule'],
			];
		}

		return array_merge( $items, $discount_items );
	}

	/**
	 * Adds the purchase rules discount data to the cart.
	 *
	 * @since 6.9.0
	 *
	 * @param array  $nothing The null value.
	 * @param array  $item    The item.
	 * @param string $type    The type.
	 *
	 * @return array The item.
	 */
	public function add_purchase_rules_discount_data_to_cart( $nothing, array $item, string $type ): array {
		if ( 'discount' !== $type ) {
			return $nothing;
		}

		// Returning a callable will make the cart apply the discount ONLY in the total and NOT in the subtotal.
		$item['sub_total'] = fn() => $item['extra']['value']->get();

		return $item;
	}

	/**
	 * Ensures the rules are honored on the checkout page.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 *
	 * @throws RuntimeException If an unknown rule type is encountered.
	 */
	public function ensure_rules_are_honored_on_checkout(): void {
		/** @var Cart $cart */
		$cart = tribe( Cart::class );

		$tickets_in_cart = $cart->get_items_in_cart();

		if ( empty( $tickets_in_cart ) ) {
			return;
		}

		$tickets_per_post = $this->get_tickets_per_post( $cart );

		if ( empty( $tickets_per_post ) ) {
			// Weird, but nothing to do then.
			return;
		}

		try {
			$this->validate_rules( $cart, $tickets_per_post );
		} catch ( RestrictionNotMetException $e ) {
			/**
			 * Fires when a purchase rule restriction is not met.
			 *
			 * @since 6.9.0
			 *
			 * @param RestrictionNotMetException $e The exception.
			 */
			do_action( 'tec_tickets_plus_purchase_rules_restriction_not_met', $e );

			$this->clear_in_flights_rules( $cart, $e->get_post_id() );

			// We validate the rules are honored in PHP as we must but we take no further action other than redirecting back the user.
			wp_safe_redirect(
				add_query_arg( 'restriction_not_met', $e->get_rule()->get_id(), get_the_permalink( $e->get_post_id() ) ),
				307,
				'Purchase rules restriction not met'
			);
			tribe_exit();
		}

		$this->apply_discounts( $cart, $tickets_per_post );
	}

	/**
	 * Displays the fee section in the checkout.
	 *
	 * @since 6.9.0
	 *
	 * @param WP_Post       $post     The post object for the current event.
	 * @param array         $items    The items in the cart.
	 * @param Base_Template $template The template object for rendering.
	 */
	public function display_purchase_rules_discount_section( WP_Post $post, array $items, Base_Template $template ): void {
		/** @var Cart $cart */
		$cart = tribe( Cart::class );

		$discounts = $cart->get_items_in_cart( false, 'discount' );

		if ( empty( $discounts ) ) {
			return;
		}

		$total_discount = Precision_Value::sum(
			...array_values(
				array_map(
					static fn( $discount ) => $discount['extra']['value'],
					array_filter(
						$discounts,
						fn( $discount ) => isset( $discount['extra'], $discount['extra']['value'] ) &&
									$discount['extra']['value'] instanceof Precision_Value
					)
				)
			)
		);

		tribe( Template::class )->template(
			'checkout',
			[
				'total_discount' => Currency_Value::create( $total_discount ),
			]
		);
	}

	/**
	 * Gets the in-flight rules for a post and cart.
	 *
	 * @since 6.9.0
	 *
	 * @param int    $post_id         The post ID.
	 * @param string $cart_hash       The cart hash.
	 * @param int    $cart_expiration The cart expiration.
	 *
	 * @return In_Flight_Rule[] The in-flight rules.
	 */
	private function get_in_flight_rules_for_post_and_cart( int $post_id, string $cart_hash, int $cart_expiration ): array {
		$stored_data = get_transient( $this->get_in_flight_rules_transient_name( $post_id, $cart_hash ) );

		$rule_data_to_in_flight_rule_callback = fn( array $rule_data ) => In_Flight_Rule::fromData( $rule_data );
		$rule_is_valid_callback               = fn( $rule ) => $rule instanceof In_Flight_Rule;

		if ( ! empty( $stored_data ) && is_array( $stored_data ) ) {
			return array_filter(
				array_map(
					$rule_data_to_in_flight_rule_callback,
					$stored_data
				),
				$rule_is_valid_callback
			);
		}

		$rule_data = array_map( fn( Rule $rule ) => $rule->toArray(), Rule::get_active_rules_for_post( $post_id ) );

		set_transient( $this->get_in_flight_rules_transient_name( $post_id, $cart_hash ), $rule_data, $cart_expiration );

		return array_filter(
			array_map( $rule_data_to_in_flight_rule_callback, $rule_data ),
			$rule_is_valid_callback
		);
	}

	/**
	 * Gets the tickets per post for a cart.
	 *
	 * Builds on when we will support a true TC cart with tickets from multiple posts.
	 *
	 * @since 6.9.0
	 *
	 * @param Cart $cart The cart.
	 *
	 * @return array The tickets per post.
	 */
	private function get_tickets_per_post( Cart $cart ): array {
		$tickets_in_cart = $cart->get_items_in_cart();

		if ( empty( $tickets_in_cart ) ) {
			return [];
		}

		/** @var Ticket $ticket_data */
		$ticket_data = tribe( Ticket::class );

		$tickets_per_post = [];

		foreach ( array_keys( $tickets_in_cart ) as $ticket_id ) {
			$ticket_object = $ticket_data->load_ticket_object( $ticket_id );

			if ( ! $ticket_object instanceof Ticket_Object ) {
				continue;
			}

			$post = $ticket_object->get_event();

			if ( empty( $post->ID ) ) {
				continue;
			}

			$post_id = $post->ID;

			if ( ! isset( $tickets_per_post[ $post_id ] ) ) {
				$tickets_per_post[ $post_id ] = [];
			}

			$tickets_per_post[ $post_id ][] = $ticket_object;
		}

		return $tickets_per_post;
	}

	/**
	 * Validates the rules for a cart and tickets per post.
	 *
	 * @since 6.9.0
	 *
	 * @param Cart  $cart             The cart.
	 * @param array $tickets_per_post The tickets per post.
	 *
	 * @return void
	 *
	 * @throws RestrictionNotMetException If a restriction is not met.
	 * @throws RuntimeException If an unknown rule type is encountered.
	 */
	private function validate_rules( Cart $cart, array $tickets_per_post ): void {
		$tickets_in_cart = $cart->get_items_in_cart();
		$cart_hash       = $cart->get_cart_hash();
		$cart_expiration = $cart->get_cart_expiration();

		$tickets_total_quantity = array_sum( wp_list_pluck( $tickets_in_cart, 'quantity' ) );

		foreach ( $tickets_per_post as $post_id => $tickets ) {
			/** @var In_Flight_Rule $rule */
			foreach ( $this->get_in_flight_rules_for_post_and_cart( $post_id, $cart_hash, $cart_expiration ) as $rule ) {
				$this->validate_rule( $rule, $tickets, $post_id, $tickets_total_quantity, $tickets_in_cart, $cart->get_cart_subtotal() );
			}
		}
	}

	/**
	 * Validates a rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets                The tickets.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If an unknown rule type is encountered.
	 */
	private function validate_rule( In_Flight_Rule $rule, array $tickets, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): void {
		$type = $rule->get_type();

		switch ( $type ) {
			case Rule::EVENT_PURCHASE_LIMIT_TYPE:
			case Rule::TICKET_PURCHASE_LIMIT_TYPE:
			case Rule::EVENT_PURCHASE_MIN_TYPE:
			case Rule::TICKET_PURCHASE_MIN_TYPE:
			case Rule::USER_ROLE_RESTRICTION_TYPE:
			case Rule::COMBINED_PURCHASE_TYPE:
				$method = 'validate_' . str_replace( '-', '_', $rule->get_type() ) . '_rule';
				$this->$method( $rule, $tickets, $post_id, $tickets_total_quantity, $tickets_in_cart, $subtotal );
				break;

			case Rule::ORDER_DISCOUNT_TYPE:
			case Rule::TICKET_DISCOUNT_TYPE:
				if ( ! isset( $this->discount_rules_in_cart[ $post_id ] ) ) {
					$this->discount_rules_in_cart[ $post_id ] = [];
				}

				$this->discount_rules_in_cart[ $post_id ][] = $rule;
				break;
			default:
				throw new RuntimeException( sprintf( 'Invalid rule type: %s', $rule->get_type() ) );
		}
	}

	/**
	 * Validates the event purchase limit rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets                The tickets.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return void
	 *
	 * @throws RestrictionNotMetException If the rule is not met.
	 */
	private function validate_event_purchase_limit_rule( In_Flight_Rule $rule, array $tickets, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): void {
		$config = $rule->get_config();

		$ticket_limit = (int) ( $config['ticketLimit'] ?? 0 );
		if ( $tickets_total_quantity > $ticket_limit ) {
			throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
		}
	}

	/**
	 * Validates the ticket purchase limit rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets                The tickets.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return void
	 *
	 * @throws RestrictionNotMetException If the rule is not met.
	 */
	private function validate_ticket_purchase_limit_rule( In_Flight_Rule $rule, array $tickets, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): void {
		$config = $rule->get_config();

		$ticket_limit   = (int) ( $config['ticketLimit'] ?? 0 );
		$limited_ticket = $config['limitedTicket'] ?? '';

		foreach ( $tickets as $ticket ) {
			if ( strstr( strtolower( $ticket->name ), strtolower( $limited_ticket ) ) === false && $ticket->ID !== (int) $limited_ticket ) {
				continue;
			}

			if ( $tickets_in_cart[ $ticket->ID ]['quantity'] <= $ticket_limit ) {
				continue;
			}

			throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
		}
	}

	/**
	 * Validates the event purchase min rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets                The tickets.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return void
	 *
	 * @throws RestrictionNotMetException If the rule is not met.
	 */
	private function validate_event_purchase_min_rule( In_Flight_Rule $rule, array $tickets, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): void {
		$config = $rule->get_config();

		$minimum = (int) ( $config['ticketMinimum'] ?? 0 );
		$type    = $config['requirement'] ?? 'quantity';
		if ( 'quantity' === $type && $tickets_total_quantity < $minimum ) {
			throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
		}

		if ( 'total' === $type && $subtotal < $minimum ) {
			throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
		}
	}

	/**
	 * Validates the ticket purchase min rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets                The tickets.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return void
	 *
	 * @throws RestrictionNotMetException If the rule is not met.
	 */
	private function validate_ticket_purchase_min_rule( In_Flight_Rule $rule, array $tickets, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): void {
		$config = $rule->get_config();

		$minimum        = (int) ( $config['ticketMinimum'] ?? 0 );
		$limited_ticket = $config['limitedTicket'] ?? '';

		foreach ( $tickets as $ticket ) {
			if ( strstr( strtolower( $ticket->name ), strtolower( $limited_ticket ) ) === false && $ticket->ID !== (int) $limited_ticket ) {
				continue;
			}

			if ( $tickets_in_cart[ $ticket->ID ]['quantity'] >= $minimum ) {
				continue;
			}

			throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
		}
	}

	/**
	 * Validates the user role restriction rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets                The tickets.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return void
	 *
	 * @throws RestrictionNotMetException If the rule is not met.
	 */
	private function validate_user_role_restriction_rule( In_Flight_Rule $rule, array $tickets, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): void {
		$config = $rule->get_config();

		$allowed_roles = $config['userRoles'] ?? [];
		if ( empty( $allowed_roles ) || ! is_user_logged_in() ) {
			throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
		}

		$user = wp_get_current_user();

		$user_roles = $user->roles;

		if ( ! in_array( 'not-guest', $allowed_roles, true ) && count( array_intersect( $user_roles, $allowed_roles ) ) === 0 ) {
			throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
		}
	}

	/**
	 * Validates the combined purchase rule.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets                The tickets.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return void
	 *
	 * @throws RestrictionNotMetException If the rule is not met.
	 */
	private function validate_combined_purchase_rule( In_Flight_Rule $rule, array $tickets, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): void {
		$config = $rule->get_config();

		$restricted_ticket = $config['restrictedTicket'] ?? '';
		$required_tickets  = $config['requiredTickets'] ?? [];
		$required_quantity = $config['requiredQuantity'] ?? 'matched';
		$specific_quantity = $config['specificQuantity'] ?? 1;

		if ( ! $restricted_ticket || empty( $required_tickets ) ) {
			return;
		}

		$restricted_tickets_found = [];

		foreach ( $tickets as $ticket ) {
			if ( strstr( strtolower( $ticket->name ), strtolower( $restricted_ticket ) ) === false && $ticket->ID !== (int) $restricted_ticket ) {
				continue;
			}

			$restricted_tickets_found[] = $ticket;
		}

		if ( ! $restricted_tickets_found ) {
			return;
		}

		foreach ( $required_tickets as $required_ticket ) {
			$requirement_met = false;

			foreach ( $tickets as $ticket ) {
				if ( strstr( strtolower( $ticket->name ), strtolower( $required_ticket ) ) === false && $ticket->ID !== (int) $required_ticket ) {
					continue;
				}

				if ( 'matched' === $required_quantity ) {
					foreach ( $restricted_tickets_found as $restricted_ticket ) {
						if ( $tickets_in_cart[ $restricted_ticket->ID ]['quantity'] !== $tickets_in_cart[ $ticket->ID ]['quantity'] ) {
							throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
						}
					}

					$requirement_met = true;
					break;
				} else {
					if ( $tickets_in_cart[ $ticket->ID ]['quantity'] < $specific_quantity ) {
						throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
					}

					$requirement_met = true;
					break;
				}
			}

			if ( ! $requirement_met ) {
				throw RestrictionNotMetException::fromRule( $rule )->set_post_id( $post_id );
			}
		}
	}

	/**
	 * Applies the discounts to the cart.
	 *
	 * @since 6.9.0
	 *
	 * @param Cart  $cart             The cart.
	 * @param array $tickets_per_post The tickets per post.
	 *
	 * @return void
	 *
	 * @throws RuntimeException If an unknown rule type is encountered.
	 */
	private function apply_discounts( Cart $cart, array $tickets_per_post ): void {
		if ( empty( $this->discount_rules_in_cart ) ) {
			return;
		}

		$cart_hash       = $cart->get_cart_hash();
		$cart_expiration = $cart->get_cart_expiration();

		$discounts = get_transient( "tec_tickets_plus_purchase_rules_discounts_{$cart_hash}" );

		if ( false === $discounts || ! is_array( $discounts ) ) {
			$tickets_in_cart = $cart->get_items_in_cart();

			$tickets_total_quantity = array_sum( wp_list_pluck( $tickets_in_cart, 'quantity' ) );

			$subtotal = $cart->get_cart_subtotal();

			$discounts = $this->calculate_discounts( $tickets_per_post, $tickets_total_quantity, $subtotal, $tickets_in_cart );

			set_transient( "tec_tickets_plus_purchase_rules_discounts_{$cart_hash}", $discounts, $cart_expiration );
		}

		if ( empty( $discounts ) ) {
			return;
		}

		$cart_repository = $cart->get_repository();

		foreach ( $discounts as $rule_id => $discount ) {
			$cart_repository->upsert_item(
				"purchase-rules-discount-{$rule_id}",
				1,
				[
					'type'  => 'discount',
					'value' => new Precision_Value( -1 * $discount['value'] ),
					'rule'  => $discount['rule'],
				]
			);
		}

		$cart_repository->save();
	}

	/**
	 * Calculates the discounts.
	 *
	 * @since 6.9.0
	 *
	 * @param array $tickets_per_post       The tickets per post.
	 * @param int   $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param float $subtotal               The subtotal of the cart.
	 * @param array $tickets_in_cart        The tickets in the cart.
	 *
	 * @return array The discounts.
	 */
	private function calculate_discounts( array $tickets_per_post, int $tickets_total_quantity, float $subtotal, array $tickets_in_cart ): array {
		$discounts = [];

		foreach ( $this->discount_rules_in_cart as $post_id => $rules ) {
			foreach ( $rules as $rule ) {
				$method = 'calculate_' . str_replace( '-', '_', $rule->get_type() );

				$discounts[ $rule->get_id() ] = $this->$method( $rule, $tickets_per_post, $post_id, $tickets_total_quantity, $tickets_in_cart, $subtotal );
			}
		}

		return array_filter( $discounts, fn( $discount ) => ! empty( $discount['value'] ) && ! empty( $discount['rule'] ) );
	}

	/**
	 * Calculates the order discount.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets_per_post       The tickets per post.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return array The discount.
	 */
	private function calculate_order_discount( In_Flight_Rule $rule, array $tickets_per_post, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): array {
		$config = $rule->get_config();

		$discount_value = $config['discountValue'] ?? 0;

		if ( ! $discount_value ) {
			return [];
		}

		$discount_type     = $config['discountType'] ?? 'flat';
		$requirement       = $config['requirement'] ?? 'quantity';
		$requirement_value = $config['requirementValue'] ?? 0;

		if ( 'quantity' === $requirement && $tickets_total_quantity < $requirement_value ) {
			return [];
		}

		if ( 'total' === $requirement && $subtotal < $requirement_value ) {
			return [];
		}

		return [
			'value' => 'flat' === $discount_type ? $discount_value : ( $discount_value * $subtotal ) / 100,
			'rule'  => $rule->toArray(),
		];
	}

	/**
	 * Calculates the ticket discount.
	 *
	 * @since 6.9.0
	 *
	 * @param In_Flight_Rule $rule                   The rule.
	 * @param array          $tickets_per_post       The tickets per post.
	 * @param int            $post_id                The post ID.
	 * @param int            $tickets_total_quantity The total quantity of tickets in the cart.
	 * @param array          $tickets_in_cart        The tickets in the cart.
	 * @param float          $subtotal               The subtotal of the cart.
	 *
	 * @return array The discount.
	 */
	private function calculate_ticket_discount( In_Flight_Rule $rule, array $tickets_per_post, int $post_id, int $tickets_total_quantity, array $tickets_in_cart, float $subtotal ): array {
		$config = $rule->get_config();

		$discount_value = $config['discountValue'] ?? 0;

		if ( ! $discount_value ) {
			return [];
		}

		$requirements = $config['requirements'] ?? [];

		if ( empty( $requirements ) ) {
			return [];
		}

		$discount_type = $config['discountType'] ?? 'flat';

		$requirements_met = [];

		foreach ( $requirements as $requirement ) {
			$ticket_keyword_or_id = $requirement['ticket'] ?? '';
			$quantity             = $requirement['quantity'] ?? 0;

			if ( ! ( $ticket_keyword_or_id && $quantity ) ) {
				continue;
			}

			$requirement_met = false;

			foreach ( $tickets_per_post[ $post_id ] as $ticket ) {
				if ( strstr( strtolower( $ticket->name ), strtolower( $ticket_keyword_or_id ) ) === false && $ticket->ID !== (int) $ticket_keyword_or_id ) {
					continue;
				}

				if ( $tickets_in_cart[ $ticket->ID ]['quantity'] < $quantity ) {
					continue;
				}

				$requirement_met = true;

				/** @var Ticket_Object $ticket */
				$requirements_met[] = $ticket;
			}

			if ( ! $requirement_met ) {
				return [];
			}
		}

		$ticket_ids_to_quantity_and_price_map = [];

		foreach ( $requirements_met as $ticket ) {
			$ticket_ids_to_quantity_and_price_map[ $ticket->ID ] = [
				'quantity' => $tickets_in_cart[ $ticket->ID ]['quantity'] ?? 0,
				'price'    => $ticket->price,
			];
		}

		if ( 'flat' === $discount_type ) {
			$total_quantity = array_sum( wp_list_pluck( $ticket_ids_to_quantity_and_price_map, 'quantity' ) );

			return [
				'value' => $discount_value * $total_quantity,
				'rule'  => $rule->toArray(),
			];
		}

		$total_discount = 0;
		foreach ( $ticket_ids_to_quantity_and_price_map as $ticket_id => $quantity_and_price ) {
			$total_discount += $discount_value * $quantity_and_price['quantity'] * $quantity_and_price['price'] / 100;
		}

		return [
			'value' => $total_discount,
			'rule'  => $rule->toArray(),
		];
	}

	/**
	 * Clears the in-flight rules for a post and cart.
	 *
	 * @since 6.9.0
	 *
	 * @param Cart $cart    The cart.
	 * @param int  $post_id The post ID.
	 *
	 * @return void
	 */
	private function clear_in_flights_rules( Cart $cart, int $post_id ): void {
		delete_transient( $this->get_in_flight_rules_transient_name( $post_id, $cart->get_cart_hash() ) );
	}

	/**
	 * Gets the in-flight rules transient name.
	 *
	 * @since 6.9.0
	 *
	 * @param int    $post_id   The post ID.
	 * @param string $cart_hash The cart hash.
	 *
	 * @return string The in-flight rules transient name.
	 */
	private function get_in_flight_rules_transient_name( int $post_id, string $cart_hash ): string {
		return "tec_tickets_plus_purchase_rules_in_flight_{$post_id}_{$cart_hash}";
	}

	/**
	 * Checks if the cart contains discounts.
	 *
	 * @since 6.9.0
	 *
	 * @param array $items The items.
	 *
	 * @return bool Whether the cart contains discounts.
	 */
	private function contains_discounts( array $items ): bool {
		foreach ( $items as $item ) {
			if ( ! isset( $item['type'] ) ) {
				continue;
			}

			if ( 'discount' !== $item['type'] ) {
				continue;
			}

			return true;
		}

		return false;
	}
}
