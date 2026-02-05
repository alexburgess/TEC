<?php
/**
 * The Seating feature controller, dependent on the Event Tickets controller for the Seating feature.
 *
 * @since 6.1.0
 *
 * @package TEC\Tickets_Plus\Seating;
 */

namespace TEC\Tickets_Plus\Seating;

use TEC\Common\lucatume\DI52\Container;
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Asset;
use TEC\Common\StellarWP\Assets\Config;
use TEC\Tickets\Commerce\Cart;
use TEC\Tickets\Seating\Frontend;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Pass;
use Tribe\Tickets\Plus\Attendee_Registration\Modal;
use Tribe__Template as Template;
use Tribe__Tickets__Attendee_Registration__Main as Attendee_Registration;
use Tribe__Tickets_Plus__Main as ET_Plus;
use TEC\Tickets\Seating\Meta;
use Tribe__Tickets__Ticket_Object as Ticket_Object;
use TEC\Tickets\Seating\Commerce\Controller as ET_Seating_Commerce_Controller;

/**
 * Class Controller.
 *
 * @since 6.1.0
 *
 * @package TEC\Tickets_Plus\Seating;
 */
class Controller extends Controller_Contract {
	/**
	 * A memoized flag indicating whether the modal is currently being used to collect Attendee Registration information or not.
	 *
	 * @since 6.1.0
	 *
	 * @var bool|null
	 */
	private ?bool $is_using_modal_flag = null;

	/**
	 * A reference to Attendee data handler
	 *
	 * @since 6.1.0
	 *
	 * @var Attendee
	 */
	private Attendee $attendee;

	/**
	 * Controller constructor.
	 *
	 * @since 6.1.0
	 *
	 * @param Container $container The DI container.
	 * @param Attendee  $attendee The Attendee data handler.
	 */
	public function __construct(
		Container $container,
		Attendee $attendee
	) {
		parent::__construct( $container );
		$this->attendee = $attendee;
	}

	/**
	 * Registers the implementations and subscribes to the hooks required to support the Seating feature
	 * in the context of Events Tickets Plus.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		Config::add_group_path( 'etp-seating', ET_Plus::instance()->plugin_path . 'build/', 'Seating/' );

		/**
		 * Register the Seating AJAX controller.
		 */
		$this->container->register( Ajax::class );

		$this->container->register_on_action( 'woocommerce_loaded', Commerce\Woo\Controller::class );
		
		/**
		 * Register Tickets Commerce controller for seating.
		 * The seating controller for Tickets Commerce in Event Tickets is only loaded if Tickets Commerce is active.
		 */
		$this->container->register_on_action( 'tec_container_registered_provider_' . ET_Seating_Commerce_Controller::class, Commerce\TicketsCommerce\Controller::class );

		add_filter( 'tec_tickets_seating_tickets_block_html', [ $this, 'filter_tickets_block_html' ], 10, 2 );
		add_action( 'template_redirect', [ $this, 'register_ar_assets_on_ar_page' ] );
		add_action( 'template_redirect', [ $this, 'register_ar_assets_on_checkout_page' ] );

		// Load block editor assets.
		add_action( 'admin_init', [ $this, 'register_block_editor_assets' ] );

