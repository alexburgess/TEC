<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use Tribe__Utils__Array as Arr;
use WP_Error;

/**
 * Class Pass_Obj
 *
 * @todo    rename this class to something that avoids the _Obj suffix. Most likely Pass, but now we have conflict.
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Package {
	/**
	 * Stores the date format used by the pass when handling generic formatted dates.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string DATE_FORMAT
	 */
	protected const DATE_FORMAT = 'Y-m-d\TH:i:sP';

	/**
	 * Stores the latest validated hash for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var ?string
	 */
	protected ?string $validated_hash = null;

	/**
	 * Store all errors that happen during the last validation.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var ?WP_Error
	 */
	protected ?WP_Error $error;

	/**
	 * Which arguments are valid for the pass,
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var array<string> $valid_arguments
	 */
	protected static array $valid_arguments = [
		'images',
		'logo_text',
		'description',
		'serial_number',
		'organization_name',
		'foreground_color',
		'background_color',
		'relevant_date',
		'header',
		'primary',
		'secondary',
		'auxiliary',
		'back',
		'barcode',
	];

	/**
	 * Stores the pass data.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var array $data
	 */
	protected array $data = [
		'header'    => [],
		'primary'   => [],
		'secondary' => [],
		'auxiliary' => [],
		'back'      => [],
		'barcode'   => [],
	];

	/**
	 * Generates a new pass object from an array of data.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $data
	 *
	 * @return self
	 */
	public static function from_array( array $data ): self {
		$pass = new static();

		foreach ( $data as $key => $value ) {
			if ( ! in_array( $key, static::$valid_arguments, true ) ) {
				continue;
			}

			$pass->$key( $value );
		}

		return $pass;
	}

	/**
	 * Determines if this instance of the pass will send URLs or full base64 encoded images.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool
	 */
	protected function should_attach_image_bits(): bool {
		$should_attach = false;
		if ( 'production' !== wp_get_environment_type() ) {
			$should_attach = true;
		}

		/**
		 * Allows to filter if we should attach the image bits to the pass or pass a URL.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param bool $should_attach Determines if we will attach the images or pass a URL.
		 */
		return apply_filters( 'tec_tickets_wallet_plus_passes_apple_wallet_pass_package_should_attach_image_bits', $should_attach, $this );
	}

	/**
	 * Embeds the images into the pass.
	 * If an image is empty, a transparent pixel will be used as a fallback.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $images Array of images to embed, using their URLs.
	 *
	 * @return $this
	 */
	public function images( array $images ): self {
		$fallback_image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+P+/HgAFhAJ/wlseKgAAAABJRU5ErkJggg==';

		if ( ! isset( $this->data['images'] ) ) {
			$this->data['images'] = [];
		}

		// If no images aren't sent, set to $fallback_image and bail.
 		if ( ! count( $images ) ) {
			$this->data['images']['icon'] = $fallback_image;
			return $this;
		}

		foreach ( $images as $name => $image ) {
			// @todo redscar - Find out best method for having an empty image logo. [ETWP-75]
			if ( empty( $image ) ) {
				// Add a transparent 1x1 pixel.
				$this->data['images'][ $name ] = $fallback_image;
				continue;
			}
			$this->add_image( $name, $image );
		}

		// If no logo is passed, use the icon as logo.
		if (
			empty( $this->data['images']['logo'] )
			&& ! empty( $this->data['images']['icon'] )
		) {
			$this->data['images']['logo'] = $this->data['images']['icon'];
		}

		return $this;
	}

	/**
	 * Handles the adding of a single image into the
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $name  Which file name we will bundle this as.
	 * @param string $image URL of the image we want to bundle.
	 *
	 * @return $this
	 */
	public function add_image( string $name, string $image ): self {
		$is_url = wp_http_validate_url( $image ) && esc_url_raw( $image ) === $image;
		if ( $this->should_attach_image_bits() || ! $is_url ) {
			$image_bits = file_get_contents( $image );
			$image = base64_encode( $image_bits );
		} else {
			$image = esc_url_raw( wp_http_validate_url( $image ) );
		}

		$this->data['images'][ $name ] = $image;

		return $this;
	}

	/**
	 * Sets the logo text for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $text
	 *
	 * @return self
	 */
	public function logo_text( string $text ): self {
		$this->data['logo_text'] = wp_kses( $text, [] );

		return $this;
	}

	/**
	 * Sets the description for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $description
	 *
	 * @return self
	 */
	public function description( string $description ): self {
		$this->data['description'] = wp_kses( $description, [] );

		return $this;
	}

	/**
	 * Sets the organization name for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $organization_name
	 *
	 * @return self
	 */
	public function organization_name( string $organization_name ): self {
		$this->data['organization_name'] = wp_kses( $organization_name, [] );

		return $this;
	}

	/**
	 * Sets the serial number for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $serial_number
	 *
	 * @return self
	 */
	public function serial_number( string $serial_number ): self {
		$this->data['serial_number'] = wp_kses( $serial_number, [] );

		return $this;
	}

	/**
	 * Sets the foreground color for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string|null $foreground_color
	 *
	 * @return self
	 */
	public function foreground_color( ?string $foreground_color ): self {
		if ( empty( $foreground_color ) ) {
			return $this;
		}

		$rba = $this->hex_to_rgb( sanitize_hex_color( $foreground_color ) );

		if ( $rba ) {
			$this->data['foreground_color'] = $rba;
		}

		return $this;
	}

	/**
	 * Sets the background color for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string|null $background_color
	 *
	 * @return self
	 */
	public function background_color( ?string $background_color ): self {
		if ( empty( $background_color ) ) {
			return $this;
		}

		$rba = $this->hex_to_rgb( sanitize_hex_color( $background_color ) );

		if ( $rba ) {
			$this->data['background_color'] = $rba;
		}

		return $this;
	}

	/**
	 * Sets the relevant date for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param \DateTimeImmutable $relevant_date
	 *
	 * @return self
	 */
	public function relevant_date( \DateTimeImmutable $relevant_date ): self {
		$this->data['relevant_date'] = $relevant_date->format( static::DATE_FORMAT );

		return $this;
	}

	/**
	 * Shortcut to add a fields to the header fieldset.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $fields
	 *
	 * @return self
	 */
	public function header( array $fields ): self {
		foreach ( $fields as $field ) {
			$this->add_field( 'header', $field );
		}

		return $this;
	}

	/**
	 * Shortcut to add a fields to the primary fieldset.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $fields
	 *
	 * @return self
	 */
	public function primary( array $fields ): self {
		foreach ( $fields as $field ) {
			$this->add_field( 'primary', $field );
		}

		return $this;
	}

	/**
	 * Shortcut to add a fields to the secondary fieldset.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $fields
	 *
	 * @return self
	 */
	public function secondary( array $fields ): self {
		foreach ( $fields as $field ) {
			$this->add_field( 'secondary', $field );
		}

		return $this;
	}

	/**
	 * Shortcut to add a fields to the auxiliary fieldset.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $fields
	 *
	 * @return self
	 */
	public function auxiliary( array $fields ): self {
		foreach ( $fields as $field ) {
			$this->add_field( 'auxiliary', $field );
		}

		return $this;
	}

	/**
	 * Shortcut to add a fields to the back fieldset.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $fields
	 *
	 * @return self
	 */
	public function back( array $fields ): self {
		foreach ( $fields as $field ) {
			$this->add_field( 'back', $field );
		}

		return $this;
	}

	/**
	 * Shortcut to add fields to the barcode fieldset.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $fields
	 *
	 * @return self
	 */
	public function barcode( array $fields ): self {
		foreach ( $fields as $key => $value ) {
			$this->data['barcode'][ $key ] = $value;
		}

		return $this;
	}

	/**
	 * Adds a field to a given fieldset area, allowed fieldsets are:
	 * - header
	 * - primary
	 * - secondary
	 * - auxiliary
	 * - back
	 * - barcode
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $fieldset Which fieldset the field will be added to.
	 * @param array  $field    The field to add.
	 *
	 * @return self
	 */
	public function add_field( string $fieldset, array $field ): self {
		if ( ! in_array( $fieldset, [ 'header', 'primary', 'secondary', 'auxiliary', 'back', 'barcode' ], true ) ) {
			return $this;
		}

		if ( ! $this->validate_field( $field ) ) {
			return $this;
		}

		$field = $this->sanitize_field( $field );

		$this->data[ $fieldset ][] = $field;

		return $this;
	}

	/**
	 * Validates the field to ensure it has the required keys.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $field
	 *
	 * @return bool
	 */
	protected function validate_field( array $field ): bool {
		$required_keys = [ 'key', 'label', 'value' ];

		foreach ( $required_keys as $required_key ) {
			if ( ! array_key_exists( $required_key, $field ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Sanitizes the field to only include the allowed keys.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	protected function sanitize_field( array $field ): array {
		$allowed_keys = [
			'key',
			'label',
			'value',
			'attributedValue',
			'changeMessage',
			'currencyCode',
			'dataDetectorTypes',
			'dateStyle',
			'ignoresTimeZone',
			'isRelative',
			'numberStyle',
			'textAlignment',
			'timeStyle',
			'format',
			'message',
			'messageEncoding',
		];

		$field = array_filter( $field, static function ( $key ) use ( $allowed_keys ) {
			return in_array( $key, $allowed_keys, true );
		}, ARRAY_FILTER_USE_KEY );

		if ( isset( $field['isRelative'] ) ) {
			$field['isRelative'] = (int) $field['isRelative'];
		}

		return $field;
	}

	/**
	 * Convert HEX to rgb last minute for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param string $color
	 *
	 * @return string|null
	 */
	protected function hex_to_rgb( string $color ): ?string {
		$color_data = \Tribe__Utils__Color::hexToRgb( $color );
		if ( ! is_array( $color_data ) ) {
			return null;
		}

		return 'rgb( ' . implode( ', ', $color_data ) . ' )';
	}

	/**
	 * Returns the hash of the current data for this pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	protected function get_data_hash(): string {
		return md5( wp_json_encode( $this->data ) );
	}

	/**
	 * Validates the data for the pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return $this
	 */
	public function validate(): self {
		// Always reset the errors before starting a new validation.
		$this->error = new \WP_Error();

		$data = $this->data;

		if ( ! Arr::get( $data, [ 'images', 'icon' ] ) ) {
			$this->error->add( 'tec_tickets_wallet_plus_passes_apple_wallet_pass_package_missing_icon', __( 'The pass is missing the icon image.', 'event-tickets-plus' ), [ 'pass' => $this ] );
		}

		if ( ! Arr::get( $data, [ 'logo_text' ] ) && ! Arr::get( $data, [ 'images', 'icon' ] ) ) {
			$this->error->add( 'tec_tickets_wallet_plus_passes_apple_wallet_pass_package_missing_logo_text', __( 'The pass is missing the logo text while there is no image.', 'event-tickets-plus' ), [ 'pass' => $this ] );
		}

		if ( ! Arr::get( $data, [ 'description' ] ) ) {
			$this->error->add( 'tec_tickets_wallet_plus_passes_apple_wallet_pass_package_missing_description', __( 'The pass is missing the description.', 'event-tickets-plus' ), [ 'pass' => $this ] );
		}

		if ( ! Arr::get( $data, [ 'serial_number' ] ) ) {
			$this->error->add( 'tec_tickets_wallet_plus_passes_apple_wallet_pass_package_missing_serial_number', __( 'The pass is missing the serial number.', 'event-tickets-plus' ), [ 'pass' => $this ] );
		}

		// If there were no errors we save the hash of the data as the validated one.
		if ( ! $this->error->has_errors() ) {
			$this->validated_hash = $this->get_data_hash();
		}

		return $this;
	}

	/**
	 * Determine if the current pass is valid, if data has changed since the last validation it will return false.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->validated_hash === $this->get_data_hash() && ! $this->error->has_errors();
	}

	/**
	 * Returns the error instance with all the problems that happened during the last validation.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return WP_Error
	 */
	public function get_error(): WP_Error {
		return $this->error;
	}

	/**
	 * Returns the pass as a JSON string.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string
	 */
	public function as_json(): string {
		if ( ! $this->is_valid() ) {
			throw new \BadMethodCallException( __( 'The pass is not valid, please validate it before trying to get the JSON.', 'event-tickets-plus' ) );
		}

		return wp_json_encode( $this->data );
	}

	/**
	 * Returns the pass as an array.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array
	 */
	public function as_array(): array {
		if ( ! $this->is_valid() ) {
			throw new \BadMethodCallException( __( 'The pass is not valid, please validate it before trying to get the data as Array.', 'event-tickets-plus' ) );
		}

		return $this->data;
	}
}
