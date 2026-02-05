<?php
/**
 * The custom tables' controller.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Schema\Register;

/**
 * Class Tables.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Tables extends Controller_Contract {

	/**
	 * Unsubscribes from WordPress hooks.
	 *
	 * @since 6.2.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		// nothing to do here.
	}

	/**
	 * Registers the tables and the bindings required to use them.
	 *
	 * @since 6.2.0
	 *
	 * @return void The tables are registered.
	 */
	protected function do_register(): void {
		Register::table( Tables\Waitlists::class );
		Register::table( Tables\Waitlist_Subscribers::class );
		Register::table( Tables\Waitlist_Pending_Users::class );
	}
}
