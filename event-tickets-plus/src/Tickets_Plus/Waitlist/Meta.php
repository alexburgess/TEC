<?php
/**
 * Information repository and handler of post meta data for the plugin.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist
 */

namespace TEC\Tickets_Plus\Waitlist;

/**
 * Class Meta.
 *
 * @since 6.2.0
 *
 * @package TEC/Tickets_Plus/Waitlist;
 */
class Meta {
	/**
	 * The meta key that let us know if the waitlist is enabled or not.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const ENABLED_KEY = '_tribe_tickets_plus_waitlist_enabled';

	/**
	 * The meta key that let us know under which conditional the waitlist is enabled.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const CONDITIONAL_KEY = '_tribe_tickets_plus_waitlist_conditional';

	/**
	 * The meta key that let us know if the waitlist is enabled or not.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const RSVP_ENABLED_KEY = '_tribe_tickets_plus_rsvp_waitlist_enabled';

	/**
	 * The meta key that let us know under which conditional the waitlist is enabled.
	 *
	 * @since 6.2.0
	 *
	 * @var string
	 */
	public const RSVP_CONDITIONAL_KEY = '_tribe_tickets_plus_rsvp_waitlist_conditional';
}
