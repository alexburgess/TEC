<?php
/**
 * Allow including of Event Tickets Wallet Plus Template.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 */
namespace TEC\Tickets_Wallet_Plus;

/**
 * Class Template
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus
 */
class Template extends \Tribe__Template {

	/**
	 * Template constructor.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function __construct() {
		$this->set_template_origin( tribe( Plugin::class ) );
		$this->set_template_folder( 'src/views/tickets-wallet-plus' );

		// Setup to look for theme files.
		$this->set_template_folder_lookup( true );

		// Configures this templating class extract variables.
		$this->set_template_context_extract( true );
	}
}
