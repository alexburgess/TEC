<?php

namespace TEC\Tickets_Wallet_Plus\Contracts\Settings;

/**
 * Class Image_Setting_Abstract.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Contracts\Setting
 */
abstract class Image_Setting_Abstract implements Setting_Interface {

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
	 * {@inheritdoc}
	 */
	public function get_validation_callback(): string {
		return 'is_numeric';
	}


	/**
	 * {@inheritdoc}
	 */
	public function get_validation_type(): string {
		return 'int';
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
	public function can_be_empty(): bool {
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_definition(): array {
		$setting = [
			'type'                => 'image_id',
			'label'               => $this->get_label(),
			'default'             => $this->get_default(),
			'validation_callback' => $this->get_validation_callback(),
			'validation_type'     => $this->get_validation_type(),
			'tooltip'             => $this->get_tooltip(),
			'can_be_empty'        => $this->can_be_empty(),
		];

		$slug = $this->get_slug();

		/**
		 * Filter the definition for this modifier visibility setting.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $setting The definition for the visibility modifier setting.
		 */
		return apply_filters( "tec_tickets_wallet_plus_{$slug}_image_get_setting_definition", $setting );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_value(): string {
		$value = tribe_get_option( $this->get_key(), $this->get_default() );

		return $value;
	}

	/**
	 * Gets the attachment, based on the ID stored in the Database.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $size The size of the image to get.
	 *
	 * @return string|null
	 */
	public function get_attachment_url( string $size = 'full' ): ?string {
		return wp_get_attachment_image_url( $this->get_value(), $size );
	}

	/**
	 * Determines if the attachment exists.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool
	 */
	public function attachment_exists(): bool {
		return wp_attachment_is( 'image', $this->get_value() );
	}

	/**
	 * Gets the attachment, based on the ID stored in the Database.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $size The size of the image to get.
	 *
	 * @return string|null
	 */
	public function get_attachment_path( string $size = 'full' ): ?string {
		$attachment_id = $this->get_value();

		$file = get_attached_file( $attachment_id, true );

		if ( empty( $file ) ) {
			return null;
		}

		if ( empty( $size ) || 'full' === $size ) {
			// for the original size get_attached_file is fine.
			return realpath( $file );
		}
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return null; // the id is not referring to a media.
		}

		$info = image_get_intermediate_size( $attachment_id, $size );
		if ( ! is_array( $info ) || ! isset( $info['file'] ) ) {
			return null; // probably a bad size argument.
		}

		return realpath( str_replace( wp_basename( $file ), $info['file'], $file ) );
	}
}
