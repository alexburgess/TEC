<?php

namespace TEC\Tickets_Wallet_Plus\Passes\Pdf;

use \TCPDF;
use TEC\Tickets\QR\Connector;
use TEC\Tickets\QR\Settings as QR_Settings;
use TEC\Tickets_Wallet_Plus\Contracts\Passes\Pass_Abstract;
use TEC\Tickets_Wallet_Plus\Contracts\Traits\Generic_Template;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings\Additional_Content_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings\Header_Color_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings\Header_Image_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings\Image_Alignment_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings\Qr_Codes_Setting;
use TEC\Tickets_Wallet_Plus\Passes\Pdf\Settings\Include_Credit_Setting;
use TEC\Tickets_Wallet_Plus\Plugin;

/**
 * Class Pass
 *
 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
 *
 * @package \TEC\Tickets_Wallet_Plus\Passes\Pdf
 */
class Pass extends Pass_Abstract {
	use Generic_Template;

	/**
	 * Stores the template folder that will be used by the Generic Template Trait.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @var string The template folder.
	 */
	protected string $template_folder = 'src/views/tickets-wallet-plus/pdf';

	/**
	 * Get the template variables.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return array The template variables.
	 */
	public function get_template_variables(): array {
		$pdf_settings = [
			'header_image_url'       => tribe( Header_Image_Setting::class )->get_attachment_url(),
			'header_image_alignment' => tribe( Image_Alignment_Setting::class )->get_value(),
			'header_bg_color'        => tribe( Header_Color_Setting::class )->get_value(),
			'additional_info'        => wpautop( tribe( Additional_Content_Setting::class )->get_value() ),
			'qr_enabled'             => tribe( QR_Settings::class )->is_enabled() && tribe( Qr_Codes_Setting::class )->get_value(),
			'include_credit'         => tribe( Include_Credit_Setting::class )->get_value(),
		];

		$attendee = $this->get_attendee();

		$template_vars = array_merge(
			$pdf_settings,
			[
				'attendee'       => $attendee,
				'post'           => get_post( $attendee['event_id'] ),
				'post_image_url' => wp_get_attachment_url( get_post_thumbnail_id( $attendee['event_id'], 'large' ) ),
			]
		);

		$qr_image = tribe( Connector::class )->get_image_url_from_ticket_data( $attendee );

		if ( ! empty( $qr_image ) ) {
			$template_vars['qr_image_url'] = $qr_image;
		}

		/**
		 * Filter the template vars for the PDF pass.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param array $template_vars The template vars.
		 *
		 * @return array The modified template vars.
		 */
		return apply_filters( 'tec_tickets_wallet_plus_pdf_pass_template_vars', $template_vars );
	}

	/**
	 * Get the HTML for the PDF pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The HTML for the PDF pass.
	 */
	public function get_html(): string {
		$template_vars = $this->get_template_variables();
		return $this->get_template()->template( 'pass', $template_vars, false );
	}

	/**
	 * Get the PDF object.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return TCPDF The PDF object.
	 */
	public function get_pdf_object(): TCPDF {
		$pdf = new TCPDF( 'P', 'pt', 'LETTER', true, 'UTF-8', false );
		$pdf->setMargins( 14, 20 );

		// Remove default header/footer.
		$pdf->setPrintHeader( false );
		$pdf->setPrintFooter( false );
		$pdf->SetAutoPageBreak( false );
		$pdf->AddPage();

		/**
		 * Filter the TCPDF object for the PDF pass.
		 *
		 * @link https://tcpdf.org/docs/srcdoc/TCPDF/classes-TCPDF/
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param TCPDF $pdf The PDF object.
		 *
		 * @return TCPDF The modified PDF object.
		 */
		return apply_filters( 'tec_tickets_wallet_plus_pdf_pass_get_pdf_object', $pdf );
	}

	/**
	 * Generate the PDF pass.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return void
	 */
	public function create() {
		$pdf_html = $this->get_html();
		$pdf      = $this->get_pdf_object();
		$pdf->writeHTML( $pdf_html, true, false, true, true, '' );
		$pdf->Output( $this->get_filename() . '.pdf' );
	}

	/**
	 * Generate the PDF contents.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The PDF contents.
	 */
	public function get_contents() {
		$pdf_html = $this->get_html();
		$pdf      = $this->get_pdf_object();
		$pdf->writeHTML( $pdf_html, true, false, true, true, '' );
		$pdf_content = $pdf->Output( $this->get_filename() . '.pdf', 'S' );
		return $pdf_content;
	}

	/**
	 * Get PDF pass URL.
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
				'tec-tickets-wallet-plus-pdf' => 1,
				'attendee_id'                 => $this->get_attendee_id(),
				'security_code'               => $attendee['security_code'],
			],
			site_url()
		);

		return $url;
	}

	/**
	 * Get PDF Pass File name.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string The PDF pass filename.
	 */
	public function get_filename(): ?string {
		$filename = sprintf(
			'%1$s-%2$s',
			tribe_get_ticket_label_singular_lowercase( 'tickets_wallet_plus_pdf_filename' ),
			$this->get_attendee_id()
		);

		/**
		 * Filter the PDF pass filename.
		 *
		 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
		 *
		 * @param string $filename The PDF pass filename.
		 * @param int    $attendee_id The attendee ID.
		 *
		 * @return string The modified PDF pass filename.
		 */
		$filename = apply_filters( 'tec_tickets_wallet_plus_pdf_pass_filename', $filename, $this->get_attendee_id() );
		return sanitize_title( $filename );
	}

	/**
	 * Get the PDF pass file path.
	 *
	 * @since 6.0.0 Migrated to Event Tickets Plus from Wallet Plus
	 *
	 * @return string|false The file path string or false if error.
	 */
	public function get_file_path(): ?string {
		// If filename is empty, return false.
		$filename = $this->get_filename();
		if ( empty( $filename ) ) {
			return false;
		}

		$contents = $this->get_contents();
		$file     = tempnam( sys_get_temp_dir(), $filename );

		if ( false === $file ) {
			/** @var \Tribe__Log $logger */
			$logger = tribe( 'logger' );
			$logger->log_error(
				sprintf(
					// Translators: %s is the attendee ID.
					__( "Couldn't generate PDF ticket file. Attendee ID: %s", 'event-tickets-plus' ),
					$this->attendee_id
				),
				'Event Tickets Wallet Plus - PDF Ticket Email Attachment'
			);
			return false;
		}

		$filename = $file . '.pdf';
		file_put_contents( $filename, $contents );
		unlink( $file );

		return $filename;
	}
}
