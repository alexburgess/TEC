<?php
/**
 * Includes Apple Wallet passes into the tickets email.
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers
 */

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers;

use TEC\Tickets\Emails\Email\Purchase_Confirmation_Email_Interface;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Controller as Apple_Wallet_Controller;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Enable_Passes_Setting;
use TEC\Tickets_Wallet_Plus\Template;
use TEC\Tickets\Emails\Email\RSVP;
use TEC\Tickets_Wallet_Plus\Emails\Settings\RSVP_Include_Passes_Setting;
use TEC\Tickets_Wallet_Plus\Emails\Settings\Ticket_Include_Passes_Setting;

/**
 * Class Include_To_Tickets_Email
 *
 * Includes Apple Wallet passes into the tickets email.
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers
 */
class Include_To_Tickets_Email extends Modifier_Abstract {

	/**
	 * @inheritDoc
	 */
	public function get_key(): string {
		return 'apple-wallet/include_to_tickets_email';
	}

	/**
	 * @inheritDoc
	 */
	protected function add_actions(): void {
		add_action( 'tribe_template_before_include:tickets/emails/template-parts/header/head/styles', [ $this, 'include_styles' ], 10, 3 );
		add_action( 'tribe_template_after_include:tickets/emails/template-parts/body/ticket/number-from-total', [ $this, 'embed_in_email' ], 20, 3 );
	}

	/**
	 * @inheritDoc
	 */
	protected function remove_actions(): void {
		remove_action( 'tribe_template_before_include:tickets/emails/template-parts/header/head/styles', [ $this, 'include_styles' ], 10 );
		remove_action( 'tribe_template_after_include:tickets/emails/template-parts/body/ticket/number-from-total', [ $this, 'embed_in_email' ], 20 );
	}

	/**
	 * Includes apple pass styles in email body for Event Tickets Wallet Plus.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param \Tribe__Template $parent_template Event Tickets template object.
	 *
	 * @return void
	 */
	public function include_styles( $parent_template ): void {
		tribe( Template::class )->template( 'apple-wallet/emails/template-parts/header/head/styles' );
	}

	/**
	 * Includes apple passes in the tickets email.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string           $file        Template file being included.
	 * @param string           $name        Name of the template.
	 * @param \Tribe__Template $et_template Event Tickets template object.
	 *
	 * @return void
	 */
	public function embed_in_email( $file, $name, $et_template ): void {
		// Early bail: Check if the provided object is an instance of \Tribe__Template.
		if ( ! $et_template instanceof \Tribe__Template ) {
			return;
		}

		// Get the email object from template values.
		$email_object = $et_template->get_values()['email'];

		// Determine if passes are enabled based on the email object type.
		$is_enabled        = false;
		$apple_wallet_slug = tribe( Apple_Wallet_Controller::class )->get_slug();
		$preview           = ! empty( $et_template->get_values()['preview'] ?? false );
		if ( $email_object instanceof RSVP ) {
			$is_enabled = $preview ?
				tribe_is_truthy( tribe_get_request_var( 'includeWalletPlusPasses', false ) )
				: tribe( RSVP_Include_Passes_Setting::class )->is_pass_included( $apple_wallet_slug );
		} elseif ( $email_object instanceof Purchase_Confirmation_Email_Interface ) {
			$is_enabled = $preview ?
				tribe_is_truthy( tribe_get_request_var( 'includeWalletPlusPasses', false ) )
				: tribe( Ticket_Include_Passes_Setting::class )->is_pass_included( $apple_wallet_slug );
		}

		// Early bail: If neither RSVP nor Ticket types are enabled for passes.
		if ( ! $is_enabled ) {
			return;
		}

		// Early bail: If the global setting for passes is disabled.
		if ( ! tribe( Enable_Passes_Setting::class )->get_value() ) {
			return;
		}

		// Fetch local template values.
		$args = $et_template->get_local_values();

		// Early bail: If 'attendee_id' is empty.
		if ( empty( $args['ticket']['attendee_id'] ) ) {
			return;
		}

		// Fetch the Apple Wallet image and pass URL.

		$pass                   = Pass::from_attendee( $args['ticket']['attendee_id'] );
		$apple_wallet_image_src = $pass->get_button_image_url( null, true );
		$apple_wallet_pass_url  = $pass->get_url();

		// If the email is being previewed then default `$apple_wallet_pass_url` to a empty url.
		$is_being_previewed = $et_template->get( 'is_preview' );
		if ( $is_being_previewed ) {
			$apple_wallet_pass_url = '#';
		}

		// Early bail: If the Apple Wallet pass URL is empty.
		if ( empty( $apple_wallet_pass_url ) ) {
			return;
		}

		// Prepare arguments for the template.
		$args = [
			'apple_wallet_image_src' => $apple_wallet_image_src,
			'apple_wallet_pass_url'  => $apple_wallet_pass_url,
		];

		// Include the Apple Pass in the email template.
		tribe( Template::class )->template( 'apple-wallet/emails/template-parts/body/apple-pass', $args, true );
	}
}
