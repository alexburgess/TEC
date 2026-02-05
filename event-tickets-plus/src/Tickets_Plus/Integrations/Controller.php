<?php

namespace TEC\Tickets_Plus\Integrations;

use TEC\Common\Contracts\Provider\Controller as Controller_Base;

/**
 * Class Controller
 *
 * @since 5.8.0
 *
 * @package TEC\Tickets_Plus\Integrations
 */
class Controller extends Controller_Base {

	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail we have the boolean living on the method.
	 *
	 * @var bool $is_active If the integration is active.
	 *
	 * @since 5.8.0
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
	}

	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		$this->boot();

		// Load providers.
		$this->container->register_on_action( 'tec_container_registered_provider_TEC\Tickets_Wallet_Plus\Controller', Tickets_Wallet_Plus\Controller::class );
		$this->container->register( Event_Tickets\Site_Health\Controller::class );
		$this->container->register( Event_Tickets\Duplicate_Ticket_Provider::class );
		$this->container->register( Tickets_Wallet_Plus_Merge_Provider::class );
	}

	/**
	 * @inheritDoc
	 */
	public function unregister(): void {}
}
