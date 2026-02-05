<?php

namespace TEC\Tickets_Plus\Integrations;

use TEC\Common\Integrations\Integration_Abstract as Common_Integration_Abstract;

/**
 * Class Integration_Abstract
 *
 * @since 5.8.0
 *
 * @link  https://docs.theeventscalendar.com/apis/integrations/including-new-integrations/
 *
 * @package TEC\Tickets_Plus\Integrations
 */
abstract class Integration_Abstract extends Common_Integration_Abstract {

	/**
	 * @inheritDoc
	 */
	public static function get_parent(): string {
		return 'event-tickets-plus';
	}
}
