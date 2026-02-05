<?php
/**
 * Purchase Rules Page which renders the Purchase Rules list table.
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules/Admin
 */

namespace TEC\Tickets_Plus\Commerce\Purchase_Rules\Admin;

use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use TEC\Common\Contracts\Container;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Template;
use Tribe__Template as Template_Base;
use TEC\Common\StellarWP\Assets\Asset;
use Tribe__Tickets_Plus__Main as Tickets_Plus;
use TEC\Tickets_Plus\Commerce\Purchase_Rules\Models\Rule;

/**
 * Class Single_Post
 *
 * @since 6.9.0
 *
 * @package TEC/Tickets_Plus/Commerce/Purchase_Rules/Admin
 */
class Single_Post extends Controller_Contract {

	/**
	 * The template instance.
	 *
	 * @since 6.9.0
	 *
	 * @var Template
	 */
	private Template $template;

	/**
	 * Single_Post constructor.
	 *
	 * @since 6.9.0
	 *
	 * @param Container $container The container.
	 * @param Template  $template  The template.
	 */
	public function __construct( Container $container, Template $template ) {
		parent::__construct( $container );
		$this->template = $template;
	}
	/**
	 * Registers the controller by subscribing to WordPress hooks and binding implementations.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	protected function do_register(): void {
		$this->register_assets();
		add_action( 'tribe_template_before_include_html:tickets/admin-views/editor/panel/settings-button', [ $this, 'render_purchase_rules_button' ], 10, 4 );
		add_filter( 'tec_tickets_panels', [ $this, 'add_purchase_rules_panel' ], 10, 2 );
	}

	/**
	 * Un-registers the Controller by unsubscribing from WordPress hooks.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	public function unregister(): void {
		remove_action( 'tribe_template_before_include_html:tickets/admin-views/editor/panel/settings-button', [ $this, 'render_purchase_rules_button' ] );
		remove_filter( 'tec_tickets_panels', [ $this, 'add_purchase_rules_panel' ] );
	}

	/**
	 * Adds the purchase rules panel to the panels array.
	 *
	 * @since 6.9.0
	 *
	 * @param array       $panels         The panels array.
	 * @param int|WP_Post $post_or_post_id The post or post ID.
	 *
	 * @return array The panels array.
	 */
	public function add_purchase_rules_panel( array $panels, $post_or_post_id ): array {
		if ( ! tribe_is_event( $post_or_post_id ) ) {
			return $panels;
		}

		$panels['purchase-rules'] = $this->template->template( 'admin/single/panel', [], false );

		return $panels;
	}

	/**
	 * Renders the purchase rules button.
	 *
	 * @since 6.9.0
	 *
	 * @param string        $html     The HTML to render.
	 * @param string        $file     The file to render.
	 * @param array         $name     The name of the template.
	 * @param Template_Base $template The template instance.
	 *
	 * @return string The HTML to render.
	 */
	public function render_purchase_rules_button( string $html, string $file, array $name, Template_Base $template ): string {
		$context = $template->get_values();

		$post_id = $context['post_id'] ?? null;

		if ( ! $post_id ) {
			return $html;
		}

		$original_id = $context['original_id'] ?? null;

		if ( $original_id && $original_id !== $post_id ) {
			return $html;
		}

		if ( ! tribe_is_event( $post_id ) ) {
			return $html;
		}

		/**
		 * Fires before the purchase rules button is rendered.
		 *
		 * @since 6.9.0
		 */
		do_action( 'tec_tickets_plus_purchase_rules_admin_single_post_before_render_button' );

		return $this->template->template( 'admin/single/button', [ 'initial_state' => Rule::get_active_rules_for_post_backend( $post_id ) ], false ) . $html;
	}

	/**
	 * Registers the assets.
	 *
	 * @since 6.9.0
	 *
	 * @return void
	 */
	protected function register_assets(): void {
		Asset::add(
			'tec-tickets-plus-purchase-rules-admin-single-post-style',
			'purchaseRules/admin/single-post.css',
			Tickets_Plus::VERSION
		)
		->add_to_group_path( Tickets_Plus::class . '-packages' )
		->enqueue_on( 'tec_tickets_plus_purchase_rules_admin_single_post_before_render_button' )
		->set_dependencies( 'wp-components' );

		Asset::add(
			'tec-tickets-plus-purchase-rules-admin-single-post',
			'purchaseRules/admin/single-post.js',
			Tickets_Plus::VERSION
		)
		->add_to_group_path( Tickets_Plus::class . '-packages' )
		->add_localize_script(
			'tec.ticketsPlus.commerce.purchaseRules.singlePost.data',
			fn(): array => [
				'types' => Rule::get_rule_type_labels(),
			]
		)
		->enqueue_on( 'tec_tickets_plus_purchase_rules_admin_single_post_before_render_button' );
	}
}
