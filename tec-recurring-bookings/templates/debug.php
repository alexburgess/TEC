<div class="tec-app" data-tec-recurring-bookings>
  <div class="tec-header">
    <h1 class="tec-title">TEC.dog Debug Compare</h1>
  </div>

  <section class="tec-section">
    <p class="tec-section-desc">Compare meta for a manual event/ticket vs a plugin-generated event/ticket.</p>
    <div class="tec-grid">
      <div class="tec-field">
        <p class="tec-label tec-subtitle">Manual Event ID</p>
        <div class="tec-control">
          <input class="tec-input" type="number" min="1" step="1" data-debug-manual-event />
        </div>
      </div>
      <div class="tec-field">
        <p class="tec-label tec-subtitle">Plugin Event ID</p>
        <div class="tec-control">
          <input class="tec-input" type="number" min="1" step="1" data-debug-plugin-event />
        </div>
      </div>
    </div>

    <div style="margin-top: 16px;">
      <p class="tec-label tec-subtitle">Ticket Pairs</p>
      <p class="tec-note">Add one or more manual vs plugin ticket product ID pairs.</p>
      <div data-debug-ticket-pairs>
        <div class="tec-grid" data-debug-ticket-pair>
          <div class="tec-field">
            <p class="tec-label">Manual Ticket Product ID</p>
            <div class="tec-control">
              <input class="tec-input" type="number" min="1" step="1" data-debug-manual-ticket />
            </div>
          </div>
          <div class="tec-field">
            <p class="tec-label">Plugin Ticket Product ID</p>
            <div class="tec-control">
              <input class="tec-input" type="number" min="1" step="1" data-debug-plugin-ticket />
            </div>
          </div>
          <div class="tec-field" style="display:flex;align-items:flex-end;">
            <button class="tec-button-secondary" type="button" data-debug-remove-pair disabled>Remove</button>
          </div>
        </div>
      </div>
      <div class="tec-inline" style="margin-top: 12px;">
        <button class="tec-button-secondary" type="button" data-debug-add-pair>Add ticket pair</button>
      </div>
    </div>
    <div class="tec-inline" style="margin-top: 16px;">
      <button class="tec-button-secondary" type="button" data-debug-compare>Compare</button>
      <button class="tec-button-secondary" type="button" data-debug-copy disabled>Copy to clipboard</button>
    </div>
    <div class="tec-results tec-debug-results" data-debug-results></div>
  </section>
</div>
