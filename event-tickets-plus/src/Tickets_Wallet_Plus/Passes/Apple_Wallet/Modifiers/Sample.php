<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Modifiers;

use TEC\Tickets_Wallet_Plus\Contracts\Passes\Modifier_Abstract;
use TEC\Tickets_Wallet_Plus\Admin\Settings\Wallet_Tab;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Pass_Color_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Text_Color_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Pass_Logo_Image_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Sample as Preview_Example;
use WP_Error;

class Sample extends Modifier_Abstract {

	/**
	 * Get key for download pass, this is also used as the nonce key.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public static string $url_get_key_download = 'tec-tickets-wallet-plus-apple-wallet-download';

	/**
	 * Add actions.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 * @since 6.5.0 updated `get_download_button` to use new action.
	 *
	 * @return void
	 */
	public function add_actions(): void {
		add_action( 'tec_settings_footer_after_save_fields_tab_' . Settings::$section_slug, [ $this, 'get_download_button' ] );
		add_action( 'template_redirect', [ $this, 'redirect_download' ] );
	}

	/**
	 * @inheritDoc
	 */
	public function remove_actions(): void {
		remove_action( 'template_redirect', [ $this, 'redirect_download' ] );
	}

	public function get_key(): string {
		return 'apple-wallet/sample';
	}

	/**
	 * Render the download button for the Apple Wallet Tab.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 * @since 6.5.0 Deprecated $render parameter.
	 *
	 * @param bool $render Whether to render the button or not. Deprecated since 6.5.1.
	 *
	 * @return void
	 */
	public function get_download_button( bool $render = true ): void {
		$current_section = tribe( Wallet_Tab::class )->get_current_section();

		if ( ! empty( $current_section ) && Settings::$section_slug !== $current_section ) {
			return;
		}

		$url = add_query_arg( self::$url_get_key_download, true, site_url() );
		$url = wp_nonce_url( $url, self::$url_get_key_download );

		printf(
			'<a href="%1$s" target="_blank" rel="nofollow noopener" class="button button-primary tec-tickets__admin-settings-emails-preview-button tec-tickets__admin-settings-apple-wallet-download-button">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'Download Example Wallet Pass', 'event-tickets-plus' )
		);
	}

	/**
	 * Manage the redirect to generate the Apple Pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return true|WP_Error
	 */
	public function redirect_download() {
		$should_download = (bool) tribe_get_request_var( self::$url_get_key_download );

		if ( ! $should_download ) {
			return new WP_Error( 'tec-tickets-wallet-plus-apple-wallet-pass-base-param-missing', sprintf( 'The `%s` parameter is empty.', self::$url_get_key_download ) );
		}

		// Check nonce.
		if ( ! check_admin_referer( self::$url_get_key_download ) ) {
			return new WP_Error( 'tec-tickets-wallet-plus-pdf-pass-nonce-fail', 'User failed nonce check.' );
		}

		Preview_Example::from_attendee( 0 )->create();

		return true;
	}


}
