<?php

namespace TEC\Tickets_Plus\Commerce\Gateways\Stripe;

/**
 * Class Hooks.
 *
 * @since 5.4.0
 *
 * @package TEC\Tickets_Plus\Commerce\Gateways\Stripe
 */
class Hooks extends \TEC\Common\Contracts\Service_Provider {

	/**
	 * Binds and sets up implementations.
	 *
	 * @since 5.4.0
	 */
	public function register() {
		$this->add_filters();
	}

	/**
	 * Adds the filters required by each Tickets component.
	 *
	 * @since 5.4.0
	 */
	protected function add_filters() {
		add_filter( 'tec_tickets_commerce_stripe_include_payment_element', [ $this, 'enable_payment_element' ] );
		add_filter( 'tec_tickets_commerce_stripe_settings', [ $this, 'enable_stripe_wallet_settings' ], 15, 2 );
		add_filter( 'tec_tickets_commerce_stripe_checkout_localized_data', [ $this, 'enable_stripe_wallet_js' ] );
		add_filter( 'tec_tickets_commerce_stripe_payment_methods_available', [ $this, 'enable_premium_payment_methods' ] );
		add_filter( 'tec_tickets_commerce_stripe_fee_is_applied_notice', [ $this, 'is_stripe_fee_applied' ] );
		add_filter( 'tec_tickets_commerce_stripe_settings', [ $this, 'remove_stripe_fee_description' ] );
		add_filter( 'tribe_settings_validate_field_value', [ $this, 'provide_defaults_for_hidden_fields'], 10, 3 );
	}

	/**
	 * Maybe enable a new settings section to be displayed for users with valid keys.
	 *
	 * @since 5.4.0
	 *
	 * @param array $settings     An array of settings.
	 * @param bool  $is_connected Whether or not gateway is connected.
	 *
	 * @return array
	 */
	public function enable_stripe_wallet_settings( $settings, $is_connected ) {
		return tribe( Settings::class )->enable_wallet_settings( $settings, $is_connected );
	}

	/**
	 * Maybe enable the wallet settings to be added to the checkout js object.
	 *
	 * @since 5.4.0
	 *
	 * @param array $asset array of localization data from tribe_asset.
	 *
	 * @return array
	 */
	public function enable_stripe_wallet_js( $asset ) {
		return tribe( Settings::class )->enable_wallet_js( $asset );
	}

	/**
	 * Enables premium payment methods if the user has a valid license.
	 *
	 * @since 5.4.0
	 *
	 * @param array $payment_methods the original list of payment methods from ET.
	 *
	 * @return array
	 */
	public function enable_premium_payment_methods( $payment_methods ) {
		if ( ! tribe( \Tribe__Tickets_Plus__PUE::class )->is_current_license_valid( $revalidate = true ) ) {
			return $payment_methods;
		}

		return array_merge( $payment_methods, tribe( Settings::class )->get_payment_methods_available() );
	}

	/**
	 * Check if current license is valid for the fee_is_applied template variable
	 *
	 * @since 5.4.0
	 *
	 * @return bool
	 */
	public function is_stripe_fee_applied() {
		return ! tribe( \Tribe__Tickets_Plus__PUE::class )->is_current_license_valid();
	}

	/**
	 * Remove the Stripe fee description from the settings page.
	 *
	 * @since 5.4.0
	 *
	 * @param array $settings The list of Stripe Commerce settings.
	 *
	 * @return array
	 */
	public function remove_stripe_fee_description( $settings ) {
		return tribe( Settings::class )->remove_stripe_fee_description( $settings );
	}

	/**
	 * Makes sure mandatory fields have values when hidden.
	 *
	 * @since 5.4.0
	 *
	 * @param mixed  $value    Field value submitted.
	 * @param string $field_id Field key in the settings array.
	 * @param array  $field    Entire field array.
	 *
	 * @return mixed
	 */
	public function provide_defaults_for_hidden_fields( $value, $field_id, $field ) {
		return tribe( Settings::class )->reset_hidden_field_values( $value, $field_id, $field );
	}

	/**
	 * Enable the Payment Element if the user has Wallets enabled.
	 *
	 * @since 6.0.4
	 *
	 * @return bool
	 */
	public function enable_payment_element() {
		$wallets = tribe_get_option( Settings::$option_checkout_element_wallets, [] );

		return is_array( $wallets ) && ! empty( $wallets );
	}
}
