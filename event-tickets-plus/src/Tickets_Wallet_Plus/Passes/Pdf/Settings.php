<?php
namespace TEC\Tickets_Wallet_Plus\Passes\Pdf;

use TEC\Tickets_Wallet_Plus\Admin\Settings\Wallet_Tab;
use TEC\Tickets_Wallet_Plus\Contracts\Settings\Settings_Abstract;

/**
 * Class Settings
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Passes\Pdf
 */
class Settings extends Settings_Abstract {

	/**
	 * Slug for the section.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string
	 */
	public static string $section_slug = 'pdf-tickets';

	/**
	 * @inheritDoc
	 */
	public function add_settings_section( $sections ): array {
		$sections[] = [
			'slug'    => self::$section_slug,
			'classes' => [],
			'url'     => tribe( Wallet_Tab::class )->get_url( [ 'section' => self::$section_slug ] ),
			'text'    => __( 'PDF tickets', 'event-tickets-plus' ),
		];

		return $sections;
	}

	/**
	 * @inheritDoc
	 */
	public function get_settings( $fields, $section ): array {

		// Bail if we're not on the PDF section.
		if ( empty( $section ) || $section !== self::$section_slug ) {
			return $fields;
		}

		$fields['tec-tickets-wallet-plus-pdf-form-toggle-wrapper-start'] = [
			'type' => 'html',
			'html' => '<div class="tec-settings-form__header-block tec-settings-form__header-block--horizontal">',
		];

		/** @var Settings\Enable_Pdf_Setting $enable_pdf_setting */
		$enable_pdf_setting = tribe( Settings\Enable_Pdf_Setting::class );
		$is_pdf_enabled     = tribe_is_truthy( $enable_pdf_setting->get_value() );

		$fields['tickets-wallet-pdf-header'] = [
			'type' => 'html',
			'html' => '<label class="tec-tickets__admin-settings-toggle-large">
								<input
									type="checkbox"
									name="' . $enable_pdf_setting->get_key() . '"
									' . checked( $is_pdf_enabled, true, false ) . '
									id="' . $enable_pdf_setting->get_key() . ' -input"
									class="tec-tickets__admin-settings-toggle-large-checkbox">
									<span class="tec-tickets__admin-settings-toggle-large-switch"></span>
									<span class="tec-tickets__admin-settings-toggle-large-label">' . $enable_pdf_setting->get_label() . '</span>
							</label>',

		];


		$fields['tickets-wallet-pdf-description'] = [
			'type' => 'html',
			'html' => '<p>' . $enable_pdf_setting->get_tooltip() . '</p>',
		];

		$fields['tec-tickets-wallet-plus-pdf-form-toggle-wrapper-end'] = [
			'type' => 'html',
			'html' => '</div>',
		];


		$fields[ $enable_pdf_setting->get_key() ] = [
			'type'            => 'hidden',
			'validation_type' => 'boolean',
		];

		$fields['tec-settings-wallet-template-settings-wrapper-start'] = [
			'type' => 'html',
			'html' => '<div class="tec-settings-form__content-section">',
		];
		$fields['tec-settings-wallet-template-settings']               = [
			'type' => 'html',
			'html' => '<h3 class="tec-settings-form__section-header tec-settings-form__section-header--sub">' . esc_html__( 'Settings', 'event-tickets-plus' ) . '</h3>',
		];

		$header_image_setting                       = tribe( Settings\Header_Image_Setting::class );
		$fields[ $header_image_setting->get_key() ] = $header_image_setting->get_definition();

		$image_alignment_setting                       = tribe( Settings\Image_Alignment_Setting::class );
		$fields[ $image_alignment_setting->get_key() ] = $image_alignment_setting->get_definition();

		$header_color_setting                       = tribe( Settings\Header_Color_Setting::class );
		$fields[ $header_color_setting->get_key() ] = $header_color_setting->get_definition();

		$additional_content_setting                       = tribe( Settings\Additional_Content_Setting::class );
		$fields[ $additional_content_setting->get_key() ] = $additional_content_setting->get_definition();

		$qr_codes_setting                       = tribe( Settings\Qr_Codes_Setting::class );
		$fields[ $qr_codes_setting->get_key() ] = $qr_codes_setting->get_definition();

		$include_credit_setting                       = tribe( Settings\Include_Credit_Setting::class );
		$fields[ $include_credit_setting->get_key() ] = $include_credit_setting->get_definition();

		$fields['tec-tickets-wallet-plus-apple-form-end'] = [
			'type' => 'html',
			'html' => '</div>',
		];

		/**
		 * Filter the settings fields for the PDF section.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $fields The settings fields.
		 */
		$fields = apply_filters( 'tec_tickets_wallet_plus_pdf_settings_fields', $fields );

		return $fields;
	}
}