		// Inject seating label for passes.
		add_filter( 'tec_tickets_wallet_plus_pdf_pass_template_vars', [ $this, 'filter_pdf_pass_template_context' ] );
		add_filter( 'tec_tickets_wallet_plus_pdf_sample_template_context', [ $this, 'filter_pdf_pass_sample_data' ] );
		add_filter( 'tec_tickets_wallet_plus_apple_pass_data', [ $this, 'filter_apple_pass_data' ], 10, 2 );
		add_filter( 'tec_tickets_wallet_plus_apple_preview_pass_data', [ $this, 'filter_apple_pass_preview_data' ] );
		add_filter( 'tribe_tickets_plus_ticket_is_unlimited', [ $this, 'filter_the_ticket_is_unlimited' ], 10, 2 );
	}

	/**
	 * Unsubscribes from the hooks required to support the Seating feature.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_filter( 'tec_tickets_seating_tickets_block_html', [ $this, 'filter_tickets_block_html' ] );
		remove_action( 'template_redirect', [ $this, 'register_ar_assets_on_ar_page' ] );
		remove_action( 'template_redirect', [ $this, 'register_ar_assets_on_checkout_page' ] );
		remove_action( 'admin_init', [ $this, 'register_block_editor_assets' ] );

		remove_filter( 'tec_tickets_wallet_plus_pdf_pass_template_vars', [ $this, 'filter_pdf_pass_template_context' ] );
		remove_filter( 'tec_tickets_wallet_plus_pdf_sample_template_context', [ $this, 'filter_pdf_pass_sample_data' ] );
		remove_filter( 'tec_tickets_wallet_plus_apple_pass_data', [ $this, 'filter_apple_pass_data' ] );
		remove_filter( 'tec_tickets_wallet_plus_apple_preview_pass_data', [ $this, 'filter_apple_pass_preview_data' ] );
		remove_filter( 'tribe_tickets_plus_ticket_is_unlimited', [ $this, 'filter_the_ticket_is_unlimited' ] );
	}

	/**
	 * Filters whether the ticket is unlimited or not.
	 *
	 * Seated tickets can't be unlimited.
	 *
	 * @since 6.1.0
	 *
	 * @param bool          $is_unlimited Whether the ticket is unlimited or not.
	 * @param Ticket_Object $ticket The ticket object.
	 *
	 * @return bool Whether the ticket is unlimited or not.
	 */
	public function filter_the_ticket_is_unlimited( bool $is_unlimited, Ticket_Object $ticket ): bool {
		if ( ! $is_unlimited ) {
			return $is_unlimited;
		}

		return ! ( (bool) get_post_meta( $ticket->ID, Meta::META_KEY_ENABLED, true ) );
	}

	/**
	 * Renders the AR modal in the context of the Seating feature.
	 *
	 * @since 6.1.0
	 *
	 * @param string   $html     The HTML rendered by the Seating feature for the frontend ticket block.
	 * @param Template $template The Event Tickets template instance used to render the HTML so far.
	 *
	 * @return string The HTML rendered by the Seating feature plus the AR modal HTML, if required.
	 */
	public function filter_tickets_block_html( $html, $template ): string {
		if ( ! ( is_string( $html ) && $template instanceof Template ) ) {
			return $html;
		}

		$this->register_ar_assets();

		if ( ! $this->is_using_modal() ) {
			return $html;
		}

		return $this->render_ar_modal( $html, $template );
	}

	/**
	 * Returns whether the modal is currently being used to collect Attendee Registration information or not.
	 *
	 * @since 6.1.0
	 *
	 * @return bool Whether the modal is currently being used to collect Attendee Registration information or not.
	 */
	private function is_using_modal(): bool {
		if ( null !== $this->is_using_modal_flag ) {
			return $this->is_using_modal_flag;
		}

		if ( ! $this->container->isBound( 'tickets.attendee_registration' ) ) {
			return false;
		}

		/** @var Attendee_Registration $attendee_registration */
		$attendee_registration = $this->container->get( 'tickets.attendee_registration' );

		$this->is_using_modal_flag = $attendee_registration->is_modal_enabled();

		return $this->is_using_modal_flag;
	}

	/**
	 * Returns the data to be used in the AR modal when used in the context of the Seating feature.
	 *
	 * @since 6.1.0
	 *
	 * @return array{
	 *     isUsingModal: boolean,
	 *     arModalObjectName: string,
	 *     arModalContentSelector: string,
	 *     seatSelectionModalObjectName: string,
	 *     arPageUrl: string,
	 * } The data to be used in the AR modal when used in the context of the Seating feature.
	 */
	public function get_ar_modal_data(): array {
		return [
			'isUsingModal'                 => $this->is_using_modal(),
			'arModalObjectName'            => 'dialog_obj_tec-tickets-ar-modal',
			'arModalContentSelector'       => "[data-js='dialog-content-tec-tickets-ar-modal']",
			'seatSelectionModalObjectName' => 'dialog_obj_' . Frontend::MODAL_ID,
			'arPageUrl'                    => add_query_arg( [ Cart::$url_query_arg => Cart::REDIRECT_MODE ] ),
		];
	}

	/**
	 * Registers the assets required to manipulate the AR modal in the context of the Seating feature.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function register_ar_assets_on_ar_page(): void {
		/** @var Attendee_Registration */
		if ( ! tribe( 'tickets.attendee_registration' )->is_on_page() ) {
			return;
		}

		$this->register_ar_with_seating_assets();
	}

	/**
	 * Registers the assets required to manipulate the Commerce checkout page.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	public function register_ar_assets_on_checkout_page(): void {
		Asset::add(
			'tec-tickets-plus-seating-frontend-checkout-page',
			'frontend/checkout.js',
			ET_Plus::VERSION
		)
			->add_to_group_path( 'etp-seating' )
			->set_dependencies( 'tec-tickets-seating-frontend' )
			->set_condition(
				fn() => $this->is_checkout_page() && $this->should_register_ar_assets_for_seating()
			)
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Registers the assets required to manipulate the AR page in the context of the Seating feature.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	protected function register_ar_with_seating_assets() {
		Asset::add(
			'tec-tickets-plus-seating-frontend-ar-page',
			'frontend/attendeeRegistration.js',
			ET_Plus::VERSION
		)
			->add_to_group_path( 'etp-seating' )
			->set_dependencies(
				'tec-tickets-seating-frontend',
				'tribe-tickets-plus-modal',
			)
			->set_condition( fn() => $this->should_register_ar_assets_for_seating() )
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Registers the assets required to manipulate the AR modal in the context of the Seating feature.
	 *
	 * @since 6.1.0
	 *
	 * @return void
	 */
	private function register_ar_assets(): void {
		Asset::add(
			'tec-tickets-plus-seating-frontend',
			'frontend/ticketsBlock.js',
			ET_Plus::VERSION
		)
			->add_to_group_path( 'etp-seating' )
			->add_localize_script( 'tec.tickets.seating.frontend.arModal', [ $this, 'get_ar_modal_data' ] )
			->set_dependencies(
				'tec-tickets-seating-frontend',
				'tec-tickets-seating-currency',
				'tribe-tickets-plus-modal' // See note below.
			)
			->enqueue_on( 'wp_enqueue_scripts' )
			->add_to_group( 'tec-tickets-seating-frontend' )
			->add_to_group( 'tec-tickets-seating' )
			->register();

		$this->register_ar_with_seating_assets();

		/*
		 * Note: the script with slug 'tribe-tickets-plus-modal' is still needed for the AR modal to work correctly.
		 * It's being loaded and enqueued to handle the AR/IAC fields when they appear in the modal.
		 */
	}

	/**
	 * Renders the AR modal for the purpose of showing it in the context of the Seating feature.
	 *
	 * @since 6.1.0
	 *
	 * @param string   $html     The Tickets block HTML.
	 * @param Template $template The Event Tickets template instance used to render the HTML so far.
	 *
	 * @return string The HTML rendered by the Seating feature plus the AR modal HTML.
	 */
	private function render_ar_modal( string $html, Template $template ): string {
		$filter_modal_arguments = static function ( array $modal_args ) use ( &$filter_modal_arguments ): array {
			// Immediately remove the filter, we don't want to run it a second time.
			remove_filter( 'tec_tickets_ar_modal_arguments', $filter_modal_arguments );

			// Do not display the button that would normally trigger the show of the AR modal.
			$modal_args['button_display'] = false;

			// Append the AR modal to the same element the Seat Selection modal is appended to.
			$modal_args['append_target'] = '.tec-tickets-seating__information';

			return $modal_args;
		};

		$filter_modal_id = static function () use ( &$filter_modal_id ): string {
			// Immediately remove the filter, we don't want to run it a second time.
			remove_filter( 'tec_tickets_ar_modal_id', $filter_modal_id );

			return 'tec-tickets-ar-modal';
		};

		// Use Closures that will immediately remove themselves from the filter to modify the arguments.
		add_filter( 'tec_tickets_ar_modal_arguments', $filter_modal_arguments );
		add_filter( 'tec_tickets_ar_modal_id', $filter_modal_id );

		/*
		 * The modal would normally render replacing the confirmation button of the Tickets block.
		 * When using Seating, that HTML will not print causing the AR modal not to be printed at all.
		 * Here we print the AR modal HTML appending it to the Tickets block HTML.
		 */
		ob_start();

		/** @var Modal $modal */
		$modal = tribe( 'tickets-plus.attendee-registration.modal' );
		$modal->render_modal_submit_button( '', [], $template );

		// Render the error templates used by the modal.
		tribe( 'tickets-plus.attendee-registration.iac.hooks' )->render_unique_error_templates();

		$modal_html = ob_get_clean();

		/*
		 * Each dialog requires a "trigger" element to read information from.
		 * The trigger element is, usually, the button that triggers the dialog opening.
		 * In this case, there is no button to open the AR dialog directly, so we create an element
		 * that contains the information the AR dialog will need to correctly render.
		 * The attributes of the element come from the AR modal id attribute set above.
		 */
		$modal_html .= <<< HTML
		<div
			style="display: none;"
			id="#tec-tickets-ar-modal-target"
			data-js="trigger-dialog-tec-tickets-ar-modal"
			data-content="dialog-content-tec-tickets-ar-modal"
		>
		</div>
		HTML;


		return $html . $modal_html;
	}

	/**
	 * Filter PDF pass template context.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string,mixed> $context Template context.
	 *
	 * @return array<string,mixed> Filtered Template context.
	 */
	public function filter_pdf_pass_template_context( array $context ): array {
		return $this->attendee->filter_pdf_pass_template_context( $context );
	}

	/**
	 * Filter PDF pass sample template context.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string,mixed> $context Template context.
	 *
	 * @return array<string,mixed> Filtered Template context.
	 */
	public function filter_pdf_pass_sample_data( array $context ): array {
		return $this->attendee->filter_pdf_pass_sample_data( $context );
	}

	/**
	 * Filter Apple pass data.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string,mixed> $data The pass data.
	 * @param Pass                $pass The pass object.
	 *
	 * @return array<string,mixed> The filtered pass data.
	 */
	public function filter_apple_pass_data( array $data, Pass $pass ): array {
		return $this->attendee->filter_apple_pass_data( $data, $pass );
	}

	/**
	 * Filter Apple pass preview data.
	 *
	 * @since 6.1.0
	 *
	 * @param array<string,mixed> $data The pass data.
	 *
	 * @return array<string,mixed> The filtered pass data.
	 */
	public function filter_apple_pass_preview_data( array $data ): array {
		return $this->attendee->filter_apple_pass_preview_data( $data );
	}

	/**
	 * Registers the block editor assets required for the Seating feature.
	 *
	 * @since 6.3.0
	 *
	 * @return void
	 */
	public function register_block_editor_assets() {
		Asset::add(
			'tec-tickets-plus-seating-block-editor',
			'blockEditor.js',
			ET_Plus::VERSION
		)
			->add_to_group_path( 'etp-seating' )
			->set_dependencies(
				'tec-tickets-seating-block-editor',
			)
			->enqueue_on( 'enqueue_block_editor_assets' )
			->add_to_group( 'tec-tickets-seating-editor' )
			->add_to_group( 'tec-tickets-seating' )
			->register();
	}

	/**
	 * Determines whether the AR assets should be registered for the Seating feature.
	 *
	 * @since 6.3.0
	 * @since 6.3.0.2 Checks whether tec_tickets_commerce_is_enabled() before the main check.
	 * @since 6.8.4 Moved Tickets Commerce checks into separate Controller.
	 *
	 * @return bool Whether the AR assets should be registered for the Seating feature.
	 */
	public function should_register_ar_assets_for_seating(): bool {
		/**
		 * Filters whether the AR assets should be registered for the Seating feature.
		 *
		 * @since 6.3.0
		 * @since 6.8.4 Updated default value to be false.
		 *
		 * @param bool $has_seating_tickets Whether the cart has seating tickets or not.
		 *
		 * @return bool Whether the AR assets should be registered for the Seating feature.
		 */
		return apply_filters( 'tec_tickets_plus_seating_register_ar_assets', false );
	}

	/**
	 * Registers the AR assets for the Seating feature on the checkout page.
	 *
	 * @since 6.3.0
	 * @since 6.3.0.2 Checks whether tec_tickets_commerce_is_enabled() before the main check.
	 * @since 6.8.4 Moved Tickets Commerce checks into separate Controller.
	 *
	 * @return bool True if it is a valid checkout page.
	 */
	public function is_checkout_page(): bool {
		/**
		 * Filters whether the current page is the checkout page.
		 *
		 * @since 6.3.0
		 * @since 6.8.4 Updated default value to be false.
		 *
		 * @param bool $checkout Whether the current page is the checkout page.
		 *
		 * @return bool Whether the current page is the checkout page.
		 */
		return apply_filters( 'tec_tickets_plus_seating_is_checkout_page', false );
	}
}
