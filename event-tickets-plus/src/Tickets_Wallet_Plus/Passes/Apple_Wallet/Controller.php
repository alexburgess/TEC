<?php
/**
 * Service Provider for Passes\Apple_Wallet functionality.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Controller_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Settings_Abstract;

/**
 * Class Controller
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Controller extends Controller_Abstract {
	/**
	 * Stores all the modifiers for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var array<string|Modifier_Abstract> The modifiers for the pass.
	 */
	protected array $modifiers = [
		Modifiers\Attendee_Table_Row_Actions::class,
		Modifiers\Handle_Pass_Redirect::class,
		Modifiers\Email_Link::class,
		Modifiers\Sample::class,
		Modifiers\Include_To_Attendee_Modal::class,
		Modifiers\Include_To_Rsvp::class,
		Modifiers\Include_To_Tickets_Email::class,
		Modifiers\Include_To_My_Tickets::class,
		Modifiers\Include_To_Attendees_List::class,
	];

	/**
	 * Determines if this controller will register.
	 * This is present due to how UOPZ works, it will fail if method belongs to the parent/abstract class.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function is_enabled(): bool {
		$pass_enabled = $this->container->make( Settings\Enable_Passes_Setting::class )->get_value();

		return tribe_is_truthy( $pass_enabled );
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings(): Settings_Abstract {
		return $this->container->make( Settings::class );
	}

	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		$this->container->singleton( Client::class, Client::class );
		$this->container->singleton( Settings::class, Settings::class );
		$this->container->bind( Package::class, Package::class );
		$this->container->bind( Pass::class, Pass::class );

		parent::do_register();
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'apple-wallet';
	}

	/**
	 * @inheritDoc
	 */
	public function get_name(): string {
		return __( 'Apple Wallet pass', 'event-tickets-plus' );
	}
}
