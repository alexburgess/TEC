<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf;

use TEC\Tickets\QR\Settings as QR_Settings;
use TEC\Tickets\Emails\Admin\Preview_Data;
use TEC\Tickets_Wallet_Plus\Admin\Settings\Wallet_Tab;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Modifiers\Handle_Pass_Redirect;
use TEC\Tickets_Wallet_Plus\Plugin;

/**
 * Class Sample
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Pdf
 */
class Sample extends Pass {

	/**
	 * Renders the sample button.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function render_button() {
		if ( Settings::$section_slug !== tribe( Wallet_Tab::class )->get_current_section() ) {
			return;
		}

		$url = add_query_arg( Handle_Pass_Redirect::$url_get_key_sample, true, site_url() );
		$url = wp_nonce_url( $url, Handle_Pass_Redirect::$url_get_key_sample );

		echo sprintf(
			'<a href="%1$s" target="_blank" rel="nofollow noopener" class="button button-primary tec-tickets__admin-settings-emails-preview-button">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'Download Example PDF Ticket', 'event-tickets-plus' )
		);
	}

	/**
	 * @inheritDoc
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 */
	public function get_template_variables(): array {
		$pdf_settings = [
			'header_image_url'       => tribe( Settings\Header_Image_Setting::class )->get_attachment_url(),
			'header_image_alignment' => tribe( Settings\Image_Alignment_Setting::class )->get_value(),
			'header_bg_color'        => tribe( Settings\Header_Color_Setting::class )->get_value(),
			'additional_info'        => wpautop( tribe( Settings\Additional_Content_Setting::class )->get_value() ),
			'qr_enabled'             => tribe( QR_Settings::class )->is_enabled() && tribe( Settings\Qr_Codes_Setting::class )->get_value(),
			'include_credit'         => tribe( Settings\Include_Credit_Setting::class )->get_value(),
		];

		// Get some info from Tickets Emails Preview data.
		$attendees = tribe( Preview_Data::class )->get_attendees();
		$post      = tribe( Preview_Data::class )->get_post();

		$attendees[0]['holder_name'] = empty( $attendees[0]['post_title'] ) ? $attendees[0]['holder_name'] : $attendees[0]['post_title'];

		$context = array_merge(
			$pdf_settings,
			[
				'attendee'       => $attendees[0],
				'post'           => $post,
				'post_image_url' => tribe( Plugin::class )->plugin_url . 'src/resources/images/tickets-wallet-plus/post-example-image.jpg',
				'qr_image_url'   => tribe( Plugin::class )->plugin_url . 'src/resources/images/tickets-wallet-plus/example-qr.png',
			]
		);

		/**
		 * Filter the template context for the PDF sample pass.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $context The template context.
		 *
		 * @return array The modified template context.
		 */
		return apply_filters( 'tec_tickets_wallet_plus_pdf_sample_template_context', $context );
	}
}
