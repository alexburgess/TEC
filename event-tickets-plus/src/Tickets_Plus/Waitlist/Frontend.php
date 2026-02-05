<?php
/**
 * The main front-end controller. This controller will directly, or by delegation, subscribe to
 * front-end related hooks.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\lucatume\DI52\Container;
use TEC\Common\Asset;
use Tribe__Tickets_Plus__Main as Tickets_Plus;
use Tribe__Template as Base_Template;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use Tribe__Date_Utils as Dates;

/**
 * Class Frontend.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Frontend extends Controller_Contract {
	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_plus_waitlist_frontend_registered';

	/**
	 * The Waitlist instance.
	 *
	 * @since 6.2.0
	 *
	 * @var Waitlists
	 */
	private Waitlists $waitlists;

	/**
	 * The Subscribers instance.
	 *
	 * @since 6.2.0
	 *
	 * @var Subscribers
	 */
	private Subscribers $subscribers;

	/**
	 * The template instance.
	 *
	 * @since 6.2.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * The AJAX action to create a subscriber.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const AJAX_CREATE_SUBSCRIBER_ACTION = 'tec_tickets_plus_ajax_create_waitlist_subscriber';

	/**
	 * Frontend constructor.
	 *
	 * @since 6.2.0
	 *
	 * @param Container   $container   The DI container.
	 * @param Waitlists   $waitlists   The Waitlist instance.
	 * @param Subscribers $subscribers The Subscribers instance.
	 * @param Template    $template    The template instance.
	 */
	public function __construct( Container $container, Waitlists $waitlists, Subscribers $subscribers, Template $template ) {
		parent::__construct( $container );
		$this->waitlists   = $waitlists;
		$this->subscribers = $subscribers;
		$this->template    = $template;
	}

	/**
	 * Registers the controller by subscribing to front-end hooks and binding implementations.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		add_action( 'tribe_template_entry_point:tickets/v2/tickets:etp-waitlist', [ $this, 'inject_waitlist_in_tickets_block' ], 10, 3 );
		add_action( 'tribe_template_entry_point:tickets/v2/rsvp/content:etp-waitlist', [ $this, 'inject_waitlist_in_rsvp_block' ], 10, 3 );
		add_filter( 'tribe_template_pre_html:tickets/v2/rsvp', [ $this, 'waitlist_for_rsvp_on_presale' ], 10, 5 );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_CREATE_SUBSCRIBER_ACTION, [ $this, 'ajax_create_subscriber' ] );
		add_action( 'wp_ajax_' . self::AJAX_CREATE_SUBSCRIBER_ACTION, [ $this, 'ajax_create_subscriber' ] );

		// Register the front-end JS.
		Asset::add(
			'tec-tickets-plus-waitlist-frontend-script',
			'waitlist/form.js',
			Tickets_Plus::VERSION
		)
			->add_to_group_path( Tickets_Plus::class )
			->set_dependencies(
				'jquery',
				'tec-ky'
			)
			->enqueue_on( 'tec_tickets_plus_waitlist_after_inject_html_in_block' )
			->add_to_group( 'tec-tickets-plus-waitlist-frontend' )
			->add_to_group( 'tec-tickets-plus-waitlist' )
			->register();

		// // Register the front-end CSS.
		Asset::add(
			'tec-tickets-plus-waitlist-frontend-style',
			'waitlist/form.css',
			Tickets_Plus::VERSION
		)
			->add_to_group_path( Tickets_Plus::class )
			->enqueue_on( 'tec_tickets_plus_waitlist_after_inject_html_in_block' )
			->add_to_group( 'tec-tickets-plus-waitlist-frontend' )
			->add_to_group( 'tec-tickets-plus-waitlist' )
			->register();
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_template_entry_point:tickets/v2/tickets:etp-waitlist', [ $this, 'inject_waitlist_in_tickets_block' ] );
		remove_action( 'tribe_template_entry_point:tickets/v2/rsvp/content:etp-waitlist', [ $this, 'inject_waitlist_in_rsvp_block' ] );
		remove_filter( 'tribe_template_pre_html:tickets/v2/rsvp', [ $this, 'waitlist_for_rsvp_on_presale' ] );
		remove_action( 'wp_ajax_nopriv_' . self::AJAX_CREATE_SUBSCRIBER_ACTION, [ $this, 'ajax_create_subscriber' ] );
		remove_action( 'wp_ajax_' . self::AJAX_CREATE_SUBSCRIBER_ACTION, [ $this, 'ajax_create_subscriber' ] );
	}

	/**
	 * Inject the waitlist markup in the tickets block.
	 *
	 * @since 6.2.0
	 *
	 * @param string        $hookname   The hookname.
	 * @param string        $entrypoint The entrypoint.
	 * @param Base_Template $template   Current instance of the Tribe__Template.
	 *
	 * @return void Outputs the waitlist HTML.
	 */
	public function inject_waitlist_in_tickets_block( string $hookname, string $entrypoint, Base_Template $template ): void {
		$data    = $template->get_values();
		$post_id = $data['post_id'] ?? null;

		if ( ! $post_id ) {
			return;
		}

		if ( tribe( Editor::class )->is_using_asc_seating( $post_id ) ) {
			return;
		}

		$this->inject_waitlist( $post_id );
	}

	/**
	 * Inject the waitlist markup in the RSVP block when there are no RSVPs.
	 *
	 * @since 6.2.0
	 *
	 * @param string        $html     The HTML.
	 * @param string        $file     The file.
	 * @param array         $name     The name.
	 * @param Base_Template $template The template.
	 * @param array         $context  The context.
	 *
	 * @return string|null The HTML.
	 */
	public function waitlist_for_rsvp_on_presale( ?string $html, string $file, array $name, Base_Template $template, array $context ): ?string {
		$post_id = $context['post_id'] ?? null;
		if ( ! $post_id ) {
			return $html;
		}

		if ( ! empty( $context['active_rsvps'] ) ) {
			return $html;
		}

		/** @var \Tribe__Tickets__Editor__Blocks__Rsvp $blocks_rsvp */
		$blocks_rsvp = tribe( 'tickets.editor.blocks.rsvp' );

		$rsvps = $blocks_rsvp->get_tickets( $post_id );

		if ( empty( $rsvps ) || ! is_array( $rsvps ) ) {
			return $html;
		}

		$rsvp = reset( $rsvps );

		if ( ! $rsvp instanceof Ticket_Object ) {
			return $html;
		}

		if ( time() > $rsvp->start_date( true ) ) {
			return $html;
		}

		ob_start();
		$this->inject_waitlist( $post_id, Waitlist::RSVP_TYPE );
		$waitlist_block = ob_get_clean();

		if ( ! $waitlist_block ) {
			return $html;
		}

		ob_start();
		?>
		<div class="tribe-common event-tickets">
			<div class="tribe-tickets__rsvp-wrapper" data-rsvp-id="<?php echo esc_attr( $rsvp->ID ); ?>">
				<div class="tribe-tickets__rsvp tribe-common-g-row tribe-common-g-row--gutters">
					<div class="tribe-common-g-col">
						<div class="tribe-tickets__rsvp-details">
							<h3 class="tribe-tickets__rsvp-title tribe-common-h2 tribe-common-h4--min-medium">
								<?php esc_html_e( 'RSVP', 'event-tickets-plus' ); ?>
							</h3>
							<div class="tribe-tickets__tickets-item tribe-tickets__tickets-item--inactive">
								<div class="tribe-tickets__tickets-item-content tribe-tickets__tickets-item-content--inactive">
									<?php
									// translators: %s: Date that the RSVP will become available.
									printf( esc_html__( 'RSVP will be available on %s', 'event-tickets-plus' ), esc_html( Dates::build_date_object( $rsvp->start_date )->format_i18n( tribe_get_date_format( true ) ) ) );
									?>
								</div>
							</div>
						</div>
					</div>
					<?php echo $waitlist_block; //phpcs:ignore StellarWP.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Inject the waitlist markup in the RSVP block.
	 *
	 * @since 6.2.0
	 *
	 * @param string        $hookname   The hookname.
	 * @param string        $entrypoint The entrypoint.
	 * @param Base_Template $template   Current instance of the Tribe__Template.
	 *
	 * @return void Outputs the waitlist HTML.
	 */
	public function inject_waitlist_in_rsvp_block( string $hookname, string $entrypoint, Base_Template $template ): void {
		if ( 'success' === tec_get_request_var_raw( 'step', '' ) ) {
			/**
			 * RSVP is being re-rendered through AJAX after a successful RSVP.
			 * In this case we don't want to show the waitlist immediately to the user that just RSVP'd.
			 */
			return;
		}

		$data    = $template->get_values();
		$post_id = $data['post_id'] ?? null;

		if ( ! $post_id ) {
			return;
		}

		if ( tribe( Editor::class )->is_using_asc_seating( $post_id ) ) {
			return;
		}

		$this->inject_waitlist( $post_id, Waitlist::RSVP_TYPE );
	}

	/**
	 * Create a subscriber for a waitlist.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function ajax_create_subscriber(): void {
		$waitlist_id = (int) tec_get_request_var_raw( 'waitlistId', 0 );

		if ( ! $waitlist_id ) {
			wp_send_json_error( __( 'Invalid waitlist ID.', 'event-tickets-plus' ), 400 );
			return;
		}

		if ( ! wp_verify_nonce( tec_get_request_var_raw( 'nonce', '' ), self::AJAX_CREATE_SUBSCRIBER_ACTION . '_' . $waitlist_id ) ) {
			wp_send_json_error( __( 'Expired or missing nonce.', 'event-tickets-plus' ), 403 );
			return;
		}

		$waitlist = $this->waitlists->get( $waitlist_id );

		if ( ! ( $waitlist && $waitlist->is_enabled() ) ) {
			wp_send_json_error( __( 'Invalid waitlist.', 'event-tickets-plus' ), 400 );
			return;
		}

		if ( is_user_logged_in() ) {
			$subscribed_already = $this->subscribers->user_already_subscribed_to_waitlist( $waitlist );
			if ( $subscribed_already ) {
				// No need for any noise. We let them know they subscribed.
				wp_send_json_success( null, 200 );
				return;
			}

			$this->subscribers->create_subscriber_for_waitlist(
				$waitlist,
				get_current_user_id()
			);

			wp_send_json_success( null, 200 );
			return;
		}

		$email = sanitize_email( tec_get_request_var_raw( 'email', '' ) );
		$name  = sanitize_text_field( tec_get_request_var_raw( 'name', '' ) );

		if ( ! $name ) {
			wp_send_json_error( __( 'Invalid name entered.', 'event-tickets-plus' ), 400 );
			return;
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error( __( 'Invalid email.', 'event-tickets-plus' ), 400 );
			return;
		}

		$subscribed_already = $this->subscribers->user_already_subscribed_to_waitlist( $waitlist, null, $email );
		if ( $subscribed_already ) {
			// Avoid social engineering. We do not want to disclose to a logged out user if the email was registered or not.
			wp_send_json_success( null, 200 );
			return;
		}

		$this->subscribers->create_subscriber_for_waitlist(
			$waitlist,
			is_user_logged_in() ? get_current_user_id() : 0,
			$email,
			$name
		);

		wp_send_json_success( null, 200 );
	}

	/**
	 * Inject the waitlist markup in the block.
	 *
	 * @since 6.2.0
	 * 
	 * @param int $post_id The post ID.
	 * @param int $type    The type of the block.
	 *
	 * @return void Outputs the waitlist HTML.
	 */
	protected function inject_waitlist( int $post_id, int $type = Waitlist::TICKET_TYPE ): void {
		$waitlist = Waitlist::TICKET_TYPE === $type ?
			$this->waitlists->get_posts_ticket_waitlist( $post_id ) :
			$this->waitlists->get_posts_rsvp_waitlist( $post_id );

		if ( ! $waitlist instanceof Waitlist ) {
			return;
		}

		if ( ! $waitlist->is_active() ) {
			return;
		}

		$hook_prefix = Waitlist::TICKET_TYPE === $type ? 'tickets' : 'rsvp';

		/**
		 * Fires before injecting the HTML for the Waitlist only, for the specific block type: tickets or rsvp.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The Waitlist instance.
		 */
		do_action( "tec_tickets_plus_waitlist_before_inject_html_in_{$hook_prefix}_block", $waitlist );

		/**
		 * Fires before injecting the HTML for the Waitlist only, for any block type.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The Waitlist instance.
		 */
		do_action( 'tec_tickets_plus_waitlist_before_inject_html_in_block', $waitlist );

		$is_unsubscribe = (bool) tec_get_request_var_raw( 'unsubscribed', false );

		$this->template->template(
			'form',
			[
				'is_unsubscribe'  => $is_unsubscribe,
				'waitlist'        => $waitlist,
				'user_subscribed' => $this->subscribers->user_already_subscribed_to_waitlist( $waitlist ),
				'form_title'      => Waitlist::TICKET_TYPE === $type ?
										_x( 'Get notified when tickets become available', 'The title of the waitlist form.', 'event-tickets-plus' ) :
										_x( 'Get notified when you can RSVP', 'The title of the waitlist form.', 'event-tickets-plus' ),
				'success_message' => $this->get_success_message( $type, $is_unsubscribe ),
			]
		);

		/**
		 * Fires after injecting the HTML for the Waitlist only, for the specific block type: tickets or rsvp.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The Waitlist instance.
		 */
		do_action( "tec_tickets_plus_waitlist_after_inject_html_in_{$hook_prefix}_block", $waitlist );

		/**
		 * Fires after injecting the HTML for the Waitlist only, for any block type.
		 *
		 * @since 6.2.0
		 *
		 * @param Waitlist $waitlist The Waitlist instance.
		 */
		do_action( 'tec_tickets_plus_waitlist_after_inject_html_in_block', $waitlist );
	}

	/**
	 * Get the success message for the waitlist form.
	 *
	 * @since 6.2.0
	 *
	 * @param int  $type           The type of the block.
	 * @param bool $is_unsubscribe Whether the form is for unsubscribing.
	 *
	 * @return string The success message.
	 */
	protected function get_success_message( int $type, bool $is_unsubscribe = false ): string {
		if ( $is_unsubscribe ) {
			return _x( 'You have unsubscribed successfully. You will no longer receive email notifications regarding this event.', 'The unsubscribe success message of the waitlist form.', 'event-tickets-plus' );
		}

		if ( Waitlist::TICKET_TYPE === $type ) {
			return _x( "We'll notify you when tickets become available", 'The success message of the waitlist form.', 'event-tickets-plus' );
		}

		return _x( "We'll notify you when you can RSVP", 'The success message of the waitlist form.', 'event-tickets-plus' );
	}
}
