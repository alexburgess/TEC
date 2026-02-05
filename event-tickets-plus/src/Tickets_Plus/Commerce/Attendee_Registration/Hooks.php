<?php
/**
 * Handles hooking all the actions and filters used by the module.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets_Plus\Commerce\Attendee_Registration\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets-plus.commerce.attendee-registration.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets_Plus\Commerce\Attendee_Registration\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets-plus.commerce.attendee-registration.hooks' ), 'some_method' ] );
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus\Commerce\Attendee_Registration
 */

namespace TEC\Tickets_Plus\Commerce\Attendee_Registration;

use TEC\Tickets\Commerce;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Commerce\Checkout;
use TEC\Tickets\Commerce\Gateways\Contracts\Gateway_Interface;
use TEC\Tickets\Commerce\Module;
use TEC\Tickets\Commerce\Status\Status_Interface;
use TEC\Tickets_Plus\Commerce\Attendee;
use TEC\Tickets_Plus\Commerce\Order;
use Tribe\Tickets\Plus\Attendee_Registration\IAC;
use WP_Post;
use Tribe__Utils__Array as Arr;

/**
 * Class Hooks.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus\Commerce\Attendee_Registration
 */
class Hooks extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.3.0
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets component.
	 *
	 * @since 5.3.0
	 */
	protected function add_actions() {
		add_action( 'tribe_template_before_include:tickets/v2/commerce/checkout/header/links/back', [ $this, 'render_modify_attendees_link' ], 10, 3 );
		add_action( 'tribe_tickets_plus_after_my_tickets_attendee_update', [ $this, 'update_attendee_meta_my_tickets_page' ], 15, 7 );
	}

	/**
	 * Adds the filters required by each Tickets component.
	 *
	 * @since 5.3.0
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_order_create_args', [ $this, 'filter_inject_order_create_args' ], 15, 2 );
		add_filter( 'tec_tickets_commerce_flag_action_generate_attendee_args', [ $this, 'filter_inject_attendee_generation_args' ], 15, 7 );
		add_filter( 'tec_tickets_commerce_legacy_attendee_meta_key', [ $this, 'filter_modify_legacy_attendee_meta_key' ], 15, 3 );
		add_filter( 'tec_tickets_commerce_get_attendee', [ $this, 'filter_attendee_object' ], 15, 3 );
		add_filter( 'tec_tickets_commerce_attendees_repository_aliases', [ $this, 'filter_inject_attendee_repository_fields_alias' ] );
		add_filter( 'tec_tickets_commerce_cart_to_checkout_redirect_url_base', [ $this, 'filter_cart_to_checkout_redirect_url' ] );
		add_filter( 'tribe_tickets_attendee_registration_checkout_url', [ $this, 'filter_attendee_registration_checkout_url' ] );

		add_filter( 'tribe_tickets_commerce_cart_get_ticket_meta', [ $this, 'filter_cart_meta' ], 15, 2 );
		add_filter( 'tribe_tickets_commerce_cart_get_data', [ $this, 'filter_cart_providers' ], 15, 3 );

		// Replace "return to cart" with "return to checkout".
		add_filter( 'tribe_template_include_html:tickets/v2/tickets/footer/return-to-cart', [ $this, 'filter_return_to_cart' ], 10, 4 );
	}


	/**
	 * Modify the order based on the IAC fields.
	 *
	 * @since 5.3.0
	 *
	 * @param array             $args
	 * @param Gateway_Interface $gateway
	 *
	 * @return array
	 */
	public function filter_inject_order_create_args( $args, Gateway_Interface $gateway ) {
		return $this->container->make( Order::class )->modify_iac_item_extra( $args, $gateway );
	}

	/**
	 * Modify the attendee generation args on the Flag Action level, to insert ET+ fields.
	 *
	 * @since 5.3.0
	 *
	 * @param array                    $args       The attendee creation args.
	 * @param \Tribe__Tickets__Tickets $ticket     The ticket the attendee is generated for.
	 * @param WP_Post                  $order      The order the attendee is generated for.
	 * @param Status_Interface         $new_status New post status.
	 * @param Status_Interface|null    $old_status Old post status.
	 * @param array                    $item       Which cart item this args are for.
	 * @param int                      $i          Which Attendee index we are generating.
	 *
	 * @return array
	 */
	public function filter_inject_attendee_generation_args( $args, $ticket, $order, $new_status, $old_status, $item, $i ) {
		// First inject fields.
		$args = $this->container->make( Attendee::class )->inject_fields_args( $args, $item, $i );

		// Always inject IAC after Fields.
		$args = $this->container->make( Attendee::class )->inject_individual_collection_args( $args, $ticket );

		return $args;
	}

	/**
	 * Filters the default Attendee object to add ET+ information.
	 *
	 * @since 5.3.0
	 *
	 * @param WP_Post $post   The attendee post object, decorated with a set of custom properties.
	 * @param string  $output The output format to use.
	 * @param string  $filter The filter, or context of the fetch.
	 *
	 * @return WP_Post
	 */
	public function filter_attendee_object( $post, $output, $filter ) {
		return $this->container->make( Attendee::class )->inject_attendee_object_fields( $post, $output, $filter );
	}

	/**
	 * Modify the key for Attendee Meta fields.
	 *
	 * @todo  TribeCommerceLegacy
	 *
	 * @since 5.3.0
	 *
	 * @param string     $meta_key    Which meta key we using for fetching fields.
	 * @param string|int $ticket_id   Which ticket are we filtering for.
	 * @param string|int $attendee_id Which attendee are we filtering for.
	 *
	 * @return string
	 */
	public function filter_modify_legacy_attendee_meta_key( $meta_key, $ticket_id, $attendee_id ) {
		return $this->container->make( Attendee::class )->modify_legacy_fields_meta_key( $meta_key, $ticket_id, $attendee_id );
	}

	/**
	 * Handle updating the attendee with IAC name/email and the email resend.
	 *
	 * @todo  TribeCommerceLegacy
	 *
	 * @since 5.3.0
	 *
	 * @param int|null                 $attendee_id  The attendee ID.
	 * @param array                    $data_to_save The data that was saved.
	 * @param array                    $data         The data prior to filtering for saving.
	 * @param int                      $order_id     The order ID.
	 * @param int                      $ticket_id    The ticket ID.
	 * @param int                      $post_id      The ID of the post associated to the ticket.
	 * @param \Tribe__Tickets__Tickets $provider     The current ticket provider object.
	 */
	public function update_attendee_meta_my_tickets_page( $attendee_id, $data_to_save, $data, $order_id, $ticket_id, $post_id, $provider ) {
		$args = [
			'fields' => $data,
		];

		$ticket = tribe( Commerce\Ticket::class )->get_ticket( $ticket_id );

		// Always inject IAC after Fields.
		$args = $this->container->make( Attendee::class )->inject_individual_collection_args( $args, $ticket );

		$attendee = tec_tc_attendees()->by( 'id', $attendee_id )->set_args( $args )->save();
	}

	/**
	 * Inject the fields alias for the Attendee repository.
	 *
	 * @since 5.3.0
	 *
	 * @param array $aliases Array of aliases within the Attendee repository.
	 *
	 * @return array
	 */
	public function filter_inject_attendee_repository_fields_alias( $aliases ) {
		$aliases['fields'] = Attendee::$fields_meta_key;

		return $aliases;
	}

	/**
	 * Filters the cart --> checkout redirect URL base.
	 *
	 * @since 5.3.0
	 *
	 * @param string $redirect_url Redirect URL.
	 *
	 * @return string
	 */
	public function filter_cart_to_checkout_redirect_url( $redirect_url ) {
		try {
			$attendee_registration = tribe( 'tickets.attendee_registration' );
		} catch ( \Exception $e ) {
			return $redirect_url;
		}

		if ( $attendee_registration->is_modal_enabled() ) {
			return $redirect_url;
		}

		// If we are attempting to redirect to the Checkout page, don't hijack the URL.
		if ( tribe_get_request_var( Checkout::$url_query_arg, false ) ) {
			return $redirect_url;
		}

		/**
		 * All of the logic for IAC and ARF need to moved and consolidated later, as is the amount of logic for this is
		 * insane and very expensive to maintain long term.
		 *
		 * @todo TribeLegacyCommerce
		 */
		$ar_json_string   = stripslashes( Arr::get( $_POST, 'tribe_tickets_ar_data', '' ) );
		$ar_data          = json_decode( $ar_json_string, ARRAY_A );
		$cart_items       = Arr::get( $ar_data, 'tribe_tickets_tickets' );
		$tickets_with_qty = [];
		foreach ( $cart_items as $item ) {
			if ( ! isset( $item['ticket_id'], $item['quantity'] ) ) {
				continue;
			}

			if ( 0 === (int) $item['quantity'] ) {
				continue;
			}

			$tickets_with_qty[] = $item['ticket_id'];
		}

		if ( empty( $tickets_with_qty ) ) {
			return $redirect_url;
		}

		$needs_redirect = false;
		foreach ( $tickets_with_qty as $ticket_id ) {
			if ( IAC::NONE_KEY !== tribe( 'tickets-plus.attendee-registration.iac' )->get_iac_setting_for_ticket( $ticket_id ) ) {
				$needs_redirect = true;
				break;
			}
			$meta = \Tribe__Tickets_Plus__Meta::get_attendee_meta_fields( $ticket_id );

			if ( ! empty( $meta ) ) {
				$needs_redirect = true;
				break;
			}
		}

		if ( ! $needs_redirect ) {
			return $redirect_url;
		}

		$redirect_url = home_url( '/' . $attendee_registration->get_slug() );
		$redirect_url = add_query_arg( tribe_tickets_get_provider_query_slug(), \TEC\Tickets\Commerce::PROVIDER, $redirect_url );

		return $redirect_url;
	}

	/**
	 * Filters the checkout URL for Attendee Registration.
	 *
	 * @since 5.3.0
	 *
	 * @param string $url Checkout URL.
	 *
	 * @return string
	 */
	public function filter_attendee_registration_checkout_url( $url ) {
		if ( \TEC\Tickets\Commerce::PROVIDER !== tribe_get_request_var( tribe_tickets_get_provider_query_slug() ) ) {
			return $url;
		}

		$url = home_url( '/' );
		$url = add_query_arg( Checkout::$url_query_arg, true, $url );
		$url = add_query_arg( Cart::$url_query_arg, Cart::REDIRECT_MODE, $url );
		$url = add_query_arg( Cart::$cookie_query_arg, tribe_get_request_var( Cart::$cookie_query_arg ), $url );

		return $url;
	}

	/**
	 * Render the modify attendees link.
	 *
	 * @since 5.3.0
	 *
	 * @param string           $file        Complete path to include the PHP File.
	 * @param array            $name        Template name.
	 * @param \Tribe__Template $et_template Current instance of the Tribe__Template.
	 */
	public function render_modify_attendees_link( $file, $name, $et_template ) {
		/** @var \Tribe__Tickets_Plus__Template $template */
		$template = tribe( 'tickets-plus.template' );

		$url = home_url( '/' . tribe( 'tickets.attendee_registration' )->get_slug() );
		$url = add_query_arg( tribe_tickets_get_provider_query_slug(), \TEC\Tickets\Commerce::PROVIDER, $url );
		$url = add_query_arg( Cart::$cookie_query_arg, tribe_get_request_var( Cart::$cookie_query_arg ), $url );

		$args                              = $et_template->get_local_values();
		$args['attendee_registration_url'] = $url;

		/** @var \Tribe__Tickets_Plus__Meta $tickets_meta */
		$tickets_meta       = tribe( \Tribe__Tickets_Plus__Meta::class );

		// Calculate number of attendees with ARF.
		$attendees_with_arf = array_reduce(
			array_filter(
				$args['items'],
				fn ( $item ) => isset( $item['ticket_id'] )
			),
			static function ( $qty, $item ) use ( $tickets_meta ) {
				$arf_quantity = $tickets_meta->ticket_has_arf( $item['ticket_id'] ) ? $item['quantity'] : 0;
				return $qty + $arf_quantity;
			},
			0
		);

		$args['attendees_with_arf'] = $attendees_with_arf;

		$template->add_template_globals( $args );

		$template->template( 'v2/commerce/checkout/header/links/modify-attendees' );
	}

	/**
	 * Filters the return-to-cart template to replace with Return to Checkout instead.
	 *
	 * @since 5.3.0
	 *
	 * @param string           $html        The HTML to render.
	 * @param string           $file        Template file.
	 * @param string           $name        Template name.
	 * @param \Tribe__Template $et_template Template object.
	 *
	 * @return string
	 */
	public function filter_return_to_cart( $html, $file, $name, $et_template ) {
		$provider = $et_template->get( 'provider' );
		if ( ! $provider instanceof Module ) {
			return $html;
		}

		/** @var \Tribe__Tickets_Plus__Template $template */
		$template = tribe( 'tickets-plus.template' );

		$url = $provider->get_checkout_url();
		$url = add_query_arg( Cart::$cookie_query_arg, tribe_get_request_var( Cart::$cookie_query_arg ), $url );

		$args                 = $et_template->get_local_values();
		$args['checkout_url'] = $url;

		return $template->template( 'v2/attendee-registration/mini-cart/footer', $args, false );
	}

	/**
	 * Modifies the Cart Meta fields for editing on the Attendee Registration page.
	 *
	 * @since 5.3.0
	 *
	 * @param array $meta
	 * @param array $tickets
	 *
	 * @return array
	 */
	public function filter_cart_meta( $meta, $tickets ) {
		$cookie = tribe_get_request_var( Cart::$cookie_query_arg );

		// If we dont have a cookie hash passed we just bail, since it means it's not Tickets Commerce.
		if ( empty( $cookie ) ) {
			return $meta;
		}

		$meta = [];
		/* @var Cart $cart */
		$cart = tribe( Cart::class );
		$cart->set_cart_hash( $cookie );
		$items = $cart->get_items_in_cart( true );

		foreach ( $items as $ticket_id => $item ) {
			$attendees        = Arr::get( $item, [ 'extra', 'attendees' ], [] );
			$meta_to_be_added = [
				'ticket_id' => $ticket_id,
				'provider'  => Commerce::PROVIDER,
				'items'     => [],
			];

			foreach ( $attendees as $attendee ) {
				if ( empty( $attendee['meta'] ) ) {
					continue;
				}
				$meta_to_be_added['items'][] = $attendee['meta'];

			}
			$meta[] = $meta_to_be_added;
		}

		return $meta;
	}

	/**
	 * For some reason we ended up with other providers showing up on Tickets Commerce cart, so we remove them in case we are dealing with Tickets Commerce
	 * cart.
	 *
	 * @since 5.3.0
	 *
	 * @param array    $data
	 * @param array    $providers
	 * @param int|null $post_id
	 *
	 * @return array
	 */
	public function filter_cart_providers( $data, $providers, $post_id ) {
		$cookie = tribe_get_request_var( Cart::$cookie_query_arg );

		// If we dont have a cookie hash passed we just bail, since it means it's not Tickets Commerce.
		if ( empty( $cookie ) ) {
			return $data;
		}

		// Reset keys.
		$data['tickets'] = array_filter( $data['tickets'], static function ( $ticket ) {
			return ( Commerce::PROVIDER === $ticket['provider'] );
		} );

		return $data;
	}
}
