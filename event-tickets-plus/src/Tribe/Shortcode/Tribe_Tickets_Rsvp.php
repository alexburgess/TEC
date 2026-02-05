<?php
/**
 * Shortcode [tribe_tickets_rsvp].
 *
 * @since 4.12.1
 * @package Tribe\Tickets\Plus\Shortcode
 */

namespace Tribe\Tickets\Plus\Shortcode;

use Tribe\Shortcode\Shortcode_Abstract;
use Tribe__Tickets__Tickets_View as Tickets_View;
use WP_Post;

/**
 * Class for Shortcode Tribe_Tickets_Rsvp.
 *
 * @since 4.12.1
 * @package Tribe\Tickets\Plus\Shortcode
 */
class Tribe_Tickets_Rsvp extends Shortcode_Abstract {

	/**
	 * {@inheritDoc}
	 */
	protected $slug = 'tribe_tickets_rsvp';

	/**
	 * {@inheritDoc}
	 */
	protected $default_arguments = [
		'post_id' => null,
		'ticket_id' => null,
	];

	/**
	 * {@inheritDoc}
	 */
	public $validate_arguments_map = [
		'post_id' => 'tribe_post_exists',
	];

	/**
	 * {@inheritDoc}
	 */
	public function get_default_arguments() {
		$default_arguments = parent::get_default_arguments();

		/**
		 * Default to current Post ID, even if zero, since validation via tribe_post_exists() requires passing some
		 * value. Respect if the attribute got set via filter from parent method.
		 */
		$default_arguments['post_id'] = absint( $default_arguments['post_id'] );

		if ( empty( $default_arguments['post_id'] ) ) {
			$default_arguments['post_id'] = absint( get_the_ID() );
		}

		return $default_arguments;
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_html() {
		$context = tribe_context();

		if ( is_admin() && ! $context->doing_ajax() ) {
			return '';
		}

		$post_id   = $this->get_argument( 'post_id' );
		$ticket_id = $this->get_argument( 'ticket_id' );

		if ( empty( $post_id ) || empty( $ticket_id ) ) {
			return $this->get_rsvp_block( $post_id );
		}

		// When post id and ticket id are present, send array of ticket ids to include.
		$include_tickets = array_map( 'intval', explode( ',', $ticket_id ) );

		return $this->get_rsvp_block( $post_id, $include_tickets );
	}

	/**
	 * Gets the block template and return it.
	 *
	 * @param WP_Post|int $post the post/event we're viewing.
	 *
	 * @return string HTML.
	 */
	public function get_rsvp_block( $post, $include_tickets = [] ) {
		$tickets_view = Tickets_View::instance();

		return $tickets_view->get_rsvp_block( $post, false, $include_tickets );
	}
}
