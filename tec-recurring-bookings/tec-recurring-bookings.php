<?php
/**
 * Plugin Name: TicketPup
 * Description: Create recurring events and tickets for The Events Calendar.
 * Version: 1.0
 * Author: Alex Burgess
 * Author URI: https://thisisa.intentionallyblank.page
 * Requires at least: 6.9
 */

if (!defined('ABSPATH')) {
    exit;
}

function tec_rb_get_build_timezone() {
    $timezone = function_exists('wp_timezone') ? wp_timezone() : null;
    if ($timezone instanceof DateTimeZone) {
        $name = $timezone->getName();
        if (!empty($name) && $name !== 'UTC') {
            return $timezone;
        }
    }
    $tz_string = get_option('timezone_string');
    if (!empty($tz_string) && $tz_string !== 'UTC') {
        try {
            return new DateTimeZone($tz_string);
        } catch (Exception $e) {
            // fall through
        }
    }
    $offset = get_option('gmt_offset', 0);
    if (is_numeric($offset) && (float) $offset !== 0.0) {
        $hours = (int) $offset;
        $minutes = (int) round(abs($offset - $hours) * 60);
        $sign = $offset >= 0 ? '+' : '-';
        $tz_name = sprintf('%s%02d:%02d', $sign, abs($hours), $minutes);
        try {
            return new DateTimeZone($tz_name);
        } catch (Exception $e) {
            // fall through
        }
    }
    return new DateTimeZone('America/New_York');
}

function tec_rb_get_build_info() {
    $timestamp = file_exists(__FILE__) ? filemtime(__FILE__) : time();
    $timezone = tec_rb_get_build_timezone();
    if (function_exists('wp_date')) {
        return array(
            'build' => wp_date('YmdHis', $timestamp, $timezone),
            'built_at' => wp_date('Y-m-d H:i:s T', $timestamp, $timezone),
        );
    }
    $dt = new DateTime('@' . $timestamp);
    $dt->setTimezone($timezone);
    return array(
        'build' => $dt->format('YmdHis'),
        'built_at' => $dt->format('Y-m-d H:i:s T'),
    );
}

function tec_rb_render_build_footer() {
    $info = tec_rb_get_build_info();
    $build = esc_html($info['build']);
    $built_at = esc_html($info['built_at']);
    return '<div class="tec-build tec-build--footer">Build ' . $build . ' · Built ' . $built_at . '</div>';
}

function tec_rb_render_topbar($title, $show_presets = false, $show_build = false) {
    $build_info = $show_build ? tec_rb_get_build_info() : null;
    ob_start();
    ?>
    <div class="tec-topbar">
      <div class="tec-topbar-inner">
        <div class="tec-topbar-left">
          <span class="tec-logo" aria-hidden="true" style="width:36px;height:36px;display:inline-flex;overflow:hidden;color:#000;">
            <?php echo tec_rb_get_header_logo_svg(); ?>
          </span>
          <h1 class="tec-title"><?php echo esc_html($title); ?></h1>
        </div>
        <?php if ($show_presets || $show_build) : ?>
          <div class="tec-header-actions">
            <?php if ($show_presets) : ?>
              <div class="tec-control tec-control--select">
                <select class="tec-select" data-preset-select>
                  <option value="">Select preset</option>
                </select>
              </div>
              <button class="tec-button-secondary" type="button" data-save-preset>Save as preset</button>
            <?php endif; ?>
            <?php if ($show_build && $build_info) : ?>
              <div class="tec-build tec-build--topbar">Build <?php echo esc_html($build_info['build']); ?> · <?php echo esc_html($build_info['built_at']); ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

function tec_rb_enqueue_assets() {
    $base_url = plugin_dir_url(__FILE__);
    $css_path = __DIR__ . '/assets/css/tec-recurring-bookings.css';
    $js_path = __DIR__ . '/assets/js/tec-recurring-bookings.js';
    $css_ver = file_exists($css_path) ? filemtime($css_path) : '1.0';
    $js_ver = file_exists($js_path) ? filemtime($js_path) : '1.0';

    wp_enqueue_style(
        'tec-recurring-bookings',
        $base_url . 'assets/css/tec-recurring-bookings.css',
        array(),
        $css_ver
    );

    wp_enqueue_script('jquery-ui-datepicker');

    if (function_exists('wp_enqueue_media')) {
        wp_enqueue_media();
    }

    wp_enqueue_script(
        'tec-recurring-bookings',
        $base_url . 'assets/js/tec-recurring-bookings.js',
        array('jquery', 'jquery-ui-datepicker'),
        $js_ver,
        true
    );

    wp_localize_script(
        'tec-recurring-bookings',
        'tecRecurringBookingsConfig',
        array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'adminUrl' => admin_url('/'),
            'siteUrl' => home_url('/'),
            'eventsManagerUrl' => admin_url('edit.php?post_type=tribe_events&page=tribe-admin-manager'),
            'nonce' => wp_create_nonce('tec_rb_ajax'),
            'presets' => tec_rb_get_presets(),
            'defaults' => tec_rb_get_default_options(),
            'ticketNameSuggestions' => tec_rb_get_ticket_name_suggestions(),
            'attendeeQuestions' => tec_rb_get_attendee_questions(),
            'attendeeQuestionPresets' => tec_rb_get_attendee_question_presets(),
        )
    );
}

function tec_rb_get_menu_icon() {
    $candidates = array(
        __DIR__ . '/assets/images/logo.svg',
    );
    $icon_path = '';
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $icon_path = $candidate;
            break;
        }
    }
    if ($icon_path === '') {
        return 'dashicons-calendar-alt';
    }
    $svg = file_get_contents($icon_path);
    if ($svg === false) {
        return 'dashicons-calendar-alt';
    }
    $svg = trim($svg);
    if ($svg === '') {
        return 'dashicons-calendar-alt';
    }
    $encoded = base64_encode($svg);
    return 'data:image/svg+xml;base64,' . $encoded;
}

function tec_rb_get_header_logo_svg() {
    $candidates = array(
        __DIR__ . '/assets/images/logo.svg',
        __DIR__ . '/assets/images/icon.svg',
        __DIR__ . '/assets/images/icon.sv',
    );
    $logo_path = '';
    foreach ($candidates as $candidate) {
        if (file_exists($candidate)) {
            $logo_path = $candidate;
            break;
        }
    }
    if ($logo_path === '') {
        return '';
    }
    $svg = file_get_contents($logo_path);
    if ($svg === false) {
        return '';
    }
    $svg = trim($svg);
    if ($svg === '') {
        return '';
    }
    $svg = preg_replace('/fill:\s*#[0-9a-fA-F]{3,6}\s*;?/i', 'fill: currentColor;', $svg);
    if (preg_match('/<svg\b[^>]*>/i', $svg, $matches)) {
        $tag = $matches[0];
        $new_tag = $tag;
        if (!preg_match('/\bwidth=/i', $tag)) {
            $new_tag = rtrim($new_tag, '>') . ' width="36"';
        }
        if (!preg_match('/\bheight=/i', $tag)) {
            $new_tag = rtrim($new_tag, '>') . ' height="36"';
        }
        if (!preg_match('/\bstyle=/i', $tag)) {
            $new_tag = rtrim($new_tag, '>') . ' style="display:block;width:36px;height:36px;"';
        }
        $new_tag = rtrim($new_tag, '>') . '>';
        $svg = preg_replace('/<svg\b[^>]*>/i', $new_tag, $svg, 1);
    }
    return $svg;
}

function tec_rb_parse_list($value, $fallback = array()) {
    $lines = is_array($value) ? $value : preg_split('/\r\n|\r|\n/', (string) $value);
    $lines = array_map('trim', $lines);
    $lines = array_values(array_filter($lines, function ($line) {
        return $line !== '';
    }));

    return empty($lines) ? $fallback : $lines;
}

function tec_rb_get_option_list($key, $fallback = array()) {
    $value = get_option($key, '');
    return tec_rb_parse_list($value, $fallback);
}

function tec_rb_get_ticket_name_suggestions() {
    return tec_rb_get_option_list('tec_rb_ticket_name_suggestions', array());
}

function tec_rb_get_attendee_questions() {
    $questions = get_option('tec_rb_attendee_questions', array());
    if (is_string($questions)) {
        $decoded = json_decode($questions, true);
        if (is_array($decoded)) {
            $questions = $decoded;
        }
    }
    return is_array($questions) ? $questions : array();
}

function tec_rb_get_attendee_question_presets() {
    $presets = get_option('tec_rb_attendee_question_presets', array());
    if (is_string($presets)) {
        $decoded = json_decode($presets, true);
        if (is_array($decoded)) {
            $presets = $decoded;
        }
    }
    return is_array($presets) ? $presets : array();
}

function tec_rb_get_venues_list() {
    $venues = get_posts(array(
        'post_type' => 'tribe_venue',
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    return array_map(function ($venue) {
        return array(
            'id' => (int) $venue->ID,
            'name' => $venue->post_title,
        );
    }, $venues);
}

function tec_rb_get_organizers_list() {
    $organizers = get_posts(array(
        'post_type' => 'tribe_organizer',
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    return array_map(function ($organizer) {
        return array(
            'id' => (int) $organizer->ID,
            'name' => $organizer->post_title,
        );
    }, $organizers);
}

function tec_rb_get_categories_list() {
    $taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ));
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }
    return array_map(function ($term) {
        return array(
            'id' => (int) $term->term_id,
            'name' => $term->name,
        );
    }, $terms);
}

function tec_rb_get_tag_taxonomy() {
    if (class_exists('Tribe__Events__Main') && defined('Tribe__Events__Main::TAG_TAXONOMY')) {
        return Tribe__Events__Main::TAG_TAXONOMY;
    }

    return 'post_tag';
}

function tec_rb_normalize_tags($event_tags) {
    $tags = array();
    if (is_array($event_tags)) {
        $tags = $event_tags;
    } elseif (is_string($event_tags)) {
        $tags = explode(',', $event_tags);
    }
    $tags = array_filter($tags, 'is_scalar');
    $tags = array_map('strval', $tags);
    $tags = array_map('trim', $tags);
    $tags = array_filter($tags, 'strlen');
    $tags = array_map('sanitize_text_field', $tags);
    return array_values(array_unique($tags));
}

function tec_rb_resolve_tag_ids($tags, $taxonomy) {
    $ids = array();
    foreach ($tags as $tag) {
        if ($tag === '') {
            continue;
        }
        $existing = term_exists($tag, $taxonomy);
        if (is_array($existing) && !empty($existing['term_id'])) {
            $ids[] = (int) $existing['term_id'];
            continue;
        }
        if (is_int($existing)) {
            $ids[] = $existing;
            continue;
        }
        $created = wp_insert_term($tag, $taxonomy);
        if (!is_wp_error($created) && !empty($created['term_id'])) {
            $ids[] = (int) $created['term_id'];
        }
    }
    return array_values(array_unique(array_filter($ids)));
}

function tec_rb_assign_event_tags($event_id, $event_tags, &$errors = null) {
    if (empty($event_id) || empty($event_tags)) {
        return;
    }
    $tags = tec_rb_normalize_tags($event_tags);
    if (empty($tags)) {
        return;
    }
    $tag_taxonomy = tec_rb_get_tag_taxonomy();
    if (!taxonomy_exists($tag_taxonomy)) {
        return;
    }
    if (!is_object_in_taxonomy('tribe_events', $tag_taxonomy)) {
        register_taxonomy_for_object_type($tag_taxonomy, 'tribe_events');
    }
    $tag_ids = tec_rb_resolve_tag_ids($tags, $tag_taxonomy);
    if (empty($tag_ids)) {
        return;
    }
    $result = wp_set_object_terms($event_id, $tag_ids, $tag_taxonomy, false);
    if (is_wp_error($result) && is_array($errors)) {
        $errors[] = 'Tags could not be assigned: ' . $result->get_error_message();
    }
}

function tec_rb_get_series_list() {
    if (!post_type_exists('tribe_event_series')) {
        return array();
    }
    $series = get_posts(array(
        'post_type' => 'tribe_event_series',
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
    ));
    return array_map(function ($series_item) {
        return array(
            'id' => (int) $series_item->ID,
            'name' => $series_item->post_title,
        );
    }, $series);
}

function tec_rb_get_presets() {
    $raw = get_option('tec_rb_presets', '');
    if (is_array($raw)) {
        return $raw;
    }
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }
    return array();
}

function tec_rb_get_default_options() {
    $defaults = array(
        'hide_from_listings' => false,
        'sticky_in_month' => false,
        'show_map_link' => true,
        'show_attendees_list' => false,
        'allow_comments' => false,
        'feature_event' => false,
        'event_website_enabled' => false,
        'waitlist_mode' => 'none',
        'attendee_collection' => 'none',
        'attendee_collection_preset' => '',
    );
    $stored = get_option('tec_rb_defaults', array());
    if (is_string($stored) && $stored !== '') {
        $decoded = json_decode($stored, true);
        if (is_array($decoded)) {
            $stored = $decoded;
        }
    }
    if (!is_array($stored)) {
        $stored = array();
    }
    return array_merge($defaults, $stored);
}

function tec_rb_normalize_created_post_id($value) {
    if (is_wp_error($value) || !$value) {
        return 0;
    }
    if (is_numeric($value)) {
        return absint($value);
    }
    if (is_object($value)) {
        if ($value instanceof WP_Post && isset($value->ID)) {
            return absint($value->ID);
        }
        if (isset($value->ID)) {
            return absint($value->ID);
        }
        if (method_exists($value, 'get_id')) {
            return absint($value->get_id());
        }
    }
    if (is_array($value)) {
        if (isset($value['ID'])) {
            return absint($value['ID']);
        }
        if (isset($value['id'])) {
            return absint($value['id']);
        }
    }
    return 0;
}

function tec_rb_save_preset_handler() {
    check_ajax_referer('tec_rb_ajax', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $data_raw = wp_unslash($_POST['data'] ?? '');
    if ($name === '' || $data_raw === '') {
        wp_send_json_error(array('message' => 'Preset name and data are required.'));
    }

    $decoded = json_decode($data_raw, true);
    if (!is_array($decoded)) {
        wp_send_json_error(array('message' => 'Invalid preset data.'));
    }

    $presets = tec_rb_get_presets();
    $presets[] = array(
        'name' => $name,
        'data' => $decoded,
    );
    update_option('tec_rb_presets', wp_json_encode($presets));

    wp_send_json_success(array('presets' => $presets));
}

add_action('wp_ajax_tec_rb_save_preset', 'tec_rb_save_preset_handler');

function tec_rb_disable_admin_footer_on_page() {
    // The Events Calendar adds its own footer copy; hide it on TicketPup pages.
    add_filter('admin_footer_text', '__return_empty_string', 1000);
    add_filter('update_footer', '__return_empty_string', 1000);
}

function tec_rb_should_guard_ian() {
    if (!is_admin() || !function_exists('get_current_screen')) {
        return false;
    }
    $screen = get_current_screen();
    if (!$screen) {
        return false;
    }
    if ($screen->post_type !== 'tribe_events') {
        return false;
    }
    if (!in_array($screen->base, array('post', 'edit'), true)) {
        return false;
    }
    return true;
}

function tec_rb_guard_ian_sidebar_markup() {
    if (!tec_rb_should_guard_ian()) {
        return;
    }
    ?>
    <script>
      (function () {
        const ensureIanMarkup = () => {
          if (document.querySelector('[data-tec-ian-trigger="notifications"]')) {
            return;
          }
          if (!document.body) {
            return;
          }
          const sidebar = document.createElement("div");
          sidebar.className = "ian-sidebar is-hidden";
          sidebar.dataset.tecIanTrigger = "sideIan";
          sidebar.style.display = "none";
          sidebar.innerHTML = `
            <div class="ian-sidebar__title">
              <div class="ian-sidebar__title--left">Notifications</div>
              <div class="ian-sidebar__title--right">
                <a href="#" data-tec-ian-trigger="readAllIan" class="is-hidden">Mark all as read</a>
                <button type="button" data-tec-ian-trigger="closeIan" class="button-link">Close</button>
              </div>
            </div>
            <div class="ian-sidebar__content" data-tec-ian-trigger="contentIan">
              <div class="ian-sidebar__loader is-hidden" data-tec-ian-trigger="loaderIan"></div>
              <div class="ian-sidebar__notifications is-hidden" data-tec-ian-trigger="notifications" data-consent="false"></div>
              <div class="ian-sidebar__optin is-hidden" data-tec-ian-trigger="emptyIan"></div>
              <div class="ian-sidebar__optin is-hidden" data-tec-ian-trigger="optinIan"></div>
            </div>
          `;
          document.body.appendChild(sidebar);

          if (!document.querySelector('[data-tec-ian-trigger="iconIan"]')) {
            const icon = document.createElement("div");
            icon.className = "ian-client";
            icon.dataset.tecIanTrigger = "iconIan";
            icon.style.display = "none";
            document.body.appendChild(icon);
          }
        };

        if (document.readyState === "loading") {
          document.addEventListener("DOMContentLoaded", ensureIanMarkup);
        } else {
          ensureIanMarkup();
        }
      })();
    </script>
    <?php
}

add_action('admin_head', 'tec_rb_guard_ian_sidebar_markup');

function tec_rb_render_form() {
    tec_rb_enqueue_assets();
    ob_start();
    include __DIR__ . '/templates/form.php';
    return ob_get_clean();
}

function tec_rb_render_admin_page() {
    if (!current_user_can('edit_posts')) {
        return;
    }
    tec_rb_disable_admin_footer_on_page();
    echo '<div class="wrap tec-wrap">';
    echo tec_rb_render_form();
    echo '</div>';
}

function tec_rb_render_debug_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    tec_rb_enqueue_assets();
    tec_rb_disable_admin_footer_on_page();
    echo '<div class="wrap tec-wrap">';
    include __DIR__ . '/templates/debug.php';
    echo '</div>';
}

