<?php
/**
 * Check-in Log Template.
 * Displays the check-in log history in a modal.
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/tickets/admin-views/attendees/attendees-table/checkin-log.php
 *
 * @since 6.7.0
 *
 * @var array $checkin_logs Array of check-in log entries
 * @var int   $attendee_id  Attendee ID
 */

?>
<div class="tec-tickets__admin-attendees-checkin-log">
	<table class="tec-tickets__admin-attendees-checkin-log-table">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Date & Time', 'event-tickets-plus' ); ?></th>
			<th><?php esc_html_e( 'Action', 'event-tickets-plus' ); ?></th>
			<th><?php esc_html_e( 'Device', 'event-tickets-plus' ); ?></th>
			<th><?php esc_html_e( 'Reason', 'event-tickets-plus' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php if ( ! empty( $checkin_logs ) ) : ?>
			<?php foreach ( $checkin_logs as $index => $log ) : ?>
				<?php
				// Determine the action text and CSS class.
				$action_text = isset( $log['status'] ) && $log['status']
					? esc_html__( 'Check-in', 'event-tickets-plus' )
					: esc_html__( 'Check-out', 'event-tickets-plus' );

				$action_class = isset( $log['status'] ) && $log['status'] ? 'checkin' : 'checkout';

				// Get the reason display text - prefer label over reason.
				$reason_text = '';
				if ( ! empty( $log['label'] ) ) {
					$reason_text = $log['label'];
				} elseif ( ! empty( $log['reason'] ) ) {
					// Fallback to reason if label is not available.
					$reason_text = $log['reason'];
				}

				// Add CSS class based on reason type.
				$reason_class = '';
				if ( ! empty( $log['reason'] ) ) {
					switch ( $log['reason'] ) {
						case 'DUPLICATE':
							$reason_class = 'duplicate';
							break;
						case 'SECURITY':
							$reason_class = 'security';
							break;
						case 'CHECKOUT':
							$reason_class = 'checkout-fail';
							break;
					}
				}
				?>
				<tr>
					<td><?php echo esc_html( $log['timestamp'] ?? '' ); ?></td>
					<td>
						<span class="action-badge action-<?php echo esc_attr( $action_class ); ?>">
							<?php echo esc_html( $action_text ); ?>
						</span>
					</td>
					<td><?php echo esc_html( $log['device_id'] ?? '' ); ?></td>
					<td>
						<?php if ( ! empty( $reason_text ) ) : ?>
							<span class="reason-badge reason-<?php echo esc_attr( $reason_class ); ?>">
								<?php echo esc_html( $reason_text ); ?>
							</span>
						<?php else : ?>
							<span class="reason-badge reason-none">-</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="4"><?php esc_html_e( 'No check-in logs found.', 'event-tickets-plus' ); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>
</div>
