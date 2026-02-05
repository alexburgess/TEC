<?php
use TEC\Common\Contracts\Provider\Controller as Controller_Contract;
use Tribe__Tickets_Plus__Main as Tickets_Plus;

/**
 * Events Gutenberg Assets
 *
 * @since 4.9
 */
class Tribe__Tickets_Plus__Editor__Assets extends Controller_Contract { // phpcs:ignore StellarWP.Classes.ValidClassName.NotSnakeCase, PEAR.NamingConventions.ValidClassName.Invalid, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace
	/**
	 * @since 4.9
	 * @deprecated 6.1.3
	 *
	 * @return void
	 */
	public function hook() {
		_deprecated_function( __METHOD__, '6.1.3' );
	}

	/**
	 * Registers and Enqueues the assets
	 *
	 * @since 4.9
	 */
	public function do_register(): void {
		tec_asset(
			Tickets_Plus::instance(),
			'tribe-tickets-plus-gutenberg-data',
			'app/data.js',
			/**
			 * @todo revise this dependencies
			 */
			[
				'react',
				'react-dom',
				'thickbox',
				'wp-components',
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				'wp-editor',
			],
			'enqueue_block_editor_assets',
			[
				'in_footer'    => false,
				'localize'     => [],
				'conditionals' => tribe_callback( 'tickets.editor', 'current_post_supports_tickets' ),
				'priority'     => 200,
				'group_path'   => Tickets_Plus::class . '-packages',
			]
		);
	}

	/**
	 * Unregisters the assets
	 *
	 * @since 6.1.3
	 */
	public function unregister(): void {
		// Do nothing.
	}
}
