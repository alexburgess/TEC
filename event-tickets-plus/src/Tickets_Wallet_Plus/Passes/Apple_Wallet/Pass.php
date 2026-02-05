<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet;

use TEC\Tickets\QR\Connector;
use TEC\Tickets\QR\Settings as QR_Settings;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Pass_Color_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Pass_Logo_Image_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Text_Color_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet\Settings\Qr_Codes_Setting;
use TEC\Tickets_Wallet_Plus\Plugin;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Pass_Abstract;
use TEC\Tickets\Commerce\Utils\Value;

use WP_Error;

/**
 * Class Pass
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Passes\Apple_Wallet
 */
class Pass extends Pass_Abstract {

	/**
	 * Get the data set related to this pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array
	 */
	protected function get_data(): array {
		$data = [
			'description'      => '',
			'logo_text'        => get_bloginfo( 'name' ), // This will be removed when the Logo Attachment is added.
			'serial_number'    => md5( wp_generate_password() ),
			'foreground_color' => tribe( Text_Color_Setting::class )->get_value(),
			'background_color' => tribe( Pass_Color_Setting::class )->get_value(),
			'images'           => [],
			'header'           => [],
			'primary'          => [],
			'auxiliary'        => [],
			'back'             => [],
		];

		$icon_attachment = tribe( Pass_Logo_Image_Setting::class );

		if ( $icon_attachment->attachment_exists() ) {
			$data['images']['icon'] = $icon_attachment->get_attachment_path();

			// When the icon/logo is added we remove the text.
			unset( $data['logo_text'] );
		}

		if ( $this->attendee_exists() ) {
			$attendee_id = $this->get_attendee_id();
			$attendee    = $this->get_attendee();

			if ( ! empty( $attendee['ticket_name'] ) ) {
				$data['description'] = $attendee['ticket_name'];
			}

			if ( ! empty( $attendee['sku'] ) ) {
				$data['serial_number'] = $attendee['sku'];
			}

			$attendee_name = empty( $attendee['holder_name'] ) ? $attendee['purchaser_name'] : $attendee['holder_name'];

			$data['secondary'][] = [
				'key'   => 'ticket_holder',
				'label' => esc_html__( 'Attendee', 'event-tickets-plus' ),
				'value' => wp_specialchars_decode( esc_html( $attendee_name ), ENT_QUOTES ),
			];

			if ( ! empty( $attendee['ticket_name'] ) ) {
				$data['auxiliary'][] = [
					'key'   => 'ticket_title',
					'label' => esc_html__( 'Ticket Title', 'event-tickets-plus' ),
					'value' => wp_specialchars_decode( esc_html( $attendee['ticket_name'] ), ENT_QUOTES ),
				];
			}
			$qr_data = tribe( Connector::class )->get_checkin_url(
				$attendee_id,
				$this->get_event_id(),
				$attendee['security_code']
			);

			if (
				! empty( $qr_data )
				&& ! empty( tribe( Qr_Codes_Setting::class )->get_value() )
				&& ! empty( tribe( QR_Settings::class )->is_enabled() )
			) {
				$data['barcode'] = [
					'format'          => 'PKBarcodeFormatQR',
					'message'         => $qr_data,
					'messageEncoding' => 'iso-8859-1',
				];
			}

			$data['back'][] = [
				'key'   => 'ticket_id',
				'label' => 'Ticket ID',
				'value' => $attendee['ticket_id'],
			];
			$data['back'][] = [
				'key'   => 'ticket_title',
				'label' => esc_html__( 'Ticket Title', 'event-tickets-plus' ),
				'value' => wp_specialchars_decode( esc_html( $attendee['ticket_name'] ), ENT_QUOTES ),
			];
			$data['back'][] = [
				'key'   => 'order_date',
				'label' => 'Order Date',
				'value' => \Tribe__Date_Utils::reformat( $attendee['post_modified'], \Tribe__Date_Utils::DATEONLYFORMAT ),
			];
			$data['back'][] = [
				'key'   => 'order_status',
				'label' => 'Order Status',
				'value' => $attendee['order_status'],
			];
			$data['back'][] = [
				'key'   => 'order_total',
				'label' => 'Order Total',
				'value' => html_entity_decode( Value::create( $attendee['price_paid'] )->get_currency() ),
			];
			$data['back'][] = [
				'key'   => 'attendee_email',
				'label' => esc_html__( 'Attendee Email', 'event-tickets-plus' ),
				'value' => ( isset( $attendee['iac'] ) && 'none' !== $attendee['iac'] ) ? $attendee['holder_email'] : $attendee['purchaser_email'],
			];

		}

		/**
		 * Filter the apple pass data.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $data The Apple Pass data.
		 * @param array $pass The pass instance.
		 */
		$data = apply_filters( 'tec_tickets_wallet_plus_apple_pass_data', $data, $this );

		if ( ! empty( $attendee_id ) ) {
			/**
			 * Filter the apple pass data per attendee ID.
			 *
			 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
			 *
			 * @param array<string,mixed> $data The Apple Pass data.
			 * @param Pass $pass The pass instance.
			 */
			$data = apply_filters( "tec_tickets_wallet_plus_apple_pass_data_{$attendee_id}", $data, $this );
		}

		return $data;
	}

