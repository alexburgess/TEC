<?php
/**
 * Renders a Purchase Rule's edit page.
 *
 * Override this template in your own theme by creating a file at:
 *
 *     [your-theme]/tribe/tickets-plus/purchase-rules/admin/edit.php
 *
 * @link https://evnt.is/1amp See more documentation about our views templating system.
 *
 * @since 6.9.0
 *
 * @version 6.9.0
 *
 * @var Template $this The Purchase Rules template instance.
 * @var Rule     $rule The Purchase Rule instance.
 */

use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Template;

defined( 'ABSPATH' ) || exit;

$purchase_rule_id = tec_get_request_var_raw( 'rule_id', null );
if ( null === $purchase_rule_id ) {
	$purchase_rule_id = 0;
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php echo $purchase_rule_id ? esc_html__( 'Edit Purchase Rule', 'event-tickets-plus' ) : esc_html__( 'New Purchase Rule', 'event-tickets-plus' ); ?>
	</h1>

	<hr class="wp-header-end" />

	<div class="clear">
		<div id="tec-tickets-plus-purchase-rules-edit"></div>
	</div>
</div>
<?php
