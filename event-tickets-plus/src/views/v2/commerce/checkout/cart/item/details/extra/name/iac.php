<?php
/**
 * Tickets Commerce: Checkout Cart Item Extra details > Name > IAC details
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/details/extra/name/iac.php
 *
 * See more documentation about our views templating system.
 *
 * @link    https://evnt.is/1amp Help article for RSVP & Ticket template files.
 *
 * @since 5.3.0
 *
 * @version 5.3.0
 *
 * @var \Tribe__Template $this             [Global] Template object.
 * @var Module           $provider         [Global] The tickets provider instance.
 * @var string           $provider_id      [Global] The tickets provider class name.
 * @var array[]          $items            [Global] List of Items on the cart to be checked out.
 * @var bool             $must_login       [Global] Whether login is required to buy tickets or not.
 * @var string           $login_url        [Global] The site's login URL.
 * @var string           $registration_url [Global] The site's registration URL.
 * @var bool             $is_tec_active    [Global] Whether `The Events Calendar` is active or not.
 * @var array[]          $gateways         [Global] An array with the gateways.
 * @var array            $item             Which item this row will be for.
 * @var array            $attendee         Array containing the attendee meta.
 */

$iac                     = tribe( 'tickets-plus.attendee-registration.iac' );
$iac_field_configuration = $iac->get_field_configurations( $item['ticket_id'] );

// Return if there's IAC data.
if ( empty( $iac_field_configuration ) ) {
	return;
}
?>

<?php foreach ( $iac_field_configuration as $field ) : ?>

	<?php
	$short_slug = 'attendee-' . str_replace( 'tribe-tickets-plus-iac-', '', $field->slug );
	?>

	<div class="tribe-tickets__commerce-checkout-cart-item-details-description-<?php echo esc_attr( $short_slug ); ?>">
		<?php echo esc_html( $attendee['meta'][ $field->slug ] ); ?>
	</div>
<?php endforeach; ?>
