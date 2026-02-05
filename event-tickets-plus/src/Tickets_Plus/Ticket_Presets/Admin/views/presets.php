<?php
/**
 * Ticket Presets list view.
 *
 * @since 6.6.0
 *
 * @var Presets_Table $presets_table The table instance.
 */

defined( 'ABSPATH' ) || exit;
use TEC\Tickets_Plus\Ticket_Presets\Admin\Form_Page;
use TEC\Tickets_Plus\Ticket_Presets\Admin\Presets_Table;
?>
<div class="tec-tickets-preset-list">
	<form id="tec-tickets-preset-list-table" method="get">
		<!-- Keep any hidden inputs needed for the page -->
		<input type="hidden" name="page" value="<?php echo esc_attr( tec_get_request_var( 'page' ) ?? '' ); ?>" />
		<input type="hidden" name="tab" value="<?php echo esc_attr( tec_get_request_var( 'tab' ) ?? '' ); ?>" />
		<div class="tec-tickets-preset-list__header">
			<h2 class="tec-tickets-preset-list__title">
				<?php esc_html_e( 'Ticket Presets', 'event-tickets-plus' ); ?>
				<a href="<?php echo esc_url( tribe( Form_Page::class )->get_url() ); ?>" class="page-title-action">
					<?php esc_html_e( 'Add New', 'event-tickets-plus' ); ?>
				</a>
			</h2>
			<p class="tec-tickets-preset-list__description">
				<?php
				printf(
					// Translators: %1$s is the URL to the documentation.
					wp_kses_post( __( 'Create a reusable Ticket Preset to make adding tickets to your events a breeze. <a href="%1$s" target="_blank" rel="noopener noreferrer">Learn more</a>.', 'event-tickets-plus' ) ),
					esc_url( '#' )
				);
				?>
			</p>

			<?php
			// This adds the search box in the top tablenav area automatically.
			$presets_table->search_box( __( 'Search', 'event-tickets-plus' ), 'tec-tickets-presets' );
			?>
		</div>
		<?php
		// Display the table - this will include the top and bottom tablenav areas.
		$presets_table->display();
		?>
	</form>
</div>
