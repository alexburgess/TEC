<?php


/**
 * Class Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Report_Tabbed_View
 *
 * Renders a tab navigation on top of a post attendance or sales report.
 *
 * The class is a convenience wrapper around the `Tribe__Tabbed_View` to set up and re-use
 * the code needed to set up and re-use this tabbed view.
 */
class Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Report_Tabbed_View {

	/**
	 * Registers the tabbed view actions.
	 *
	 * The tabs are not AJAX powered by UI around existing links.
	 *
	 * @since 4.7
	 */
	public function register() {
		add_filter( 'tribe_tickets_orders_tabbed_view_tab_map', [ $this, 'filter_tribe_tickets_orders_tabbed_view_tab_map' ] );
		add_action( 'tribe_tickets_orders_tabbed_view_register_tab_right', [ $this, 'register_orders_tab' ], 10, 2 );
		add_action( 'tec_tickets_commerce_reports_tabbed_view_before_register_tab', [ $this, 'register_orders_tab' ], 10, 2 );
	}

	/**
	 * Adds the WooCommerce orders tab slug to the tab slug map.
	 *
	 * @since 4.7
	 *
	 * @param array<string, string> $tab_map The map of tabs to their slugs.
	 *
	 * @return array
	 */
	public function filter_tribe_tickets_orders_tabbed_view_tab_map( array $tab_map = [] ) {
		$tab_map['tickets-orders'] = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report::$tab_slug;

		return $tab_map;
	}

	/**
	 * Registers the WooCommerce orders tab among those the tabbed view should render.
	 *
	 * @since 4.7
	 * @since 4.12.3 Show WooCommerce Orders tab if has any WooCommerce tickets, even if not the default provider.
	 *
	 * @param Tribe__Tabbed_View $tabbed_view The tabbed view that is rendering.
	 * @param WP_Post            $post    The post orders should be shown for.
	 */
	public function register_orders_tab( Tribe__Tabbed_View $tabbed_view, WP_Post $post ) {
		/** @var \Tribe__Tickets_Plus__Commerce__WooCommerce__Main $woo */
		$woo = tribe( 'tickets-plus.commerce.woo' );

		if (
			! tribe_tickets_is_provider_active( $woo )
			|| empty( $woo->post_has_tickets( $post ) )
		) {
			return;
		}

		$orders_report     = new Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab( $tabbed_view );
		$orders_report_url = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$tabbed_view->register( $orders_report );
	}

	/**
	 * Renders the tabbed view for the current post.
	 *
	 * @since 4.7
	 *
	 * @param bool $active Whether this tab should be set to active or not.
	 */
	public function render( $active = null ) {
		$view = new Tribe__Tabbed_View();
		$view->set_label( apply_filters( 'the_title', get_post( $this->post_id )->post_title, $this->post_id ) );
		$query_string = empty( $_SERVER['QUERY_STRING'] ) ? '' : '?' . $_SERVER['QUERY_STRING'];
		$request_uri  = 'edit.php' . $query_string;
		$view->set_url( remove_query_arg( 'tab', $request_uri ) );

		if ( ! empty( $active ) ) {
			$view->set_active( $active );
		} else {
			// Try to set the active tab from the requested page.
			parse_str( $request_uri, $query_args );
			if ( ! empty( $query_args['page'] ) && isset( $this->tab_map[ $query_args['page'] ] ) ) {
				$active = $this->tab_map[ $query_args['page'] ];
				$view->set_active( $active );
			}
		}

		$attendees_report = new Tribe__Tickets__Tabbed_View__Attendee_Report_Tab( $view );
		$post             = get_post( $this->post_id );
		$attendees_report->set_url( tribe( 'tickets.attendees' )->get_report_link( $post ) );
		$view->register( $attendees_report );

		$orders_report     = new Tribe__Tickets_Plus__Commerce__WooCommerce__Tabbed_View__Orders_Report_Tab( $view );
		$orders_report_url = Tribe__Tickets_Plus__Commerce__WooCommerce__Orders__Report::get_tickets_report_link( $post );
		$orders_report->set_url( $orders_report_url );
		$view->register( $orders_report );

		echo $view->render();
	}
}
