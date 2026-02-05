<?php
/**
 * The custom tables' controller.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\StellarWP\Schema\Register;
use TEC\Common\StellarWP\DB\Database\Exceptions\DatabaseQueryException;
use TEC\Common\StellarWP\AdminNotices\AdminNotices;

/**
 * Class Tables.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */
class Tables extends Controller_Contract {
	/**
	 * Registers the tables and the bindings required to use them.
	 *
	 * @since 6.9.0
	 *
	 * @return void The tables are registered.
	 */
	protected function do_register(): void {
		try {
			Register::table( Tables\Rules::class );
			Register::table( Tables\Relationships::class );

			/**
			 * Fires when the purchase rules tables are registered.
			 *
			 * @since 6.9.0
			 */
			do_action( 'tec_tickets_plus_purchase_rules_tables_registered' );
		} catch ( DatabaseQueryException $e ) {
			do_action( 'tec_tickets_plus_purchase_rules_tables_not_registered', $e );

			AdminNotices::show(
				'tec_tickets_plus_purchase_rules_tables_not_registered',
				function () use ( $e ) {
					?>
					<div class="notice notice-error">
						<p><?php esc_html_e( 'Purchase rules tables could not be registered. The Purchase Rules feature will remain disabled until this issue is resolved.', 'event-tickets-plus' ); ?></p>
						<?php // Translators: %s is the query error message. ?>
						<p><?php printf( esc_html__( 'The below query failed with the message(s): %s', 'event-tickets-plus' ), '<code>' . esc_html( implode( '<br>', $e->getQueryErrors() ) ) . '</code>' ); ?></p>
						<p><code><?php echo esc_html( $e->getQuery() ); ?></code></p>
					</div>
					<?php
				}
			)
				->urgency( 'error' )
				->dismissible( false )
				->inline();
		}
	}

	/**
	 * Unsubscribes from WordPress hooks.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		// nothing to do here.
	}
}
