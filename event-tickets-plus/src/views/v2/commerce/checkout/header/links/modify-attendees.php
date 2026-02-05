<?php
/**
 * Tickets Commerce: Checkout Page Header Links > Modify Attendees
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets-plus/v2/commerce/checkout/header/links/modify-attendees.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.3.0
 *
 * @since 5.4.2 Added $attendees_with_arf template var to dynamically change the modify attendees link label.
 *
 * @since 5.5.1 Fixed wrong text domains.
 *
 * @version 5.5.1
 *
 * @var \Tribe__Template $this             [Global] Template object.
 * @var Module           $provider         [Global] The tickets provider instance.
 * @var string           $provider_id      [Global] The tickets provider class name.
 * @var array[]          $items            [Global] List of Items on the cart to be checked out.
 * @var array[]          $sections         [Global] Which events we have tickets for.
 * @var bool             $must_login       [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url        [Global] The site's login URL.
 * @var string           $registration_url [Global] The site's registration URL.
 * @var bool             $is_tec_active    [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways         [Global] An array with the gateways.
 * @var string           $attendee_registration_url The attendee registration URL.
 * @var int              $attendees_with_arf  [Global] The number of tickets that has meta.
 */
if ( empty( $items ) || empty( $attendees_with_arf ) ) {
	return;
}

?>
<a
	class="tribe-common-anchor-alt tribe-tickets__commerce-checkout-header-link-modify-attendees"
	href="<?php echo esc_url( $attendee_registration_url ); ?>"
><?php echo _n( 'modify attendee', 'modify attendees', $attendees_with_arf, 'event-tickets-plus' ); ?></a>
