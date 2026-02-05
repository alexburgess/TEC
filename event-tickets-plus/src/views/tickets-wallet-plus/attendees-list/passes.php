<?php
/**
 * Wallet Plus: Attendee List - Passes
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/attendees-list/passes.php
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
<div class="tec-tickets__wallet-plus-passes-container tec-tickets__wallet-plus-passes-container--attendee-list">
	<?php
	/**
	 * Allow enabled and future passes to be placed onto the Attendees List section as needed.
	 * Example: The PDF pass would pass the /components/pdf-button.php view.
	 *
	 * @since 1.0.0
	 *
	 * @param array $attendee Attendee array.
	 */
	$this->do_entry_point( 'buttons' );
	?>
</div>
