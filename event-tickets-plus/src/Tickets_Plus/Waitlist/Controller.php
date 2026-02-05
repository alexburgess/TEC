<?php
/**
 * The main Waitlist Controller plugin controllers, it bootstraps the ancillary controllers and binds the main
 * definitions.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Assets\Config;
use Tribe__Tickets_Plus__Main as Tickets_Plus_Plugin;
use TEC\Tickets_Plus\Waitlist\Admin\Waitlist_Subscribers_Table;
use TEC\Tickets_Plus\Waitlist\Admin\Waitlist_Subscribers_Page;

/**
 * Class Controller
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Controller extends Controller_Contract {

	/**
	 * The name of the constant that will be used to disable the feature.
	 * Setting it to a truthy value will disable the feature.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const DISABLED = 'TEC_TICKETS_PLUS_WAITLIST_DISABLED';

	/**
	 * The action that will be fired when this Controller registers.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public static string $registration_action = 'tec_tickets_plus_waitlist_registered';

	/**
	 * Determines if the feature is enabled or not.
	 *
	 * @since 6.2.0
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
		 * Allows filtering whether the Waitlist feature
		 * should be activated or not.
		 *
		 * Note: this filter will only apply if the disable constant or env var
		 * are not set or are set to falsy values.
		 *
		 * @since 6.2.0
		 *
		 * @param bool $activate Defaults to `true`.
		 */
		return (bool) apply_filters( 'tec_tickets_plus_waitlist_feature_active', true );
	}

	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		Config::add_group_path( 'tec-tickets-plus-waitlist', Tickets_Plus_Plugin::instance()->plugin_path . 'build/', 'Waitlist/' );

		$this->container->singleton( Template::class );
		$this->container->singleton( Waitlists::class );
		$this->container->singleton( Subscribers::class );

		if ( is_admin() ) {
			$this->container->singleton( Waitlist_Subscribers_Table::class );
			$this->container->register( Waitlist_Subscribers_Page::class );
		}

		$this->container->register( Tables::class );
		$this->container->register( Triggers::class );
		$this->container->register( Emails::class );

		/**
		 * Frontend and Editor should be registered in all contexts as well.
		 *
		 * Even though their name suggests otherwise, they are not exclusive to the front-end or the editor.
		 *
		 * They interact with AJAX and REST endpoints.
		 */
		$this->container->register( Editor::class );
		$this->container->register( Frontend::class );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		$this->container->get( Tables::class )->unregister();
		$this->container->get( Editor::class )->unregister();
		$this->container->get( Frontend::class )->unregister();
		$this->container->get( Triggers::class )->unregister();
		$this->container->get( Emails::class )->unregister();
		if ( is_admin() ) {
			$this->container->get( Waitlist_Subscribers_Page::class )->unregister();
		}
	}
}
