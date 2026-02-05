<?php

namespace TEC\Tickets_Plus\Commerce;

use Tribe\Tickets\Plus\Attendee_Registration\IAC;
use TEC\Tickets\Commerce\Attendee as ET_Attendee;
use Tribe__Utils__Array as Arr;

use WP_Post;

/**
 * Class Attendee.
 *
 * @since 5.3.0
 *
 * @package TEC\Tickets_Plus\Commerce
 */
class Attendee {
	/**
	 * Meta key holding attendee field data.
	 *
	 * @since 5.3.0
	 *
	 * @var string
	 */
	public static $fields_meta_key = '_tec_tickets_commerce_attendee_fields';

	/**
	 * Modify the key for Attendee registration fields.
	 *
	 * @since 5.3.0
	 *
	 * @param string     $meta_key    Meta key used to fetch attendee registration fields.
	 * @param string|int $ticket_id   Which ticket are we filtering for.
	 * @param string|int $attendee_id Which attendee are we filtering for.
	 *
	 * @return string
	 */
	public function modify_legacy_fields_meta_key( $meta_key, $ticket_id, $attendee_id ) {

		if ( ! ET_Attendee::is_valid( $attendee_id ) ) {
			return $meta_key;
		}

		return static::$fields_meta_key;
	}

	/**
	 * Modify the object for attendee with teh Fields for Registration Meta.
	 *
	 * @since 5.3.0
	 *
	 * @param WP_Post $post   The attendee post object, decorated with a set of custom properties.
	 * @param string  $output The output format to use.
	 * @param string  $filter The filter, or context of the fetch.
	 *
	 * @return WP_Post
	 */
	public function inject_attendee_object_fields( $post, $output, $filter ) {
		/* @var $iac IAC */
		$iac = tribe( 'tickets-plus.attendee-registration.iac' );

		// Store the IAC settings.
		$post->iac = $iac->get_iac_setting_for_ticket( $post->ticket_id );

		$fields = get_post_meta( $post->ID, static::$fields_meta_key, true );

		if ( IAC::NONE_KEY !== $post->iac ) {
			$iac_name_field         = $iac->get_iac_ticket_field_slug_for_name();
			$iac_email_field        = $iac->get_iac_ticket_field_slug_for_email();
			$iac_email_resend_field = $iac->get_iac_ticket_field_slug_for_resend_email();

			if ( isset( $fields[ $iac_name_field ] ) ) {
				unset( $fields[ $iac_name_field ] );
			}

			if ( isset( $fields[ $iac_email_field ] ) ) {
				unset( $fields[ $iac_email_field ] );
			}

			if ( isset( $fields[ $iac_email_resend_field ] ) ) {
				unset( $fields[ $iac_email_resend_field ] );
			}
		}

		// After modifications needed to the fields we save into the Attendee Object.
		$post->attendee_meta = $fields;

		return $post;
	}

	/**
	 * Modify a set of arguments to match the usage around Fields used for Registration.
	 *
	 * @since 5.3.0
	 *
	 * @param array $args  The attendee creation args.
	 * @param array $item  Which cart item this args are for.
	 * @param int   $index Which Attendee index we are generating.
	 *
	 * @return array
	 */
	public function inject_fields_args( $args, $item, $index ) {
		$fields = Arr::get( $item, [ 'extra', 'attendees', $index + 1, 'meta' ], [] );

		if ( empty( $fields ) ) {
			return $args;
		}

		$args['fields'] = $fields;

		return $args;
	}

	/**
	 * Modify a set of arguments to match the usage around individual collection.
	 *
	 * @since 5.3.0
	 *
	 * @param array                          $args   The attendee creation args.
	 * @param \Tribe__Tickets__Ticket_Object $ticket The ticket the attendee is generated for.
	 *
	 * @return array
	 */
	public function inject_individual_collection_args( $args, $ticket ) {
		if ( empty( $args['fields'] ) ) {
			return $args;
		}

		/* @var $iac IAC */
		$iac = tribe( 'tickets-plus.attendee-registration.iac' );
		// Dont modify when IAC is not active.
		if ( IAC::NONE_KEY === $iac->get_iac_setting_for_ticket( $ticket->ID ) ) {
			return $args;
		}

		$iac_name_field         = $iac->get_iac_ticket_field_slug_for_name();
		$iac_email_field        = $iac->get_iac_ticket_field_slug_for_email();
		$iac_email_resend_field = $iac->get_iac_ticket_field_slug_for_resend_email();
		$full_name              = Arr::get( $args['fields'], $iac_name_field );
		if ( ! empty( $full_name ) ) {
			$args['full_name'] = $full_name;

			if ( isset( $args['fields'][ $iac_name_field ] ) ) {
				unset( $args['fields'][ $iac_name_field ] );
			}
		}

		$email = Arr::get( $args['fields'], $iac_email_field );
		if ( ! empty( $email ) ) {
			$args['email'] = $email;

			if ( isset( $args['fields'][ $iac_email_field ] ) ) {
				unset( $args['fields'][ $iac_email_field ] );
			}
		}

		// This particular value is only transitional.
		$resend = Arr::get( $args['fields'], $iac_email_resend_field );
		if ( ! empty( $resend ) ) {
			if ( isset( $args['fields'][ $iac_email_resend_field ] ) ) {
				unset( $args['fields'][ $iac_email_resend_field ] );
			}
		}

		return $args;
	}
}
