<?php
/**
 * Tickets Commerce: Checkout Cart Item Extra details > Fields
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/v2/commerce/checkout/cart/item/details/extra/fields.php
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

$meta                     = tribe( 'tickets-plus.meta' );
$meta_field_configuration = $meta->get_meta_fields_by_ticket( $item['ticket_id'] );

// Bail if there are no fields to display.
if ( empty( $meta_field_configuration ) || empty( $attendee['meta'] ) ) {
	return;
}

$field_data = [];
?>
<?php foreach ( $meta_field_configuration as $field ) : ?>
	<?php
	$options = $field->get_hashed_options_map();

	if ( ! empty( $options ) ) {
		$values = [];
		foreach ( $options as $option_slug => $option_label ) {
			$key = 'checkbox' === $field->type ? $option_slug : $field->slug;

			if ( empty( $attendee['meta'][ $key ] ) ) {
				continue;
			}

			if (
				( 'radio' === $field->type || 'select' === $field->type )
				&& ! empty( $attendee['meta'][ $key ] )
			) {
				$values[] = $attendee['meta'][ $key ];
				break;
			}

			$values[] = $attendee['meta'][ $key ];
		}

		if ( empty( $values ) ) {
			continue;
		}

		$field_data[] = "{$field->label}: " . implode( ', ', $values );
	} else {
		$slug = $field->slug;

		if ( empty( $attendee['meta'][ $slug ] ) ) {
			continue;
		}

		$field_data[] = "{$field->label}: {$attendee['meta'][ $slug ]}";
	}

	?>
<?php endforeach; ?>
<div class="tribe-tickets__commerce-checkout-cart-item-details-description-attendee-fields"><?php echo esc_html( implode( ', ', $field_data ) ); ?></div>
