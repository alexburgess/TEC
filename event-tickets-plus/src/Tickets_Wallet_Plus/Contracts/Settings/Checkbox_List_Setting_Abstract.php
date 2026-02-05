<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Settings;

/**
 * Class Checkbox_List_Setting_Abstract.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Contracts\Setting
 */
abstract class Checkbox_List_Setting_Abstract implements Setting_Interface {

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
	public function get_validation_callback() {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function get_default(): array {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function get_validation_type(): string {
		return 'options_multi';
	}

	/**
	 * @inheritDoc
	 */
	public function get_tooltip(): ?string {
		return null;
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
	public function get_definition(): array {
		$setting = [
			'type'            => 'checkbox_list',
			'label'           => $this->get_label(),
			'default'         => $this->get_default(),
			'validation_type' => $this->get_validation_type(),
			'options'         => $this->get_options(),
			'tooltip'         => $this->get_tooltip(),
			'can_be_empty'    => $this->can_be_empty(),
		];

		$slug = $this->get_slug();

		/**
		 * Filter the definition for this modifier setting.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $setting The definition for the modifier setting.
		 */
		return apply_filters( "tec_tickets_wallet_plus_{$slug}_checkbox_list_get_setting_definition", $setting );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_value(): array {
		$value = (array) tribe_get_option( $this->get_key(), $this->get_default() );
		return $value;
	}

	/**
	 * {@inheritdoc}
	 */
	abstract public function get_options(): array;
}
