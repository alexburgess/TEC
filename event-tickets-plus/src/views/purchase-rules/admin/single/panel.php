<?php
/**
 * Render the purchase rules panel for the single post.
 *
 * @since 6.9.0
 *
 * @version 6.9.0
 *
 * @var array $initial_state The initial state of the panel.
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="tec-tickets-plus-panel_purchase-rules" class="ticket_panel panel_base panel_purchase-rules" aria-hidden="true">
	<div id="tec-tickets-plus-panel_purchase-rules-app" data-initialState="<?php echo esc_attr( wp_json_encode( $initial_state ) ); ?>"></div>
</div>
