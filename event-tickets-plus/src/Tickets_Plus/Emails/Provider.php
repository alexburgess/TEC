<?php
namespace TEC\Tickets_Plus\Emails;

/**
 * Class Provider
 *
 * @since 5.6.6
 *
 * @package \TEC\Tickets_Plus\Emails
 */
class Provider extends \TEC\Common\Contracts\Service_Provider {
	/**
	 * Register the provider singletons.
	 *
	 * @since 5.3.0
	 */
	public function register() {
		// Register the Service Provider for Hooks.
		tribe_register_provider( Hooks::class );

		// Register the service provider itself on the container.
		$this->container->singleton( static::class, $this );
		$this->container->singleton( 'tickets-plus.emails.provider', $this );
	}
}
