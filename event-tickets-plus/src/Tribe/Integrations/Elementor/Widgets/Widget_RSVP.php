<?php
/**
 * Event Tickets Elementor Widget for RSVP.
 *
 * @since 5.4.4
 *
 * @package Tribe\Tickets\Plus\Integrations\Elementor\Widgets
 */

namespace Tribe\Tickets\Plus\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Tribe\Tickets\Plus\Shortcode\Tribe_Tickets_Rsvp;

class Widget_RSVP extends Widget_Abstract {

	/**
	 * {@inheritdoc}
	 */
	protected static $widget_slug = 'tec-tickets-rsvp';

	/**
	 * {@inheritdoc}
	 */
	protected $widget_icon = 'fas fa-envelope-open-text';

	/**
	 * @var string
	 */
	protected $shortcode;

	/**
	 * {@inheritdoc}
	 */
	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );

		$this->widget_title = __( 'Event RSVP', 'event-tickets-plus' );
		$this->shortcode    = tribe( Tribe_Tickets_Rsvp::class )->get_registration_slug();
	}

	/**
	 * Render widget output.
	 *
	 * @since 5.4.4
	 */
	protected function render() {
		$settings   = $this->get_settings_for_display();
		$attributes = $this->get_shortcode_attribute_string( $settings, [ 'post_id', 'ticket_id'] );

		echo do_shortcode( "[{$this->shortcode} {$attributes}]" );
	}

	/**
	 * Register widget controls.
	 *
	 * @since 5.4.4
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Settings', 'event-tickets-plus' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'post_id_type',
			[
				'type'    => Controls_Manager::SELECT,
				'label'   => esc_html__( 'Select Post ID', 'event-tickets-plus' ),
				'options' => [
					'current' => esc_html__( 'Use the Current ID', 'event-tickets-plus' ),
					'manual'  => esc_html__( 'Manually enter ID', 'event-tickets-plus' ),
				],
				'default' => 'current',
			]
		);

		$this->add_control(
			'post_id',
			[
				'label' => esc_html__( 'Post ID', 'event-tickets-plus' ),
				'type'  => Controls_Manager::TEXT,
				'condition' => [
					'post_id_type' => 'manual',
				],
			]
		);

		$this->add_control(
			'ticket_id_type',
			[
				'type'    => Controls_Manager::SELECT,
				'label'   => esc_html__( 'RSVP', 'event-tickets-plus' ),
				'options' => [
					'all'       => esc_html__( 'Show All', 'event-tickets-plus' ),
					'selected'  => esc_html__( 'Select Manually', 'event-tickets-plus' ),
				],
				'default' => 'all',
			]
		);

		$this->add_control(
			'ticket_id',
			[
				'label' => esc_html__( 'RSVP IDs', 'event-tickets-plus' ),
				'type'  => Controls_Manager::TEXT,
				'condition' => [
					'ticket_id_type' => 'selected',
				],
				'placeholder' => esc_html__( 'ID-1, ID-2, ID-3', 'event-tickets-plus' ),
			]
		);

		$this->end_controls_section();
	}
}
