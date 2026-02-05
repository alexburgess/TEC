<?php
/**
 * The main provider for the Ticket Presets feature.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */

namespace TEC\Tickets_Plus\Ticket_Presets;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Tickets_Plus\Ticket_Presets\Templates\Admin_Views;
use TEC\Tickets_Plus\Ticket_Presets\Admin\Controller as Admin_Controller;

/**
 * Class Controller.
 *
 * @since 6.6.0
 *
 * @package TEC\Tickets_Plus\Ticket_Presets;
 */
class Controller extends Controller_Contract {
	/**
	 * The action that will be dispatched when the provider is registered.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_plus_presets_registered';

	/**
	 * The name of the constant that will be used to disable the feature.
	 * Setting it to a truthy value will disable the feature.
	 *
	 * @since 6.6.0
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKET_PRESETS_DISABLED';

	/**
	 * Registers the bindings, service providers and controllers part of the feature.
	 *
	 * @since 6.6.0
	 * @since 6.6.1 Updated access modifier to protected and simplify container registrations.
	 *
	 * @return void The bindings, service providers and controllers are registered in the container.
	 */
	protected function do_register(): void {
		// Register the service provider itself on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets-plus.presets.provider', $this );

		// Register the custom tables first.
		$this->container->register( Custom_Tables::class );
		$this->container->register( Base::class );
		
		if ( is_admin() ) {
			// Register the Admin Controller - this needs to happen early for admin_post hooks.
			$this->container->register( Admin_Controller::class );
		}
		
		// Register the Tab class as a singleton.
		$this->container->singleton( Admin\Tab::class );
		
		// Register the Form Page class as a singleton.
		$this->container->singleton( Admin\Form_Page::class );
		
		// Bind some implementations.
		$this->container->singleton( Admin_Views::class );
		
		// Register the tab.
		$this->container->make( Admin\Tab::class )->register();
		
		// Register the form page.
		add_action(
			'admin_menu',
			function () {
				$this->container->make( Admin\Form_Page::class )->admin_page();
			},
			20
		);

		/**
		 * Fires when the TEC Ticket Presets feature is activated.
		 *
		 * @since 6.6.0
		 */
		do_action( 'tec_tickets_plus_ticket_presets_activated' );
	}

	/**
	 * Unregisters the bindings, service providers and controllers part of the feature.
	 *
	 * @since 6.6.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Custom_Tables::class )->unregister();
	}

	/**
	 * Determines if the feature is enabled or not.
	 *
	 * The method will check if the feature has been disabled via a constant, an environment variable,
	 * an option or a filter.
	 *
	 * @since 6.6.0
	 *
	 * @return bool Whether the feature is enabled or not.
	 */
	public function is_active(): bool {
		if ( defined( self::DISABLED ) && constant( self::DISABLED ) ) {

			// The constant to disable the feature is defined and it's truthy.
			return false;
		}

		if ( getenv( self::DISABLED ) ) {

			// The environment variable to disable the feature is truthy.
			return false;
		}

		/**
		 * Allows filtering whether the whole Recurring Tickets feature
		 * should be activated or not.
		 *
		 * Note: this filter will only apply if the disable constant or env var
		 * are not set or are set to falsy values.
		 *
		 * @since 6.6.0
		 *
		 * @param bool $activate Defaults to `true`.
		 */
		return (bool) apply_filters( 'tec_tickets_plus_ticket_presets_active', true );
	}
}
