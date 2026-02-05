<?php
/**
 * The Tickets Plus Provider.
 *
 * @since 5.3.0
 */

namespace TEC\Tickets_Plus;

/**
 * Class Provider
 *
 * @since 5.3.0
 *
 * @package \TEC\Tickets_Plus
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

		// Register the Service Provider for Assets.
		$this->register_assets();

		// Register the service provider itself on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets-plus.provider', $this );

		// Loads all of tickets commerce.
		$this->container->register( Commerce\Provider::class );
		$this->container->register( Commerce\Gateways\Stripe\Provider::class );
		$this->container->register( Commerce\Gateways\Square\Controller::class );

		// Loads Admin Providers.
		$this->container->register( Admin\Tabs\Provider::class );

		// Loads Tickets Emails.
		$this->container->register( Emails\Provider::class );

		// Register the Flexible Tickets feature if the ET feature is enabled.
		$this->container->register_on_action( 'tec_flexible_tickets_registered', Flexible_Tickets\Provider::class );

		// Registers the Libraries controller.
		$this->container->register( Libraries\Controller::class );

		// Loads Integrations.
		$this->container->register( Integrations\Controller::class );

		// Seating feature.
		$this->container->register_on_action(
			'tec_tickets_seating_registered',
			Seating\Controller::class
		);

		// Upon ET being loaded, register the Ticket Presets feature.
		$this->container->register( Ticket_Presets\Controller::class );
		$this->container->register( Waitlist\Controller::class );

		// Registers the checkin controller.
		$this->container->register( Checkin\Controller::class );

		// Registers the REST API controller.
		$this->container->register( REST\Controller::class );
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

		// Allow Hooks to be removed, by having the them registered to the container.
		$this->container->singleton( Hooks::class, $hooks );
		$this->container->singleton( 'tickets-plus.hooks', $hooks );
	}
}
