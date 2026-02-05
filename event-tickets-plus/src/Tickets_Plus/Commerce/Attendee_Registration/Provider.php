<?php
namespace TEC\Tickets_Plus\Commerce\Attendee_Registration;

/**
 * Class Provider
 *
 * @since 5.3.0
 *
 * @package \TEC\Tickets_Plus\Commerce\Attendee_Registration
 */
class Provider extends \TEC\Common\Contracts\Service_Provider {
	/**
	 * Register the provider singletons.
	 *
	 * @since 5.3.0
	 */
	public function register() {
		// Register the Service Provider for Hooks.
		$this->register_hooks();

		// Register the service provider itself on the container.
		$this->container->singleton( static::class, $this );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider.
	 *
	 * @since 5.3.0
	 */
	protected function register_hooks() {
		$hooks = new Hooks( $this->container );
		$hooks->register();

		// Allow Hooks to be removed, by having the them registered to the container
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets-plus.commerce.attendee-registration.hooks', $hooks );
	}
}
