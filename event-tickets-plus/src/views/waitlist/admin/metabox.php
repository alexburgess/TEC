<?php
/**
 * Renders the Waitlist metabox.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe/tickets-plus/waitlist/admin/metabox.php
 *
 * @link https://evnt.is/1amp See more documentation about our views templating system.
 *
 * @since 6.2.0
 *
 * @version 6.2.0
 *
 * @var WP_Post                              $event              The post object of the current event.
 * @var \TEC\Tickets_Plus\Waitlist\Template  $this               The Waitlist template instance.
 * @var ?Waitlist $ticket_waitlist    The ticket Waitlist instance.
 * @var ?Waitlist $rsvp_waitlist      The rsvp Waitlist instance.
 * @var int                                  $ticket_subscribers The number of ticket subscribers.
 * @var int                                  $rsvp_subscribers   The number of rsvp subscribers.
 * @var array                                $tickets_data       Ticket data.
 * @var array                                $rsvp_data          RSVP data.
 * @var string                               $table_url          The URL for the subscribers table.
 */

defined( 'ABSPATH' ) || exit;

use TEC\Tickets_Plus\Waitlist\Waitlist;

$tickets_disabled = $ticket_waitlist || empty( $tickets_data['ticket_count'] ) ? 'disabled="disabled"' : '';
$rsvp_disabled    = $rsvp_waitlist || empty( $rsvp_data['ticket_count'] ) ? 'disabled="disabled"' : '';

