<?php
/**
 * Plugin Name: TEC.dog
 * Description: Create recurring events and tickets for The Events Calendar.
 * Version: 0.1.0
 * Author: Alex Burgess
 * Author URI: https://thisisa.intentionallyblank.page
 * Requires at least: 6.9
 */

if (!defined('ABSPATH')) {
    exit;
}

function tec_rb_enqueue_assets() {
    $base_url = plugin_dir_url(__FILE__);
    $css_path = __DIR__ . '/assets/css/tec-recurring-bookings.css';
    $js_path = __DIR__ . '/assets/js/tec-recurring-bookings.js';
    $css_ver = file_exists($css_path) ? filemtime($css_path) : '0.1.0';
    $js_ver = file_exists($js_path) ? filemtime($js_path) : '0.1.0';

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
        )
    );
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
    // The Events Calendar adds its own footer copy; hide it on TEC.dog pages.
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
    echo '<div class="wrap">';
    echo tec_rb_render_form();
    echo '</div>';
}

function tec_rb_render_debug_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    tec_rb_enqueue_assets();
    tec_rb_disable_admin_footer_on_page();
    echo '<div class="wrap">';
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
    $recurrence_days = isset($payload['recurrenceDays']) && is_array($payload['recurrenceDays'])
        ? array_map('sanitize_text_field', $payload['recurrenceDays'])
        : array();
    $occurrences = isset($payload['occurrences']) && is_array($payload['occurrences'])
        ? $payload['occurrences']
        : array();

    if ($event_name === '' || $start_date === '' || $end_date === '') {
        wp_send_json_error(array('message' => 'Missing event name or date range.'));
    }

    $timezone = new DateTimeZone('America/New_York');
    $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
    $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
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
    $venue_name = sanitize_text_field($payload['eventVenue'] ?? '');
    $organizer_name = sanitize_text_field($payload['eventOrganizer'] ?? '');
    $category_name = sanitize_text_field($payload['eventCategory'] ?? '');
    $ticket_types = isset($payload['ticketTypes']) && is_array($payload['ticketTypes'])
        ? $payload['ticketTypes']
        : array();
    $shared_capacity = !empty($payload['sharedCapacity']) && count($ticket_types) > 1;
    $shared_capacity_total = isset($payload['sharedCapacityTotal']) ? (int) $payload['sharedCapacityTotal'] : 0;

    if ($event_name === '' || $start_date === '' || $end_date === '') {
        wp_send_json_error(array('message' => 'Missing event name or date range.'));
    }

    $timezone = new DateTimeZone('America/New_York');
    $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
    $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
    if (!$start_dt || !$end_dt) {
        wp_send_json_error(array('message' => 'Invalid date range.'));
    }

    if ($venue_name !== '') {
        $venue = tec_rb_find_post_by_title($venue_name, 'tribe_venue');
        if (!$venue) {
            wp_send_json_error(array('message' => 'Venue not found: ' . $venue_name));
        }
    }

    if ($organizer_name !== '') {
        $organizer = tec_rb_find_post_by_title($organizer_name, 'tribe_organizer');
        if (!$organizer) {
            wp_send_json_error(array('message' => 'Organizer not found: ' . $organizer_name));
        }
    }

    $category_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
    if ($category_name !== '') {
        $category_id = tec_rb_find_term_id($category_name, $category_taxonomy);
        if (!$category_id) {
            wp_send_json_error(array('message' => 'Event category not found: ' . $category_name));
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

    $instances = tec_rb_build_instances($start_dt, $end_dt, $selected_days, $occurrences, $timezone);
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

function tec_rb_build_ticket_args($provider, $ticket, $event_id, $event_start_dt, $timezone, $shared_capacity = false, $shared_capacity_level = 0) {
    $name = sanitize_text_field($ticket['name'] ?? '');
    if ($name === '') {
        $name = 'Ticket';
    }
    $description = wp_kses_post($ticket['description'] ?? '');
    $price = tec_rb_parse_price($ticket['price'] ?? '');
    $quantity = trim((string) ($ticket['quantity'] ?? ''));
    $capacity = $quantity === '' ? -1 : max(0, (int) $quantity);
    $show_description = !empty($ticket['showDescription']);

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
            '_tribe_tickets_ar_iac' => 'required',
            '_tribe_tickets_meta' => array(),
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
    update_post_meta($ticket_id, '_tribe_tickets_ar_iac', 'required');
    update_post_meta($ticket_id, '_tribe_tickets_meta', array());
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
    $event_excerpt = wp_kses_post($payload['eventExcerpt'] ?? '');
    $event_description = wp_kses_post($payload['eventDescription'] ?? '');
    $event_website = esc_url_raw($payload['eventWebsite'] ?? '');
    $event_tags = sanitize_text_field($payload['eventTags'] ?? '');
    $venue_name = sanitize_text_field($payload['eventVenue'] ?? '');
    $organizer_name = sanitize_text_field($payload['eventOrganizer'] ?? '');
    $category_name = sanitize_text_field($payload['eventCategory'] ?? '');
    $featured_image_url = esc_url_raw($payload['eventFeaturedImage'] ?? '');
    $show_map_link = !empty($payload['showMapLink']);
    $hide_from_listings = !empty($payload['hideFromListings']);
    $sticky_in_month = !empty($payload['stickyInMonthView']);
    $allow_comments = !empty($payload['allowComments']);
    $ticket_types = isset($payload['ticketTypes']) && is_array($payload['ticketTypes'])
        ? $payload['ticketTypes']
        : array();
    $shared_capacity = !empty($payload['sharedCapacity']) && count($ticket_types) > 1;
    $shared_capacity_total = isset($payload['sharedCapacityTotal']) ? (int) $payload['sharedCapacityTotal'] : 0;
    $waitlist_mode = sanitize_text_field($payload['waitlistMode'] ?? 'none');

    if ($event_name === '' || $start_date === '' || $end_date === '') {
        wp_send_json_error(array('message' => 'Missing event name or date range.'));
    }

    $timezone = new DateTimeZone('America/New_York');
    $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
    $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
    if (!$start_dt || !$end_dt) {
        wp_send_json_error(array('message' => 'Invalid date range.'));
    }

    if ($venue_name !== '') {
        $venue = tec_rb_find_post_by_title($venue_name, 'tribe_venue');
        if (!$venue) {
            wp_send_json_error(array('message' => 'Venue not found: ' . $venue_name));
        }
    } else {
        $venue = null;
    }

    if ($organizer_name !== '') {
        $organizer = tec_rb_find_post_by_title($organizer_name, 'tribe_organizer');
        if (!$organizer) {
            wp_send_json_error(array('message' => 'Organizer not found: ' . $organizer_name));
        }
    } else {
        $organizer = null;
    }

    $category_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
    $category_id = 0;
    if ($category_name !== '') {
        $category_id = tec_rb_find_term_id($category_name, $category_taxonomy);
        if (!$category_id) {
            wp_send_json_error(array('message' => 'Event category not found: ' . $category_name));
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

    $instances = tec_rb_build_instances($start_dt, $end_dt, $selected_days, $occurrences, $timezone);
    if (empty($instances)) {
        wp_send_json_error(array('message' => 'No events were generated. Check recurrence days and occurrences.'));
    }

    $found = array();
    $missing = array();
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

        if ($event_tags !== '') {
            $tags = array_filter(array_map('trim', explode(',', $event_tags)));
            if (!empty($tags)) {
                $tag_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAG_TAXONOMY : 'post_tag';
                wp_set_object_terms($event_id, $tags, $tag_taxonomy, false);
            }
        }

        $event_ids[] = $event_id;
        $event_ticket_ids = array();

        if (!empty($ticket_types) && $provider !== '') {
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

            foreach ($ticket_types as $ticket) {
                $args = tec_rb_build_ticket_args(
                    $provider,
                    $ticket,
                    $event_id,
                    $event_start_dt,
                    $timezone,
                    ($shared_capacity && $global_stock_level > 0),
                    $global_stock_level
                );
                $created_ticket = tribe_tickets($provider)->set_args($args)->create();
                $ticket_id = tec_rb_normalize_created_post_id($created_ticket);
                if ($ticket_id) {
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
        'errors' => array(),
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
    $event_excerpt = wp_kses_post($payload['eventExcerpt'] ?? '');
    $event_description = wp_kses_post($payload['eventDescription'] ?? '');
    $event_website = esc_url_raw($payload['eventWebsite'] ?? '');
    $event_tags = sanitize_text_field($payload['eventTags'] ?? '');
    $venue_name = sanitize_text_field($payload['eventVenue'] ?? '');
    $organizer_name = sanitize_text_field($payload['eventOrganizer'] ?? '');
    $category_name = sanitize_text_field($payload['eventCategory'] ?? '');
    $featured_image_url = esc_url_raw($payload['eventFeaturedImage'] ?? '');
    $show_map_link = !empty($payload['showMapLink']);
    $hide_from_listings = !empty($payload['hideFromListings']);
    $sticky_in_month = !empty($payload['stickyInMonthView']);
    $allow_comments = !empty($payload['allowComments']);

    if ($event_name === '' || $start_date === '' || $end_date === '') {
        wp_send_json_error(array('message' => 'Missing event name or date range.'));
    }

    $timezone = new DateTimeZone('America/New_York');
    $start_dt = DateTime::createFromFormat('Y-m-d', $start_date, $timezone);
    $end_dt = DateTime::createFromFormat('Y-m-d', $end_date, $timezone);
    if (!$start_dt || !$end_dt) {
        wp_send_json_error(array('message' => 'Invalid date range.'));
    }

    if ($venue_name !== '') {
        $venue = tec_rb_find_post_by_title($venue_name, 'tribe_venue');
        if (!$venue) {
            wp_send_json_error(array('message' => 'Venue not found: ' . $venue_name));
        }
    } else {
        $venue = null;
    }

    if ($organizer_name !== '') {
        $organizer = tec_rb_find_post_by_title($organizer_name, 'tribe_organizer');
        if (!$organizer) {
            wp_send_json_error(array('message' => 'Organizer not found: ' . $organizer_name));
        }
    } else {
        $organizer = null;
    }

    $category_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAXONOMY : 'tribe_events_cat';
    $category_id = 0;
    if ($category_name !== '') {
        $category_id = tec_rb_find_term_id($category_name, $category_taxonomy);
        if (!$category_id) {
            wp_send_json_error(array('message' => 'Event category not found: ' . $category_name));
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

    $found = array();
    $missing = array();
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

                if ($start_time === '' || $end_time === '') {
                    $missing[] = array('startDateTime' => $date . ' ' . $start_time);
                    continue;
                }

                list($start_hour, $start_minute) = tec_rb_parse_time_parts($start_time);
                list($end_hour, $end_minute) = tec_rb_parse_time_parts($end_time);

                $slug = tec_rb_build_slug($event_name, $date, $start_time, $occ_name, $index + 1);

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

                if ($event_tags !== '') {
                    $tags = array_filter(array_map('trim', explode(',', $event_tags)));
                    if (!empty($tags)) {
                        $tag_taxonomy = class_exists('Tribe__Events__Main') ? Tribe__Events__Main::TAG_TAXONOMY : 'post_tag';
                        wp_set_object_terms($event_id, $tags, $tag_taxonomy, false);
                    }
                }

                $found[] = array(
                    'id' => $event_id,
                    'slug' => get_post_field('post_name', $event_id),
                    'startDateTime' => $date . ' ' . $start_time,
                );
            }
        }
        $cursor->modify('+1 day');
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
        'TEC.dog',
        'TEC.dog',
        'edit_posts',
        'tec-recurring-bookings',
        'tec_rb_render_admin_page',
        'dashicons-calendar-alt',
        tec_rb_get_menu_position()
    );

    add_submenu_page(
        'tec-recurring-bookings',
        'TEC.dog Settings',
        'Settings',
        'manage_options',
        'tec-recurring-bookings-settings',
        'tec_rb_render_settings_page'
    );

    add_submenu_page(
        'tec-recurring-bookings',
        'TEC.dog Debug',
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
    tec_rb_disable_admin_footer_on_page();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tec_rb_nonce']) && check_admin_referer('tec_rb_settings_save', 'tec_rb_nonce')) {
        update_option('tec_rb_venues', sanitize_textarea_field(wp_unslash($_POST['tec_rb_venues'] ?? '')));
        update_option('tec_rb_categories', sanitize_textarea_field(wp_unslash($_POST['tec_rb_categories'] ?? '')));
        update_option('tec_rb_organizers', sanitize_textarea_field(wp_unslash($_POST['tec_rb_organizers'] ?? '')));
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
        if (!empty($preset_errors)) {
            echo '<div class="error"><p>' . implode('<br>', array_map('esc_html', array_unique($preset_errors))) . '</p></div>';
        } else {
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
    }

    $venues = esc_textarea(get_option('tec_rb_venues', ''));
    $categories = esc_textarea(get_option('tec_rb_categories', ''));
    $organizers = esc_textarea(get_option('tec_rb_organizers', ''));
    $presets = tec_rb_get_presets();
    ?>
    <div class="wrap">
        <h1>TEC.dog Settings</h1>
        <p>Enter one value per line. These will be available in the form dropdowns.</p>
        <form method="post">
            <?php wp_nonce_field('tec_rb_settings_save', 'tec_rb_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="tec_rb_venues">Venue Names</label></th>
                    <td><textarea id="tec_rb_venues" name="tec_rb_venues" rows="6" class="large-text"><?php echo $venues; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="tec_rb_categories">Event Categories</label></th>
                    <td><textarea id="tec_rb_categories" name="tec_rb_categories" rows="6" class="large-text"><?php echo $categories; ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="tec_rb_organizers">Organizer Names</label></th>
                    <td><textarea id="tec_rb_organizers" name="tec_rb_organizers" rows="6" class="large-text"><?php echo $organizers; ?></textarea></td>
                </tr>
            </table>

            <h2>Presets</h2>
            <p>Create presets by pasting JSON built from the form payload. Each preset can be selected in the form dropdown.</p>
            <div id="tec-rb-presets">
                <?php if (!empty($presets)) : ?>
                    <?php foreach ($presets as $preset) : ?>
                        <div class="tec-rb-preset">
                            <input class="regular-text" type="text" name="tec_rb_presets_name[]" value="<?php echo esc_attr($preset['name'] ?? ''); ?>" placeholder="Preset name" />
                            <textarea class="large-text" rows="6" name="tec_rb_presets_data[]"><?php echo esc_textarea(wp_json_encode($preset['data'] ?? array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></textarea>
                            <button class="button tec-rb-remove-preset" type="button">Remove</button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                <div class="tec-rb-preset">
                    <input class="regular-text" type="text" name="tec_rb_presets_name[]" placeholder="Preset name" />
                    <textarea class="large-text" rows="6" name="tec_rb_presets_data[]" placeholder='{"eventName":"Example","startDate":"2026-02-05","endDate":"2026-02-06","recurrenceDays":["mon"],"occurrences":[{"name":"Morning","startTime":"9:00 AM","endTime":"10:00 AM"}],"ticketTypes":[{"name":"General","price":"25","quantity":"10"}]}'></textarea>
                    <button class="button tec-rb-remove-preset" type="button">Remove</button>
                </div>
            </div>
            <p><button class="button" type="button" id="tec-rb-add-preset">Add Preset</button></p>

            <?php submit_button('Save Settings'); ?>
        </form>
        <script>
          (function() {
            const container = document.getElementById('tec-rb-presets');
            const addButton = document.getElementById('tec-rb-add-preset');
            if (!container || !addButton) return;
            const buildRow = () => {
              const row = document.createElement('div');
              row.className = 'tec-rb-preset';
              row.innerHTML = `
                <input class="regular-text" type="text" name="tec_rb_presets_name[]" placeholder="Preset name" />
                <textarea class="large-text" rows="6" name="tec_rb_presets_data[]" placeholder="{}"></textarea>
                <button class="button tec-rb-remove-preset" type="button">Remove</button>
              `;
              return row;
            };
            addButton.addEventListener('click', () => {
              container.appendChild(buildRow());
            });
            container.addEventListener('click', (event) => {
              const target = event.target;
              if (target && target.classList.contains('tec-rb-remove-preset')) {
                const row = target.closest('.tec-rb-preset');
                if (row) {
                  row.remove();
                }
              }
            });
          })();
        </script>
    </div>
    <?php
}
