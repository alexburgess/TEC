<?php
/**
 * Base Widget.
 *
 * @since 5.4.4
 *
 * @package Tribe\Tickets\Plus\Integrations\Elementor\Widgets
 */

namespace Tribe\Tickets\Plus\Integrations\Elementor\Widgets;

use Elementor\Widget_Base;

abstract class Widget_Abstract extends Widget_Base {

	/**
	 * Widget slug prefix.
	 *
	 * @since 5.4.4
	 *
	 * @var string
	 */
	protected static $widget_slug_prefix = 'tec_et_elementor_widget_';

	/**
	 * Widget slug (name).
	 *
	 * @since 5.4.4
	 *
	 * @var string
	 */
	protected static $widget_slug;

	/**
	 * Widget title.
	 *
	 * @since 5.4.4
	 *
	 * @var string
	 */
	protected $widget_title;

	/**
	 * Widget icon.
	 *
	 * @since 5.4.4
	 *
	 * @var string
	 */
	protected $widget_icon;

	/**
	 * Widget categories.
	 *
	 * @since 5.4.4
	 *
	 * @var array<string>
	 */
	protected $widget_categories = [ 'event-tickets' ];

	/**
	 * {@inheritdoc}
	 */
	public function __construct( $data = [], $args = null ) {
		parent::__construct( $data, $args );
	}

	/**
	 * Gets the name (aka slug) of the widget.
	 *
	 * @since 5.4.4
	 *
	 * @return string
	 */
	public function get_name() {
		return static::get_elementor_slug();
	}

	/**
	 * Get elementor widget slug.
	 *
	 * @since 5.4.4
	 *
	 * @return string
	 */
	public static function get_elementor_slug() {
		return static::$widget_slug_prefix . static::get_slug();
	}

	/**
	 * Get local widget slug.
	 *
	 * @since 5.4.4
	 *
	 * @return string
	 */
	public static function get_slug() {
		return static::$widget_slug;
	}

	/**
	 * Gets the title of the widget.
	 *
	 * @since 5.4.4
	 *
	 * @return string
	 */
	public function get_title() {
		return $this->widget_title;
	}

	/**
	 * Gets the icon for the widget.
	 *
	 * @since 5.4.4
	 *
	 * @return string
	 */
	public function get_icon() {
		return $this->widget_icon;
	}

	/**
	 * Gets the categories of the widget.
	 *
	 * @since 5.4.4
	 *
	 * @return array<string>
	 */
	public function get_categories() {
		return $this->widget_categories;
	}

	/**
	 * Convert settings to a set of shortcode attributes.
	 *
	 * @since 5.4.4
	 *
	 * @param array $settings Elementor widget settings.
	 * @param array<string> $allowed Allowed settings for shortcode.
	 *
	 * @return string
	 */
	protected function get_shortcode_attribute_string( $settings, $allowed = [] ) {
		$settings_string = '';

		$allowed = array_flip( $allowed );

		foreach ( $settings as $key => $value ) {
			if ( ! empty( $allowed ) && ! isset( $allowed[ $key ] ) ) {
				continue;
			}

			$key = esc_attr( $key );

			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			$value = esc_attr( $value );

			$settings_string .= " {$key}=\"{$value}\"";
		}

		return $settings_string;
	}

	/**
	 * Gets settings while removing the prefix from keys.
	 *
	 * @since 5.4.4
	 * @since 5.7.4   Only add `post_id` if `$settings` is an array.
	 *
	 * @param null $setting_key
	 *
	 * @return array
	 */
	public function get_settings_for_display( $setting_key = null ) {
		$settings = parent::get_settings_for_display( $setting_key );

		if ( ! is_array( $settings ) ) {
			return $settings;
		}

		$settings['post_id'] = isset( $settings['post_id'] ) ? absint( $settings['post_id'] ) : get_post()->ID;

		return $settings;
	}
}