function tec_rb_parse_time_to_24($time, $timezone) {
    $time = trim((string) $time);
    if ($time === '') {
        return '';
    }
    $formats = array('g:i A', 'g:i a', 'H:i', 'H:i:s');
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $time, $timezone);
        if ($dt instanceof DateTime) {
            return $dt->format('H:i:00');
        }
    }
    return $time;
}

function tec_rb_build_slug($event_name, $date, $time, $occurrence_name, $index) {
    $event_part = sanitize_title($event_name);
    $occ_part = $occurrence_name !== '' ? sanitize_title($occurrence_name) : 'occ-' . $index;
    $hhmm = substr($time, 0, 2) . substr($time, 3, 2);
    $slug = trim($event_part . '-' . $date . '-' . $hhmm . '-' . $occ_part, '-');
    return $slug;
}

function tec_rb_find_post_by_title($title, $post_type) {
    if ($title === '') {
        return null;
    }
    $post = get_page_by_title($title, OBJECT, $post_type);
    return $post instanceof WP_Post ? $post : null;
}

function tec_rb_find_term_id($name, $taxonomy) {
    if ($name === '') {
        return 0;
    }
    $term = get_term_by('name', $name, $taxonomy);
    if ($term && !is_wp_error($term)) {
        return (int) $term->term_id;
    }
    return 0;
}

function tec_rb_parse_time_parts($time) {
    $time = trim((string) $time);
    if ($time === '') {
        return array('', '');
    }
    $parts = explode(':', $time);
    if (count($parts) < 2) {
        return array('', '');
    }
    return array(str_pad($parts[0], 2, '0', STR_PAD_LEFT), str_pad($parts[1], 2, '0', STR_PAD_LEFT));
}

function tec_rb_get_ticket_provider() {
    if (!function_exists('tribe_tickets')) {
        return '';
    }
    $repo = tribe_tickets('woo');
    if (is_object($repo) && method_exists($repo, 'set_args') && method_exists($repo, 'create')) {
        return 'woo';
    }
    return '';
}

function tec_rb_parse_price($price) {
    $price = preg_replace('/[^0-9.]/', '', (string) $price);
    if ($price === '') {
        return '';
    }
    return (float) $price;
}

function tec_rb_apply_relative_offset($dt, $amount, $unit) {
    if (!$dt instanceof DateTime) {
        return null;
    }
    $amount = (int) $amount;
    if ($amount <= 0) {
        return null;
    }
    $unit = strtolower(trim((string) $unit));
    $clone = clone $dt;
    if (strpos($unit, 'day') === 0) {
        $clone->modify("-{$amount} days");
    } elseif (strpos($unit, 'week') === 0) {
        $clone->modify("-" . ($amount * 7) . " days");
    } elseif (strpos($unit, 'hour') === 0) {
        $clone->modify("-{$amount} hours");
    } elseif (strpos($unit, 'minute') === 0) {
        $clone->modify("-{$amount} minutes");
    } else {
        return null;
    }
    return $clone;
}

function tec_rb_format_datetime($date, $time, $timezone) {
    if ($date === '' || $time === '') {
        return '';
    }
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $time, $timezone);
    if (!$dt) {
        return '';
    }
    return $dt->format('Y-m-d H:i:s');
}

function tec_rb_build_instances($start_dt, $end_dt, $selected_days, $occurrences, $timezone) {
    $instances = array();
    $cursor = clone $start_dt;
    while ($cursor <= $end_dt) {
        $day = (int) $cursor->format('w');
        if (empty($selected_days) || in_array($day, $selected_days, true)) {
            foreach ($occurrences as $index => $occurrence) {
                $occ_name = sanitize_text_field($occurrence['name'] ?? '');
                $start_time_raw = sanitize_text_field($occurrence['startTime'] ?? '');
                $end_time_raw = sanitize_text_field($occurrence['endTime'] ?? '');
                $start_time = tec_rb_parse_time_to_24($start_time_raw, $timezone);
                $end_time = tec_rb_parse_time_to_24($end_time_raw, $timezone);
                $date = $cursor->format('Y-m-d');
                $instances[] = array(
                    'date' => $date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'occurrence_name' => $occ_name,
                    'occurrence_index' => $index + 1,
                    'start_datetime' => $date . ' ' . $start_time,
                );
            }
        }
        $cursor->modify('+1 day');
    }
    return $instances;
}

function tec_rb_parse_specific_dates($raw_dates, $timezone) {
    if (empty($raw_dates) || !is_array($raw_dates)) {
        return array();
    }
    $dates = array();
    foreach ($raw_dates as $date) {
        $date = trim((string) $date);
        if ($date === '') {
            continue;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            continue;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $date, $timezone);
        if (!$dt) {
            continue;
        }
        $dates[] = $dt->format('Y-m-d');
    }
    $dates = array_values(array_unique($dates));
    sort($dates);
    return $dates;
}

function tec_rb_build_instances_from_dates($dates, $occurrences, $timezone) {
    $instances = array();
    if (empty($dates)) {
        return $instances;
    }
    foreach ($dates as $date) {
        foreach ($occurrences as $index => $occurrence) {
            $occ_name = sanitize_text_field($occurrence['name'] ?? '');
            $start_time_raw = sanitize_text_field($occurrence['startTime'] ?? '');
            $end_time_raw = sanitize_text_field($occurrence['endTime'] ?? '');
            $start_time = tec_rb_parse_time_to_24($start_time_raw, $timezone);
            $end_time = tec_rb_parse_time_to_24($end_time_raw, $timezone);
            $instances[] = array(
                'date' => $date,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'occurrence_name' => $occ_name,
                'occurrence_index' => $index + 1,
                'start_datetime' => $date . ' ' . $start_time,
            );
        }
    }
    return $instances;
}

