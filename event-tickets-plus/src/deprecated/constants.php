<?php
/**
 * Deprecated constants.
 * Holds constants that we've removed from active code to prevent fatals if they are called by external code.
 *
 * @package Event Tickets Plus
 *
 * @since 6.0.0
 */

if ( ! defined( 'EVENT_TICKETS_WALLET_FILE' ) ) {
	/**
	 * Define the file path to the Event Tickets Wallet plugin.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @deprecated 6.0.0
	 */
	define( 'EVENT_TICKETS_WALLET_FILE', EVENT_TICKETS_PLUS_FILE );
}
