<?php
/**
 * PDF Pass
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/tickets-wallet-plus/pdf/pass.php
 *
 * See more documentation about our views templating system.
 *
 * @link https://evnt.is/event-tickets-wallet-plus-tpl Help article for Wallet Plus template files.
 *
 * @since 1.0.0
 *
 * @version 1.0.0
 */

$this->template( 'pass/styles' );
?>
<table class="tec-tickets__wallet-plus-pdf-table">
	<?php $this->template( 'pass/header' ); ?>
	<?php $this->template( 'pass/body' ); ?>
	<?php $this->template( 'pass/footer' ); ?>
</table>
