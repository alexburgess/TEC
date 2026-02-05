<?php
/**
 * Service Provider for Passes\Pdf functionality.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf
 */

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf;

use TEC\Tickets_Wallet_Plus\Admin\Settings\Wallet_Tab;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Controller_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Settings_Abstract;
use TEC\Tickets_Wallet_Plus\Plugin;

/**
 * Class Controller
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Passes\Pdf
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
		Modifiers\Include_To_Attendee_Modal::class,
		Modifiers\Include_To_Rsvp::class,
		Modifiers\Attach_To_Emails::class,
		Modifiers\Include_To_My_Tickets::class,
		Modifiers\Include_To_Attendees_List::class,
	];

	/**
	 * Depending on the PHP version and the Imagick extension version built with it, seeking on remote files,
	 * a capability required by the underlying PDF generation library to work when the Imagick extension is
	 * installed, will be required. This feature detection runs once a week.
	 *
	 * @since 6.1.1
	 *
	 * @return bool Whether the Image Magick extension is loaded and the loaded version can seek on remote files or not.
	 */
	private function imagick_can_fseek_remote_files(): bool {
		if ( ! extension_loaded( 'imagick' ) ) {
			return false;
		}

		$transient = get_transient( 'tec_tickets_plus_imagick_fseek_support' );

		if ( ! empty( $transient ) && in_array( $transient, [ 'yes', 'no' ], true ) ) {
			return 'yes' === $transient;
		}

		$test_image_file_path = tribe( Plugin::class )->plugin_url . 'src/resources/images/tickets-wallet-plus/example-qr.png';

		try {
			$imagick = new \Imagick();
			$imagick->readImage( $test_image_file_path );
		} catch ( \Throwable $e ) {
			set_transient( 'tec_tickets_plus_imagick_fseek_support', 'no' );

			return false;
		}

		set_transient( 'tec_tickets_plus_imagick_fseek_support', 'yes', WEEK_IN_SECONDS );

		return true;
	}

	/**
	 * Determines if this controller will register. The GD extension is required.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 * @since 6.1.1 Activate the controller if the GD or Imagick extensions are loaded and of the correct version.
	 * @since 6.6.0 Change the logic to check if the required extensions are loaded.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return $this->imagick_can_fseek_remote_files() || extension_loaded( 'gd' );
	}

	/**
	 * @inheritDoc
	 */
	public function is_enabled(): bool {
		$pass_enabled = $this->container->make( Settings\Enable_Pdf_Setting::class )->get_value();

		return tribe_is_truthy( $pass_enabled );
	}

	/**
	 * @inheritDoc
	 */
	public function do_register(): void {
		$this->container->singleton( Settings::class, Settings::class );
		$this->container->bind( Pass::class, Pass::class );

		// Define some constants used by the TCPDF library to handle exceptions.
		if ( ! defined( 'K_TCPDF_EXTERNAL_CONFIG' ) ) {
			define( 'K_TCPDF_EXTERNAL_CONFIG', true );
		}

		if ( ! defined( 'K_TCPDF_THROW_EXCEPTION_ERROR' ) ) {
			define( 'K_TCPDF_THROW_EXCEPTION_ERROR', true );
		}

		parent::do_register();
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
	public function add_actions(): void {
		add_action( 'tec_settings_footer_after_save_fields_tab_' . Settings::$section_slug, [ $this, 'render_sample_button' ] );
	}

	/**
	 * @inheritDoc
	 */
	public function remove_actions(): void {
		remove_action( 'tec_settings_footer_after_save_fields_tab_' . Settings::$section_slug, [ $this, 'render_sample_button' ] );
	}

	/**
	 * Render sample button.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function render_sample_button() {
		tribe( Sample::class )->render_button();
	}

	/**
	 * @inheritDoc
	 */
	public function get_slug(): string {
		return 'pdf';
	}

	/**
	 * @inheritDoc
	 */
	public function get_name(): string {
		return __( 'PDF tickets', 'event-tickets-plus' );
	}
}