?>
<div class="tec-tickets-plus-waitlist-metabox">
	<input type="hidden" name="tec-tickets-plus-waitlist-metabox-delete-nonce" value="<?php echo esc_attr( wp_create_nonce( 'tec_tickets_plus_waitlist_delete' ) ); ?>" />
	<div class="tec-tickets-plus-waitlist-metabox__overview">
		<?php if ( empty( $tickets_data['ticket_count'] ) || empty( $rsvp_data['ticket_count'] ) ) : ?>
			<div class="tec-tickets-plus-waitlist-metabox__warnings ticket_table_intro__warnings">
				<div class="ticket-editor-notice info info--background">
					<span class="dashicons dashicons-lightbulb"></span>
					<div class="ticket-editor-notice_warning--messages">
						<?php if ( empty( $tickets_data['ticket_count'] ) ) : ?>
							<p class="ticket-editor-notice_warning--message">
								<?php esc_html_e( 'You need to create at least one Ticket to enable the Tickets Waitlist.', 'event-tickets-plus' ); ?>
							</p>
						<?php endif; ?>
						<?php if ( empty( $rsvp_data['ticket_count'] ) ) : ?>
							<p class="ticket-editor-notice_warning--message">
								<?php esc_html_e( 'You need to create an RSVP to enable the RSVP Waitlist.', 'event-tickets-plus' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>
		<div class="tec-tickets-plus-waitlist-metabox__add">
			<?php // phpcs:disable StellarWP.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<button data-type="ticket" class="button-secondary tribe-button-icon tribe-button-icon-plus tec-tickets-plus-waitlist-metabox__add-ticket" <?php echo $tickets_disabled; ?>>
				<?php esc_html_e( 'Add Tickets Waitlist', 'event-tickets-plus' ); ?>
			</button>
			<button data-type="rsvp" class="button-secondary tribe-button-icon tribe-button-icon-plus tec-tickets-plus-waitlist-metabox__add-ticket" <?php echo $rsvp_disabled; ?>>
				<?php esc_html_e( 'Add RSVP Waitlist', 'event-tickets-plus' ); ?>
			</button>
			<?php // phpcs:enable StellarWP.XSS.EscapeOutput.OutputNotEscaped, WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php if ( $ticket_waitlist || $rsvp_waitlist ) : ?>
			<table class="tribe_ticket_list_table tribe-tickets-editor-table eventtable ticket_list eventForm widefat fixed">
				<thead>
					<tr class="table-header">
						<th class="ticket_name column-primary"><?php esc_html_e( 'Waitlist', 'event-tickets-plus' ); ?></th>
						<th class="ticket_price"><?php esc_html_e( 'Signups', 'event-tickets-plus' ); ?></th>
						<th class="ticket_edit"></th>
					</tr>
				</thead>
				<tbody class="tribe-tickets-editor-table-tickets-body">
					<?php if ( $ticket_waitlist ) : ?>
					<tr class="is-expanded">
						<td class="column-primary ticket_name" data-label="<?php esc_attr_e( 'Waitlist', 'event-tickets-plus' ); ?>:">
							<div class="tribe-tickets__tickets-editor-ticket-name">
								<div class="tribe-tickets__tickets-editor-ticket-name-title">
									<?php esc_html_e( 'Tickets waitlist', 'event-tickets-plus' ); ?>
									<div class="tribe-tickets__tickets-editor-ticket-available-dates">
										<?php echo esc_html( $ticket_waitlist->get_conditional_label() ); ?>
									</div>
								</div>
							</div>
						</td>
						<td class="ticket_price" data-label="<?php esc_attr_e( 'Signups', 'event-tickets-plus' ); ?>:">
							<a href="<?php echo esc_url( $table_url ); ?>">
								<?php echo intval( $ticket_subscribers ); ?>
							</a>
						</td>
						<td class="ticket_edit">
							<button data-type="ticket" title="<?php esc_attr_e( 'Edit Waitlist', 'event-tickets-plus' ); ?>" class="tec-tickets_edit tec-tickets-plus-waitlist-metabox__edit">
								<span class="tec-tickets_edit_text"><?php esc_html_e( 'Edit Waitlist', 'event-tickets-plus' ); ?></span>
							</button>
							<button data-type="ticket" data-waitlist-id="<?php echo (int) $ticket_waitlist->get_id(); ?>" title="<?php esc_attr_e( 'Delete Waitlist', 'event-tickets-plus' ); ?>" class="tec-tickets_delete tec-tickets-plus-waitlist-metabox__delete">
								<span class="tec-tickets_delete_text"><?php esc_html_e( 'Delete Waitlist', 'event-tickets-plus' ); ?></span>
							</button>
						</td>
					</tr>
					<?php endif; ?>
					<?php if ( $rsvp_waitlist ) : ?>
					<tr class="is-expanded">
						<td class="column-primary ticket_name" data-label="<?php esc_attr_e( 'Waitlist', 'event-tickets-plus' ); ?>:">
							<div class="tribe-tickets__tickets-editor-ticket-name">
								<div class="tribe-tickets__tickets-editor-ticket-name-title">
									<?php esc_html_e( 'RSVP waitlist', 'event-tickets-plus' ); ?>
									<div class="tribe-tickets__tickets-editor-ticket-available-dates">
										<?php echo esc_html( $rsvp_waitlist->get_conditional_label() ); ?>
									</div>
								</div>
							</div>
						</td>
						<td class="ticket_price" data-label="<?php esc_attr_e( 'Signups', 'event-tickets-plus' ); ?>:">
							<a href="<?php echo esc_url( $table_url ); ?>">
								<?php echo intval( $ticket_subscribers ); ?>
							</a>
						</td>
						<td class="ticket_edit">
							<button data-type="rsvp" title="<?php esc_attr_e( 'Edit Waitlist', 'event-tickets-plus' ); ?>" class="tec-tickets_edit tec-tickets-plus-waitlist-metabox__edit">
								<span class="tec-tickets_edit_text"><?php esc_html_e( 'Edit Waitlist', 'event-tickets-plus' ); ?></span>
							</button>
							<button data-type="rsvp" data-waitlist-id="<?php echo (int) $rsvp_waitlist->get_id(); ?>" title="<?php esc_attr_e( 'Delete Waitlist', 'event-tickets-plus' ); ?>" class="tec-tickets_delete tec-tickets-plus-waitlist-metabox__delete">
								<span class="tec-tickets_delete_text"><?php esc_html_e( 'Delete Waitlist', 'event-tickets-plus' ); ?></span>
							</button>
						</td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<div class="tec-tickets-plus-waitlist-metabox__create">
		<div data-type="ticket" class="tec-tickets-plus-waitlist-metabox__create-container">
			<h4><?php echo esc_html_x( 'ADD TICKET WAITLIST', 'This is in all caps for emphasis', 'event-tickets-plus' ); ?></h4>
			<div class="tec-tickets-plus-waitlist-metabox__create-row">
				<p>
					<?php echo esc_html_x( 'Shows', 'Label for waitlist conditional', 'event-tickets-plus' ); ?>:</td>
				</p>
				<div class="tec-tickets-plus-waitlist-metabox__create-radio">
					<input type="hidden" name="waitlist_id" value="<?php echo $ticket_waitlist ? (int) $ticket_waitlist->get_id() : 0; ?>"/>
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'tec_tickets_plus_waitlist_save' ) ); ?>"/>
					<div>
						<input type="radio" name="ticket-conditional" value="<?php echo esc_attr( Waitlist::ALWAYS_CONDITIONAL ); ?>" <?php echo ! $ticket_waitlist || ( $ticket_waitlist && Waitlist::ALWAYS_CONDITIONAL === $ticket_waitlist->get_conditional() ) ? 'checked="checked"' : ''; ?>/>
						<label><?php esc_html_e( 'When tickets are on pre-sale or sold out', 'event-tickets-plus' ); ?></label>
					</div>
					<div>
						<input type="radio" name="ticket-conditional" value="<?php echo esc_attr( Waitlist::BEFORE_SALE_CONDITIONAL ); ?>" <?php echo $ticket_waitlist && Waitlist::BEFORE_SALE_CONDITIONAL === $ticket_waitlist->get_conditional() ? 'checked="checked"' : ''; ?>/>
						<label><?php esc_html_e( 'Before tickets go on sale', 'event-tickets-plus' ); ?></label>
					</div>
					<div>
						<input type="radio" name="ticket-conditional" value="<?php echo esc_attr( Waitlist::ON_SOLD_OUT_CONDITIONAL ); ?>" <?php echo $ticket_waitlist && Waitlist::ON_SOLD_OUT_CONDITIONAL === $ticket_waitlist->get_conditional() ? 'checked="checked"' : ''; ?>/>
						<label><?php esc_html_e( 'When tickets are sold out', 'event-tickets-plus' ); ?></label>
					</div>
				</div>
			</div>
			<div class="tec-tickets-plus-waitlist-metabox__create-buttons">
				<button data-type="ticket" class="button-primary tec-tickets-plus-waitlist-metabox__save-waitlist"><?php esc_html_e( 'Save Waitlist', 'event-tickets-plus' ); ?></button>
				<button class="button-secondary tec-tickets-plus-waitlist-metabox__save-cancel"><?php esc_html_e( 'Cancel', 'event-tickets-plus' ); ?></button>
			</div>
		</div>
		<div data-type="rsvp" class="tec-tickets-plus-waitlist-metabox__create-container">
			<h4><?php echo esc_html_x( 'ADD RSVP WAITLIST', 'This is in all caps for emphasis', 'event-tickets-plus' ); ?></h4>
			<div class="tec-tickets-plus-waitlist-metabox__create-row">
				<p>
					<?php echo esc_html_x( 'Shows', 'Label for waitlist conditional', 'event-tickets-plus' ); ?>:</td>
				</p>
				<div class="tec-tickets-plus-waitlist-metabox__create-radio">
					<input type="hidden" name="waitlist_id" value="<?php echo $rsvp_waitlist ? (int) $rsvp_waitlist->get_id() : 0; ?>"/>
					<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'tec_tickets_plus_waitlist_save' ) ); ?>"/>
					<div>
						<input type="radio" name="rsvp-conditional" value="<?php echo esc_attr( Waitlist::ALWAYS_CONDITIONAL ); ?>" <?php echo ! $rsvp_waitlist || ( $rsvp_waitlist && Waitlist::ALWAYS_CONDITIONAL === $rsvp_waitlist->get_conditional() ) ? 'checked="checked"' : ''; ?>/>
						<label><?php esc_html_e( 'Whenever RSVP is not available', 'event-tickets-plus' ); ?></label>
					</div>
					<div>
						<input type="radio" name="rsvp-conditional" value="<?php echo esc_attr( Waitlist::BEFORE_SALE_CONDITIONAL ); ?>" <?php echo $rsvp_waitlist && Waitlist::BEFORE_SALE_CONDITIONAL === $rsvp_waitlist->get_conditional() ? 'checked="checked"' : ''; ?>/>
						<label><?php esc_html_e( 'Before RSVP starts', 'event-tickets-plus' ); ?></label>
					</div>
					<div>
						<input type="radio" name="rsvp-conditional" value="<?php echo esc_attr( Waitlist::ON_SOLD_OUT_CONDITIONAL ); ?>" <?php echo $rsvp_waitlist && Waitlist::ON_SOLD_OUT_CONDITIONAL === $rsvp_waitlist->get_conditional() ? 'checked="checked"' : ''; ?>/>
						<label><?php esc_html_e( 'When RSVP reaches capacity', 'event-tickets-plus' ); ?></label>
					</div>
				</div>
			</div>
			<div class="tec-tickets-plus-waitlist-metabox__create-buttons">
				<button data-type="rsvp" class="button-primary tec-tickets-plus-waitlist-metabox__save-waitlist"><?php esc_html_e( 'Save Waitlist', 'event-tickets-plus' ); ?></button>
				<button class="button-secondary tec-tickets-plus-waitlist-metabox__save-cancel"><?php esc_html_e( 'Cancel', 'event-tickets-plus' ); ?></button>
			</div>
		</div>
	</div>
</div>
<?php
