<?php
/**
 * Template tags for Event Tickets Plus.
 *
 * @since 6.8.0
 *
 * @package TEC\Tickets_Plus\Functions\Template_Tags
 */

use TEC\Tickets\Commerce\Models\Ticket_Model;

if ( ! function_exists( 'tribe_tickets_is_edd_active' ) ) {
	/**
	 * Check if Easy Digital Downloads is active.
	 *
	 * @since 4.7.3
	 * @since 4.12.3 Changed from class_exists() check to function_exists() check.
	 *
	 * @return bool Whether the core ecommerce plugin is active.
	 */
	function tribe_tickets_is_edd_active() {
		return function_exists( 'EDD' );
	}
}

if ( ! function_exists( 'tribe_tickets_is_woocommerce_active' ) ) {
	/**
	 * Check if WooCommerce is active.
	 *
	 * @since 4.12.3
	 *
	 * @return bool Whether the core ecommerce plugin is active.
	 */
	function tribe_tickets_is_woocommerce_active() {
		return function_exists( 'WC' );
	}
}

if ( ! function_exists( 'tribe_tickets_plus_meta_field_is_required' ) ) {
	/**
	 * Check if the AR field is required.
	 *
	 * @since 5.0.0
	 *
	 * @param object $field The field object.
	 *
	 * @return bool True if is required
	 */
	function tribe_tickets_plus_meta_field_is_required( $field ) {
		return $field->is_required();
	}
}

if ( ! function_exists( 'tribe_tickets_plus_meta_field_get_attendee_id' ) ) {
	/**
	 * Get the attendee ID for the meta field.
	 *
	 * @since 5.0.0
	 *
	 * @param string|null $attendee_id The attendee ID or null to default to dynamic ID.
	 *
	 * @return string The AR field name.
	 */
	function tribe_tickets_plus_meta_field_get_attendee_id( $attendee_id = null ) {
		if ( null === $attendee_id ) {
			return '{{data.attendee_id}}';
		}

		return $attendee_id;
	}
}

if ( ! function_exists( 'tribe_tickets_plus_meta_field_name' ) ) {
	/**
	 * Build the AR meta field name.
	 *
	 * @since 5.0.0
	 *
	 * @param int         $ticket_id   The ticket ID.
	 * @param string|null $field_slug  The field slug.
	 * @param string|null $attendee_id The attendee ID or null to default to dynamic ID.
	 *
	 * @return string The AR field name.
	 */
	function tribe_tickets_plus_meta_field_name( $ticket_id, $field_slug, $attendee_id = null ) {
		// Get attendee ID to use, possibly using default dynamic ID.
		$attendee_id = tribe_tickets_plus_meta_field_get_attendee_id( $attendee_id );

		$field_name = 'tribe_tickets[' . $ticket_id . '][attendees][' . $attendee_id . '][meta]';

		if ( null === $field_slug ) {
			return $field_name;
		}

		return $field_name . '[' . $field_slug . ']';
	}
}

if ( ! function_exists( 'tribe_tickets_plus_meta_field_id' ) ) {
	/**
	 * Build the AR field `id`.
	 *
	 * @since 5.0.0
	 *
	 * @param int         $ticket_id   The ticket ID.
	 * @param string      $field_slug  The field slug.
	 * @param string      $option_slug The field option slug (in case they need it).
	 * @param string|null $attendee_id The attendee ID or null to default to dynamic ID.
	 *
	 * @return string The AR field id.
	 */
	function tribe_tickets_plus_meta_field_id( $ticket_id, $field_slug, $option_slug = '', $attendee_id = null ) {
		// Get attendee ID to use, possibly using default dynamic ID.
		$attendee_id = tribe_tickets_plus_meta_field_get_attendee_id( $attendee_id );

		$field_id = "tribe-tickets_{$ticket_id}_{$field_slug}_{$attendee_id}";

		if ( ! empty( $option_slug ) ) {
			$field_id .= "_{$option_slug}";
		}

		return $field_id;
	}
}

if ( ! function_exists( 'tribe_tickets_ma_is_enabled' ) ) {
	/**
	 * Determine whether the MA feature is enabled.
	 *
	 * @todo Remove this function before release.
	 *
	 * In order: the function will check the constant, the environment variable, and then
	 * allow filtering.
	 *
	 * @since 5.2.0
	 *
	 * @return bool Whether the Manual Attendees feature is enabled.
	 */
	function tribe_tickets_ma_is_enabled() {
		// Check for constant.
		if ( defined( 'TRIBE_TICKETS_MA_ENABLED' ) ) {
			return (bool) TRIBE_TICKETS_MA_ENABLED;
		}

		// Check for env var.
		$env_var = getenv( 'TRIBE_TICKETS_MA_ENABLED' );

		if ( false !== $env_var ) {
			return (bool) $env_var;
		}

		/**
		 * Allows filtering whether the manual attendee is enabled.
		 *
		 * @since 5.2.0
		 *
		 * @param bool $enabled Whether the manual attendee is enabled.
		 *
		 * @var bool   $enabled Whether the manual attendee is enabled.
		 */
		return (bool) apply_filters( 'tribe_tickets_manual_attendees_enabled', true );
	}
}

