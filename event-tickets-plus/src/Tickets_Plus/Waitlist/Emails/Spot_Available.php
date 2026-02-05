<?php
/**
 * Spot Available Email template.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist\Emails;

use TEC\Tickets\Emails\Dispatcher;
use TEC\Tickets\Emails\Email_Abstract;
use TEC\Tickets_Plus\Waitlist\Subscriber;

/**
 * Class Spot_Available.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Spot_Available extends Email_Abstract {
	/**
	 * Email ID.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public $id = 'tec_tickets_emails_waitlist_spot_available';

	/**
	 * Email slug.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public $slug = 'waitlist-spot-available';

	/**
	 * Email template.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public $template = 'spot-available';

	/**
	 * Return the email recipient type string.
	 *
	 * @since 6.2.0
	 *
	 * @return string The email recipient type string.
	 */
	public function get_to(): string {
		return esc_html__( 'Waitlist Subscriber', 'event-tickets-plus' );
	}

	/**
	 * Returns the settings fields for the email.
	 *
	 * @since 6.2.0
	 *
	 * @return array<string,mixed> The settings fields for the email.
	 */
	public function get_settings_fields(): array {
		$kb_link = sprintf(
			'<a href="https://evnt.is/event-tickets-emails" target="_blank" rel="noopener noreferrer">%s</a>',
			esc_html__( 'Knowledgebase', 'event-tickets-plus' )
		);

		$email_description = sprintf(
		// Translators: %1$s is the knowledgebase link.
			esc_html_x(
				'Waitlist subscribers will receive an email confirming their subscription. Customize the content of this specific email using the tools below. The brackets {event_name}, and {ticket_name} can be used to pull dynamic content from the ticket into your email. Learn more about customizing email templates in our %1$s.',
				'Email description',
				'event-tickets-plus'
			),
			$kb_link
		);

		return [

			'tec-settings-email-template-wrapper_start'   => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__header-block--horizontal">',
			],
			'tec-settings-email-template-header'          => [
				'type' => 'html',
				'html' => '<h3>' . esc_html_x( 'Waitlist Spot Available', 'Email Title', 'event-tickets-plus' ) . '</h3>',
			],
			'info-box-description'                        => [
				'type' => 'html',
				'html' => '<p class="tec-settings-form__section-description">'
							. $email_description
							. '</p><br/>',
			],
			[
				'type' => 'html',
				'html' => '</div>',
			],
			'tec-settings-email-template-settings-wrapper-start' => [
				'type' => 'html',
				'html' => '<div class="tec-settings-form__content-section">',
			],
			'tec-settings-email-template-settings'        => [
				'type' => 'html',
				'html' => '<h3 class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . esc_html__( 'Settings', 'event-tickets-plus' ) . '</h3>',
			],
			'tec-settings-email-template-settings-wrapper-end' => [
				'type' => 'html',
				'html' => '</div>',
			],
			$this->get_option_key( 'enabled' )            => [
				'type'            => 'toggle',
				'label'           => sprintf(
				// Translators: %s - Title of email.
					esc_html__( '%s Email', 'event-tickets-plus' ),
					$this->get_title()
				),
				'default'         => true,
				'validation_type' => 'boolean',
			],
			$this->get_option_key( 'subject' )            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Subject', 'event-tickets-plus' ),
				'default'             => $this->get_default_subject(),
				'placeholder'         => $this->get_default_subject(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'heading' )            => [
				'type'                => 'text',
				'label'               => esc_html__( 'Heading', 'event-tickets-plus' ),
				'default'             => $this->get_default_heading(),
				'placeholder'         => $this->get_default_heading(),
				'size'                => 'large',
				'validation_callback' => 'is_string',
			],
			$this->get_option_key( 'additional-content' ) => [
				'type'            => 'wysiwyg',
				'label'           => esc_html__( 'Content', 'event-tickets-plus' ),
				'default'         => $this->get_default_additional_content(),
				'size'            => 'large',
				'validation_type' => 'html',
				'settings'        => [
					'media_buttons' => false,
					'quicktags'     => false,
					'editor_height' => 200,
					'buttons'       => [
						'bold',
						'italic',
						'underline',
						'strikethrough',
						'alignleft',
						'aligncenter',
						'alignright',
						'link',
					],
				],
			],
		];
	}

	/**
	 * Get email title.
	 *
	 * @since 6.2.0
	 *
	 * @return string The email title.
	 */
	public function get_title(): string {
		return esc_html_x( 'Waitlist Spot Available', 'Email title', 'event-tickets-plus' );
	}

	/**
	 * Get default subject.
	 *
	 * @since 6.2.0
	 *
	 * @return string The default subject.
	 */
	public function get_default_subject(): string {
		return esc_html_x( 'There are available tickets for {event_name}', 'Default subject for the spot available email', 'event-tickets-plus' );
	}

	/**
	 * Get default heading.
	 *
	 * @since 6.2.0
	 *
	 * @return string The default heading.
	 */
	public function get_default_heading(): string {
		return esc_html_x( '{event_name} now has tickets available!', 'Default heading for the spot available email', 'event-tickets-plus' );
	}

	/**
	 * Default content to show below email content.
	 *
	 * @since 6.2.0
	 *
	 * @return string
	 */
	public function get_default_additional_content(): string {
		// phpcs:disable Squiz.PHP.CommentedOutCode.Found, PEAR.Functions.FunctionCallSignature
		// Translators: (1), (2), (3), (4) and (5) are dynamic variables {waitlist_subscriber_name}, {site_title}, {event_hyperlink}, {available_ticket_names} and {event_url}.
		return sprintf( esc_html_x( 'Hi %1$s,
We are reaching out to you because you requested to be notified when a ticket became available on %2$s.

The following tickets are now available for %3$s:
%4$s

Go get them here %5$s!', 'Default content for the spot available email', 'event-tickets-plus' ), '{waitlist_subscriber_name}', '{site_title}', '{event_hyperlink}', '{available_ticket_names}', '{event_url}' );
		// phpcs:enable Squiz.PHP.CommentedOutCode.Found, PEAR.Functions.FunctionCallSignature
	}

	/**
	 * Set the subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @param Subscriber $subscriber The subscriber.
	 *
	 * @return void
	 */
	public function set_subscriber( Subscriber $subscriber ): void {
		$this->set( 'subscriber', $subscriber );
		$this->recipient = $subscriber->get_email();
	}

	/**
	 * Get default preview context for email.
	 *
	 * @since 6.2.0
	 *
	 * @param array $args The arguments.
	 *
	 * @return array $args The modified arguments
	 */
	public function get_default_preview_context( $args = [] ): array {
		$subscriber = tribe( Subscriber::class )->set_fullname( 'John Doe' )->set_email( 'john@gmail.com' );

		$placeholders = [
			'{waitlist_subscriber_name}' => esc_html__( 'John Doe', 'event-tickets-plus' ),
			'{event_name}'               => esc_html__( 'Cutting Onions Like a Pro', 'event-tickets-plus' ),
			'{event_url}'                => 'https://example.com/event/',
			'{available_ticket_names}'   => 'Test Ticket 1, Test Ticket 2',
			'{event_hyperlink}'          => sprintf( '<a href="https://example.com/event/">%s</a>', esc_html__( 'Cutting Onions Like a Pro', 'event-tickets-plus' ) ),
		];

		$this->set_placeholders( $placeholders );

		$defaults = [
			'email'              => $this,
			'is_preview'         => true,
			'title'              => $this->get_heading(),
			'heading'            => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'subscriber'         => $subscriber,
		];

		return wp_parse_args( $args, $defaults );
	}

	/**
	 * Get default template context for email.
	 *
	 * @since 6.2.0
	 *
	 * @return array $args The default arguments
	 */
	public function get_default_template_context(): array {
		return [
			'email'              => $this,
			'title'              => $this->get_title(),
			'heading'            => $this->get_heading(),
			'additional_content' => $this->get_additional_content(),
			'subscriber'         => $this->get( 'subscriber' ),
		];
	}

	/**
	 * Send the email.
	 *
	 * @since 6.2.0
	 *
	 * @return bool Whether the email was sent or not.
	 */
	public function send() {
		$recipient = $this->get_recipient();

		// Bail if there is no email address to send to.
		if ( empty( $recipient ) ) {
			return false;
		}

		if ( ! $this->is_enabled() ) {
			return false;
		}

		$subscriber = $this->get( 'subscriber' );

		// Bail if there's no order.
		if ( ! $subscriber instanceof Subscriber ) {
			return false;
		}

		$waitlist = $subscriber->get_waitlist();

		$tickets_about_to_go_to_sale = $waitlist ? $waitlist->get_tickets_about_to_go_on_sale() : [];
		$tickets_on_sale             = $waitlist ? $waitlist->get_tickets_on_sale() : [];

		$tickets = array_merge( $tickets_about_to_go_to_sale, $tickets_on_sale );

		$placeholders = [
			'{waitlist_subscriber_name}' => $subscriber->get_fullname(),
			'{event_name}'               => esc_html( get_the_title( $subscriber->get_post_id() ) ),
			'{event_url}'                => esc_url( get_the_permalink( $subscriber->get_post_id() ) ),
			'{available_ticket_names}'   => esc_html( implode( ', ', array_map( fn( $ticket_id ) => get_the_title( $ticket_id ), $tickets ) ) ),
			'{event_hyperlink}'          => sprintf( '<a href="%s">%s</a>', get_the_permalink( $subscriber->get_post_id() ), get_the_title( $subscriber->get_post_id() ) ),
		];

		$this->set_placeholders( $placeholders );

		return Dispatcher::from_email( $this )->send();
	}
}
