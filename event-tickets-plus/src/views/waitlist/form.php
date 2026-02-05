<?php
/**
 * Renders the Waitlist form on the frontend.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe/tickets-plus/waitlist/form.php
 *
 * @link https://evnt.is/1amp See more documentation about our views templating system.
 *
 * @since 6.2.0
 *
 * @version 6.2.0
 *
 * @var \TEC\Tickets_Plus\Waitlist\Template $this            The Waitlist template instance.
 * @var Waitlist                            $waitlist        The Waitlist instance.
 * @var bool                                $user_subscribed Whether the current user is subscribed to the waitlist.
 * @var string                              $form_title      The title of the form.
 * @var string                              $success_message The success message to display after submitting the form.
 * @var bool                                $is_unsubscribe  Whether the form is for unsubscribing.
 */

use TEC\Tickets_Plus\Waitlist\Waitlist;
use TEC\Tickets_Plus\Waitlist\Frontend;

defined( 'ABSPATH' ) || exit;

$waitlist_type = $waitlist->get_type();

$classes = [
	'tec-tickets-plus-waitlist-container',
	'tribe-common-g-col' => Waitlist::RSVP_TYPE === $waitlist_type,
];
?>
<div <?php tribe_classes( $classes ); ?>>
	<div class="tec-tickets-plus-waitlist-container__inner-wrap <?php echo $is_unsubscribe ? 'tec-tickets-plus-waitlist-container--hidden' : ''; ?>">
		<input type="hidden" name="tec-tickets-plus-waitlist[nonce]" value="<?php echo esc_attr( wp_create_nonce( Frontend::AJAX_CREATE_SUBSCRIBER_ACTION . '_' . $waitlist->get_id() ) ); ?>"/>
		<input type="hidden" name="tec-tickets-plus-waitlist[waitlist_id]" value="<?php echo esc_attr( $waitlist->get_id() ); ?>"/>
		<input type="hidden" name="tec-tickets-plus-waitlist[action]" value="<?php echo esc_attr( Frontend::AJAX_CREATE_SUBSCRIBER_ACTION ); ?>"/>
		<?php if ( $user_subscribed ) : ?>
			<h3><?php echo esc_html_x( 'You are subscribed!', '', 'event-tickets-plus' ); ?></h3>
			<p><?php echo esc_html( $success_message ); ?></p>
		<?php else : ?>
			<h3><?php echo esc_html( $form_title ); ?></h3>
			<?php if ( ! is_user_logged_in() ) : ?>
				<div class="tec-tickets-plus-waitlist-container--input">
					<label for="tec-tickets-plus-waitlist-<?php echo (int) $waitlist_type; ?>-name">
						<?php echo esc_html_x( 'Name', 'Label for the name input on the waitlist form.', 'event-tickets-plus' ); ?>
					</label>
					<input
						type="text"
						id="tec-tickets-plus-waitlist-<?php echo (int) $waitlist_type; ?>-name"
						name="tec-tickets-plus-waitlist[name]"
						required
					/>
					<div class="tec-tickets-plus-waitlist-container--input--error" data-show-when="empty">
						<p><?php echo esc_html_x( 'This field is required', 'Error message for the name input on the waitlist form.', 'event-tickets-plus' ); ?></p>
					</div>
				</div>
				<div class="tec-tickets-plus-waitlist-container--input">
					<label for="tec-tickets-plus-waitlist-<?php echo (int) $waitlist_type; ?>-email">
						<?php echo esc_html_x( 'Email address', 'Label for the email input on the waitlist form.', 'event-tickets-plus' ); ?>
					</label>
					<input
						type="email"
						id="tec-tickets-plus-waitlist-<?php echo (int) $waitlist_type; ?>-email"
						name="tec-tickets-plus-waitlist[email]"
						required
					/>
					<div class="tec-tickets-plus-waitlist-container--input--error" data-show-when="empty">
						<p><?php echo esc_html_x( 'This field is required', 'Error message for the email input on the waitlist form.', 'event-tickets-plus' ); ?></p>
					</div>
					<div class="tec-tickets-plus-waitlist-container--input--error" data-show-when="invalid">
						<p><?php echo esc_html_x( 'Invalid email address', 'Error message for the email input on the waitlist form.', 'event-tickets-plus' ); ?></p>
					</div>
				</div>
			<?php endif; ?>
			<div class="tec-tickets-plus-waitlist-container--submit">
				<button
					type="submit"
					class="tec-tickets-plus-waitlist-submit"
					<?php echo is_user_logged_in() ? '' : 'disabled="true"'; ?>
				>
					<?php echo esc_html_x( 'Notify me', 'Submit button for the waitlist form.', 'event-tickets-plus' ); ?>
				</button>
			</div>
		<?php endif; ?>
	</div>
	<div class="tec-tickets-plus-waitlist-container__success <?php echo $is_unsubscribe ? 'tec-tickets-plus-waitlist-container--visible' : ''; ?>">
		<div class="tec-tickets-plus-waitlist-container__success-row">
			<div class="tec-tickets-plus-waitlist-container__success-column">
				<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
					<path d="M19.4436 3L22 4.80753L10.797 21H8.2406L2 12.2636L4.55639 9.85356L9.5188 14.4477L19.4436 3Z" fill="black"/>
				</svg>
			</div>
			<div class="tec-tickets-plus-waitlist-container__success-column">
				<h4><?php esc_html_e( 'Success', 'event-tickets-plus' ); ?></h4>
				<p><?php echo esc_html( $success_message ); ?></p>
			</div>
		</div>
	</div>
</div>
<?php