	/**
	 * Create the Apple Wallet Pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function create() {
		// @todo On the first MR related to Wallet+ we need to add validation of data here.

		$pass = Package::from_array( $this->get_data() );

		$pass_file_bits = tribe( Client::class )->get_pass_package( $pass, (bool) tribe_get_request_var( 'json' ) );

		$this->send_response( $pass_file_bits );
	}

	/**
	 * Sends the Apple Wallet Pass as an HTTP response.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param mixed $pass_file_bits The pass file bits to send.
	 *
	 * @return void
	 */
	protected function send_response( $pass_file_bits ): void {
		// Check for errors.
		if ( is_wp_error( $pass_file_bits ) ) {
			wp_send_json( $pass_file_bits, 500 );
			exit;
		}

		// Allows a debugging mode.
		if ( is_array( $pass_file_bits ) ) {
			wp_send_json( $pass_file_bits, 200 );
			exit;
		}

		// Existing logic to send response.
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/vnd.apple.pkpass' );
		header( 'Content-Disposition: attachment; filename="' . $this->get_filename() . '.pkpass"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Connection: Keep-Alive' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s T' ) );
		header( 'Pragma: public' );
		echo $pass_file_bits;
		exit;
	}

	/**
	 * Get URL for the Apple Wallet Passes.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return ?string
	 */
	public function get_url(): ?string {
		if ( ! $this->attendee_exists() ) {
			return null;
		}

		$attendee = $this->get_attendee();

		if ( empty( $attendee ) ) {
			return null;
		}

		if ( empty( $attendee['security_code'] ) ) {
			return null;
		}

		$url = add_query_arg(
			[
				'apple-wallet-pass' => 1,
				'attendee_id'       => $this->get_attendee_id(),
				'security_code'     => $attendee['security_code'],
			],
			site_url()
		);

		return $url;
	}

	/**
	 * Get pass File name for the Apple Wallet Passes.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The Apple Wallet pass filename.
	 */
	public function get_filename(): string {
		$hash = md5( wp_generate_password() . '-' . $this->get_attendee_id() );
		$hash = substr( $hash, 0, 10 );

		return Plugin::SLUG . '-' . $hash;
	}

	/**
	 * Get pass `Add to Apple Wallet` image URL.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param ?string $locale The locale for which you want to get the image URL.
	 * @param bool $force_png Whether the image should be PNG.
	 *
	 * @return string The `Add to Apple Wallet` image url.
	 */
	public function get_button_image_url( ?string $locale = null, bool $force_png = false ): string {
		if ( empty( $locale ) ) {
			$locale = get_locale();
		}

		$plugin = tribe( Plugin::class );

		$file_extension = '.svg';
		if ( $force_png ) {
			$locale = null;
			$file_extension = '.png';
		}

		$localized_image = $plugin->plugin_path . "src/resources/images/tickets-wallet-plus/add-to-apple-wallet/{$locale}{$file_extension}";

		if ( file_exists( $localized_image ) ) {
			return $plugin->plugin_url . "src/resources/images/tickets-wallet-plus/add-to-apple-wallet/{$locale}{$file_extension}";
		}

		return $plugin->plugin_url . "src/resources/images/tickets-wallet-plus/add-to-apple-wallet{$file_extension}";
	}

	/**
	 * Sorts a specific section of the ticket data array by a custom order of keys.
	 *
	 * This function is designed to sort sections of a ticket data array, such as 'secondary',
	 * based on a predefined custom order. It modifies the specified section to reorder its
	 * elements according to the sequence of keys provided in the custom order array. If the
	 * section does not exist in the data array or if the custom order does not match any
	 * items in the section, the original data array is returned unmodified.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array  $data The complete ticket data array.
	 * @param string $section The specific section of the data array to sort (e.g., 'secondary').
	 * @param array  $custom_order An array of keys defining the desired order of items within the section.
	 *
	 * @return array The modified data array with the specified section sorted according to the custom order.
	 */
	public function ticket_data_sorter( array $data, string $section, array $custom_order ): array {
		if ( ! isset( $data[ $section ] ) ) {
			return $data;
		}

		$sorted_section = [];

		// Loop through the custom order.
		foreach ( $custom_order as $order_key ) {
			// Loop through the section to find matching items.
			foreach ( $data[ $section ] as $item ) {
				// If the item's key matches the current order key, add it to the sorted array.
				if ( isset( $item['key'] ) && $item['key'] === $order_key ) {
					$sorted_section[] = $item;
				}
			}
		}

		// Replace the original section with the sorted section.
		$data[ $section ] = $sorted_section;

		return $data;
	}

	/**
	 * Replaces the label for a specified key within a given section of the data array.
	 *
	 * This method searches for a specific key within a subsection of the provided data array.
	 * If the key is found, its associated label is updated to the specified replacement value.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @param array  $data The data array to search within.
	 * @param string $section The section of the data array to search.
	 * @param string $needle The key to search for within the specified section.
	 * @param string $replacement The new label to apply to the found key.
	 *
	 * @return array The updated data array.
	 */
	public function replace_label_by_key( array $data, string $section, string $needle, string $replacement ): array {
		// Check if the specified section exists in the data array.
		if ( ! isset( $data[ $section ] ) ) {
			return $data; // Return the original data if the section is not found.
		}

		// Retrieve the keys from the specified section.
		$keys = array_column( $data[ $section ], 'key' );

		// Search for the needle in the keys array.
		$index = array_search( $needle, $keys );

		// If the needle exists, update its associated label.
		if ( false !== $index ) {
			$data[ $section ][ $index ]['label'] = $replacement;
		}

		return $data;
	}
}