if ( ! function_exists( 'tec_wc_get_ticket' ) ) {
	/**
	 * Fetches and returns a decorated post object representing an ticket.
	 *
	 * @since 6.8.0
	 *
	 * @param null|int|WP_Post $ticket                 The ticket ID or post object or `null` to use the global one.
	 * @param string|null      $output                 The required return type. One of `OBJECT`, `ARRAY_A`, or `ARRAY_N`, which
	 *                                                 correspond to a WP_Post object, an associative array, or a numeric array,
	 *                                                 respectively. Defaults to `OBJECT`.
	 * @param string           $filter                 Type of filter to apply.
	 * @param bool             $force                  Whether to force a re-fetch ignoring cached results or not.
	 *
	 * @return array|mixed|void|WP_Post|null    The Order post object or array, `null` if not found.
	 */
	function tec_wc_get_ticket( $ticket = null, $output = OBJECT, $filter = 'raw', $force = false ) {
		/**
		 * Filters the ticket result before any logic applies.
		 *
		 * Returning a non `null` value here will short-circuit the function and return the value.
		 * Note: this value will not be cached and the caching of this value is a duty left to the filtering function.
		 *
		 * @since 6.8.0
		 *
		 * @param mixed       $return      The ticket object to return.
		 * @param mixed       $ticket      The ticket object to fetch.
		 * @param string|null $output      The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
		 *                                 correspond to a `WP_Post` object, an associative array, or a numeric array,
		 *                                 respectively. Defaults to `OBJECT`.
		 * @param string      $filter      Type of filter to apply.
		 */
		$return = apply_filters( 'tec_tickets_plus_woo_get_ticket_before', null, $ticket, $output, $filter );

		if ( null !== $return ) {
			return $return;
		}

		$post = false;

		/** @var Tribe__Cache $cache */
		$cache = tribe( 'cache' );

		$cache_post = get_post( $ticket );

		if ( empty( $cache_post ) ) {
			return null;
		}

		$key_fields = [
			$cache_post->ID,
			$cache_post->post_modified,
			// Use the `post_password` field as we show/hide some information depending on that.
			$cache_post->post_password,
			// We must include options on cache key, because options influence the hydrated data on the Order object.
			wp_json_encode( Tribe__Settings_Manager::get_options() ),
			wp_json_encode(
				[
					get_option( 'start_of_week' ),
					get_option( 'timezone_string' ),
					get_option( 'gmt_offset' ),
				]
			),
			$output,
			$filter,
		];

		$cache_key = 'tec_wc_get_ticket_' . md5( wp_json_encode( $key_fields ) );

		if ( ! $force ) {
			$post = $cache->get( $cache_key, Tribe__Cache_Listener::TRIGGER_SAVE_POST );
		}

		if ( false === $post ) {
			$post = Ticket_Model::from_post( $ticket )->to_post( $output, $filter );

			if ( empty( $post ) ) {
				return null;
			}

			/**
			 * Filters the ticket post object before caching it and returning it.
			 *
			 * Note: this value will be cached; as such this filter might not run on each request.
			 * If you need to filter the output value on each call of this function then use the `tec_tickets_commerce_get_ticket_before`
			 * filter.
			 *
			 * @since 6.8.0
			 *
			 * @param WP_Post $post   The ticket post object, decorated with a set of custom properties.
			 * @param string  $output The output format to use.
			 * @param string  $filter The filter, or context of the fetch.
			 */
			$post = apply_filters( 'tec_tickets_plus_woo_get_ticket', $post, $output, $filter );

			// Dont try to reset cache when forcing.
			if ( ! $force ) {
				$cache->set( $cache_key, $post, WEEK_IN_SECONDS, Tribe__Cache_Listener::TRIGGER_SAVE_POST );
			}
		}

		/**
		 * Filters the ticket result after the ticket has been built from the function.
		 *
		 * Note: this value will not be cached and the caching of this value is a duty left to the filtering function.
		 *
		 * @since 6.8.0
		 *
		 * @param WP_Post     $post        The ticket post object to filter and return.
		 * @param int|WP_Post $ticket      The ticket object to fetch.
		 * @param string|null $output      The required return type. One of OBJECT, ARRAY_A, or ARRAY_N, which
		 *                                 correspond to a `WP_Post` object, an associative array, or a numeric array,
		 *                                 respectively. Defaults to `OBJECT`.
		 * @param string      $filter      Type of filter to apply.
		 */
		$post = apply_filters( 'tec_tickets_plus_woo_get_ticket_after', $post, $ticket, $output, $filter );

		if ( OBJECT !== $output ) {
			$post = ARRAY_A === $output ? (array) $post : array_values( (array) $post );
		}

		return $post;
	}
}
