<?php
/**
 * Manual Attendees: Loader
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/admin-views/manual-attendees/loader.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://m.tri.be/1amp
 *
 * @since 5.2.0
 * @since 6.1.2 Corrected template override path to include `admin-views/`.
 *
 * @version 6.1.2
 */

/** @var Tribe__Tickets__Editor__Template $template */
$template = tribe( 'tickets.editor.template' );

$template->template( 'v2/components/loader/loader' );
