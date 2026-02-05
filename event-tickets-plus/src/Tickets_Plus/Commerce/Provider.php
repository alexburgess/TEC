<?php
namespace TEC\Tickets_Plus\Commerce;

/**
 * Class Provider
 *
 * @since 5.3.0
 *
 * @package \TEC\Tickets_Plus\Commerce
 */
class Provider extends \TEC\Common\Contracts\Service_Provider {
	/**
	 * Register the provider singletons.
	 *
	 * @since 5.3.0
	 */
	public function register() {
		if ( ! tec_tickets_commerce_is_enabled() ) {
			return;
		}

		// Register the Service Provider for Hooks.
		$this->register_hooks();

		// Register the Service Provider for Assets.
		$this->register_assets();

		$this->container->singleton( Attendee::class );
		$this->container->singleton( Order::class );

		// Register the service provider itself on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets-plus.commerce.provider', $this );

		$this->container->register( Attendee_Registration\Provider::class );
		$this->container->register( Purchase_Rules\Controller::class );
	}

	/**
	 * Registers the provider handling all the 1st level filters and actions for this Service Provider
	 *
	 * @since 5.3.0
	 */
	protected function register_assets() {
		$assets = new Assets( $this->container );
		$assets->register();

		$this->container->singleton( Assets::class, $assets );
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
		$this->container->singleton( 'tickets-plus.commerce.hooks', $hooks );
	}
}
