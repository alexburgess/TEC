<?php
/**
 * The main front-end controller.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Commerce\Module;
use TEC\Common\Asset;
use Tribe__Tickets_Plus__Main as Tickets_Plus;
use Tribe__Template as Base_Template;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use Tribe__Events__Main as Events_Main;
use TEC\Tickets\Commerce\Values\Precision_Value;
use TEC\Tickets\Commerce\Values\Currency_Value;

/**
 * Class Frontend.
 *
 * @since 6.9.0
 *
 * @package TEC\Tickets_Plus\Commerce\Purchase_Rules
 */
class Frontend extends Controller_Contract {
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 6.9.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_plus_purchase_rules_frontend_registered';

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tribe_template_before_include_html:tickets/v2/tickets/footer', [ $this, 'show_purchase_rules_restriction_messages' ], 10, 4 );
		add_filter( 'tribe_template_after_include_html:tickets/v2/tickets/title', [ $this, 'show_purchase_rules_promotional_messages' ], 10, 4 );
		add_filter( 'tribe_template_before_include_html:tickets/v2/commerce/order/details/coupons', [ $this, 'add_purchase_rules_discount_data_to_order_details' ], 10, 4 );

		Asset::add(
			'tec-tickets-plus-purchase-rules-frontend-single-post',
			'purchaseRules/singlePost.js',
			Tickets_Plus::VERSION
		)
			->add_to_group_path( Tickets_Plus::class . '-packages' )
			->enqueue_on( 'tec_tickets_plus_purchase_rules_restriction_messages_added' )
			->add_localize_script(
				'tec.ticketsPlus.commerce.purchaseRules.singlePost.data',
				/**
				 * Filters the data for the purchase rules frontend single post.
				 *
				 * @since 6.9.0
				 *
				 * @return array The data for the purchase rules frontend single post.
				 */
				fn() => (array) apply_filters( 'tec_tickets_plus_purchase_rules_frontend_single_post_data', [] )
			)
			->register();

		Asset::add(
			'tec-tickets-plus-purchase-rules-frontend-single-post-style',
			'purchaseRules/singlePost.css',
			Tickets_Plus::VERSION
		)
			->add_to_group_path( Tickets_Plus::class . '-packages' )
			->set_condition( [ $this, 'should_enqueue_single_post_assets' ] )
			->enqueue_on( 'wp_enqueue_scripts' )
			->register();
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tribe_template_after_include_html:tickets/v2/tickets/title', [ $this, 'show_purchase_rules_promotional_messages' ] );
		remove_filter( 'tribe_template_before_include_html:tickets/v2/tickets/footer', [ $this, 'show_purchase_rules_restriction_messages' ] );
		remove_filter( 'tribe_template_before_include_html:tickets/v2/commerce/order/details/coupons', [ $this, 'add_purchase_rules_discount_data_to_order_details' ] );
	}

	/**
	 * Adds the purchase rules discount data to the order details.
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
	public function add_purchase_rules_discount_data_to_order_details( string $html, string $file, array $name, Base_Template $template ): string {
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
			'order-details',
			[
				'total_discount' => Currency_Value::create( $total_discount ),
			],
			false
		);
	}

	/**
	 * Shows the promotional messages for the purchase rules.
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
	public function show_purchase_rules_promotional_messages( string $html, string $file, array $name, Base_Template $template ): string {
		$promotional_rules = $this->get_rules_from_template_context( $template );

		if ( empty( $promotional_rules ) ) {
			return $html;
		}

		$messages = array_values( array_unique( array_filter( array_map( fn( Rule $rule ) => $rule->get_config()['message'] ?? '', $promotional_rules ) ) ) );

		if ( empty( $messages ) ) {
			return $html;
		}

		$promotional_message = sprintf( '<p class="tec-tickets-plus-purchase-rules-messages__message tec-tickets-plus-purchase-rules-messages__message--promotional">%s</p>', implode( ' ', $messages ) );

		return $html . $promotional_message;
	}

	/**
	 * Shows the restriction messages for the purchase rules.
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
	public function show_purchase_rules_restriction_messages( string $html, string $file, array $name, Base_Template $template ): string {
		$restriction_rules = $this->get_rules_from_template_context( $template, false );

		// The container is being printed regardless to amend styling!
		$html .= '<div class="tec-tickets-plus-purchase-rules-messages__container">';

		if ( empty( $restriction_rules ) ) {
			return $html . '</div>';
		}

		$restriction_messages = [];
		$rule_js_data         = [];

		foreach ( $restriction_rules as $rule ) {
			$message = $rule->get_config()['message'] ?? '';
			if ( ! $message ) {
				continue;
			}

			if ( ! isset( $rule_js_data[ $rule->get_type() ] ) ) {
				$rule_js_data[ $rule->get_type() ] = [];
			}

			$rule_js_data[ $rule->get_type() ][ $rule->getPrimaryValue() ] = $rule->get_config();

			$restriction_messages[] = sprintf( '<p data-rule-id="%d" aria-hidden="true" class="tec-tickets-plus-purchase-rules-messages__message tec-tickets-plus-purchase-rules-messages__message--restriction">%s</p>', $rule->getPrimaryValue(), $message );
		}

		/**
		 * Filters the restriction messages.
		 *
		 * @since 6.9.0
		 *
		 * @param array         $restriction_messages The restriction messages.
		 * @param array         $restriction_rules    The restriction rules.
		 * @param Base_Template $template             The template.
		 *
		 * @return array The restriction messages.
		 */
		$restriction_messages = (array) apply_filters( 'tec_tickets_plus_purchase_rules_restriction_messages', $restriction_messages, $restriction_rules, $template );

		if ( empty( $restriction_messages ) ) {
			return $html . '</div>';
		}

		add_filter(
			'tec_tickets_plus_purchase_rules_frontend_single_post_data',
			static fn(): array => [
				'ruleData'  => $rule_js_data,
				'userRoles' => (array) ( wp_get_current_user()->roles ?? [] ),
			]
		);

		/**
		 * Fires when the restriction messages are being shown.
		 *
		 * @since 6.9.0
		 */
		do_action( 'tec_tickets_plus_purchase_rules_restriction_messages_added' );

		return $html . implode( '', $restriction_messages ) . '</div>';
	}

	/**
	 * Checks if the single post assets should be enqueued.
	 *
	 * @since 6.9.0
	 *
	 * @return bool Whether the single post assets should be enqueued.
	 */
	public function should_enqueue_single_post_assets(): bool {
		return is_singular( Events_Main::POSTTYPE );
	}

	/**
	 * Gets the rules for the template.
	 *
	 * @since 6.9.0
	 *
	 * @param Base_Template $template    The template.
	 * @param bool          $promotional Whether to get promotional rules.
	 *
	 * @return array The rules.
	 */
	private function get_rules_from_template_context( Base_Template $template, bool $promotional = true ): array {
		$context = $template->get_values();

		$provider = $context['provider'] ?? null;

		if ( get_class( $provider ) !== Module::class ) {
			return [];
		}

		$tickets_on_sale = $context['tickets_on_sale'] ?? [];

		if ( empty( $tickets_on_sale ) ) {
			// No tickets on sale, so no promotional messages to show.
			return [];
		}

		$post_id = $context['post_id'] ?? null;

		if ( ! ( function_exists( 'tribe_is_event' ) && tribe_is_event( $post_id ) ) ) {
			return [];
		}

		return $promotional ? Rule::get_active_promotional_rules_for_post( $post_id ) : Rule::get_active_non_promotional_rules_for_post( $post_id );
	}
}
