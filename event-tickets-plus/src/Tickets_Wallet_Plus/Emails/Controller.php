<?php
namespace TEC\Tickets_Wallet_Plus\Emails;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets_Wallet_Plus\Emails\Modifiers\Include_Settings_To_RSVP_Email;
use TEC\Tickets_Wallet_Plus\Emails\Modifiers\Include_Settings_To_Ticket_Email;
use TEC\Tickets_Wallet_Plus\Emails\Settings\RSVP_Include_Passes_Setting;

/**
 * Class Provider
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Emails
 */
class Controller extends Controller_Contract {
	/**
	 * Register the controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function do_register(): void {
		$this->add_filters();
	}

	/**
	 * Unregister the controller.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->remove_filters();
	}

	/**
	 * Add the filter hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function add_filters(): void {
		$slug = $this->container->make( RSVP_Include_Passes_Setting::class )->get_slug();
		add_filter( "tec_tickets_wallet_plus_{$slug}_checkbox_list_get_setting_definition", [ $this, 'add_rsvp_fieldset_attributes' ] );
		add_filter( 'tec_tickets_emails_ticket_settings', [ $this, 'add_ticket_email_settings' ] );
		add_filter( 'tec_tickets_emails_rsvp_settings', [ $this, 'add_rsvp_email_settings' ] );
	}

	/**
	 * Remove the filter hooks.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function remove_filters(): void {
		$slug = $this->container->make( RSVP_Include_Passes_Setting::class )->get_slug();
		remove_filter( "tec_tickets_wallet_plus_{$slug}_checkbox_list_get_setting_definition", [ $this, 'add_rsvp_fieldset_attributes' ] );
		remove_filter( 'tec_tickets_emails_ticket_settings', [ $this, 'add_ticket_email_settings' ] );
		remove_filter( 'tec_tickets_emails_rsvp_settings', [ $this, 'add_rsvp_email_settings' ] );
	}

	/**
	 * Add the ticket email settings.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $settings The ticket email settings.
	 *
	 * @return array The ticket email settings.
	 */
	public function add_ticket_email_settings( $settings ) {
		return $this->container->make( Include_Settings_To_Ticket_Email::class )->add_include_pass_settings( $settings );
	}

	/**
	 * Add the rsvp fieldset attributes.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $definition The rsvp setting definition.
	 *
	 * @return array The modified rsvp setting definition.
	 */
	public function add_rsvp_fieldset_attributes( $definition ) {
		return $this->container->make( Include_Settings_To_RSVP_Email::class )->add_fieldset_attributes( $definition );
	}

	/**
	 * Add the rsvp email settings.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $settings The rsvp email settings.
	 *
	 * @return array The modified rsvp email settings.
	 */
	public function add_rsvp_email_settings( $settings ) {
		return $this->container->make( Include_Settings_To_RSVP_Email::class )->add_include_pass_settings( $settings );
	}
}
