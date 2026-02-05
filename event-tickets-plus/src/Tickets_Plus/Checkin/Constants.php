<?php
/**
 * Constants for check-in related functionality.
 *
 * @since 6.7.0
 *
 * @package TEC\Tickets_Plus\Checkin;
 */

namespace TEC\Tickets_Plus\Checkin;

/**
 * Class Constants.
 *
 * Defines constants used in check-in related functionality.
 *
 * @since 6.7.0
 *
 * @package TEC\Tickets_Plus\Checkin;
 */
class Constants {

	/**
	 * Meta key for storing check-in logs.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_META_KEY = '_tec_tickets_checkin_log';

	/**
	 * Meta key for storing the count of duplicate check-ins for an event.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_COUNT_META_KEY = '_tec_tickets_failed_checkin_count';

	/**
	 * Meta key for storing the count of failed check-ins for duplicates for an event.
	 * This is a count of the number of times a duplicate check-in has occurred for an event.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_DUPLICATE_META_KEY = '_tec_tickets_failed_duplicate_checkin_count';

	/**
	 * Meta key for storing the count of failed check-ins for security failures for an event.
	 * This is a count of the number of times a security failure has occurred for an event.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_SECURITY_META_KEY = '_tec_tickets_failed_security_checkin_count';

	/**
	 * Meta key for storing the count of failed check-ins for checkouts for an event.
	 * This is a count of the number of times a checkout has failed for an event.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_CHECKOUT_META_KEY = '_tec_tickets_failed_checkout_checkin_count';

	/**
	 * Meta key for storing the count of unique attendees with duplicate check-ins for an event.
	 * This is a count of the number of unique attendees that have had at least one duplicate check-in.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_DUPLICATE_ATTENDEES_META_KEY = '_tec_tickets_duplicate_checkin_attendees_count';

	/**
	 * Meta key for storing the duplicate check-in flag.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_DUPLICATE_FLAG = 'DUPLICATE';

	/**
	 * Meta key for storing the security failure check-in flag.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_SECURITY_FLAG = 'SECURITY';

	/**
	 * Meta key for storing the checkout flag.
	 *
	 * @since 6.7.0
	 *
	 * @var string
	 */
	const CHECKIN_LOGGING_CHECKOUT_FLAG = 'CHECKOUT';
}
