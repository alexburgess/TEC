<?php
$venues = function_exists('tec_rb_get_option_list') ? tec_rb_get_option_list('tec_rb_venues', array()) : array();
$categories = function_exists('tec_rb_get_option_list') ? tec_rb_get_option_list('tec_rb_categories', array()) : array();
$organizers = function_exists('tec_rb_get_option_list') ? tec_rb_get_option_list('tec_rb_organizers', array()) : array();
$settings_url = function_exists('admin_url') ? admin_url('admin.php?page=tec-recurring-bookings-settings') : '#';
?>

<div class="tec-app" data-tec-recurring-bookings>
  <div class="tec-header">
    <h1 class="tec-title">TEC.dog</h1>
    <div class="tec-header-actions">
      <div class="tec-control tec-control--select">
        <select class="tec-select" data-preset-select>
          <option value="">Select preset</option>
        </select>
      </div>
      <button class="tec-button-secondary" type="button" data-save-preset>Save as preset</button>
      <a class="tec-button-secondary" href="<?php echo esc_url($settings_url); ?>">Settings</a>
    </div>
  </div>

  <form class="tec-form">
  <section class="tec-section">
    <p class="tec-section-title">Event Details</p>
    <p class="tec-section-desc">Information about the event</p>
    <div class="tec-event-grid">
      <div class="tec-grid">
        <div class="tec-field">
          <p class="tec-label">Event Name*</p>
          <div class="tec-control">
            <input class="tec-input" name="event_name" type="text" placeholder="e.g. Summer Experience" />
          </div>
        </div>
        <div class="tec-field">
          <p class="tec-label">Event Excerpt</p>
          <div class="tec-control">
            <input class="tec-input" name="event_excerpt" type="text" placeholder="e.g. A brief invitation to visit us and your guests" />
          </div>
        </div>
        <div class="tec-field">
          <div class="tec-label-row">
            <p class="tec-label">Event Venue Name*</p>
            <span class="tec-info-dot" data-tooltip="Manage venues in the Settings section." aria-label="Manage venues in the Settings section." tabindex="0">i</span>
          </div>
          <div class="tec-control tec-control--select">
            <select class="tec-select" name="event_venue">
              <option value="" selected>Select venue</option>
              <?php foreach ($venues as $venue) : ?>
                <option value="<?php echo esc_attr($venue); ?>"><?php echo esc_html($venue); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="tec-field">
          <div class="tec-label-row">
            <p class="tec-label">Event Category*</p>
            <span class="tec-info-dot" data-tooltip="Manage categories in the Settings section." aria-label="Manage categories in the Settings section." tabindex="0">i</span>
          </div>
          <div class="tec-control tec-control--select">
            <select class="tec-select" name="event_category">
              <option value="" selected>Select category</option>
              <?php foreach ($categories as $category) : ?>
                <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="tec-field">
          <p class="tec-label">Event Tags</p>
          <div class="tec-control">
            <input class="tec-input" name="event_tags" type="text" placeholder="Separate tags by commas" />
          </div>
        </div>
        <div class="tec-field">
          <p class="tec-label">Event Organizer Name*</p>
          <div class="tec-control tec-control--select">
            <select class="tec-select" name="event_organizer">
              <option value="" selected>Select organizer</option>
              <?php foreach ($organizers as $organizer) : ?>
                <option value="<?php echo esc_attr($organizer); ?>"><?php echo esc_html($organizer); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="tec-field">
          <p class="tec-label">Event Description*</p>
          <div class="tec-control">
            <textarea class="tec-textarea" name="event_description" placeholder="e.g. Bring the family to ride the hayride. Create your adventure."></textarea>
          </div>
        </div>
        <div class="tec-field">
          <p class="tec-label">Event Featured Image</p>
          <div class="tec-featured-image">
            <div class="tec-control tec-control--drag" style="flex: 1;">
              <input class="tec-input" name="event_featured_image" data-featured-image-input type="text" placeholder="Full path to image" />
            </div>
            <button class="tec-button-secondary" type="button" data-featured-image-button>Select Image</button>
          </div>
        </div>
        <div class="tec-field">
          <p class="tec-label">Event Website</p>
          <div class="tec-control">
            <input class="tec-input" name="event_website" type="url" placeholder="e.g. https://www.example.com/event" />
          </div>
        </div>
      </div>
      <div class="tec-image-preview" data-featured-image-preview aria-hidden="true"></div>
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
        <span>Sticky in Month View?</span>
      </label>
      <label class="tec-checkbox">
        <input type="checkbox" name="show_map_link" checked />
        <span>Show Map Link?</span>
      </label>
      <label class="tec-checkbox">
        <input type="checkbox" name="allow_comments" />
        <span>Allow Comments?</span>
      </label>
    </div>
  </section>

  <div class="tec-divider tec-divider--section"></div>

  <section class="tec-section">
    <p class="tec-section-title">Schedule &amp; Dates</p>
    <p class="tec-section-desc">Set range to create bookings, and its recurrences and occurrences</p>

    <div class="tec-grid tec-grid--stack">
      <div class="tec-field">
        <p class="tec-label tec-subtitle">Dates to create bookings</p>
        <div class="tec-range">
          <div class="tec-field">
            <p class="tec-label">From</p>
            <div class="tec-control tec-control--date">
              <input class="tec-input" name="event_date_from" type="date" />
            </div>
          </div>
          <span class="tec-range-arrow">→</span>
          <div class="tec-field">
            <p class="tec-label">To</p>
            <div class="tec-control tec-control--date">
              <input class="tec-input" name="event_date_to" type="date" />
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

    <div class="tec-expand-container" style="margin-top: 24px;">
      <div data-ticket-list class="tec-list"></div>
    </div>
  </section>

  <div class="tec-divider tec-divider--section"></div>

  <section class="tec-section">
    <p class="tec-section-title">Create Events &amp; Tickets</p>
    <p class="tec-section-desc">Run a dry preview, then create events and tickets directly in WordPress.</p>
    <div class="tec-inline">
      <button class="tec-button-secondary" type="button" data-dry-run>Dry Run</button>
      <button class="tec-button" type="button" data-create-events>Create Events &amp; Tickets</button>
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
