<?php
/**
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus;
 */

namespace TEC\Tickets_Wallet_Plus;

use TEC\Common\Contracts\Provider\Controller as Controller_Base;
use TEC\Tickets_Wallet_Plus\Modifiers\Include_To_My_Tickets;
use TEC\Tickets_Wallet_Plus\Modifiers\Include_To_Order_Page;
use TEC\Tickets_Wallet_Plus\Modifiers\Include_To_Attendee_Modal;
use TEC\Tickets_Wallet_Plus\Passes\Manager;

/**
 * Main Wallet Plus Controller.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus;
 */
class Controller extends Controller_Base {
	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail we have the boolean living on the method.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	protected bool $is_active = true;

	/**
	 * @inheritDoc
	 */
	public function is_active(): bool {
		return $this->is_active;
	}

	/**
	 * @inheritDoc
	 */
	public function boot() {
		parent::boot();
		$this->container->singleton( Template::class, Template::class );
		$this->container->singleton( Manager::class, Manager::class );

		// Load assets.
		$this->container->register( Assets::class );

		// Load providers.
		$this->container->register( Admin\Controller::class );
		$this->container->register( Emails\Controller::class );

		$this->container->register( Passes\Apple_Wallet\Controller::class );
		$this->container->register( Passes\Pdf\Controller::class );
	}

	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		$this->boot();

		$this->add_filters();
		$this->add_actions();
	}

	/**
	 * @inheritDoc
	 */
	public function unregister(): void {
		$this->remove_filters();
		$this->remove_actions();
	}

	/**
	 * @inheritDoc
	 */
	public function add_actions(): void {
		add_action( 'event_tickets_orders_attendee_contents', [ $this, 'include_pass_container_my_tickets' ] );
		add_action( 'tribe_template_after_include:tickets/components/attendees-list/attendees/attendee', [ $this, 'include_pass_container_your_tickets' ], 20, 3 );
		add_action( 'tribe_template_after_include:tickets/admin-views/attendees/modal/attendee', [ $this, 'include_pass_container_attendee_modal' ], 20, 3 );
	}

	/**
	 * @inheritDoc
	 */
	public function remove_actions(): void {
		remove_action( 'event_tickets_orders_attendee_contents', [ $this, 'include_pass_container_my_tickets' ] );
		remove_action( 'tribe_template_after_include:tickets/components/attendees-list/attendees/attendee', [ $this, 'include_pass_container_your_tickets' ], 20 );
		remove_action( 'tribe_template_after_include:tickets/admin-views/attendees/modal/attendee', [ $this, 'include_pass_container_attendee_modal' ], 20, 3 );
	}

	/**
	 * @inheritDoc
	 */
	public function add_filters(): void {
		add_filter( 'tribe_template_theme_path_list', [ $this, 'filter_tribe_template_theme_path_list' ], 20, 3 );
	}

	/**
	 * @inheritDoc
	 */
	public function remove_filters(): void {

	}

	/**
	 * Add pass container My Tickets page.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $attendee The attendee.
	 *
	 * @return void
	 */
	public function include_pass_container_my_tickets( $attendee ): void {
		$this->container->make( Include_To_My_Tickets::class )->include_pass_container_my_tickets( $attendee );
	}

	/**
	 * Add pass container Attendees List page.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string   $file     Which file is being included.
	 * @param string   $name     Name of the file.
	 * @param Template $template Template including the file.
	 *
	 * @return void
	 */
	public function include_pass_container_your_tickets( $file, $name, $template ): void {
		$this->container->make( Include_To_Order_Page::class )->include_pass_container_your_tickets( $file, $name, $template );
	}

	/**
	 * Add pass container to Attendee Modal.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string   $file     Which file is being included.
	 * @param string   $name     Name of the file.
	 * @param Template $template Template including the file.
	 *
	 * @return void
	 */
	public function include_pass_container_attendee_modal( $file, $name, $template ): void {
		$this->container->make( Include_To_Attendee_Modal::class )->include_pass_container_attendee_modal( $file, $name, $template );
	}



	/**
	 * Handles backwards-compatible namespacing when loading override template files from the theme.
	 *
	 * @since 6.0.0
	 *
	 * @param array           $folders            The current list of folders that will be searched template files.
	 * @param string          $template_namespace The template namespace we are dealing with (unused).
	 * @param Tribe__Template $template           Current instance of the template class.
	 *
	 * @return array  Override (theme) folders map after adding Pro to the list.
	 */
	public function filter_tribe_template_theme_path_list( $folders, $template_namespace, $template ) {
		// If not from Tickets Wallet Plus, bail.
		if ( ! empty( $template->origin->template_namespace ) && 'tickets-wallet-plus' !== $template->origin->template_namespace ) {
			return $folders;
		}

		// If the legacy folder doesn't exist, bail.
		if ( ! file_exists( trailingslashit( get_stylesheet_directory() ) . 'tribe/tickets-wallet-plus' ) ) {
			return $folders;
		}

		/**
		 * Allows a user to opt-out of the legacy template path.
		 * Useful for when they move to using the new template path for the merged plugins.
		 *
		 * @since 6.0.0
		 *
		 * @param bool $legacy Whether to use the legacy template file path. Cast to a boolean.
		 */
		$legacy = (bool) apply_filters( 'tec_tickets_wallet_plus_legacy_template_path', true );

		if ( ! $legacy ) {
			return $folders;
		}

		foreach ( $folders as $key => $folder ) {
			$folders[ $key ]['path'] = str_replace( 'tickets-plus/tickets-wallet-plus', '/tickets-wallet-plus', $folder['path'] );
		}

		return $folders;
	}
}
