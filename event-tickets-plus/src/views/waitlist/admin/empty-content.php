<?php
/**
 * Renders the Waitlist Subscribers page when there are no Subscribers.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe/tickets-plus/waitlist/admin/empty-content.php
 *
 * @link https://evnt.is/1amp See more documentation about our views templating system.
 *
 * @since 6.2.0
 *
 * @version 6.2.0
 */

use Tribe__Tickets_Plus__Main as Tickets_Plus;

defined( 'ABSPATH' ) || exit;
?>
<div class="tec-admin-page-no-content">
	<div class="tec-admin-page-no-content--inner-wrap">
		<div>
			<img
				class="tec-admin-page-no-content--icon"
				src="<?php echo esc_url( tribe_resource_url( 'icons/no-subscribers.svg', false, null, Tickets_Plus::instance() ) ); ?>"
				alt="No subscribers icon"
				/>
		</div>
		<div class="tec-admin-page-no-content--heading">
			<?php esc_html_e( 'Nothing here yet.', 'event-tickets-plus' ); ?>
		</div>
		<div class="tec-admin-page-no-content--content">
			<?php
			printf(
				// Translators: 1) is opening link tag, 2) is closing link tag.
				esc_html__(
					'There are no current subscribers to waitlists at the moment. Once someone subscribes to a ticket or RSVP waitlist they will appear here. Learn more about waitlists at the %1$sknowledgebase%2$s.',
					'event-tickets-plus'
				),
				'<a href="https://evnt.is/waitlist-kb" target="_blank" rel="nofollow noopener">',
				'</a>'
			);
			?>
		</div>
	</div>
</div>

<?php
