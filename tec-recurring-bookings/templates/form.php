<?php
$venues = function_exists('tec_rb_get_venues_list') ? tec_rb_get_venues_list() : array();
$categories = function_exists('tec_rb_get_categories_list') ? tec_rb_get_categories_list() : array();
$organizers = function_exists('tec_rb_get_organizers_list') ? tec_rb_get_organizers_list() : array();
$series_items = function_exists('tec_rb_get_series_list') ? tec_rb_get_series_list() : array();
$settings_url = function_exists('admin_url') ? admin_url('admin.php?page=tec-recurring-bookings-settings') : '#';
$venues_url = function_exists('admin_url') ? admin_url('edit.php?post_type=tribe_venue') : '#';
$organizers_url = function_exists('admin_url') ? admin_url('edit.php?post_type=tribe_organizer') : '#';
$categories_url = function_exists('admin_url') ? admin_url('edit-tags.php?taxonomy=tribe_events_cat&post_type=tribe_events') : '#';
$series_url = function_exists('admin_url') ? admin_url('edit.php?post_type=tribe_event_series') : '#';
?>

<div class="tec-app" data-tec-recurring-bookings>
  <div class="tec-topbar">
    <div class="tec-topbar-inner">
      <div class="tec-topbar-left">
        <span class="tec-logo" aria-hidden="true" style="width:36px;height:36px;display:inline-flex;overflow:hidden;color:#000;">
          <?php echo tec_rb_get_header_logo_svg(); ?>
        </span>
        <h1 class="tec-title">TicketPup</h1>
      </div>
      <div class="tec-header-actions">
        <div class="tec-control tec-control--select">
          <select class="tec-select" data-preset-select>
            <option value="">Select preset</option>
          </select>
        </div>
        <button class="tec-button-secondary" type="button" data-save-preset>Save as preset</button>
      </div>
    </div>
  </div>

  <form class="tec-form">
  <section class="tec-section">
    <p class="tec-section-title">Event Details</p>
    <p class="tec-section-desc">Information about the event</p>
    <div class="tec-grid tec-grid--stack">
      <div class="tec-field tec-field--full">
        <p class="tec-label">Event Name*</p>
        <div class="tec-control">
          <input class="tec-input tec-input--xl" name="event_name" type="text" placeholder="e.g. Summer Experience" />
        </div>
      </div>
      <label class="tec-checkbox tec-checkbox--inline">
        <input type="checkbox" name="feature_event" />
        <span>Feature this event</span>
      </label>
      <div class="tec-field">
        <p class="tec-label">Event Excerpt</p>
        <div class="tec-control">
          <input class="tec-input" name="event_excerpt" type="text" placeholder="e.g. A brief invitation to visit us and your guests" />
        </div>
      </div>
      <div class="tec-field tec-field--full">
        <p class="tec-label">Description*</p>
        <div class="tec-textarea-toolbar" data-rich-toolbar>
          <button class="tec-toolbar-button" type="button" data-format="bold"><strong>B</strong></button>
          <button class="tec-toolbar-button" type="button" data-format="italic"><em>I</em></button>
          <button class="tec-toolbar-button" type="button" data-format="underline"><span style="text-decoration: underline;">U</span></button>
          <button class="tec-toolbar-button" type="button" data-format="link">Link</button>
        </div>
        <div class="tec-control">
          <textarea class="tec-textarea" name="event_description" data-rich-text placeholder="e.g. Bring the family to ride the hayride. Create your adventure."></textarea>
        </div>
        <p class="tec-note">Supports HTML tags</p>
      </div>
      <div class="tec-row-fields">
        <div class="tec-field">
          <div class="tec-label-row">
            <p class="tec-label">Category*</p>
            <span class="tec-info-dot" data-tooltip="Manage categories in Events → Categories." aria-label="Manage categories in Events → Categories." tabindex="0">i</span>
          </div>
          <div class="tec-control tec-control--select">
            <select class="tec-select" name="event_category">
              <option value="" selected>Select category</option>
              <?php foreach ($categories as $category) : ?>
                <option value="<?php echo esc_attr($category['id']); ?>"><?php echo esc_html($category['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="tec-field">
          <div class="tec-label-row">
            <p class="tec-label">Organizer*</p>
            <span class="tec-info-dot" data-tooltip="Manage organizers in Events → Organizers." aria-label="Manage organizers in Events → Organizers." tabindex="0">i</span>
          </div>
          <div class="tec-control tec-control--select">
            <select class="tec-select" name="event_organizer">
              <option value="" selected>Select organizer</option>
              <?php foreach ($organizers as $organizer) : ?>
                <option value="<?php echo esc_attr($organizer['id']); ?>"><?php echo esc_html($organizer['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="tec-field">
          <div class="tec-label-row">
            <p class="tec-label">Venue*</p>
            <span class="tec-info-dot" data-tooltip="Manage venues in Events → Venues." aria-label="Manage venues in Events → Venues." tabindex="0">i</span>
          </div>
          <div class="tec-control tec-control--select">
            <select class="tec-select" name="event_venue">
              <option value="" selected>Select venue</option>
              <?php foreach ($venues as $venue) : ?>
                <option value="<?php echo esc_attr($venue['id']); ?>"><?php echo esc_html($venue['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="tec-field">
          <div class="tec-label-row">
            <p class="tec-label">Series</p>
            <span class="tec-info-dot" data-tooltip="Manage series in Events → Series." aria-label="Manage series in Events → Series." tabindex="0">i</span>
          </div>
          <div class="tec-control tec-control--select">
            <select class="tec-select" name="event_series">
              <option value="" selected>No series</option>
              <?php foreach ($series_items as $series) : ?>
                <option value="<?php echo esc_attr($series['id']); ?>"><?php echo esc_html($series['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="tec-field tec-field--full tec-field--tags">
        <p class="tec-label">Tags</p>
        <div class="tec-tags-row">
          <div class="tec-control">
            <input class="tec-input" type="text" data-tags-entry placeholder="Type a tag and press Enter" />
          </div>
          <div class="tec-tags-list" data-tags-list></div>
        </div>
        <input type="hidden" name="event_tags" data-tags-input />
      </div>
      <div class="tec-field tec-field--full">
        <p class="tec-label">Featured Image</p>
        <div class="tec-featured-row">
          <div class="tec-image-preview tec-image-preview--small" data-featured-image-preview aria-hidden="true"></div>
          <div class="tec-control tec-control--drag">
            <input class="tec-input" name="event_featured_image" data-featured-image-input type="text" placeholder="Full path to image" />
          </div>
          <button class="tec-button-secondary" type="button" data-featured-image-button>Select Image</button>
          <label class="tec-checkbox tec-checkbox--inline">
            <input type="checkbox" name="ticket_header_from_featured" checked />
            <span>Use featured image as ticket header image</span>
          </label>
        </div>
      </div>
    </div>
  </section>

  <div class="tec-divider tec-divider--section"></div>

  <section class="tec-section">
    <p class="tec-section-title">Extra Options</p>
    <p class="tec-section-desc">Typically, you would leave these as default</p>
    <div class="tec-checkbox-group">
      <label class="tec-checkbox">
        <input type="checkbox" name="hide_from_listings" />
        <span>Hide from event listings</span>
      </label>
      <label class="tec-checkbox">
        <input type="checkbox" name="hide_from_month" />
        <span>Sticky in Month View</span>
      </label>
      <label class="tec-checkbox">
        <input type="checkbox" name="show_map_link" checked />
        <span>Show Map Link</span>
      </label>
      <label class="tec-checkbox">
        <input type="checkbox" name="show_attendees_list" />
        <span>Show attendees list on event page</span>
      </label>
      <label class="tec-checkbox">
        <input type="checkbox" name="allow_comments" />
        <span>Allow Comments</span>
      </label>
    </div>
    <div class="tec-field" style="margin-top: 12px;">
      <label class="tec-checkbox tec-checkbox--inline">
        <input type="checkbox" name="event_website_enabled" />
        <span>Add event website</span>
      </label>
      <div class="tec-control is-hidden" data-event-website-field style="margin-top: 8px;">
        <input class="tec-input" name="event_website" type="url" placeholder="e.g. https://www.example.com/event" disabled />
      </div>
    </div>
  </section>

  <div class="tec-divider tec-divider--section"></div>

  <section class="tec-section">
    <p class="tec-section-title">Schedule &amp; Dates</p>
    <p class="tec-section-desc">Set range to create bookings, and its recurrences and occurrences</p>

    <div class="tec-field tec-schedule-mode">
      <p class="tec-label tec-subtitle">Schedule mode</p>
      <div class="tec-radio-group">
        <label class="tec-radio">
          <input type="radio" name="schedule_mode" value="specific" />
          <span>Create events for single or multiple specific dates</span>
        </label>
        <label class="tec-radio">
          <input type="radio" name="schedule_mode" value="recurring" checked />
          <span>Create events for recurring dates</span>
        </label>
      </div>
    </div>

    <div class="tec-field is-hidden" data-schedule-specific>
      <p class="tec-label tec-subtitle">Select specific dates</p>
      <div class="tec-specific-picker">
        <div class="tec-specific-calendar" data-specific-calendar></div>
        <div class="tec-specific-summary">
          <p class="tec-note">Selected dates</p>
          <div class="tec-specific-list" data-specific-list></div>
          <input type="hidden" name="specific_dates" data-specific-input />
          <button class="tec-button-secondary" type="button" data-specific-clear>Clear dates</button>
        </div>
      </div>
    </div>

    <div class="tec-grid tec-grid--stack" data-schedule-recurring>
      <div class="tec-field">
        <p class="tec-label tec-subtitle">Dates to create bookings</p>
        <div class="tec-range">
          <div class="tec-field">
            <p class="tec-label">From</p>
            <div class="tec-control tec-control--date">
              <input class="tec-input" name="event_date_from" type="text" inputmode="numeric" placeholder="YYYY-MM-DD" />
            </div>
          </div>
          <span class="tec-range-arrow">→</span>
          <div class="tec-field">
            <p class="tec-label">To</p>
            <div class="tec-control tec-control--date">
              <input class="tec-input" name="event_date_to" type="text" inputmode="numeric" placeholder="YYYY-MM-DD" />
            </div>
          </div>
        </div>
      </div>
      <div class="tec-field">
        <p class="tec-label tec-subtitle">Recurrence</p>
        <div class="tec-days">
          <label class="tec-checkbox"><input type="checkbox" name="recurrence_days[]" value="sun" /> Sun</label>
          <label class="tec-checkbox"><input type="checkbox" name="recurrence_days[]" value="mon" /> Mon</label>
          <label class="tec-checkbox"><input type="checkbox" name="recurrence_days[]" value="tue" /> Tue</label>
          <label class="tec-checkbox"><input type="checkbox" name="recurrence_days[]" value="wed" /> Wed</label>
          <label class="tec-checkbox"><input type="checkbox" name="recurrence_days[]" value="thu" /> Thu</label>
          <label class="tec-checkbox"><input type="checkbox" name="recurrence_days[]" value="fri" /> Fri</label>
          <label class="tec-checkbox"><input type="checkbox" name="recurrence_days[]" value="sat" /> Sat</label>
        </div>
      </div>
    </div>

    <div class="tec-field" style="margin-top: 16px;">
      <p class="tec-label tec-subtitle">Occurrences</p>
      <div class="tec-inline">
        <div class="tec-field">
          <p class="tec-label">How many occurrences per day?</p>
          <div class="tec-control tec-control--select">
            <select class="tec-select" data-occurrence-count name="occurrence_count" value="1">
              <option>1</option>
            </select>
          </div>
        </div>
      </div>
    </div>

    <div class="tec-expand-container" style="margin-top: 16px;">
      <div data-occurrence-list class="tec-list"></div>
    </div>
  </section>

  <div class="tec-divider tec-divider--section"></div>

  <section class="tec-section">
    <p class="tec-section-title">Ticketing</p>
    <p class="tec-section-desc">Set pricing &amp; sale schedules</p>

    <div class="tec-field">
      <p class="tec-label tec-subtitle">How many ticket types to create?</p>
      <p class="tec-note">Create different tickets per event, to set different price points or sections.</p>
      <div class="tec-control tec-control--select" style="max-width: 160px;">
        <select class="tec-select" data-ticket-count name="ticket_type_count" value="1">
          <option>1</option>
        </select>
      </div>
    </div>

    <div class="tec-field" data-shared-capacity-field style="margin-top: 12px;">
      <label class="tec-checkbox">
        <input type="checkbox" name="ticket_shared_capacity" />
        <span>Share capacity with other tickets</span>
      </label>
      <div class="tec-control tec-control--number is-hidden" data-shared-capacity-control style="margin-top: 8px; max-width: 220px;">
        <input class="tec-input" type="number" min="0" name="shared_capacity_total" placeholder="Total shared capacity" />
      </div>
      <p class="tec-note tec-note--small is-hidden" data-shared-capacity-note>Shared capacity cannot exceed the total ticket quantities.</p>
    </div>

    <div class="tec-field" data-waitlist-field style="margin-top: 16px;">
      <p class="tec-label tec-subtitle">Waitlist</p>
      <div class="tec-radio-group">
        <label class="tec-radio">
          <input type="radio" name="waitlist_mode" value="none" checked />
          <span>No waitlist</span>
        </label>
        <label class="tec-radio">
          <input type="radio" name="waitlist_mode" value="presale_or_sold_out" />
          <span>When tickets are on pre-sale or sold out</span>
        </label>
        <label class="tec-radio">
          <input type="radio" name="waitlist_mode" value="before_sale" />
          <span>Before tickets go on sale</span>
        </label>
        <label class="tec-radio">
          <input type="radio" name="waitlist_mode" value="sold_out" />
          <span>When tickets are sold out</span>
        </label>
      </div>
    </div>

    <div class="tec-expand-container" style="margin-top: 24px;">
      <div data-ticket-list class="tec-list"></div>
    </div>
  </section>

  <div class="tec-divider tec-divider--section"></div>

  <section class="tec-section">
    <p class="tec-section-title">Create Events &amp; Tickets</p>
    <p class="tec-section-desc">Run a dry preview, then create events and tickets directly in WordPress.</p>
    <div class="tec-inline">
      <button class="tec-button" type="button" data-create-events>Create Events &amp; Tickets 🐕💨</button>
      <button class="tec-button-secondary" type="button" data-dry-run>Dry Run</button>
      <button class="tec-button-secondary" type="button" data-delete-batch>Delete Last Batch</button>
    </div>
    <div class="tec-results" data-import-results></div>
  </section>

  <div class="tec-divider tec-divider--section"></div>
  <div class="tec-modal" data-confirm-modal aria-hidden="true" hidden>
    <div class="tec-modal-card" role="dialog" aria-modal="true" aria-labelledby="tec-modal-title">
      <h3 class="tec-modal-title" id="tec-modal-title">Delete last batch?</h3>
      <p class="tec-modal-text">This will permanently delete the last events and tickets you created.</p>
      <div class="tec-modal-actions">
        <button class="tec-button-secondary" type="button" data-modal-cancel>Cancel</button>
        <button class="tec-button" type="button" data-modal-confirm>Delete</button>
      </div>
    </div>
  </div>
  </form>
</div>
