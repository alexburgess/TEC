<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Settings;

/**
 * Class Text_Setting_Abstract.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Contracts\Setting
 */
abstract class Text_Setting_Abstract implements Setting_Interface {

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
	public function get_default(): string {
		return '';
	}

	/**
	 * Get the default size.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The default string.
	 */
	public function get_size(): string {
		return 'medium';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_validation_callback(): string {
		return 'is_string';
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
	public function get_tooltip(): ?string {
		return null;
	}

	/**
	 * Get the setting definition.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array The setting definition.
	 */
	public function get_definition(): array {
		$setting = [
			'type'                => 'text',
			'label'               => $this->get_label(),
			'size'                => $this->get_size(),
			'default'             => $this->get_default(),
			'validation_callback' => $this->get_validation_callback(),
			'can_be_empty'        => $this->can_be_empty(),
			'tooltip'             => $this->get_tooltip(),
		];

		$slug = $this->get_slug();

		/**
		 * Filter the definition for this modifier visibility setting.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $setting The definition for the visibility modifier setting.
		 */
		return apply_filters( "tec_tickets_wallet_plus_{$slug}_text_get_setting_definition", $setting );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_value(): string {
		$value = tribe_get_option( $this->get_key(), $this->get_default() );

		return $value;
	}
}
