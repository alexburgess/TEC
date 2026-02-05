<?php
/**
 * Handles hooking all the actions and filters used by Tickets Emails.
 *
 * To remove a filter:
 * remove_filter( 'some_filter', [ tribe( TEC\Tickets_Plus\Emails\Hooks::class ), 'some_filtering_method' ] );
 * remove_filter( 'some_filter', [ tribe( 'tickets-plus.emails.hooks' ), 'some_filtering_method' ] );
 *
 * To remove an action:
 * remove_action( 'some_action', [ tribe( TEC\Tickets_Plus\Emails\Hooks::class ), 'some_method' ] );
 * remove_action( 'some_action', [ tribe( 'tickets-plus.emails.hooks' ), 'some_method' ] );
 *
 * @since 5.6.6
 *
 * @package TEC\Tickets_Plus\Emails
 */

namespace TEC\Tickets_Plus\Emails;

use Tribe__Tickets_Plus__QR;
use TEC\Tickets\QR\Settings as QR_Settings;

/**
 * Class Hooks.
 *
 * @since 5.6.6
 *
 * @package TEC\Tickets_Plus
 */
class Hooks extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.6.6
	 */
	public function register() {
		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Adds the actions required by each Tickets Emails component.
	 *
	 * @since 5.6.6
	 */
	protected function add_actions() {
		add_action( 'tribe_template_after_include:tickets/emails/template-parts/body/ticket/ticket-name', [ $this, 'maybe_add_ticket_qr_code' ], 10, 3 );

		// Include Attendee Registration Fields in Ticket & RSVP emails.
		add_action( 'tribe_template_after_include:tickets/emails/template-parts/body/ticket/number-from-total', [ $this, 'maybe_include_attendee_registration_fields_ticket_rsvp_emails' ], 10, 3 );

		add_action( 'tribe_template_after_include:tickets/emails/template-parts/header/head/styles', [ $this, 'maybe_include_ticket_rsvp_styles' ], 10, 3 );
	}

	/**
	 * Adds the filters required by each Tickets Emails component.
	 *
	 * @since 5.6.6
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_emails_settings_email_styling_fields', [ $this, 'filter_tickets_emails_settings' ] );

		// Ticket Email.
		add_filter( 'tec_tickets_emails_ticket_settings', tribe_callback( Email\Ticket::class, 'filter_tec_tickets_emails_ticket_settings' ), 10 );

		// RSVP.
		add_filter( 'tec_tickets_emails_rsvp_settings', tribe_callback( Email\RSVP::class, 'filter_tec_tickets_emails_rsvp_settings' ), 10 );

		add_filter( 'tec_tickets_emails_email_template_context', [ $this, 'filter_email_template_context' ] );

		add_filter( 'tribe_tickets_get_template_part_content', [ $this, 'filter_email_qr_template' ], 10, 6 );
	}

	/**
	 * Filters the list of fields for Tickets Emails settings, and add the footer credit setting.
	 *
	 * @since 5.6.6
	 *
	 * @param array $fields The current list of fields for Tickets Emails settings.
	 *
	 * @return array The filtered list of fields.
	 */
	public function filter_tickets_emails_settings( array $fields ): array {
		return tribe( Settings::class )->add_footer_credit_setting( $fields );
	}

	/**
	 * Adds QR code template, if settings allow.
	 *
	 * @param string           $file        Template file.
	 * @param string           $name        Template name.
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 * @return void
	 */
	public function maybe_add_ticket_qr_code( $file, $name, $et_template ) {

		if ( ! $et_template instanceof \Tribe__Template ) {
			return;
		}

		if ( ! tribe( QR_Settings::class )->is_enabled() ) {
			return;
		}

		$this->container->make( Email\RSVP::class )->maybe_include_qr_code_template( $et_template );
		$this->container->make( Email\Ticket::class )->maybe_include_qr_code_template( $et_template );
	}

	/**
	 * Filters the context array from the email tickets template.
	 *
	 * @since 5.6.6
	 *
	 * @param array $context Context array from event tickets emails template.
	 *
	 * @return array Filtered context.
	 */
	public function filter_email_template_context( $context ): array {

		// Add footer credit option from settings.
		$context['footer_credit'] = tribe_get_option( Settings::$option_footer_credit, true );

		return $context;
	}

	/**
	 * Maybe include Attendee Registration Fields for RSVP and Tickets emails.
	 *
	 * @since 5.6.10
	 *
	 * @param string           $file        Template file.
	 * @param string           $name        Template name.
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 * @return void
	 */
	public function maybe_include_attendee_registration_fields_ticket_rsvp_emails( $file, $name, $et_template ) {
		if ( ! $et_template instanceof \Tribe__Template ) {
			return;
		}

		$this->container->make( Email\RSVP::class )->maybe_include_ar_fields( $et_template );
		$this->container->make( Email\Ticket::class )->maybe_include_ar_fields( $et_template );
	}

	/**
	 * Filter email QR template.
	 *
	 * @since 5.6.10
	 *
	 * @param string $html     The final HTML
	 * @param string $template The Template file, which is a relative path from the Folder we are dealing with
	 * @param string $file     Complete path to include the PHP File
	 * @param string $slug     Slug for this template
	 * @param string $name     Template name
	 * @param array  $data     The Data that will be used on this template
	 *
	 * @return string
	 */
	public function filter_email_qr_template( $html, $template, $file, $slug, $name, $data ): string {
		if ( ! tec_tickets_emails_is_enabled() ) {
			return $html;
		}

		if ( 'tickets-plus/email-qr' !== $slug ) {
			return $html;
		}

		$data['include_qr'] = true;

		return tribe( 'tickets-plus.template' )->template( 'emails/template-parts/body/ticket/qr-image', $data, false );
	}

	/**
	 * Filter to include Ticket & RSVP styles.
	 *
	 * @since 5.7.3
	 *
	 * @param string           $file        Template file.
	 * @param string           $name        Template name.
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 *
	 * @return void
	 */
	public function maybe_include_ticket_rsvp_styles( $file, $name, $et_template ) {
		if ( ! $et_template instanceof \Tribe__Template ) {
			return;
		}

		$this->container->make( Email\RSVP::class )->maybe_include_styles( $et_template );
		$this->container->make( Email\Ticket::class )->maybe_include_styles( $et_template );
	}
}
