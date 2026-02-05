<?php
/**
 * Handles the integration for emails.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets\Emails\Email\Purchase_Receipt;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets_Plus\Waitlist\Emails\Spot_Available;
use TEC\Tickets_Plus\Waitlist\Emails\Subscribed;

/**
 * Class Emails controller.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Emails extends Controller_Contract {
	/**
	 * Registers the filters and actions hooks added by the controller.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_filter( 'tec_tickets_emails_registered_emails', [ $this, 'register_waitlist_emails_to_email_types' ] );
		add_action( 'tec_tickets_plus_waitlist_after_create_subscriber', [ $this, 'send_subscribed_email' ] );
		add_action( 'tec_tickets_plus_waitlist_subscriber_pre_notify', [ $this, 'send_notification_email' ] );

		$this->container->singleton( Spot_Available::class );
		$this->container->singleton( Subscribed::class );
	}

	/**
	 * Removes the filters and actions hooks added by the controller.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_emails_registered_emails', [ $this, 'register_waitlist_emails_to_email_types' ] );
		remove_action( 'tec_tickets_plus_waitlist_after_create_subscriber', [ $this, 'send_subscribed_email' ] );
		remove_action( 'tec_tickets_plus_waitlist_subscriber_pre_notify', [ $this, 'send_notification_email' ] );
	}

	/**
	 * Add the Waitlists emails to the registered email types.
	 *
	 * @since 6.2.0
	 *
	 * @param array<Email_Abstract> $email_types The email types.
	 *
	 * @return array<Email_Abstract> The modified email types.
	 */
	public function register_waitlist_emails_to_email_types( array $email_types ): array {
		$ticket_email_position = 4; // Default position for the Waitlist emails.
		foreach ( $email_types as $position => $email_type ) {
			if ( $email_type instanceof Purchase_Receipt ) {
				$ticket_email_position = $position;
				break;
			}
		}

		// Insert the waitlist emails before the Purchase Receipt email.
		array_splice(
			$email_types,
			$ticket_email_position,
			0,
			[ $this->container->get( Spot_Available::class ), $this->container->get( Subscribed::class ) ]
		);

		return $email_types;
	}

	/**
	 * Send the subscribed email.
	 *
	 * @since 6.2.0
	 *
	 * @param Subscriber $subscriber The subscriber.
	 *
	 * @return void
	 */
	public function send_subscribed_email( Subscriber $subscriber ): void {
		$email = $this->container->get( Subscribed::class );
		$email->hook();
		$email->set_subscriber( $subscriber );
		$email->send();
	}

	/**
	 * Send notification email.
	 *
	 * @since 6.2.0
	 *
	 * @param Subscriber $subscriber The subscriber.
	 *
	 * @return void
	 */
	public function send_notification_email( Subscriber $subscriber ): void {
		$email = $this->container->get( Spot_Available::class );
		$email->hook();
		$email->set_subscriber( $subscriber );
		$email->send();
	}
}
