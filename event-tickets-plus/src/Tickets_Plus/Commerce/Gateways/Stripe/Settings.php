<?php

namespace TEC\Tickets_Plus\Commerce\Gateways\Stripe;

use TEC\Tickets\Commerce\Gateways\Stripe\Settings as Base_Settings;

/**
 * Stripe Settings class for users with valid license keys.
 *
 * @since 5.4.0
 *
 * @package TEC\Tickets_Plus\Commerce\Gateways\Stripe
 */
class Settings {

	/**
	 * The options key to use when storing disabled wallets.
	 *
	 * @since 5.4.0
	 *
	 * @var string
	 */
	public static $option_checkout_element_wallets = 'tickets-commerce-stripe-checkout-element-wallets';

	/**
	 * Enables the wallet settings to be added to the checkout javascript object.
	 *
	 * @since 5.4.0
	 *
	 * @param array $asset array of localization data from tribe_asset.
	 *
	 * @return array
	 */
	public function enable_wallet_js( $asset ) {
		if ( ! tribe( \Tribe__Tickets_Plus__PUE::class )->is_current_license_valid() ) {
			return $asset;
		}

		$wallet_settings = tribe_get_option( static::$option_checkout_element_wallets, [] );

		$asset['wallet_settings'] = [
			'apple_pay'  => in_array( 'apple_pay', $wallet_settings, true ),
			'google_pay' => in_array( 'google_pay', $wallet_settings, true ),
		];

		return $asset;
	}

	/**
	 * Adds a new section to Stripe settings allowing to disable the different payment wallets.
	 *
	 * @since 5.4.0
	 *
	 * @param array $settings     An array of settings.
	 * @param bool  $is_connected Whether or not gateway is connected.
	 *
	 * @return array
	 */
	public function enable_wallet_settings( $settings, $is_connected ) {
		// If Stripe isn't connected, bail.
		if ( ! $is_connected ) {
			return $settings;
		}

		if ( ! tribe( \Tribe__Tickets_Plus__PUE::class )->is_current_license_valid() ) {
			return $settings;
		}

		$wallets = [
			static::$option_checkout_element_wallets => [
				'type'                => 'checkbox_list',
				'label'               => esc_html__( 'Payment Wallets', 'event-tickets-plus' ),
				'tooltip'             => wp_kses(
					sprintf(
						// Translators: %1$s A link to the KB article. %2$s closing `</a>` link.
						__( 'Enable Apple Pay and/or Google Pay payments. Make sure your stripe account is configured to accept wallet payments. %1$sLearn more%2$s', 'event-tickets-plus' ),
						'<a target="_blank" rel="noopener noreferrer" href="https://evnt.is/1b3t">',
						'</a>'
					)
				, [ 'a' => [ 'target' => [], 'rel' => [], 'href' => [], 'class' => [] ] ] ),
				'fieldset_attributes' => [
					'data-depends'              => '#tribe-field-' . Base_Settings::$option_checkout_element . '-' . Base_Settings::PAYMENT_ELEMENT_SLUG,
					'data-condition-is-checked' => true,
				],
				'validation_type'     => 'options_multi',
				'can_be_empty'        => true,
				'options'         => [
					'apple_pay'  => esc_html__( 'Apple Pay', 'event-tickets-plus' ),
					'google_pay' => esc_html__( 'Google Pay', 'event-tickets-plus' ),
				],
			],

		];

		return array_merge( $settings, $wallets );
	}

	/**
	 * Extra payment methods available for licensed ET+ users.
	 *
	 * @since 5.4.0
	 *
	 * @return array[]
	 */
	public function get_payment_methods_available() {
		return [
			'bancontact'    => [
				'currencies' => [ 'EUR' ],
				'label'      => esc_html__( 'Bancontact', 'event-tickets-plus' ),
			],
			'au_becs_debit' => [
				'currencies' => [ 'AUD' ],
				'label'      => esc_html__( 'BECS Direct Debit', 'event-tickets-plus' ),
			],
			'boleto'        => [
				'currencies' => [ 'BRL' ],
				'label'      => esc_html__( 'Boleto', 'event-tickets-plus' ),
			],
			'eps'           => [
				'currencies' => [ 'EUR' ],
				'label'      => esc_html__( 'EPS', 'event-tickets-plus' ),
			],
			'fpx'           => [
				'currencies' => [ 'MYR' ],
				'label'      => esc_html__( 'FPX', 'event-tickets-plus' ),
			],
			'grabpay'       => [
				'currencies' => [ 'MYR', 'SGD' ],
				'label'      => esc_html__( 'GrabPay', 'event-tickets-plus' ),
			],
			'ideal'         => [
				'currencies' => [ 'EUR' ],
				'label'      => esc_html__( 'iDEAL', 'event-tickets-plus' ),
			],
			'oxxo'          => [
				'currencies' => [ 'MXN' ],
				'label'      => esc_html__( 'OXXO', 'event-tickets-plus' ),
			],
			'p24'           => [
				'currencies' => [ 'EUR', 'PLN' ],
				'label'      => esc_html__( 'P24', 'event-tickets-plus' ),
			],
			'sepa_debit'    => [
				'currencies' => [ 'EUR' ],
				'label'      => esc_html__( 'SEPA debit', 'event-tickets-plus' ),
			],
			'sofort'        => [
				'currencies' => [ 'EUR' ],
				'label'      => esc_html__( 'Sofort', 'event-tickets-plus' ),
			],
			'wechat_pay'    => [
				'currencies' => [
					'AUD',
					'CAD',
					'CHF',
					'CNY',
					'DKK',
					'GBP',
					'HKD',
					'JPY',
					'NOK',
					'SEK',
					'SGD',
					'USD',
				],
				'label'      => esc_html__( 'WeChat Pay', 'event-tickets-plus' ),
			],
		];
	}

	/**
	 * Remove the Stripe fee description from the settings page, for premium users.
	 *
	 * @since 5.4.0
	 *
	 * @param array $settings The list of Stripe Commerce settings.
	 *
	 * @return array
	 */
	public function remove_stripe_fee_description( $settings ) {
		if ( ! tribe( \Tribe__Tickets_Plus__PUE::class )->is_current_license_valid() ) {
			return $settings;
		}

		unset( $settings['tickets-commerce-stripe-commerce-description'] );

		return $settings;
	}

	/**
	 * Resets the values of payment methods and card options if they are no longer in use and avoid a settings
	 * notice for empty values.
	 *
	 * @since 5.4.0
	 *
	 * @param mixed  $value    Field value submitted.
	 * @param string $field_id Field key in the settings array.
	 * @param array  $field    Entire field array.
	 *
	 * @return mixed
	 */
	public function reset_hidden_field_values( $value, $field_id, $field ) {

		if ( $value ) {
			return $value;
		}

		if ( $field_id === static::$option_checkout_element_wallets ) {
			return tribe_get_option( static::$option_checkout_element_wallets, [] );
		}

		return $value;
	}

}
