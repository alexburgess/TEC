<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Settings;

/**
 * Class Checkbox_Setting_Abstract.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Contracts\Setting
 */
abstract class Checkbox_Setting_Abstract implements Setting_Interface {

	/**
	 * @inheritDoc
	 */
	abstract public function get_key(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_label(): string;

	/**
	 * @inheritDoc
	 */
	abstract public function get_slug(): string;

	/**
	 * @inheritDoc
	 */
	public function get_default(): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function can_be_empty(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_validation_callback() {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_validation_type(): string {
		return 'boolean';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_tooltip(): ?string {
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_definition(): array {
		$setting = [
			'type'            => 'checkbox_bool',
			'label'           => $this->get_label(),
			'default'         => $this->get_default(),
			'validation_type' => $this->get_validation_type(),
			'tooltip'         => $this->get_tooltip(),
		];

		$slug = $this->get_slug();

		/**
		 * Filter the definition for this modifier visibility setting.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $setting The definition for the visibility modifier setting.
		 */
		return apply_filters( "tec_tickets_wallet_plus_{$slug}_checkbox_get_setting_definition", $setting );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_value(): string {
		$value = tribe_get_option( $this->get_key(), $this->get_default() );
		return $value;
	}
}
