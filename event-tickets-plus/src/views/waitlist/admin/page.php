<?php
/**
 * Renders the Waitlist Subscribers page.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe/tickets-plus/waitlist/admin/page.php
 *
 * @link https://evnt.is/1amp See more documentation about our views templating system.
 *
 * @since 6.2.0
 *
 * @version 6.2.0
 *
 * @var \TEC\Tickets_Plus\Waitlist\Template                         $this   The Waitlist template instance.
 * @var \TEC\Tickets_Plus\Waitlist\Admin\Waitlist_Subscribers_Table $table  The Waitlist Subscribers table instance.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'tec_conditional_content_header_notice' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Waitlist Subscribers', 'event-tickets-plus' ); ?>
	</h1>

	<?php if ( tec_get_request_var( 's', '' ) ) : ?>
		<span class="subtitle">
		<?php
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, StellarWP.XSS.EscapeOutput.OutputNotEscaped
		/* translators: %s: Search query. */
		printf( __( 'Search results for: %s', 'event-tickets-plus' ), '<strong>' . esc_html( tec_get_request_var( 's' ) ) . '</strong>' );
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped, StellarWP.XSS.EscapeOutput.OutputNotEscaped
		?>
		</span>
	<?php endif; ?>

	<hr class="wp-header-end">

	<?php
	$deleted_count = (int) tec_get_request_var_raw( 'deleted', 0 );
	if ( $deleted_count ) {
		wp_admin_notice(
			// Translators: %s is the number of subscribers that were deleted.
			sprintf( _n( '%s subscriber permanently deleted.', '%s subscribers permanently deleted.', $deleted_count, 'event-tickets-plus' ), $deleted_count ),
			[
				'id'                 => 'message',
				'additional_classes' => [ 'updated' ],
				'dismissible'        => true,
			]
		);
	}
	?>

	<?php $table->views(); ?>

	<form id="posts-filter" method="get">
		<?php
		if ( ! $table->is_empty() ) {
			$table->search_box( esc_html__( 'Search Subscribers', 'event-tickets-plus' ), 'waitlist_subscribers' );
		}
		$table->display();
		?>
	</form>

	<?php
	if ( $table->has_items() ) {
		$table->inline_edit();
	}
	?>

	<div class="clear"></div>
</div>
<?php