function tec_rb_find_events_handler() {
    check_ajax_referer('tec_rb_ajax', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    $payload_raw = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : '';
    $payload = json_decode($payload_raw, true);
    if (!is_array($payload)) {
        wp_send_json_error(array('message' => 'Invalid payload.'));
    }

    $event_name = sanitize_text_field($payload['eventName'] ?? '');
    $start_date = sanitize_text_field($payload['startDate'] ?? '');
    $end_date = sanitize_text_field($payload['endDate'] ?? '');
    $schedule_mode = sanitize_text_field($payload['scheduleMode'] ?? 'recurring');
    $timezone = new DateTimeZone('America/New_York');
    $specific_dates = tec_rb_parse_specific_dates($payload['specificDates'] ?? array(), $timezone);
    $recurrence_days = isset($payload['recurrenceDays']) && is_array($payload['recurrenceDays'])
        ? array_map('sanitize_text_field', $payload['recurrenceDays'])
        : array();
    $occurrences = isset($payload['occurrences']) && is_array($payload['occurrences'])
        ? $payload['occurrences']
        : array();

    if ($event_name === '') {
        wp_send_json_error(array('message' => 'Missing event name.'));
    }

    $start_dt = null;
    $end_dt = null;
    if ($schedule_mode === 'specific') {
        if (empty($specific_dates)) {
            wp_send_json_error(array('message' => 'No specific dates were selected.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[0], $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[count($specific_dates) - 1], $timezone);
    } else {
        if ($start_date === '' || $end_date === '') {
            wp_send_json_error(array('message' => 'Missing event date range.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
    }
    if (!$start_dt || !$end_dt) {
        wp_send_json_error(array('message' => 'Invalid date range.'));
    }

    $day_map = array(
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    );
    $selected_days = array();
    foreach ($recurrence_days as $day) {
        if (isset($day_map[$day])) {
            $selected_days[] = $day_map[$day];
        }
    }

    $expected = array();
    if ($schedule_mode === 'specific') {
        $instances = tec_rb_build_instances_from_dates($specific_dates, $occurrences, $timezone);
        foreach ($instances as $instance) {
            $expected[$instance['start_datetime']] = array(
                'date' => $instance['date'],
                'time' => $instance['start_time'],
                'occurrence' => $instance['occurrence_name'],
                'index' => $instance['occurrence_index'],
            );
        }
    } else {
        $cursor = clone $start_dt;
        while ($cursor <= $end_dt) {
            $day = (int) $cursor->format('w');
            if (empty($selected_days) || in_array($day, $selected_days, true)) {
                foreach ($occurrences as $index => $occurrence) {
                    $occ_name = sanitize_text_field($occurrence['name'] ?? '');
                    $start_time = sanitize_text_field($occurrence['startTime'] ?? '');
                    $start_time_24 = tec_rb_parse_time_to_24($start_time, $timezone);
                    $start_date_str = $cursor->format('Y-m-d');
                    $start_date_time = $start_date_str . ' ' . $start_time_24;
                    $expected[$start_date_time] = array(
                        'date' => $start_date_str,
                        'time' => $start_time_24,
                        'occurrence' => $occ_name,
                        'index' => $index + 1,
                    );
                }
            }
            $cursor->modify('+1 day');
        }
    }

    if (empty($expected)) {
        wp_send_json_success(array('found' => array(), 'missing' => array(), 'errors' => array('No expected events were generated.')));
    }

    $expected_keys = array_keys($expected);
    sort($expected_keys);
    $min_start = $expected_keys[0];
    $max_start = $expected_keys[count($expected_keys) - 1];

    $query = new WP_Query(array(
        'post_type' => 'tribe_events',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_EventStartDate',
                'value' => array($min_start, $max_start),
                'compare' => 'BETWEEN',
                'type' => 'DATETIME',
            ),
        ),
    ));

    $found = array();
    $matched = array();
    if ($query->have_posts()) {
        foreach ($query->posts as $post) {
            if ($post->post_title !== $event_name) {
                continue;
            }
            $event_start = get_post_meta($post->ID, '_EventStartDate', true);
            if (!isset($expected[$event_start])) {
                continue;
            }
            $meta = $expected[$event_start];
            $desired_slug = tec_rb_build_slug($event_name, $meta['date'], $meta['time'], $meta['occurrence'], $meta['index']);
            $unique_slug = wp_unique_post_slug($desired_slug, $post->ID, $post->post_status, $post->post_type, $post->post_parent);
            wp_update_post(array('ID' => $post->ID, 'post_name' => $unique_slug));
            $actual_slug = get_post_field('post_name', $post->ID);
            $found[] = array(
                'id' => $post->ID,
                'slug' => $actual_slug,
                'startDateTime' => $event_start,
            );
            $matched[$event_start] = true;
        }
    }

    $missing = array();
    foreach ($expected as $start_time => $meta) {
        if (!isset($matched[$start_time])) {
            $missing[] = array('startDateTime' => $start_time);
        }
    }

    wp_send_json_success(array(
        'found' => $found,
        'missing' => $missing,
        'errors' => array(),
    ));
}

add_action('wp_ajax_tec_rb_find_events', 'tec_rb_find_events_handler');

function tec_rb_dry_run_handler() {
    check_ajax_referer('tec_rb_ajax', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    $payload_raw = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : '';
    $payload = json_decode($payload_raw, true);
    if (!is_array($payload)) {
        wp_send_json_error(array('message' => 'Invalid payload.'));
    }

    $event_name = sanitize_text_field($payload['eventName'] ?? '');
    $start_date = sanitize_text_field($payload['startDate'] ?? '');
    $end_date = sanitize_text_field($payload['endDate'] ?? '');
    $schedule_mode = sanitize_text_field($payload['scheduleMode'] ?? 'recurring');
    $venue_value = sanitize_text_field($payload['eventVenue'] ?? '');
    $organizer_value = sanitize_text_field($payload['eventOrganizer'] ?? '');
    $category_value = sanitize_text_field($payload['eventCategory'] ?? '');
    $ticket_types = isset($payload['ticketTypes']) && is_array($payload['ticketTypes'])
        ? $payload['ticketTypes']
        : array();
    $shared_capacity = !empty($payload['sharedCapacity']) && count($ticket_types) > 1;
    $shared_capacity_total = isset($payload['sharedCapacityTotal']) ? (int) $payload['sharedCapacityTotal'] : 0;

    if (!empty($ticket_types)) {
        foreach ($ticket_types as &$ticket) {
            $is_free = !empty($ticket['isFree']);
            $price_raw = isset($ticket['price']) ? trim((string) $ticket['price']) : '';
            if ($price_raw === '' && !$is_free) {
                wp_send_json_error(array('message' => 'Ticket price is required unless the ticket is marked free.'));
            }
            if ($is_free && $price_raw === '') {
                $ticket['price'] = '0';
            }
        }
        unset($ticket);
    }
    $attendee_presets = tec_rb_get_attendee_question_presets();
    $has_attendee_presets = !empty($attendee_presets);
    foreach ($ticket_types as $ticket) {
        $collection = sanitize_text_field($ticket['attendeeCollection'] ?? 'none');
        $preset_key = isset($ticket['attendeePreset']) ? trim((string) $ticket['attendeePreset']) : '';
        if ($collection !== 'none') {
            if (!$has_attendee_presets) {
                wp_send_json_error(array('message' => 'Attendee collection requires at least one attendee question preset.'));
            }
            if ($preset_key === '') {
                wp_send_json_error(array('message' => 'Select an attendee question preset for tickets with attendee collection enabled.'));
            }
        }
    }
    $attendee_presets = tec_rb_get_attendee_question_presets();
    $has_attendee_presets = !empty($attendee_presets);
    foreach ($ticket_types as $ticket) {
        $collection = sanitize_text_field($ticket['attendeeCollection'] ?? 'none');
        $preset_key = isset($ticket['attendeePreset']) ? trim((string) $ticket['attendeePreset']) : '';
        if ($collection !== 'none') {
            if (!$has_attendee_presets) {
                wp_send_json_error(array('message' => 'Attendee collection requires at least one attendee question preset.'));
            }
            if ($preset_key === '') {
                wp_send_json_error(array('message' => 'Select an attendee question preset for tickets with attendee collection enabled.'));
            }
        }
    }

    $timezone = new DateTimeZone('America/New_York');
    $specific_dates = tec_rb_parse_specific_dates($payload['specificDates'] ?? array(), $timezone);

    if ($event_name === '') {
        wp_send_json_error(array('message' => 'Missing event name.'));
    }

    $start_dt = null;
    $end_dt = null;
    if ($schedule_mode === 'specific') {
        if (empty($specific_dates)) {
            wp_send_json_error(array('message' => 'No specific dates were selected.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[0], $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[count($specific_dates) - 1], $timezone);
    } else {
        if ($start_date === '' || $end_date === '') {
            wp_send_json_error(array('message' => 'Missing event date range.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
    }
    if (!$start_dt || !$end_dt) {
        wp_send_json_error(array('message' => 'Invalid date range.'));
    }

    if ($venue_value !== '') {
        $venue = null;
        $venue_id = (int) $venue_value;
        if ($venue_id) {
            $venue = get_post($venue_id);
        } else {
            $venue = tec_rb_find_post_by_title($venue_value, 'tribe_venue');
        }
        if (!$venue) {
            wp_send_json_error(array('message' => 'Venue not found: ' . $venue_value));
        }
    }

    if ($organizer_value !== '') {
        $organizer = null;
        $organizer_id = (int) $organizer_value;
        if ($organizer_id) {
            $organizer = get_post($organizer_id);
        } else {
            $organizer = tec_rb_find_post_by_title($organizer_value, 'tribe_organizer');
        }
        if (!$organizer) {
            wp_send_json_error(array('message' => 'Organizer not found: ' . $organizer_value));
        }
    }

    $category_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
    if ($category_value !== '') {
        $category_id = 0;
        if (is_numeric($category_value)) {
            $category_id = (int) $category_value;
        } else {
            $category_id = tec_rb_find_term_id($category_value, $category_taxonomy);
        }
        if (!$category_id) {
            wp_send_json_error(array('message' => 'Event category not found: ' . $category_value));
        }
    }

    $recurrence_days = isset($payload['recurrenceDays']) && is_array($payload['recurrenceDays'])
        ? array_map('sanitize_text_field', $payload['recurrenceDays'])
        : array();
    $occurrences = isset($payload['occurrences']) && is_array($payload['occurrences'])
        ? $payload['occurrences']
        : array();

    if (empty($occurrences)) {
        wp_send_json_error(array('message' => 'No occurrences were provided.'));
    }

    $day_map = array(
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    );
    $selected_days = array();
    foreach ($recurrence_days as $day) {
        if (isset($day_map[$day])) {
            $selected_days[] = $day_map[$day];
        }
    }

    if (!empty($ticket_types)) {
        $provider = tec_rb_get_ticket_provider();
        if ($provider === '') {
            wp_send_json_error(array('message' => 'WooCommerce ticket provider not found.'));
        }
    }

    if ($schedule_mode === 'specific') {
        $instances = tec_rb_build_instances_from_dates($specific_dates, $occurrences, $timezone);
    } else {
        $instances = tec_rb_build_instances($start_dt, $end_dt, $selected_days, $occurrences, $timezone);
    }
    $event_count = count($instances);
    $ticket_count = $event_count * count($ticket_types);

    $samples = array();
    foreach ($instances as $index => $instance) {
        if ($index >= 5) {
            break;
        }
        $slug = tec_rb_build_slug($event_name, $instance['date'], $instance['start_time'], $instance['occurrence_name'], $instance['occurrence_index']);
        $samples[] = array(
            'id' => '',
            'slug' => $slug,
            'startDateTime' => $instance['start_datetime'],
        );
    }

    wp_send_json_success(array(
        'found' => $samples,
        'missing' => array(),
        'errors' => array(),
        'summary' => 'events would be created.',
        'eventCount' => $event_count,
        'ticketCount' => $ticket_count,
        'ticketSummary' => 'tickets would be created.',
    ));
}

add_action('wp_ajax_tec_rb_dry_run', 'tec_rb_dry_run_handler');

function tec_rb_build_ticket_args($provider, $ticket, $event_id, $event_start_dt, $timezone, $shared_capacity = false, $shared_capacity_level = 0, $attendee_questions = array()) {
    $name = sanitize_text_field($ticket['name'] ?? '');
    if ($name === '') {
        $name = 'Ticket';
    }
    $description = wp_kses_post($ticket['description'] ?? '');
    $price = tec_rb_parse_price($ticket['price'] ?? '');
    $is_free = !empty($ticket['isFree']);
    if ($is_free) {
        $price = 0;
    }
    $quantity = trim((string) ($ticket['quantity'] ?? ''));
    $capacity = $quantity === '' ? -1 : max(0, (int) $quantity);
    $show_description = !empty($ticket['showDescription']);
    $attendee_mode_raw = sanitize_text_field($ticket['attendeeCollection'] ?? 'none');
    $attendee_mode = in_array($attendee_mode_raw, array('none', 'allow', 'require'), true) ? $attendee_mode_raw : 'none';
    $iac_value = $attendee_mode === 'allow' ? 'allowed' : ($attendee_mode === 'require' ? 'required' : 'none');
    if (!empty($attendee_questions) && is_array($attendee_questions)) {
        $attendee_questions = array_values($attendee_questions);
        foreach ($attendee_questions as $idx => &$question) {
            if (!is_array($question)) {
                continue;
            }
            if (!isset($question['field_order'])) {
                $question['field_order'] = $idx;
            }
        }
        unset($question);
    }
    $use_attendee_questions = $iac_value !== 'none' && !empty($attendee_questions);

    $sale_start_mode = sanitize_text_field($ticket['saleStartMode'] ?? 'immediate');
    $sale_start_date = sanitize_text_field($ticket['saleStartDate'] ?? '');
    $sale_start_time_raw = sanitize_text_field($ticket['saleStartTime'] ?? '');
    $sale_start_time = tec_rb_parse_time_to_24($sale_start_time_raw, $timezone);
    $sale_start_offset = sanitize_text_field($ticket['saleStartOffset'] ?? '');
    $sale_start_unit = sanitize_text_field($ticket['saleStartUnit'] ?? '');

    $sale_end_mode = sanitize_text_field($ticket['saleEndMode'] ?? 'start');
    $sale_end_offset = sanitize_text_field($ticket['saleEndOffset'] ?? '');
    $sale_end_unit = sanitize_text_field($ticket['saleEndUnit'] ?? '');

    $start_dt = null;
    $end_dt = null;

    if ($sale_start_mode === 'immediate') {
        $start_dt = new DateTime('now', $timezone);
    } elseif ($sale_start_mode === 'set') {
        $start_dt_value = tec_rb_format_datetime($sale_start_date, $sale_start_time, $timezone);
        if ($start_dt_value !== '') {
            $start_dt = DateTime::createFromFormat('Y-m-d H:i:s', $start_dt_value, $timezone);
        }
    } elseif ($sale_start_mode === 'relative' && $event_start_dt instanceof DateTime) {
        if ($sale_start_offset !== '' && $sale_start_offset !== 'X') {
            $start_dt = tec_rb_apply_relative_offset($event_start_dt, $sale_start_offset, $sale_start_unit);
        }
    }

    if ($sale_end_mode === 'start' && $event_start_dt instanceof DateTime) {
        $end_dt = clone $event_start_dt;
    } elseif ($sale_end_mode === 'relative' && $event_start_dt instanceof DateTime) {
        if ($sale_end_offset !== '' && $sale_end_offset !== 'X') {
            $end_dt = tec_rb_apply_relative_offset($event_start_dt, $sale_end_offset, $sale_end_unit);
        }
    }

    $ticket_start = $start_dt instanceof DateTime ? $start_dt->format('Y-m-d H:i:s') : '';
    $ticket_end = $end_dt instanceof DateTime ? $end_dt->format('Y-m-d H:i:s') : '';

    if ($provider === 'tickets-commerce') {
        $args = array(
            'title' => $name,
            'status' => 'publish',
            'event' => $event_id,
            'price' => $price === '' ? 0 : $price,
            'show_description' => $show_description,
            'excerpt' => $description,
            '_sku' => '',
            '_manage_stock' => $capacity >= 0 ? 'yes' : 'no',
            '_stock' => $capacity >= 0 ? $capacity : '',
            '_tribe_ticket_capacity' => $capacity >= 0 ? $capacity : -1,
            '_global_stock_mode' => $capacity >= 0 ? 'own' : 'unlimited',
        );
    } elseif ($provider === 'woo') {
        $use_shared_capacity = $shared_capacity && (int) $shared_capacity_level > 0;
        $shared_capacity_level = (int) $shared_capacity_level;

        // For shared capacity, tickets use the event shared pool; TEC stores them as "global" mode.
        // We set each ticket capacity to the shared total (matching manual TEC behavior).
        $effective_capacity = $use_shared_capacity ? $shared_capacity_level : $capacity;

        $stock_value = $effective_capacity >= 0 ? $effective_capacity : '';
        $stock_status = $effective_capacity === 0 ? 'outofstock' : 'instock';
        $stock_mode = $effective_capacity >= 0 ? 'own' : 'unlimited';
        if ($use_shared_capacity) {
            $stock_mode = 'global';
        }
        $args = array(
            'title' => $name,
            'status' => 'publish',
            'event' => $event_id,
            'price' => $price === '' ? 0 : $price,
            'capacity' => $effective_capacity >= 0 ? $effective_capacity : -1,
            'stock' => $stock_value,
            'stock_mode' => $stock_mode,
            'show_description' => $show_description,
            'description' => $description,
            '_tribe_wooticket_for_event' => $event_id,
            '_tribe_wooticket_event' => $event_id,
            '_price' => $price === '' ? 0 : $price,
            '_regular_price' => $price === '' ? 0 : $price,
            '_manage_stock' => $effective_capacity >= 0 ? 'yes' : 'no',
            '_stock' => $stock_value,
            '_stock_status' => $stock_status,
            '_backorders' => 'no',
            '_sold_individually' => 'no',
            '_virtual' => 'yes',
            '_downloadable' => 'no',
            '_tribe_ticket_capacity' => $effective_capacity >= 0 ? $effective_capacity : -1,
            '_tribe_ticket_stock' => $stock_value,
            '_tribe_ticket_show_description' => $show_description ? 'yes' : 'no',
            '_global_stock_mode' => $stock_mode,
            '_global_stock_cap' => '',
            'excerpt' => $description,
            '_sku' => '',
            '_type' => 'default',
            '_tribe_tickets_ar_iac' => $iac_value,
            '_tribe_tickets_meta' => $use_attendee_questions ? $attendee_questions : array(),
            '_tribe_tickets_meta_enabled' => $use_attendee_questions ? 'yes' : 'no',
            'total_sales' => 0,
            '_tax_status' => 'taxable',
            '_tax_class' => '',
            '_download_limit' => '-1',
            '_download_expiry' => '-1',
        );
        if (function_exists('WC') && WC() && isset(WC()->version)) {
            $args['_product_version'] = WC()->version;
        }
    } else {
        $args = array(
            'title' => $name,
            'status' => 'publish',
            '_tribe_rsvp_for_event' => $event_id,
            '_tribe_ticket_capacity' => $capacity >= 0 ? $capacity : -1,
            '_tribe_ticket_show_description' => $show_description ? 1 : 0,
            'excerpt' => $description,
        );
    }

    if ($ticket_start !== '') {
        $args['_ticket_start_date'] = $ticket_start;
    }
    if ($ticket_end !== '') {
        $args['_ticket_end_date'] = $ticket_end;
    }

    return $args;
}

function tec_rb_enable_shared_capacity_for_event($event_id, $ticket_ids, $event_capacity) {
    $event_id = (int) $event_id;
    $event_capacity = (int) $event_capacity;
    if ($event_id <= 0 || $event_capacity <= 0) {
        return;
    }
    if (empty($ticket_ids) || !is_array($ticket_ids)) {
        return;
    }
    if (!function_exists('tribe') || !class_exists('Tribe__Tickets__Global_Stock')) {
        return;
    }

    $ticket_ids = array_values(array_unique(array_map('intval', $ticket_ids)));
    $ticket_ids = array_values(array_filter($ticket_ids));
    if (empty($ticket_ids)) {
        return;
    }

    // Mirror the state created by TEC when enabling global stock:
    // - enable global stock
    // - mark each ticket as shared ("global") so capacity/stock will be synced
    $object_stock = new Tribe__Tickets__Global_Stock( $event_id );
    $object_stock->enable( true );

    $modified_fields = get_post_meta($event_id, '_tribe_modified_fields', true);
    if (!is_array($modified_fields)) {
        $modified_fields = array();
    }
    $now = current_time('timestamp');
    $modified_fields['_tribe_ticket_capacity'] = $now;
    $modified_fields[Tribe__Tickets__Global_Stock::GLOBAL_STOCK_ENABLED] = $now;
    $modified_fields[Tribe__Tickets__Global_Stock::GLOBAL_STOCK_LEVEL] = $now;
    update_post_meta($event_id, '_tribe_modified_fields', $modified_fields);

    foreach ($ticket_ids as $ticket_id) {
        update_post_meta($ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_MODE, Tribe__Tickets__Global_Stock::GLOBAL_STOCK_MODE);
        delete_post_meta($ticket_id, Tribe__Tickets__Global_Stock::TICKET_STOCK_CAP);
        clean_post_cache($ticket_id);
    }

    // Setting the event capacity triggers TEC's `updated_postmeta` hook to sync shared capacity.
    update_post_meta($event_id, '_tribe_ticket_capacity', (string) $event_capacity);

    $handler = tribe('tickets.handler');
    if ($handler && method_exists($handler, 'sync_shared_capacity')) {
        $handler->sync_shared_capacity($event_id, $event_capacity);
    }
}

function tec_rb_set_ticket_waitlist($event_id, $mode) {
    if (!$event_id) {
        return;
    }
    if (!class_exists('\\TEC\\Tickets_Plus\\Waitlist\\Waitlists') || !class_exists('\\TEC\\Tickets_Plus\\Waitlist\\Waitlist')) {
        return;
    }
    if (!function_exists('tribe')) {
        return;
    }

    $mode = sanitize_text_field($mode);
    $enabled = true;
    $conditional = \TEC\Tickets_Plus\Waitlist\Waitlist::ALWAYS_CONDITIONAL;

    switch ($mode) {
        case 'presale_or_sold_out':
            $conditional = \TEC\Tickets_Plus\Waitlist\Waitlist::ALWAYS_CONDITIONAL;
            break;
        case 'before_sale':
            $conditional = \TEC\Tickets_Plus\Waitlist\Waitlist::BEFORE_SALE_CONDITIONAL;
            break;
        case 'sold_out':
            $conditional = \TEC\Tickets_Plus\Waitlist\Waitlist::ON_SOLD_OUT_CONDITIONAL;
            break;
        default:
            $enabled = false;
            $conditional = \TEC\Tickets_Plus\Waitlist\Waitlist::ALWAYS_CONDITIONAL;
            break;
    }

    $waitlists = tribe(\TEC\Tickets_Plus\Waitlist\Waitlists::class);
    if (!$waitlists || !method_exists($waitlists, 'upsert_waitlist_for_post')) {
        return;
    }

    $waitlists->upsert_waitlist_for_post(
        (int) $event_id,
        array(
            'enabled' => $enabled,
            'conditional' => $conditional,
        ),
        \TEC\Tickets_Plus\Waitlist\Waitlist::TICKET_TYPE
    );

    // Avoid leaving mismatched meta values; waitlists are stored in custom tables.
    if (class_exists('\\TEC\\Tickets_Plus\\Waitlist\\Meta')) {
        delete_post_meta($event_id, \TEC\Tickets_Plus\Waitlist\Meta::ENABLED_KEY);
        delete_post_meta($event_id, \TEC\Tickets_Plus\Waitlist\Meta::CONDITIONAL_KEY);
    }
}

function tec_rb_mark_event_has_tickets($event_id, $provider) {
    if (!$event_id) {
        return;
    }
    $provider_key = $provider;
    if (function_exists('tribe') && class_exists('Tribe__Tickets__Status__Manager')) {
        $status = tribe('tickets.status');
        if ($status && method_exists($status, 'get_provider_class_from_slug')) {
            $resolved = $status->get_provider_class_from_slug($provider);
            if (is_string($resolved) && $resolved !== '') {
                $provider_key = $resolved;
            }
        }
    }

    update_post_meta($event_id, '_tribe_tickets_status', 'yes');
    update_post_meta($event_id, '_tribe_tickets_enabled', 'yes');
    update_post_meta($event_id, '_tribe_has_tickets', 1);
    update_post_meta($event_id, '_tribe_tickets_has_tickets', 1);
    update_post_meta($event_id, '_tribe_tickets_default_provider', $provider_key);
    update_post_meta($event_id, '_tribe_default_ticket_provider', $provider_key);
    update_post_meta($event_id, '_tribe_tickets_last_updated', current_time('timestamp'));
}

function tec_rb_normalize_ticket_sale_meta($ticket_id) {
    $start_date_raw = (string) get_post_meta($ticket_id, '_ticket_start_date', true);
    $start_time_raw = (string) get_post_meta($ticket_id, '_ticket_start_time', true);
    if ($start_date_raw !== '' && $start_time_raw === '' && strpos($start_date_raw, ' ') !== false) {
        $start_dt = date_create($start_date_raw);
        if ($start_dt instanceof DateTime) {
            update_post_meta($ticket_id, '_ticket_start_date', $start_dt->format('Y-m-d'));
            update_post_meta($ticket_id, '_ticket_start_time', $start_dt->format('H:i:s'));
        }
    }

    $end_date_raw = (string) get_post_meta($ticket_id, '_ticket_end_date', true);
    $end_time_raw = (string) get_post_meta($ticket_id, '_ticket_end_time', true);
    if ($end_date_raw !== '' && $end_time_raw === '' && strpos($end_date_raw, ' ') !== false) {
        $end_dt = date_create($end_date_raw);
        if ($end_dt instanceof DateTime) {
            update_post_meta($ticket_id, '_ticket_end_date', $end_dt->format('Y-m-d'));
            update_post_meta($ticket_id, '_ticket_end_time', $end_dt->format('H:i:s'));
        }
    }
}

function tec_rb_touch_event($event_id) {
    if (!$event_id) {
        return;
    }
    wp_update_post(array('ID' => $event_id));
    clean_post_cache($event_id);
}

function tec_rb_finalize_woo_ticket($ticket_id, $show_description) {
    if (!$ticket_id) {
        return;
    }
    $capacity = get_post_meta($ticket_id, '_tribe_ticket_capacity', true);
    $capacity = $capacity === '' ? null : (int) $capacity;
    if ($capacity !== null && $capacity >= 0) {
        update_post_meta($ticket_id, '_stock', (string) $capacity);
        update_post_meta($ticket_id, '_tribe_ticket_stock', (string) $capacity);
        update_post_meta($ticket_id, '_stock_status', $capacity === 0 ? 'outofstock' : 'instock');
    }

    update_post_meta($ticket_id, '_type', 'default');
    $existing_iac = get_post_meta($ticket_id, '_tribe_tickets_ar_iac', true);
    if ($existing_iac === '') {
        update_post_meta($ticket_id, '_tribe_tickets_ar_iac', 'required');
    }
    $existing_meta = get_post_meta($ticket_id, '_tribe_tickets_meta', true);
    if ($existing_meta === '') {
        update_post_meta($ticket_id, '_tribe_tickets_meta', array());
    }
    $existing_meta_enabled = get_post_meta($ticket_id, '_tribe_tickets_meta_enabled', true);
    if ($existing_meta_enabled === '') {
        $has_meta = is_array($existing_meta) ? !empty($existing_meta) : false;
        update_post_meta($ticket_id, '_tribe_tickets_meta_enabled', $has_meta ? 'yes' : 'no');
    }
    update_post_meta($ticket_id, '_tribe_ticket_show_description', $show_description ? 'yes' : 'no');
    update_post_meta($ticket_id, '_tribe_ticket_version', defined('TRIBE_TICKETS_VERSION') ? TRIBE_TICKETS_VERSION : '5.27.4');
    update_post_meta($ticket_id, '_tax_status', 'taxable');
    update_post_meta($ticket_id, '_tax_class', '');
    update_post_meta($ticket_id, '_download_limit', '-1');
    update_post_meta($ticket_id, '_download_expiry', '-1');
    update_post_meta($ticket_id, 'total_sales', 0);
    if (function_exists('WC') && WC() && isset(WC()->version)) {
        update_post_meta($ticket_id, '_product_version', WC()->version);
    }

    if (function_exists('wp_set_object_terms')) {
        wp_set_object_terms($ticket_id, 'simple', 'product_type', false);
        wp_set_object_terms($ticket_id, array('exclude-from-catalog', 'exclude-from-search'), 'product_visibility', false);
    }
}

function tec_rb_abbreviate_name($name) {
    $name = trim((string) $name);
    if ($name === '') {
        return 'EVT';
    }
    $parts = preg_split('/\s+/', $name);
    $abbr = '';
    foreach ($parts as $part) {
        $clean = preg_replace('/[^A-Za-z0-9]/', '', $part);
        if ($clean === '') {
            continue;
        }
        $abbr .= strtoupper(substr($clean, 0, 1));
        if (strlen($abbr) >= 4) {
            break;
        }
    }
    if ($abbr === '') {
        $abbr = strtoupper(substr(sanitize_title($name), 0, 4));
    }
    return $abbr;
}

function tec_rb_generate_ticket_sku($event_name, $date, $time, $occurrence_name, $ticket_name, $index) {
    $abbr = tec_rb_abbreviate_name($event_name);
    $date_part = preg_replace('/[^0-9]/', '', (string) $date);
    $time_part = preg_replace('/[^0-9]/', '', (string) $time);
    $occ_part = $occurrence_name !== '' ? sanitize_title($occurrence_name) : 'occ-' . $index;
    $ticket_part = $ticket_name !== '' ? sanitize_title($ticket_name) : 'ticket-' . $index;
    $sku = strtoupper(trim("{$abbr}-{$date_part}-{$time_part}-{$occ_part}-{$ticket_part}", '-'));
    $sku = preg_replace('/[^A-Z0-9\-]+/', '-', $sku);
    $sku = trim($sku, '-');
    return $sku;
}

function tec_rb_generate_unique_ticket_sku($ticket_id, $sku) {
    $sku = trim((string) $sku);
    if ($sku === '') {
        return '';
    }
    if (function_exists('wc_product_generate_unique_sku')) {
        $sku = wc_product_generate_unique_sku($ticket_id, $sku);
        return $sku ?: '';
    }
    if (function_exists('wc_get_product_id_by_sku')) {
        $existing = wc_get_product_id_by_sku($sku);
        if ($existing && (int) $existing !== (int) $ticket_id) {
            $sku = $sku . '-' . $ticket_id;
        }
    }
    return $sku;
}

function tec_rb_get_ticket_category_id() {
    if (!taxonomy_exists('product_cat')) {
        return 0;
    }
    $term = get_term_by('slug', 'event-tickets', 'product_cat');
    if ($term && !is_wp_error($term)) {
        return (int) $term->term_id;
    }
    $created = wp_insert_term('Event Tickets', 'product_cat', array('slug' => 'event-tickets'));
    if (is_wp_error($created)) {
        return 0;
    }
    return (int) $created['term_id'];
}

function tec_rb_apply_ticket_product_meta($ticket_id, $event_name, $instance, $ticket, $index) {
    if (!$ticket_id) {
        return;
    }
    $current_sku = get_post_meta($ticket_id, '_sku', true);
    if ($current_sku === '' || $current_sku === null) {
        $sku = tec_rb_generate_ticket_sku(
            $event_name,
            $instance['date'] ?? '',
            $instance['start_time'] ?? '',
            $instance['occurrence_name'] ?? '',
            $ticket['name'] ?? '',
            $index
        );
        $sku = tec_rb_generate_unique_ticket_sku($ticket_id, $sku);
        if ($sku !== '') {
            update_post_meta($ticket_id, '_sku', $sku);
        }
    }

    $category_id = tec_rb_get_ticket_category_id();
    if ($category_id) {
        wp_set_object_terms($ticket_id, array($category_id), 'product_cat', true);
    }
}

function tec_rb_set_ticket_header_image($event_id, $attachment_id) {
    if (!$event_id) {
        return;
    }
    $attachment_id = (int) $attachment_id;
    if ($attachment_id > 0) {
        update_post_meta($event_id, '_tribe_ticket_header', $attachment_id);
    } else {
        delete_post_meta($event_id, '_tribe_ticket_header');
    }
}

function tec_rb_assign_event_to_series($event_id, $series_id) {
    $series_id = (int) $series_id;
    if (!$event_id || !$series_id) {
        return;
    }
    if (!class_exists('\\TEC\\Events_Pro\\Custom_Tables\\V1\\Series\\Relationship')) {
        return;
    }
    if (!class_exists('\\TEC\\Events\\Custom_Tables\\V1\\Models\\Event')) {
        return;
    }
    $event_model = \TEC\Events\Custom_Tables\V1\Models\Event::find($event_id, 'post_id');
    if (!$event_model instanceof \TEC\Events\Custom_Tables\V1\Models\Event) {
        return;
    }
    if (function_exists('tribe')) {
        $relationship = tribe(\TEC\Events_Pro\Custom_Tables\V1\Series\Relationship::class);
        if ($relationship && method_exists($relationship, 'with_event')) {
            $relationship->with_event($event_model, array($series_id));
        }
    }
}

function tec_rb_get_event_ticket_ids($event_id, $provider) {
    if (!$event_id) {
        return array();
    }
    if ($provider !== 'woo') {
        return array();
    }
    $tickets = get_posts(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'private'),
        'meta_key' => '_tribe_wooticket_for_event',
        'meta_value' => (string) $event_id,
        'fields' => 'ids',
        'posts_per_page' => -1,
        'no_found_rows' => true,
    ));
    return array_map('intval', $tickets);
}

function tec_rb_build_ticket_summaries($ticket_ids) {
    if (empty($ticket_ids) || !is_array($ticket_ids)) {
        return array();
    }
    $summaries = array();
    foreach ($ticket_ids as $ticket_id) {
        $ticket_id = (int) $ticket_id;
        if ($ticket_id <= 0) {
            continue;
        }
        $summaries[] = array(
            'id' => $ticket_id,
            'name' => get_the_title($ticket_id),
        );
    }
    return $summaries;
}

function tec_rb_ticket_show_description($ticket_id) {
    $raw = get_post_meta($ticket_id, '_tribe_ticket_show_description', true);
    if ($raw === 'yes' || $raw === '1' || $raw === 1 || $raw === true) {
        return true;
    }
    return false;
}

function tec_rb_collect_ticket_prices_from_input($ticket_types) {
    if (!is_array($ticket_types)) {
        return array();
    }
    $prices = array();
    foreach ($ticket_types as $ticket) {
        $price = tec_rb_parse_price($ticket['price'] ?? '');
        $normalized = tec_rb_normalize_price_value($price);
        if ($normalized === null) {
            continue;
        }
        $prices[] = $normalized;
    }
    return array_values(array_unique($prices));
}

function tec_rb_calculate_shared_capacity($ticket_types) {
    if (!is_array($ticket_types)) {
        return 0;
    }
    $total = 0;
    foreach ($ticket_types as $ticket) {
        $quantity = trim((string) ($ticket['quantity'] ?? ''));
        if ($quantity === '') {
            continue;
        }
        $qty = (int) $quantity;
        if ($qty > 0) {
            $total += $qty;
        }
    }
    return $total;
}

function tec_rb_normalize_price_value($price) {
    if ($price === '' || $price === null) {
        return null;
    }
    if (is_string($price)) {
        $price = trim($price);
        if ($price === '') {
            return null;
        }
    }
    if (!is_numeric($price)) {
        return null;
    }
    return number_format((float) $price, 2, '.', '');
}

function tec_rb_update_event_cost_from_prices($event_id, $prices) {
    if (!$event_id || empty($prices)) {
        return;
    }

    $prices = array_values(array_unique($prices));
    if (empty($prices)) {
        return;
    }

    if (class_exists('Tribe__Events__API') && method_exists('Tribe__Events__API', 'update_event_cost')) {
        Tribe__Events__API::update_event_cost($event_id, $prices);
    }

    delete_post_meta($event_id, '_EventCost');
    foreach ($prices as $price) {
        add_post_meta($event_id, '_EventCost', $price);
    }

    $numeric_prices = array_map('floatval', $prices);
    if (!empty($numeric_prices)) {
        update_post_meta($event_id, '_EventCostMin', (string) min($numeric_prices));
        update_post_meta($event_id, '_EventCostMax', (string) max($numeric_prices));
    }

    $modified_fields = get_post_meta($event_id, '_tribe_modified_fields', true);
    if (!is_array($modified_fields)) {
        $modified_fields = array();
    }
    $modified_fields['_EventCost'] = current_time('timestamp');
    update_post_meta($event_id, '_tribe_modified_fields', $modified_fields);
}

function tec_rb_update_event_cost_from_ticket_ids($event_id, $ticket_ids) {
    if (!$event_id || empty($ticket_ids)) {
        return;
    }
    $prices = array();
    foreach ($ticket_ids as $ticket_id) {
        $ticket_id = absint($ticket_id);
        if (!$ticket_id) {
            continue;
        }
        $price = get_post_meta($ticket_id, '_price', true);
        if ($price === '' || $price === null) {
            $price = get_post_meta($ticket_id, '_regular_price', true);
        }
        $normalized = tec_rb_normalize_price_value($price);
        if ($normalized === null) {
            continue;
        }
        $prices[] = $normalized;
    }
    tec_rb_update_event_cost_from_prices($event_id, $prices);
}

function tec_rb_sync_custom_tables($event_id) {
    if (!$event_id || !class_exists('\\TEC\\Events\\Custom_Tables\\V1\\Provider')) {
        return;
    }
    if (!\TEC\Events\Custom_Tables\V1\Provider::is_active()) {
        return;
    }

    if (class_exists('\\TEC\\Events\\Custom_Tables\\V1\\Models\\Event')) {
        $event_model = \TEC\Events\Custom_Tables\V1\Models\Event::find($event_id, 'post_id');
        if ($event_model instanceof \TEC\Events\Custom_Tables\V1\Models\Event) {
            try {
                $event_model->occurrences()->save_occurrences();
                return;
            } catch (Exception $e) {
                // Fall back to repository update below.
            }
        }
    }

    if (class_exists('\\TEC\\Events\\Custom_Tables\\V1\\Repository\\Events') && function_exists('tribe')) {
        $repo = tribe(\TEC\Events\Custom_Tables\V1\Repository\Events::class);
        if ($repo && method_exists($repo, 'update')) {
            $repo->update($event_id, array());
        }
    }
}

function tec_rb_refresh_event($event_id, $event_args) {
    if (!$event_id) {
        return;
    }
    if (class_exists('Tribe__Events__API') && method_exists('Tribe__Events__API', 'updateEvent')) {
        Tribe__Events__API::updateEvent($event_id, $event_args);
    } elseif (function_exists('tribe_update_event')) {
        tribe_update_event($event_id, $event_args);
    } else {
        tec_rb_touch_event($event_id);
    }
    clean_post_cache($event_id);
}

function tec_rb_force_publish_event($event_id) {
    if (!$event_id) {
        return;
    }
    $post = get_post($event_id);
    if (!$post instanceof WP_Post) {
        return;
    }
    wp_update_post(array(
        'ID' => $event_id,
        'post_title' => $post->post_title,
        'post_content' => $post->post_content,
        'post_excerpt' => $post->post_excerpt,
        'post_status' => 'publish',
        'post_author' => $post->post_author,
        'post_type' => $post->post_type,
        'post_date' => $post->post_date,
        'post_date_gmt' => $post->post_date_gmt,
        'edit_date' => true,
    ));
    clean_post_cache($event_id);
}

function tec_rb_refresh_ticket_caches($event_id, $provider, $ticket_ids = array()) {
    if (!$event_id) {
        return;
    }
    if (function_exists('tribe_tickets')) {
        $provider_obj = tribe_tickets($provider);
        if ($provider_obj && method_exists($provider_obj, 'clear_ticket_cache_for_post')) {
            $provider_obj->clear_ticket_cache_for_post($event_id);
        }
    }
    if (class_exists('\\Tribe\\Tickets\\Events\\Views\\V2\\Models\\Tickets')) {
        \Tribe\Tickets\Events\Views\V2\Models\Tickets::regenerate_caches((int) $event_id);
        foreach ($ticket_ids as $ticket_id) {
            \Tribe\Tickets\Events\Views\V2\Models\Tickets::regenerate_caches((int) $ticket_id);
        }
    }
}

function tec_rb_post_process_tickets($event_id, $provider, $ticket_ids = array()) {
    if (!$event_id || !function_exists('tribe')) {
        return;
    }
    $handler = tribe('tickets.handler');
    if (!$handler) {
        return;
    }
    $provider_class = $provider;
    if (class_exists('Tribe__Tickets__Status__Manager')) {
        $status = tribe('tickets.status');
        if ($status && method_exists($status, 'get_provider_class_from_slug')) {
            $resolved = $status->get_provider_class_from_slug($provider);
            if (is_string($resolved) && $resolved !== '') {
                $provider_class = $resolved;
            }
        }
    }

    $settings = array(
        'default_provider' => $provider_class,
    );
    if (method_exists($handler, 'save_form_settings')) {
        $handler->save_form_settings($event_id, $settings);
    }

    if (!empty($ticket_ids) && method_exists($handler, 'save_order')) {
        $order = array();
        $index = 0;
        foreach ($ticket_ids as $ticket_id) {
            $order[(int) $ticket_id] = array('order' => $index);
            $index++;
        }
        $handler->save_order($event_id, $order);
    }

    do_action('tribe_tickets_save_post', get_post($event_id));
}

function tec_rb_create_events_tickets_handler() {
    check_ajax_referer('tec_rb_ajax', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    if (!function_exists('tribe_create_event')) {
        wp_send_json_error(array('message' => 'The Events Calendar API is not available.'));
    }

    $payload_raw = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : '';
    $payload = json_decode($payload_raw, true);
    if (!is_array($payload)) {
        wp_send_json_error(array('message' => 'Invalid payload.'));
    }

    $event_name = sanitize_text_field($payload['eventName'] ?? '');
    $start_date = sanitize_text_field($payload['startDate'] ?? '');
    $end_date = sanitize_text_field($payload['endDate'] ?? '');
    $schedule_mode = sanitize_text_field($payload['scheduleMode'] ?? 'recurring');
    $event_excerpt = wp_kses_post($payload['eventExcerpt'] ?? '');
    $event_description = wp_kses_post($payload['eventDescription'] ?? '');
    $event_website = esc_url_raw($payload['eventWebsite'] ?? '');
    $event_tags = $payload['eventTags'] ?? ($payload['eventTagsRaw'] ?? '');
    $venue_value = sanitize_text_field($payload['eventVenue'] ?? '');
    $organizer_value = sanitize_text_field($payload['eventOrganizer'] ?? '');
    $category_value = sanitize_text_field($payload['eventCategory'] ?? '');
    $series_id = isset($payload['eventSeries']) ? (int) $payload['eventSeries'] : 0;
    $featured_image_url = esc_url_raw($payload['eventFeaturedImage'] ?? '');
    $show_map_link = !empty($payload['showMapLink']);
    $hide_from_listings = !empty($payload['hideFromListings']);
    $sticky_in_month = !empty($payload['stickyInMonthView']);
    $allow_comments = !empty($payload['allowComments']);
    $show_attendees_list = !empty($payload['showAttendeesList']);
    $feature_event = !empty($payload['featureEvent']);
    $ticket_header_from_featured = !empty($payload['ticketHeaderFromFeatured']);
    if ($feature_event) {
        $sticky_in_month = true;
    }
    if ($feature_event) {
        $sticky_in_month = true;
    }
    $ticket_types = isset($payload['ticketTypes']) && is_array($payload['ticketTypes'])
        ? $payload['ticketTypes']
        : array();
    $shared_capacity = !empty($payload['sharedCapacity']) && count($ticket_types) > 1;
    $shared_capacity_total = isset($payload['sharedCapacityTotal']) ? (int) $payload['sharedCapacityTotal'] : 0;
    $waitlist_mode = sanitize_text_field($payload['waitlistMode'] ?? 'none');

    if (!empty($ticket_types)) {
        foreach ($ticket_types as &$ticket) {
            $is_free = !empty($ticket['isFree']);
            $price_raw = isset($ticket['price']) ? trim((string) $ticket['price']) : '';
            if ($price_raw === '' && !$is_free) {
                wp_send_json_error(array('message' => 'Ticket price is required unless the ticket is marked free.'));
            }
            if ($is_free && $price_raw === '') {
                $ticket['price'] = '0';
            }
        }
        unset($ticket);
    }

    $timezone = new DateTimeZone('America/New_York');
    $specific_dates = tec_rb_parse_specific_dates($payload['specificDates'] ?? array(), $timezone);

    if ($event_name === '') {
        wp_send_json_error(array('message' => 'Missing event name.'));
    }

    $start_dt = null;
    $end_dt = null;
    if ($schedule_mode === 'specific') {
        if (empty($specific_dates)) {
            wp_send_json_error(array('message' => 'No specific dates were selected.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[0], $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[count($specific_dates) - 1], $timezone);
    } else {
        if ($start_date === '' || $end_date === '') {
            wp_send_json_error(array('message' => 'Missing event date range.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
    }
    if (!$start_dt || !$end_dt) {
        wp_send_json_error(array('message' => 'Invalid date range.'));
    }

    $venue = null;
    $venue_id = (int) $venue_value;
    if ($venue_id) {
        $venue = get_post($venue_id);
    } elseif ($venue_value !== '') {
        $venue = tec_rb_find_post_by_title($venue_value, 'tribe_venue');
    }
    if ($venue_value !== '' && !$venue) {
        wp_send_json_error(array('message' => 'Venue not found: ' . $venue_value));
    }

    $organizer = null;
    $organizer_id = (int) $organizer_value;
    if ($organizer_id) {
        $organizer = get_post($organizer_id);
    } elseif ($organizer_value !== '') {
        $organizer = tec_rb_find_post_by_title($organizer_value, 'tribe_organizer');
    }
    if ($organizer_value !== '' && !$organizer) {
        wp_send_json_error(array('message' => 'Organizer not found: ' . $organizer_value));
    }

    $category_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
    $category_id = 0;
    if ($category_value !== '') {
        $category_id = (int) $category_value;
        if (!$category_id) {
            $category_id = tec_rb_find_term_id($category_value, $category_taxonomy);
        }
        if (!$category_id) {
            wp_send_json_error(array('message' => 'Event category not found: ' . $category_value));
        }
    }

    $recurrence_days = isset($payload['recurrenceDays']) && is_array($payload['recurrenceDays'])
        ? array_map('sanitize_text_field', $payload['recurrenceDays'])
        : array();
    $occurrences = isset($payload['occurrences']) && is_array($payload['occurrences'])
        ? $payload['occurrences']
        : array();

    if (empty($occurrences)) {
        wp_send_json_error(array('message' => 'No occurrences were provided.'));
    }

    $day_map = array(
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    );
    $selected_days = array();
    foreach ($recurrence_days as $day) {
        if (isset($day_map[$day])) {
            $selected_days[] = $day_map[$day];
        }
    }

    $provider = '';
    if (!empty($ticket_types)) {
        $provider = tec_rb_get_ticket_provider();
        if ($provider === '') {
            wp_send_json_error(array('message' => 'WooCommerce ticket provider not found.'));
        }
    }

    if ($schedule_mode === 'specific') {
        $instances = tec_rb_build_instances_from_dates($specific_dates, $occurrences, $timezone);
    } else {
        $instances = tec_rb_build_instances($start_dt, $end_dt, $selected_days, $occurrences, $timezone);
    }
    if (empty($instances)) {
        wp_send_json_error(array('message' => 'No events were generated. Check recurrence days and occurrences.'));
    }

    $found = array();
    $missing = array();
    $errors = array();
    $event_ids = array();
    $ticket_ids = array();
    $event_costs_input = tec_rb_collect_ticket_prices_from_input($ticket_types);

    foreach ($instances as $instance) {
        if ($instance['start_time'] === '' || $instance['end_time'] === '') {
            $missing[] = array('startDateTime' => $instance['start_datetime']);
            continue;
        }

        list($start_hour, $start_minute) = tec_rb_parse_time_parts($instance['start_time']);
        list($end_hour, $end_minute) = tec_rb_parse_time_parts($instance['end_time']);

        $slug = tec_rb_build_slug($event_name, $instance['date'], $instance['start_time'], $instance['occurrence_name'], $instance['occurrence_index']);

        $event_args = array(
            'post_title' => $event_name,
            'post_content' => $event_description,
            'post_excerpt' => $event_excerpt,
            'post_status' => 'publish',
            'post_name' => $slug,
            'comment_status' => $allow_comments ? 'open' : 'closed',
            'ping_status' => 'closed',
            'menu_order' => $sticky_in_month ? -1 : 0,
            'EventAllDay' => false,
            'EventStartDate' => $instance['date'],
            'EventEndDate' => $instance['date'],
            'EventStartHour' => $start_hour,
            'EventStartMinute' => $start_minute,
            'EventEndHour' => $end_hour,
            'EventEndMinute' => $end_minute,
            'EventShowMapLink' => $show_map_link,
            'EventShowMap' => $show_map_link,
            'EventURL' => $event_website,
        );
        if (!empty($event_costs_input)) {
            $event_args['EventCost'] = $event_costs_input;
        }

        if ($hide_from_listings) {
            $event_args['EventHideFromUpcoming'] = true;
        }

        if ($venue) {
            $event_args['Venue'] = array('VenueID' => $venue->ID);
        }

        if ($organizer) {
            $event_args['Organizer'] = array('OrganizerID' => $organizer->ID);
        }

        if ($featured_image_url !== '') {
            $attachment_id = attachment_url_to_postid($featured_image_url);
            if ($attachment_id) {
                $event_args['FeaturedImage'] = $attachment_id;
            } else {
                $event_args['FeaturedImage'] = $featured_image_url;
            }
        }

        $event_id = tribe_create_event($event_args);
        if (is_wp_error($event_id) || !$event_id) {
            $missing[] = array('startDateTime' => $instance['start_datetime']);
            continue;
        }

        $unique_slug = wp_unique_post_slug($slug, $event_id, 'publish', 'tribe_events', 0);
        wp_update_post(array('ID' => $event_id, 'post_name' => $unique_slug));

        if ($category_id) {
            wp_set_object_terms($event_id, array($category_id), $category_taxonomy, false);
        }

        if (!empty($event_tags)) {
            tec_rb_assign_event_tags($event_id, $event_tags, $errors);
        }

        if ($feature_event) {
            update_post_meta($event_id, '_tribe_featured', 1);
        } else {
            delete_post_meta($event_id, '_tribe_featured');
        }

        if (class_exists('\\Tribe\\Tickets\\Events\\Attendees_List')) {
            if ($show_attendees_list) {
                delete_post_meta($event_id, \Tribe\Tickets\Events\Attendees_List::HIDE_META_KEY);
            } else {
                update_post_meta($event_id, \Tribe\Tickets\Events\Attendees_List::HIDE_META_KEY, 1);
            }
        } else {
            if ($show_attendees_list) {
                delete_post_meta($event_id, '_tribe_hide_attendees_list');
            } else {
                update_post_meta($event_id, '_tribe_hide_attendees_list', 1);
            }
        }

        if ($ticket_header_from_featured) {
            $header_image_id = get_post_thumbnail_id($event_id);
            if (!$header_image_id && $featured_image_url !== '') {
                $header_image_id = attachment_url_to_postid($featured_image_url);
            }
            tec_rb_set_ticket_header_image($event_id, $header_image_id);
        } else {
            tec_rb_set_ticket_header_image($event_id, 0);
        }

        $event_ids[] = $event_id;
        $event_ticket_ids = array();

        if (!empty($ticket_types) && $provider !== '') {
            $attendee_presets = tec_rb_get_attendee_question_presets();
            $event_start_dt = DateTime::createFromFormat('Y-m-d H:i:s', $instance['start_datetime'], $timezone);
            $created_for_event = 0;
            $global_stock_level = $shared_capacity ? tec_rb_calculate_shared_capacity($ticket_types) : 0;
            if ($shared_capacity) {
                if ($shared_capacity_total > 0 && $shared_capacity_total < $global_stock_level) {
                    $global_stock_level = $shared_capacity_total;
                }
                if ($shared_capacity_total > $global_stock_level && $global_stock_level > 0) {
                    $shared_capacity_total = $global_stock_level;
                }
            }

            // Capture ticket IDs before creation; some repositories return non-scalar values from `create()`.
            // We'll query for the actual created ticket IDs after creation and use those everywhere (output, cache refresh, shared capacity).
            $ticket_ids_before = tec_rb_get_event_ticket_ids($event_id, $provider);
            $normalized_ticket_ids = array();

            // Pre-enable shared capacity on the event before ticket creation so the provider can persist the mode.
            if ($provider === 'woo' && $shared_capacity && $global_stock_level > 0) {
                update_post_meta($event_id, '_tribe_ticket_use_global_stock', 1);
                update_post_meta($event_id, '_tribe_ticket_global_stock_level', (string) $global_stock_level);
                update_post_meta($event_id, '_tribe_ticket_capacity', (string) $global_stock_level);

                $modified_fields = get_post_meta($event_id, '_tribe_modified_fields', true);
                if (!is_array($modified_fields)) {
                    $modified_fields = array();
                }
                $now = current_time('timestamp');
                $modified_fields['_tribe_ticket_capacity'] = $now;
                $modified_fields['_tribe_ticket_use_global_stock'] = $now;
                $modified_fields['_tribe_ticket_global_stock_level'] = $now;
                update_post_meta($event_id, '_tribe_modified_fields', $modified_fields);
            }

            foreach ($ticket_types as $ticket_index => $ticket) {
                $ticket_preset_key = isset($ticket['attendeePreset']) ? trim((string) $ticket['attendeePreset']) : '';
                $ticket_questions = array();
                if ($ticket_preset_key !== '') {
                    $preset_index = (int) $ticket_preset_key;
                    if (isset($attendee_presets[$preset_index]['questions']) && is_array($attendee_presets[$preset_index]['questions'])) {
                        $ticket_questions = $attendee_presets[$preset_index]['questions'];
                    }
                }
                $args = tec_rb_build_ticket_args(
                    $provider,
                    $ticket,
                    $event_id,
                    $event_start_dt,
                    $timezone,
                    ($shared_capacity && $global_stock_level > 0),
                    $global_stock_level,
                    $ticket_questions
                );
                $created_ticket = tribe_tickets($provider)->set_args($args)->create();
                $ticket_id = tec_rb_normalize_created_post_id($created_ticket);
                if ($ticket_id) {
                    tec_rb_apply_ticket_product_meta($ticket_id, $event_name, $instance, $ticket, $ticket_index + 1);
                    $normalized_ticket_ids[] = $ticket_id;
                }
            }

            $ticket_ids_after = tec_rb_get_event_ticket_ids($event_id, $provider);
            $new_ticket_ids = array();
            if (!empty($ticket_ids_after)) {
                $new_ticket_ids = array_values(array_diff($ticket_ids_after, $ticket_ids_before));
                if (empty($new_ticket_ids) && empty($ticket_ids_before)) {
                    $new_ticket_ids = $ticket_ids_after;
                }
            }
            if (empty($new_ticket_ids) && !empty($normalized_ticket_ids)) {
                $new_ticket_ids = $normalized_ticket_ids;
            }
            $new_ticket_ids = array_values(array_unique(array_map('intval', $new_ticket_ids)));
            $new_ticket_ids = array_values(array_filter($new_ticket_ids));
            sort($new_ticket_ids);

            $event_ticket_ids = $new_ticket_ids;
            $event_ticket_summaries = tec_rb_build_ticket_summaries($event_ticket_ids);
            $created_for_event = count($event_ticket_ids);

            if ($created_for_event > 0 && $provider === 'woo') {
                foreach ($event_ticket_ids as $created_ticket_id) {
                    tec_rb_finalize_woo_ticket($created_ticket_id, tec_rb_ticket_show_description($created_ticket_id));
                    tec_rb_normalize_ticket_sale_meta($created_ticket_id);
                }
            }
            if ($created_for_event > 0) {
                // Track all created ticket IDs so Delete Last Batch can remove them.
                $ticket_ids = array_merge($ticket_ids, $event_ticket_ids);

                tec_rb_mark_event_has_tickets($event_id, $provider);
                tec_rb_refresh_event($event_id, $event_args);
                tec_rb_force_publish_event($event_id);
                tec_rb_refresh_ticket_caches($event_id, $provider, $event_ticket_ids);
                tec_rb_post_process_tickets($event_id, $provider, $event_ticket_ids);
                $input_prices = tec_rb_collect_ticket_prices_from_input($ticket_types);
                if (!empty($input_prices)) {
                    tec_rb_update_event_cost_from_prices($event_id, $input_prices);
                } else {
                    tec_rb_update_event_cost_from_ticket_ids($event_id, $event_ticket_ids);
                }
                if ($provider === 'woo' && $shared_capacity && $global_stock_level > 0) {
                    // Must run after refresh/save hooks, as some save routines can reset stock mode.
                    tec_rb_enable_shared_capacity_for_event($event_id, $event_ticket_ids, $global_stock_level);
                    tec_rb_refresh_ticket_caches($event_id, $provider, $event_ticket_ids);
                }

                if ($waitlist_mode !== 'none' && !empty($event_ticket_ids)) {
                    tec_rb_set_ticket_waitlist($event_id, $waitlist_mode);
                }

                tec_rb_sync_custom_tables($event_id);
                if ($series_id) {
                    tec_rb_assign_event_to_series($event_id, $series_id);
                }
            }
        }

        $found[] = array(
            'id' => $event_id,
            'slug' => get_post_field('post_name', $event_id),
            'startDateTime' => $instance['start_datetime'],
            'ticketIds' => $event_ticket_ids,
            'tickets' => $event_ticket_summaries,
        );
    }

    $user_id = get_current_user_id();
    update_user_meta($user_id, 'tec_rb_last_batch', array(
        'event_ids' => $event_ids,
        'ticket_ids' => $ticket_ids,
        'created_at' => current_time('mysql'),
    ));

    wp_send_json_success(array(
        'found' => $found,
        'missing' => $missing,
        'errors' => $errors,
        'summary' => 'events created.',
        'eventCount' => count($found),
        'ticketCount' => count($ticket_ids),
        'ticketSummary' => 'tickets created.',
    ));
}

add_action('wp_ajax_tec_rb_create_events_tickets', 'tec_rb_create_events_tickets_handler');

function tec_rb_event_has_woo_tickets($event_id) {
    if (!$event_id) {
        return false;
    }
    $tickets = get_posts(array(
        'post_type' => 'product',
        'post_status' => array('publish', 'private'),
        'meta_key' => '_tribe_wooticket_for_event',
        'meta_value' => (string) $event_id,
        'fields' => 'ids',
        'posts_per_page' => 1,
        'no_found_rows' => true,
    ));
    return !empty($tickets);
}

function tec_rb_filter_has_tickets($has_tickets, $event) {
    if ($has_tickets) {
        return $has_tickets;
    }
    $event_id = 0;
    if (is_object($event) && isset($event->ID)) {
        $event_id = (int) $event->ID;
    } else {
        $event_id = (int) $event;
    }
    return tec_rb_event_has_woo_tickets($event_id);
}

add_filter('tribe_events_has_tickets', 'tec_rb_filter_has_tickets', 10, 2);
add_filter('tribe_tickets_has_tickets', 'tec_rb_filter_has_tickets', 10, 2);

function tec_rb_debug_post_snapshot($post_id) {
    $post_id = absint($post_id);
    if (!$post_id) {
        return null;
    }
    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        return null;
    }
    $meta_raw = get_post_meta($post_id);
    $meta = array();
    foreach ($meta_raw as $key => $values) {
        $cleaned = array_map('maybe_unserialize', $values);
        $meta[$key] = count($cleaned) === 1 ? $cleaned[0] : $cleaned;
    }
    $terms = array();
    if (function_exists('wp_get_object_terms')) {
        if ($post->post_type === 'product') {
            $terms['product_type'] = wp_get_object_terms($post_id, 'product_type', array('fields' => 'names'));
            $terms['product_visibility'] = wp_get_object_terms($post_id, 'product_visibility', array('fields' => 'names'));
        }
        if ($post->post_type === 'tribe_events') {
            $terms['tribe_events_cat'] = wp_get_object_terms($post_id, 'tribe_events_cat', array('fields' => 'names'));
            $terms['post_tag'] = wp_get_object_terms($post_id, 'post_tag', array('fields' => 'names'));
        }
    }
    return array(
        'id' => $post_id,
        'post_type' => $post->post_type,
        'post_status' => $post->post_status,
        'post_title' => $post->post_title,
        'post_name' => $post->post_name,
        'post_date' => $post->post_date,
        'post_modified' => $post->post_modified,
        'meta' => $meta,
        'terms' => $terms,
    );
}

function tec_rb_debug_event_snapshot($event_id) {
    $snapshot = tec_rb_debug_post_snapshot($event_id);
    if (!$snapshot) {
        return null;
    }

    $snapshot['default_provider_meta'] = get_post_meta($event_id, '_tribe_default_ticket_provider', true);
    if (class_exists('Tribe__Tickets__Tickets')) {
        $snapshot['default_provider'] = Tribe__Tickets__Tickets::get_event_ticket_provider($event_id);
        $tickets = Tribe__Tickets__Tickets::get_all_event_tickets($event_id);
        $snapshot['tickets'] = array();
        foreach ($tickets as $ticket) {
            if (!$ticket instanceof Tribe__Tickets__Ticket_Object) {
                continue;
            }
            $snapshot['tickets'][] = array(
                'id' => $ticket->ID,
                'provider_class' => $ticket->provider_class,
                'name' => $ticket->name,
                'start_date' => $ticket->start_date,
                'start_time' => $ticket->start_time,
                'end_date' => $ticket->end_date,
                'end_time' => $ticket->end_time,
                'capacity' => $ticket->capacity,
                'stock' => method_exists($ticket, 'stock') ? $ticket->stock() : null,
                'date_in_range' => method_exists($ticket, 'date_in_range') ? $ticket->date_in_range('now') : null,
            );
        }
    }

    $snapshot['flags'] = array(
        'has_tickets' => function_exists('tribe_events_has_tickets') ? tribe_events_has_tickets($event_id) : null,
        'in_date_window' => function_exists('tribe_tickets_is_current_time_in_date_window')
            ? tribe_tickets_is_current_time_in_date_window($event_id)
            : null,
    );

    $snapshot['event_cost'] = function_exists('tribe_get_cost') ? tribe_get_cost($event_id, true) : null;
    $snapshot['event_cost_raw'] = get_post_meta($event_id, '_EventCost', false);

    if (class_exists('\\TEC\\Events\\Custom_Tables\\V1\\Models\\Occurrence')) {
        try {
            $snapshot['occurrences_count'] = \TEC\Events\Custom_Tables\V1\Models\Occurrence::where('post_id', '=', $event_id)->count();
        } catch (Exception $e) {
            $snapshot['occurrences_count'] = null;
        }
    }

    return $snapshot;
}

function tec_rb_debug_compare_handler() {
    check_ajax_referer('tec_rb_ajax', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    $manual_event_id = absint($_POST['manual_event_id'] ?? 0);
    $plugin_event_id = absint($_POST['plugin_event_id'] ?? 0);
    $pairs_raw = isset($_POST['ticket_pairs']) ? wp_unslash($_POST['ticket_pairs']) : '';
    $pairs = array();
    if (is_string($pairs_raw) && $pairs_raw !== '') {
        $decoded = json_decode($pairs_raw, true);
        if (is_array($decoded)) {
            $pairs = $decoded;
        }
    }

    // Backwards-compatible single-pair params.
    if (empty($pairs)) {
        $manual_ticket_id = absint($_POST['manual_ticket_id'] ?? 0);
        $plugin_ticket_id = absint($_POST['plugin_ticket_id'] ?? 0);
        if ($manual_ticket_id || $plugin_ticket_id) {
            $pairs[] = array(
                'manual_ticket_id' => $manual_ticket_id,
                'plugin_ticket_id' => $plugin_ticket_id,
            );
        }
    }

    $ticket_pairs = array();
    foreach ($pairs as $pair) {
        if (!is_array($pair)) {
            continue;
        }
        $manual_ticket_id = absint($pair['manual_ticket_id'] ?? 0);
        $plugin_ticket_id = absint($pair['plugin_ticket_id'] ?? 0);
        if (!$manual_ticket_id && !$plugin_ticket_id) {
            continue;
        }
        $ticket_pairs[] = array(
            'manual_ticket_id' => $manual_ticket_id,
            'plugin_ticket_id' => $plugin_ticket_id,
            'manual_ticket' => tec_rb_debug_post_snapshot($manual_ticket_id),
            'plugin_ticket' => tec_rb_debug_post_snapshot($plugin_ticket_id),
        );
    }

    $payload = array(
        'generated_at' => current_time('mysql'),
        'manual_event' => tec_rb_debug_event_snapshot($manual_event_id),
        'plugin_event' => tec_rb_debug_event_snapshot($plugin_event_id),
        'ticket_pairs' => $ticket_pairs,
    );

    wp_send_json_success($payload);
}
add_action('wp_ajax_tec_rb_debug_compare', 'tec_rb_debug_compare_handler');

function tec_rb_delete_last_batch_handler() {
    check_ajax_referer('tec_rb_ajax', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    $user_id = get_current_user_id();
    $batch = get_user_meta($user_id, 'tec_rb_last_batch', true);
    if (!is_array($batch) || (empty($batch['event_ids']) && empty($batch['ticket_ids']))) {
        wp_send_json_error(array('message' => 'No batch found to delete.'));
    }

    $deleted_events = 0;
    $deleted_tickets = 0;

    $ticket_ids = array();
    if (!empty($batch['ticket_ids'])) {
        $ticket_ids = array_map('intval', (array) $batch['ticket_ids']);
    }
    if (!empty($batch['event_ids'])) {
        foreach ((array) $batch['event_ids'] as $event_id) {
            $event_id = (int) $event_id;
            if (!$event_id) {
                continue;
            }
            $event_ticket_ids = tec_rb_get_event_ticket_ids($event_id, 'woo');
            if (!empty($event_ticket_ids)) {
                $ticket_ids = array_merge($ticket_ids, $event_ticket_ids);
            }
        }
    }
    $ticket_ids = array_values(array_unique(array_filter($ticket_ids)));

    if (!empty($ticket_ids)) {
        foreach ($ticket_ids as $ticket_id) {
            $deleted = wp_delete_post((int) $ticket_id, true);
            if ($deleted) {
                $deleted_tickets++;
            }
        }
    }

    if (!empty($batch['event_ids'])) {
        foreach ($batch['event_ids'] as $event_id) {
            $deleted = wp_delete_post((int) $event_id, true);
            if ($deleted) {
                $deleted_events++;
            }
        }
    }

    delete_user_meta($user_id, 'tec_rb_last_batch');

    wp_send_json_success(array(
        'found' => array(),
        'missing' => array(),
        'errors' => array(),
        'summary' => 'events deleted.',
        'eventCount' => $deleted_events,
        'ticketCount' => $deleted_tickets,
        'ticketSummary' => 'tickets deleted.',
    ));
}

add_action('wp_ajax_tec_rb_delete_last_batch', 'tec_rb_delete_last_batch_handler');

function tec_rb_create_events_handler() {
    check_ajax_referer('tec_rb_ajax', 'nonce');
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    if (!function_exists('tribe_create_event')) {
        wp_send_json_error(array('message' => 'The Events Calendar API is not available.'));
    }

    $payload_raw = isset($_POST['payload']) ? wp_unslash($_POST['payload']) : '';
    $payload = json_decode($payload_raw, true);
    if (!is_array($payload)) {
        wp_send_json_error(array('message' => 'Invalid payload.'));
    }

    $event_name = sanitize_text_field($payload['eventName'] ?? '');
    $start_date = sanitize_text_field($payload['startDate'] ?? '');
    $end_date = sanitize_text_field($payload['endDate'] ?? '');
    $schedule_mode = sanitize_text_field($payload['scheduleMode'] ?? 'recurring');
    $event_excerpt = wp_kses_post($payload['eventExcerpt'] ?? '');
    $event_description = wp_kses_post($payload['eventDescription'] ?? '');
    $event_website = esc_url_raw($payload['eventWebsite'] ?? '');
    $event_tags = $payload['eventTags'] ?? ($payload['eventTagsRaw'] ?? '');
    $venue_value = sanitize_text_field($payload['eventVenue'] ?? '');
    $organizer_value = sanitize_text_field($payload['eventOrganizer'] ?? '');
    $category_value = sanitize_text_field($payload['eventCategory'] ?? '');
    $series_id = isset($payload['eventSeries']) ? (int) $payload['eventSeries'] : 0;
    $featured_image_url = esc_url_raw($payload['eventFeaturedImage'] ?? '');
    $show_map_link = !empty($payload['showMapLink']);
    $hide_from_listings = !empty($payload['hideFromListings']);
    $sticky_in_month = !empty($payload['stickyInMonthView']);
    $allow_comments = !empty($payload['allowComments']);
    $show_attendees_list = !empty($payload['showAttendeesList']);
    $feature_event = !empty($payload['featureEvent']);
    $ticket_header_from_featured = !empty($payload['ticketHeaderFromFeatured']);

    $timezone = new DateTimeZone('America/New_York');
    $specific_dates = tec_rb_parse_specific_dates($payload['specificDates'] ?? array(), $timezone);

    if ($event_name === '') {
        wp_send_json_error(array('message' => 'Missing event name.'));
    }

    $start_dt = null;
    $end_dt = null;
    if ($schedule_mode === 'specific') {
        if (empty($specific_dates)) {
            wp_send_json_error(array('message' => 'No specific dates were selected.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[0], $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $specific_dates[count($specific_dates) - 1], $timezone);
    } else {
        if ($start_date === '' || $end_date === '') {
            wp_send_json_error(array('message' => 'Missing event date range.'));
        }
        $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
        $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
    }
    if (!$start_dt || !$end_dt) {
        wp_send_json_error(array('message' => 'Invalid date range.'));
    }

    $venue = null;
    $venue_id = (int) $venue_value;
    if ($venue_id) {
        $venue = get_post($venue_id);
    } elseif ($venue_value !== '') {
        $venue = tec_rb_find_post_by_title($venue_value, 'tribe_venue');
    }
    if ($venue_value !== '' && !$venue) {
        wp_send_json_error(array('message' => 'Venue not found: ' . $venue_value));
    }

    $organizer = null;
    $organizer_id = (int) $organizer_value;
    if ($organizer_id) {
        $organizer = get_post($organizer_id);
    } elseif ($organizer_value !== '') {
        $organizer = tec_rb_find_post_by_title($organizer_value, 'tribe_organizer');
    }
    if ($organizer_value !== '' && !$organizer) {
        wp_send_json_error(array('message' => 'Organizer not found: ' . $organizer_value));
    }

    $category_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
    $category_id = 0;
    if ($category_value !== '') {
        $category_id = (int) $category_value;
        if (!$category_id) {
            $category_id = tec_rb_find_term_id($category_value, $category_taxonomy);
        }
        if (!$category_id) {
            wp_send_json_error(array('message' => 'Event category not found: ' . $category_value));
        }
    }

    $recurrence_days = isset($payload['recurrenceDays']) && is_array($payload['recurrenceDays'])
        ? array_map('sanitize_text_field', $payload['recurrenceDays'])
        : array();
    $occurrences = isset($payload['occurrences']) && is_array($payload['occurrences'])
        ? $payload['occurrences']
        : array();

    if (empty($occurrences)) {
        wp_send_json_error(array('message' => 'No occurrences were provided.'));
    }

    $day_map = array(
        'sun' => 0,
        'mon' => 1,
        'tue' => 2,
        'wed' => 3,
        'thu' => 4,
        'fri' => 5,
        'sat' => 6,
    );
    $selected_days = array();
    foreach ($recurrence_days as $day) {
        if (isset($day_map[$day])) {
            $selected_days[] = $day_map[$day];
        }
    }

    if ($schedule_mode === 'specific') {
        $instances = tec_rb_build_instances_from_dates($specific_dates, $occurrences, $timezone);
    } else {
        $instances = tec_rb_build_instances($start_dt, $end_dt, $selected_days, $occurrences, $timezone);
    }

    if (empty($instances)) {
        wp_send_json_error(array('message' => 'No events were generated. Check recurrence days and occurrences.'));
    }

    $found = array();
    $missing = array();
    foreach ($instances as $instance) {
        $occ_name = sanitize_text_field($instance['occurrence_name'] ?? '');
        $start_time = sanitize_text_field($instance['start_time'] ?? '');
        $end_time = sanitize_text_field($instance['end_time'] ?? '');
        $date = sanitize_text_field($instance['date'] ?? '');
        $occ_index = (int) ($instance['occurrence_index'] ?? 1);

        if ($start_time === '' || $end_time === '') {
            $missing[] = array('startDateTime' => $date . ' ' . $start_time);
            continue;
        }

        list($start_hour, $start_minute) = tec_rb_parse_time_parts($start_time);
        list($end_hour, $end_minute) = tec_rb_parse_time_parts($end_time);

        $slug = tec_rb_build_slug($event_name, $date, $start_time, $occ_name, $occ_index);

        $event_args = array(
            'post_title' => $event_name,
            'post_content' => $event_description,
            'post_excerpt' => $event_excerpt,
            'post_status' => 'publish',
            'post_name' => $slug,
            'comment_status' => $allow_comments ? 'open' : 'closed',
            'ping_status' => 'closed',
            'menu_order' => $sticky_in_month ? -1 : 0,
            'EventAllDay' => false,
            'EventStartDate' => $date,
            'EventEndDate' => $date,
            'EventStartHour' => $start_hour,
            'EventStartMinute' => $start_minute,
            'EventEndHour' => $end_hour,
            'EventEndMinute' => $end_minute,
            'EventShowMapLink' => $show_map_link,
            'EventShowMap' => $show_map_link,
            'EventURL' => $event_website,
        );

                if ($hide_from_listings) {
                    $event_args['EventHideFromUpcoming'] = true;
                }

                if ($venue) {
                    $event_args['Venue'] = array('VenueID' => $venue->ID);
                }

                if ($organizer) {
                    $event_args['Organizer'] = array('OrganizerID' => $organizer->ID);
                }

                if ($featured_image_url !== '') {
                    $attachment_id = attachment_url_to_postid($featured_image_url);
                    if ($attachment_id) {
                        $event_args['FeaturedImage'] = $attachment_id;
                    } else {
                        $event_args['FeaturedImage'] = $featured_image_url;
                    }
                }

                $event_id = tribe_create_event($event_args);
                if (is_wp_error($event_id) || !$event_id) {
                    $missing[] = array('startDateTime' => $date . ' ' . $start_time);
                    continue;
                }

                $unique_slug = wp_unique_post_slug($slug, $event_id, 'publish', 'tribe_events', 0);
                wp_update_post(array('ID' => $event_id, 'post_name' => $unique_slug));

                if ($category_id) {
                    wp_set_object_terms($event_id, array($category_id), $category_taxonomy, false);
                }

        if (!empty($event_tags)) {
            tec_rb_assign_event_tags($event_id, $event_tags);
        }

                if ($feature_event) {
                    update_post_meta($event_id, '_tribe_featured', 1);
                } else {
                    delete_post_meta($event_id, '_tribe_featured');
                }

                if (class_exists('\\Tribe\\Tickets\\Events\\Attendees_List')) {
                    if ($show_attendees_list) {
                        delete_post_meta($event_id, \Tribe\Tickets\Events\Attendees_List::HIDE_META_KEY);
                    } else {
                        update_post_meta($event_id, \Tribe\Tickets\Events\Attendees_List::HIDE_META_KEY, 1);
                    }
                } else {
                    if ($show_attendees_list) {
                        delete_post_meta($event_id, '_tribe_hide_attendees_list');
                    } else {
                        update_post_meta($event_id, '_tribe_hide_attendees_list', 1);
                    }
                }

                if ($ticket_header_from_featured) {
                    $header_image_id = get_post_thumbnail_id($event_id);
                    if (!$header_image_id && $featured_image_url !== '') {
                        $header_image_id = attachment_url_to_postid($featured_image_url);
                    }
                    tec_rb_set_ticket_header_image($event_id, $header_image_id);
                } else {
                    tec_rb_set_ticket_header_image($event_id, 0);
                }

                if ($series_id) {
                    tec_rb_sync_custom_tables($event_id);
                    tec_rb_assign_event_to_series($event_id, $series_id);
                }

        $found[] = array(
            'id' => $event_id,
            'slug' => get_post_field('post_name', $event_id),
            'startDateTime' => $date . ' ' . $start_time,
        );
    }

    wp_send_json_success(array(
        'found' => $found,
        'missing' => $missing,
        'errors' => array(),
        'summary' => 'events created.',
    ));
}

add_action('wp_ajax_tec_rb_create_events', 'tec_rb_create_events_handler');

function tec_rb_get_menu_position() {
    $default_position = 58;
    if (!is_admin()) {
        return $default_position;
    }
    global $menu;
    if (!is_array($menu)) {
        return $default_position;
    }
    foreach ($menu as $index => $item) {
        if (!isset($item[2])) {
            continue;
        }
        if ($item[2] === 'edit.php?post_type=tribe_events' || $item[2] === 'tribe_events') {
            return $index + 0.1;
        }
    }
    return $default_position;
}

function tec_rb_register_admin_pages() {
    add_menu_page(
        'TicketPup',
        'TicketPup',
        'edit_posts',
        'tec-recurring-bookings',
        'tec_rb_render_admin_page',
        tec_rb_get_menu_icon(),
        tec_rb_get_menu_position()
    );

    add_submenu_page(
        'tec-recurring-bookings',
        'TicketPup Settings',
        'Settings',
        'manage_options',
        'tec-recurring-bookings-settings',
        'tec_rb_render_settings_page'
    );

    add_submenu_page(
        null,
        'TicketPup Debug',
        'Debug Compare',
        'manage_options',
        'tec-recurring-bookings-debug',
        'tec_rb_render_debug_page'
    );
}

add_action('admin_menu', 'tec_rb_register_admin_pages', 20);

function tec_rb_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    tec_rb_enqueue_assets();
    tec_rb_disable_admin_footer_on_page();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tec_rb_nonce']) && check_admin_referer('tec_rb_settings_save', 'tec_rb_nonce')) {
        $preset_names = $_POST['tec_rb_presets_name'] ?? array();
        $preset_data = $_POST['tec_rb_presets_data'] ?? array();
        $presets = array();
        $preset_errors = array();
        foreach ((array) $preset_names as $index => $name) {
            $name = sanitize_text_field(wp_unslash($name));
            $json = trim((string) wp_unslash($preset_data[$index] ?? ''));
            if ($name === '' && $json === '') {
                continue;
            }
            if ($name === '' || $json === '') {
                $preset_errors[] = 'Each preset needs both a name and JSON data.';
                continue;
            }
            $decoded = json_decode($json, true);
            if (!is_array($decoded)) {
                $preset_errors[] = 'Preset "' . esc_html($name) . '" has invalid JSON.';
                continue;
            }
            $presets[] = array(
                'name' => $name,
                'data' => $decoded,
            );
        }
        update_option('tec_rb_presets', wp_json_encode($presets));
        $waitlist_mode = isset($_POST['tec_rb_default_waitlist_mode']) ? sanitize_text_field(wp_unslash($_POST['tec_rb_default_waitlist_mode'])) : 'none';
        $allowed_waitlist = array('none', 'presale_or_sold_out', 'before_sale', 'sold_out');
        if (!in_array($waitlist_mode, $allowed_waitlist, true)) {
            $waitlist_mode = 'none';
        }
        $defaults = array(
            'hide_from_listings' => !empty($_POST['tec_rb_default_hide_from_listings']),
            'sticky_in_month' => !empty($_POST['tec_rb_default_sticky_in_month']),
            'show_map_link' => !empty($_POST['tec_rb_default_show_map_link']),
            'show_attendees_list' => !empty($_POST['tec_rb_default_show_attendees_list']),
            'allow_comments' => !empty($_POST['tec_rb_default_allow_comments']),
            'feature_event' => !empty($_POST['tec_rb_default_feature_event']),
            'event_website_enabled' => !empty($_POST['tec_rb_default_event_website']),
            'waitlist_mode' => $waitlist_mode,
            'attendee_collection' => isset($_POST['tec_rb_default_attendee_collection']) ? sanitize_text_field(wp_unslash($_POST['tec_rb_default_attendee_collection'])) : 'none',
            'attendee_collection_preset' => isset($_POST['tec_rb_default_attendee_preset']) ? sanitize_text_field(wp_unslash($_POST['tec_rb_default_attendee_preset'])) : '',
        );
        update_option('tec_rb_defaults', $defaults);

        $ticket_suggestions_raw = isset($_POST['tec_rb_ticket_name_suggestions']) ? wp_unslash($_POST['tec_rb_ticket_name_suggestions']) : '';
        $ticket_suggestions = tec_rb_parse_list($ticket_suggestions_raw, array());
        update_option('tec_rb_ticket_name_suggestions', implode("\n", $ticket_suggestions));

        $question_labels = isset($_POST['tec_rb_question_label']) ? (array) wp_unslash($_POST['tec_rb_question_label']) : array();
        $question_types = isset($_POST['tec_rb_question_type']) ? (array) wp_unslash($_POST['tec_rb_question_type']) : array();
        $question_required = isset($_POST['tec_rb_question_required']) ? (array) wp_unslash($_POST['tec_rb_question_required']) : array();
        $question_placeholders = isset($_POST['tec_rb_question_placeholder']) ? (array) wp_unslash($_POST['tec_rb_question_placeholder']) : array();
        $question_descriptions = isset($_POST['tec_rb_question_description']) ? (array) wp_unslash($_POST['tec_rb_question_description']) : array();
        $question_options = isset($_POST['tec_rb_question_options']) ? (array) wp_unslash($_POST['tec_rb_question_options']) : array();
        $question_multiline = isset($_POST['tec_rb_question_multiline']) ? (array) wp_unslash($_POST['tec_rb_question_multiline']) : array();

        $allowed_question_types = array('text', 'email', 'telephone', 'url', 'date', 'select', 'radio', 'checkbox');
        $attendee_questions = array();
        foreach ($question_labels as $index => $label_raw) {
            $label = sanitize_text_field($label_raw);
            if ($label === '') {
                continue;
            }
            $type = isset($question_types[$index]) ? sanitize_text_field($question_types[$index]) : 'text';
            if (in_array($type, array('birth', 'datetime'), true)) {
                $type = 'date';
            }
            if (!in_array($type, $allowed_question_types, true)) {
                $type = 'text';
            }
            $required = !empty($question_required[$index]) ? 'on' : '';
            $placeholder = isset($question_placeholders[$index]) ? sanitize_text_field($question_placeholders[$index]) : '';
            $description = isset($question_descriptions[$index]) ? sanitize_textarea_field($question_descriptions[$index]) : '';
            $options_raw = isset($question_options[$index]) ? $question_options[$index] : '';
            $options = array();
            if (in_array($type, array('radio', 'checkbox', 'select'), true)) {
                $options = array_values(array_filter(array_map('sanitize_text_field', array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $options_raw)))));
            }
            $extra = array();
            if ($type === 'text' && !empty($question_multiline[$index])) {
                $extra['multiline'] = 'yes';
            }
            if (!empty($options)) {
                $extra['options'] = $options;
            }

            $field_order = count($attendee_questions);
            $attendee_questions[] = array(
                'id' => 0,
                'type' => $type,
                'required' => $required,
                'label' => $label,
                'slug' => sanitize_title($label),
                'extra' => $extra,
                'classes' => array(),
                'attributes' => array(),
                'placeholder' => $placeholder,
                'description' => $description,
                'field_order' => $field_order,
            );
        }
        update_option('tec_rb_attendee_questions', $attendee_questions);

        $attendee_preset_names = isset($_POST['tec_rb_attendee_preset_name']) ? (array) wp_unslash($_POST['tec_rb_attendee_preset_name']) : array();
        $attendee_preset_data = isset($_POST['tec_rb_attendee_preset_data']) ? (array) wp_unslash($_POST['tec_rb_attendee_preset_data']) : array();
        $attendee_presets = array();
        foreach ($attendee_preset_names as $index => $preset_name_raw) {
            $preset_name = sanitize_text_field($preset_name_raw);
            $preset_json = isset($attendee_preset_data[$index]) ? trim((string) $attendee_preset_data[$index]) : '';
            if ($preset_name === '' && $preset_json === '') {
                continue;
            }
            if ($preset_name === '' || $preset_json === '') {
                $preset_errors[] = 'Each attendee question preset needs both a name and JSON data.';
                continue;
            }
            $decoded = json_decode($preset_json, true);
            if (!is_array($decoded)) {
                $preset_errors[] = 'Attendee preset "' . esc_html($preset_name) . '" has invalid JSON.';
                continue;
            }
            $attendee_presets[] = array(
                'name' => $preset_name,
                'questions' => $decoded,
            );
        }
        update_option('tec_rb_attendee_question_presets', $attendee_presets);
        if (!empty($preset_errors)) {
            echo '<div class="error"><p>' . implode('<br>', array_map('esc_html', array_unique($preset_errors))) . '</p></div>';
        } else {
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
    }

    $presets = tec_rb_get_presets();
    $defaults = tec_rb_get_default_options();
    $ticket_suggestions = tec_rb_get_ticket_name_suggestions();
    $attendee_questions = tec_rb_get_attendee_questions();
    $attendee_question_presets = tec_rb_get_attendee_question_presets();
    $has_attendee_presets = !empty($attendee_question_presets);
    $venues_url = admin_url('edit.php?post_type=tribe_venue');
    $organizers_url = admin_url('edit.php?post_type=tribe_organizer');
    $categories_url = admin_url('edit-tags.php?taxonomy=tribe_events_cat&post_type=tribe_events');
    $series_url = admin_url('edit.php?post_type=tribe_event_series');
    $debug_url = admin_url('admin.php?page=tec-recurring-bookings-debug');
    ?>
    <div class="wrap tec-wrap">
        <div class="tec-app">
        <?php echo tec_rb_render_topbar('TicketPup Settings', false, true); ?>
        <div class="tec-settings-content">
        <style>
          .tec-settings-table {
            overflow-x: auto;
            max-width: 100%;
          }
          #tec-rb-attendee-questions th,
          #tec-rb-attendee-questions td {
            vertical-align: top;
          }
          #tec-rb-attendee-questions input[type="text"],
          #tec-rb-attendee-questions select,
          #tec-rb-attendee-questions textarea {
            width: 100%;
            min-width: 140px;
            box-sizing: border-box;
          }
          .tec-question-options {
            display: none;
          }
          .tec-question-options.is-visible {
            display: table-cell;
          }
          .tec-rb-preset {
            border: 1px solid #dcdcde;
            border-radius: 6px;
            background: #fff;
            padding: 12px;
            margin-bottom: 12px;
          }
          .tec-rb-preset-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
          }
          .tec-rb-preset-json {
            margin-top: 8px;
          }
          .tec-rb-preset-json.is-hidden {
            display: none;
          }
          .tec-settings-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
          }
        </style>
        <h2>Create Event Data</h2>
        <p>Venues, organizers, categories, and series are pulled directly from The Events Calendar.</p>
        <p class="tec-inline">
            <a class="button" href="<?php echo esc_url($venues_url); ?>">Manage Venues</a>
            <a class="button" href="<?php echo esc_url($organizers_url); ?>">Manage Organizers</a>
            <a class="button" href="<?php echo esc_url($categories_url); ?>">Manage Categories</a>
            <a class="button" href="<?php echo esc_url($series_url); ?>">Manage Series</a>
        </p>
        <div class="tec-divider tec-divider--section"></div>
        <form method="post">
            <?php wp_nonce_field('tec_rb_settings_save', 'tec_rb_nonce'); ?>
            <h2>Default Options</h2>
            <p>Set the default state of extra options and waitlist mode for new bookings.</p>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Extra Options</th>
                    <td>
                        <label><input type="checkbox" name="tec_rb_default_hide_from_listings" <?php checked(!empty($defaults['hide_from_listings'])); ?> /> Hide from event listings</label><br />
                        <label><input type="checkbox" name="tec_rb_default_sticky_in_month" <?php checked(!empty($defaults['sticky_in_month'])); ?> /> Sticky in Month View</label><br />
                        <label><input type="checkbox" name="tec_rb_default_show_map_link" <?php checked(!empty($defaults['show_map_link'])); ?> /> Show Map Link</label><br />
                        <label><input type="checkbox" name="tec_rb_default_show_attendees_list" <?php checked(!empty($defaults['show_attendees_list'])); ?> /> Show attendees list on event page</label><br />
                        <label><input type="checkbox" name="tec_rb_default_allow_comments" <?php checked(!empty($defaults['allow_comments'])); ?> /> Allow Comments</label><br />
                        <label><input type="checkbox" name="tec_rb_default_feature_event" <?php checked(!empty($defaults['feature_event'])); ?> /> Feature this event</label><br />
                        <label><input type="checkbox" name="tec_rb_default_event_website" <?php checked(!empty($defaults['event_website_enabled'])); ?> /> Add event website</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Waitlist Default</th>
                    <td>
                        <label><input type="radio" name="tec_rb_default_waitlist_mode" value="none" <?php checked(($defaults['waitlist_mode'] ?? 'none') === 'none'); ?> /> No waitlist</label><br />
                        <label><input type="radio" name="tec_rb_default_waitlist_mode" value="presale_or_sold_out" <?php checked(($defaults['waitlist_mode'] ?? 'none') === 'presale_or_sold_out'); ?> /> When tickets are on pre-sale or sold out</label><br />
                        <label><input type="radio" name="tec_rb_default_waitlist_mode" value="before_sale" <?php checked(($defaults['waitlist_mode'] ?? 'none') === 'before_sale'); ?> /> Before tickets go on sale</label><br />
                        <label><input type="radio" name="tec_rb_default_waitlist_mode" value="sold_out" <?php checked(($defaults['waitlist_mode'] ?? 'none') === 'sold_out'); ?> /> When tickets are sold out</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Default Attendee Collection</th>
                    <td>
                        <select name="tec_rb_default_attendee_collection">
                            <option value="none" <?php selected(($defaults['attendee_collection'] ?? 'none') === 'none'); ?>>No Individual Attendee Collection</option>
                            <option value="allow" <?php selected(($defaults['attendee_collection'] ?? 'none') === 'allow'); ?> <?php disabled(!$has_attendee_presets); ?>>Allow Individual Attendee Collection</option>
                            <option value="require" <?php selected(($defaults['attendee_collection'] ?? 'none') === 'require'); ?> <?php disabled(!$has_attendee_presets); ?>>Require Individual Attendee Collection</option>
                        </select>
                        <?php if (!$has_attendee_presets) : ?>
                            <p class="description">Create an attendee question preset to enable attendee collection options.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="tec-rb-default-attendee-preset-row" style="<?php echo (!$has_attendee_presets || !in_array(($defaults['attendee_collection'] ?? 'none'), array('allow', 'require'), true)) ? 'display:none;' : ''; ?>">
                    <th scope="row">Default Attendee Preset</th>
                    <td>
                        <select name="tec_rb_default_attendee_preset">
                            <option value="">Select preset</option>
                            <?php foreach ($attendee_question_presets as $index => $preset) : ?>
                                <?php $preset_label = $preset['name'] ?? ('Preset ' . ($index + 1)); ?>
                                <option value="<?php echo esc_attr($index); ?>" <?php selected((string) ($defaults['attendee_collection_preset'] ?? '') === (string) $index); ?>>
                                    <?php echo esc_html($preset_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <div class="tec-divider tec-divider--section"></div>
            <h2>Ticket Name Suggestions</h2>
            <p>Type a suggestion and press Enter to save it. These will appear in the Ticket Name dropdown.</p>
            <div class="tec-tags-row" data-tec-tags-picker>
              <div class="tec-control">
                <input class="tec-input" type="text" data-tags-entry placeholder="Type a ticket name and press Enter" />
              </div>
              <button class="button" type="button" data-tags-add>Add</button>
              <div class="tec-tags-list" data-tags-list></div>
            </div>
            <textarea class="tec-input is-hidden" rows="4" name="tec_rb_ticket_name_suggestions" data-tags-input data-tags-separator="newline"><?php echo esc_textarea(implode("\n", $ticket_suggestions)); ?></textarea>

            <div class="tec-divider tec-divider--section"></div>
            <h2>Attendee Question Presets</h2>
            <p>Saved presets. Click “Edit” to update the name or JSON.</p>
            <div id="tec-rb-attendee-presets">
                <?php if (!empty($attendee_question_presets)) : ?>
                    <?php foreach ($attendee_question_presets as $preset) : ?>
                        <div class="tec-rb-preset">
                            <div class="tec-rb-preset-row">
                                <input class="regular-text" type="text" name="tec_rb_attendee_preset_name[]" value="<?php echo esc_attr($preset['name'] ?? ''); ?>" placeholder="Preset name" readonly />
                                <button class="button tec-rb-toggle-json" type="button">Edit</button>
                                <button class="button tec-rb-remove-attendee-preset" type="button">Remove</button>
                            </div>
                            <div class="tec-rb-preset-json is-hidden">
                                <textarea class="large-text" rows="6" name="tec_rb_attendee_preset_data[]"><?php echo esc_textarea(wp_json_encode($preset['questions'] ?? array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="description tec-rb-empty">No presets have been set.</p>
                <?php endif; ?>
            </div>
            <p><button class="button" type="button" id="tec-rb-open-attendee-builder">Add attendee question preset</button></p>
            <div id="tec-rb-attendee-builder" class="tec-rb-builder is-hidden">
                <div class="tec-settings-table">
                <table class="widefat striped" id="tec-rb-attendee-questions">
                    <thead>
                        <tr>
                            <th>Label</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Placeholder</th>
                            <th>Description</th>
                            <th class="tec-question-options">Options</th>
                            <th>Multiline</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><input class="regular-text" type="text" name="tec_rb_question_label[]" placeholder="Question label" /></td>
                            <td>
                                <select name="tec_rb_question_type[]">
                                    <option value="text">Text</option>
                                    <option value="email">Email</option>
                                    <option value="telephone">Telephone</option>
                                    <option value="url">URL</option>
                                    <option value="date">Date</option>
                                    <option value="select">Dropdown</option>
                                    <option value="radio">Radio</option>
                                    <option value="checkbox">Checkbox</option>
                                </select>
                            </td>
                            <td><input type="checkbox" name="tec_rb_question_required[]" /></td>
                            <td><input class="regular-text" type="text" name="tec_rb_question_placeholder[]" /></td>
                            <td><input class="regular-text" type="text" name="tec_rb_question_description[]" /></td>
                            <td class="tec-question-options"><input class="regular-text" type="text" name="tec_rb_question_options[]" placeholder="Option 1, Option 2" /></td>
                            <td><input type="checkbox" name="tec_rb_question_multiline[]" /></td>
                            <td><button class="button tec-rb-remove-question" type="button">Remove</button></td>
                        </tr>
                    </tbody>
                </table>
                </div>
                <p class="tec-inline">
                    <button class="button" type="button" id="tec-rb-add-question">Add Question</button>
                    <button class="button" type="button" id="tec-rb-save-attendee-preset">Save attendee questions preset</button>
                    <button class="button" type="button" id="tec-rb-close-attendee-builder">Close builder</button>
                </p>
            </div>
            <div class="tec-divider tec-divider--section"></div>
            <h2>Event & Ticket Presets</h2>
            <p>Useful when you’re generating the same type of tickets and events regularly and want to keep consistency.</p>
            <div id="tec-rb-presets">
                <?php if (!empty($presets)) : ?>
                    <?php foreach ($presets as $preset) : ?>
                        <div class="tec-rb-preset">
                            <div class="tec-rb-preset-row">
                                <input class="regular-text" type="text" name="tec_rb_presets_name[]" value="<?php echo esc_attr($preset['name'] ?? ''); ?>" placeholder="Preset name" readonly />
                                <button class="button tec-rb-toggle-json" type="button">Edit</button>
                                <button class="button tec-rb-remove-preset" type="button">Remove</button>
                            </div>
                            <div class="tec-rb-preset-json is-hidden">
                                <textarea class="large-text" rows="6" name="tec_rb_presets_data[]"><?php echo esc_textarea(wp_json_encode($preset['data'] ?? array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="description tec-rb-empty">No presets have been set.</p>
                <?php endif; ?>
            </div>
            <p><button class="button" type="button" id="tec-rb-add-preset">Add Preset</button></p>
            <div class="tec-divider tec-divider--section"></div>
            <h2>Troubleshooting</h2>
            <p>Use this tool to compare saved events and tickets by ID.</p>
            <p><a class="button" href="<?php echo esc_url($debug_url); ?>">Debug events &amp; tickets with ID numbers</a></p>

            <?php submit_button('Save Settings'); ?>
        </form>
        </div>
        <script>
          (function() {
            const container = document.getElementById('tec-rb-presets');
            const addButton = document.getElementById('tec-rb-add-preset');
            const attendeeContainer = document.getElementById('tec-rb-attendee-presets');
            const builder = document.getElementById('tec-rb-attendee-builder');
            const openBuilderButton = document.getElementById('tec-rb-open-attendee-builder');
            const closeBuilderButton = document.getElementById('tec-rb-close-attendee-builder');
            const defaultCollectionSelect = document.querySelector('select[name="tec_rb_default_attendee_collection"]');
            const defaultPresetRow = document.getElementById('tec-rb-default-attendee-preset-row');
            const hasAttendeePresets = <?php echo $has_attendee_presets ? 'true' : 'false'; ?>;

            const removeEmpty = (rootEl) => {
              if (!rootEl) return;
              const empty = rootEl.querySelector('.tec-rb-empty');
              if (empty) {
                empty.remove();
              }
            };

            const togglePresetEdit = (row) => {
              if (!row) return;
              const panel = row.querySelector('.tec-rb-preset-json');
              const input = row.querySelector('input[type="text"]');
              const isEditing = !row.classList.contains('is-editing');
              row.classList.toggle('is-editing', isEditing);
              if (panel) {
                panel.classList.toggle('is-hidden', !isEditing);
              }
              if (input) {
                input.readOnly = !isEditing;
                if (isEditing) {
                  input.focus();
                }
              }
            };

            const toggleDefaultPresetRow = () => {
              if (!defaultPresetRow || !defaultCollectionSelect) {
                return;
              }
              const show = hasAttendeePresets && (defaultCollectionSelect.value === 'allow' || defaultCollectionSelect.value === 'require');
              defaultPresetRow.style.display = show ? '' : 'none';
            };
            if (defaultCollectionSelect) {
              defaultCollectionSelect.addEventListener('change', toggleDefaultPresetRow);
              toggleDefaultPresetRow();
            }

            if (container && addButton) {
              const buildRow = () => {
                const row = document.createElement('div');
                row.className = 'tec-rb-preset';
                row.innerHTML = `
                  <div class="tec-rb-preset-row">
                    <input class="regular-text" type="text" name="tec_rb_presets_name[]" placeholder="Preset name" />
                    <button class="button tec-rb-toggle-json" type="button">Edit</button>
                    <button class="button tec-rb-remove-preset" type="button">Remove</button>
                  </div>
                  <div class="tec-rb-preset-json">
                    <textarea class="large-text" rows="6" name="tec_rb_presets_data[]" placeholder="{}"></textarea>
                  </div>
                `;
                return row;
              };
              addButton.addEventListener('click', () => {
                removeEmpty(container);
                const row = buildRow();
                container.appendChild(row);
                togglePresetEdit(row);
              });
              container.addEventListener('click', (event) => {
                const target = event.target;
                if (target && target.classList.contains('tec-rb-remove-preset')) {
                  const row = target.closest('.tec-rb-preset');
                  if (row) {
                    row.remove();
                    if (!container.querySelector('.tec-rb-preset') && !container.querySelector('.tec-rb-empty')) {
                      container.insertAdjacentHTML('beforeend', '<p class="description tec-rb-empty">No presets have been set.</p>');
                    }
                  }
                }
                if (target && target.classList.contains('tec-rb-toggle-json')) {
                  const row = target.closest('.tec-rb-preset');
                  togglePresetEdit(row);
                }
              });
            }

            if (attendeeContainer) {
              attendeeContainer.addEventListener('click', (event) => {
                const target = event.target;
                if (target && target.classList.contains('tec-rb-remove-attendee-preset')) {
                  const row = target.closest('.tec-rb-preset');
                  if (row) {
                    row.remove();
                    if (!attendeeContainer.querySelector('.tec-rb-preset') && !attendeeContainer.querySelector('.tec-rb-empty')) {
                      attendeeContainer.insertAdjacentHTML('beforeend', '<p class="description tec-rb-empty">No presets have been set.</p>');
                    }
                  }
                }
                if (target && target.classList.contains('tec-rb-toggle-json')) {
                  const row = target.closest('.tec-rb-preset');
                  togglePresetEdit(row);
                }
              });
            }

            const questionsTable = document.getElementById('tec-rb-attendee-questions');
            const addQuestionButton = document.getElementById('tec-rb-add-question');
            const saveAttendeePresetButton = document.getElementById('tec-rb-save-attendee-preset');
            if (questionsTable && addQuestionButton) {
              const tbody = questionsTable.querySelector('tbody');
              const slugify = (value) => String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
              const collectQuestions = () => {
                const rows = Array.from(questionsTable.querySelectorAll('tbody tr'));
                const questions = [];
                rows.forEach((row) => {
                  const labelInput = row.querySelector('input[name=\"tec_rb_question_label[]\"]');
                  const label = labelInput ? labelInput.value.trim() : '';
                  if (!label) return;
                  const typeSelect = row.querySelector('select[name=\"tec_rb_question_type[]\"]');
                  const type = typeSelect ? typeSelect.value : 'text';
                  const required = row.querySelector('input[name=\"tec_rb_question_required[]\"]')?.checked ? 'on' : '';
                  const placeholder = row.querySelector('input[name=\"tec_rb_question_placeholder[]\"]')?.value?.trim() || '';
                  const description = row.querySelector('input[name=\"tec_rb_question_description[]\"]')?.value?.trim() || '';
                  const optionsRaw = row.querySelector('input[name=\"tec_rb_question_options[]\"]')?.value || '';
                  const options = ['radio', 'checkbox', 'select'].includes(type)
                    ? optionsRaw.split(/\\r\\n|\\r|\\n|,/).map((value) => value.trim()).filter(Boolean)
                    : [];
                  const extra = {};
                  const multiline = row.querySelector('input[name=\"tec_rb_question_multiline[]\"]')?.checked;
                  if (type === 'text' && multiline) {
                    extra.multiline = 'yes';
                  }
                  if (options.length) {
                    extra.options = options;
                  }
                  questions.push({
                    id: 0,
                    type,
                    required,
                    label,
                    slug: slugify(label),
                    extra,
                    classes: [],
                    attributes: [],
                    placeholder,
                    description,
                    field_order: questions.length,
                  });
                });
                return questions;
              };
              const updateRowVisibility = (row) => {
                if (!row) return;
                const typeSelect = row.querySelector('select[name="tec_rb_question_type[]"]');
                const optionsCell = row.querySelector('.tec-question-options');
                const optionsInput = optionsCell ? optionsCell.querySelector('input') : null;
                const multilineCheckbox = row.querySelector('input[name="tec_rb_question_multiline[]"]');
                const type = typeSelect ? typeSelect.value : 'text';
                const showOptions = ['radio', 'checkbox', 'select'].includes(type);
                if (optionsCell) {
                  optionsCell.classList.toggle('is-visible', showOptions);
                }
                if (!showOptions && optionsInput) {
                  optionsInput.value = '';
                }
                if (multilineCheckbox) {
                  multilineCheckbox.disabled = type !== 'text';
                  if (type !== 'text') {
                    multilineCheckbox.checked = false;
                  }
                }
              };
              const buildQuestionRow = () => {
                const row = document.createElement('tr');
                row.innerHTML = `
                  <td><input class="regular-text" type="text" name="tec_rb_question_label[]" placeholder="Question label" /></td>
                  <td>
                    <select name="tec_rb_question_type[]">
                      <option value="text">Text</option>
                      <option value="email">Email</option>
                      <option value="telephone">Telephone</option>
                      <option value="url">URL</option>
                      <option value="date">Date</option>
                      <option value="select">Dropdown</option>
                      <option value="radio">Radio</option>
                      <option value="checkbox">Checkbox</option>
                    </select>
                  </td>
                  <td><input type="checkbox" name="tec_rb_question_required[]" /></td>
                  <td><input class="regular-text" type="text" name="tec_rb_question_placeholder[]" /></td>
                  <td><input class="regular-text" type="text" name="tec_rb_question_description[]" /></td>
                  <td class="tec-question-options"><input class="regular-text" type="text" name="tec_rb_question_options[]" placeholder="Option 1, Option 2" /></td>
                  <td><input type="checkbox" name="tec_rb_question_multiline[]" /></td>
                  <td><button class="button tec-rb-remove-question" type="button">Remove</button></td>
                `;
                updateRowVisibility(row);
                const typeSelect = row.querySelector('select[name="tec_rb_question_type[]"]');
                if (typeSelect) {
                  typeSelect.addEventListener('change', () => updateRowVisibility(row));
                }
                return row;
              };
              const resetBuilder = () => {
                if (!tbody) return;
                tbody.innerHTML = "";
                tbody.appendChild(buildQuestionRow());
              };
              if (openBuilderButton && builder) {
                openBuilderButton.addEventListener('click', () => {
                  resetBuilder();
                  builder.classList.remove('is-hidden');
                });
              }
              if (closeBuilderButton && builder) {
                closeBuilderButton.addEventListener('click', () => {
                  builder.classList.add('is-hidden');
                });
              }
              addQuestionButton.addEventListener('click', () => {
                if (tbody) {
                  tbody.appendChild(buildQuestionRow());
                }
              });
              questionsTable.addEventListener('click', (event) => {
                const target = event.target;
                if (target && target.classList.contains('tec-rb-remove-question')) {
                  const row = target.closest('tr');
                  if (row) {
                    row.remove();
                  }
                }
              });
              questionsTable.querySelectorAll('tbody tr').forEach((row) => {
                const typeSelect = row.querySelector('select[name="tec_rb_question_type[]"]');
                if (typeSelect) {
                  typeSelect.addEventListener('change', () => updateRowVisibility(row));
                }
                updateRowVisibility(row);
              });
              if (saveAttendeePresetButton && attendeeContainer) {
                saveAttendeePresetButton.addEventListener('click', () => {
                  const name = window.prompt('Preset name');
                  if (!name) {
                    return;
                  }
                  const questions = collectQuestions();
                  if (!questions.length) {
                    window.alert('Add at least one question before saving a preset.');
                    return;
                  }
                  removeEmpty(attendeeContainer);
                  const row = document.createElement('div');
                  row.className = 'tec-rb-preset';
                  row.innerHTML = `
                    <div class="tec-rb-preset-row">
                      <input class="regular-text" type="text" name="tec_rb_attendee_preset_name[]" placeholder="Preset name" readonly />
                      <button class="button tec-rb-toggle-json" type="button">Edit</button>
                      <button class="button tec-rb-remove-attendee-preset" type="button">Remove</button>
                    </div>
                    <div class="tec-rb-preset-json is-hidden">
                      <textarea class="large-text" rows="6" name="tec_rb_attendee_preset_data[]" placeholder="[]"></textarea>
                    </div>
                  `;
                  const nameInput = row.querySelector('input[name="tec_rb_attendee_preset_name[]"]');
                  const jsonArea = row.querySelector('textarea[name="tec_rb_attendee_preset_data[]"]');
                  if (nameInput) {
                    nameInput.value = name;
                    nameInput.readOnly = true;
                  }
                  if (jsonArea) {
                    jsonArea.value = JSON.stringify(questions, null, 2);
                  }
                  attendeeContainer.appendChild(row);
                });
              }
            }
          })();
        </script>
        </div>
    </div>
    <?php
}
