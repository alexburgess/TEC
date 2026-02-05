<?php
/**
 * Wallet Plus: My Tickets - Passes
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/my-tickets/passes.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

?>
<div class="tec-tickets__wallet-plus-passes-container tec-tickets__wallet-plus-passes-container--my-tickets">
	<?php
	/**
	 * Allow enabled and future passes to be placed onto the My Tickets Page as needed.
	 * Example: The PDF pass would pass the /components/pdf-button.php view.
	 *
	 * @since 1.0.0
	 *
	 * @see  Tribe__Template\do_entry_point()
	 * @link https://docs.theeventscalendar.com/reference/classes/tribe__template/do_entry_point/
	 */
	$this->do_entry_point( 'wallet_plus_my_tickets_passes' );
	?>
</div>
