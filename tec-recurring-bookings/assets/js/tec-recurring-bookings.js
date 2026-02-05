(() => {
  const EVENT_HEADERS = [
    "Event Name",
    "Event Excerpt",
    "EVENT VENUE NAME",
    "EVENT ORGANIZER NAME",
    "EVENT START DATE",
    "EVENT START TIME",
    "EVENT END DATE",
    "EVENT END TIME",
    "All Day Event",
    "Event Time Zone",
    "Hide Event From Event Listings",
    "Event Sticky in Month View",
    "EVENT CATEGORY",
    "EVENT TAGS",
    "EVENT COST",
    "EVENT CURRENCY SYMBOL",
    "EVENT CURRENCY POSITION",
    "Event ISO Currency Code",
    "Event Featured Image",
    "EVENT WEBSITE",
    "EVENT SHOW MAP LINK",
    "EVENT SHOW MAP",
    "Event Allow Comments",
    "Event Allow Trackbacks and Pingbacks",
    "EVENT DESCRIPTION",
  ];

  const TICKET_HEADERS = [
    "Event Name or ID or Slug",
    "Ticket Name",
    "Ticket Price",
    "Ticket SKU",
    "Ticket Description",
    "Ticket Start Sale Date",
    "Ticket Start Sale Time",
    "Ticket End Sale Date",
    "Ticket End Sale Time",
    "Ticket Stock",
    "Ticket Show Description",
  ];

  const timeOptions = () => {
    const options = [];
    const startMinutes = 6 * 60;
    const endMinutes = 23 * 60;
    for (let m = startMinutes; m <= endMinutes; m += 30) {
      const hours24 = Math.floor(m / 60);
      const minutes = m % 60;
      const suffix = hours24 >= 12 ? "PM" : "AM";
      const hours12 = hours24 % 12 === 0 ? 12 : hours24 % 12;
      const label = `${hours12}:${minutes === 0 ? "00" : minutes} ${suffix}`;
      options.push(label);
    }
    return options;
  };

  const buildOptions = (values, selected) =>
    values
      .map((option) => {
        const value = typeof option === "string" ? option : option.value;
        const label = typeof option === "string" ? option : option.label;
        return `<option value="${value}"${value === selected ? " selected" : ""}>${label}</option>`;
      })
      .join("");

  const buildOccurrenceOptions = (selected) => {
    const values = Array.from({ length: 20 }, (_, i) => String(i + 1));
    return buildOptions(values, selected);
  };

  const buildTicketOptions = (selected) => {
    const values = [
      { value: "0", label: "0 - don't create tickets" },
      ...Array.from({ length: 20 }, (_, i) => ({ value: String(i + 1), label: String(i + 1) })),
    ];
    return buildOptions(values, selected);
  };

  const csvEscape = (value) => {
    const stringValue = value == null ? "" : String(value);
    return `"${stringValue.replace(/"/g, "\"\"")}"`;
  };

  const normalizeCsvText = (value) =>
    (value == null ? "" : String(value)).replace(/\r\n|\r|\n/g, " ").trim();

  const to24HourTime = (timeValue) => {
    if (!timeValue) return "";
    const match = timeValue.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return timeValue;
    let hours = Number(match[1]);
    const minutes = match[2];
    const period = match[3].toUpperCase();
    if (period === "AM" && hours === 12) {
      hours = 0;
    }
    if (period === "PM" && hours !== 12) {
      hours += 12;
    }
    return `${String(hours).padStart(2, "0")}:${minutes}:00`;
  };

  const formatDate = (date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  };

  const formatTime24 = (date) => {
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");
    return `${hours}:${minutes}:00`;
  };

  const parseLocalDate = (dateString) => {
    if (!dateString) return null;
    const parts = dateString.split("-").map(Number);
    if (parts.length !== 3 || parts.some((value) => Number.isNaN(value))) {
      return null;
    }
    const [year, month, day] = parts;
    return new Date(year, month - 1, day);
  };

  const toDateTime = (dateString, timeString) => {
    if (!dateString || !timeString) return null;
    const [year, month, day] = dateString.split("-").map(Number);
    const [hours, minutes] = timeString.split(":").map(Number);
    if (!year || !month || !day || Number.isNaN(hours) || Number.isNaN(minutes)) return null;
    return new Date(year, month - 1, day, hours, minutes, 0, 0);
  };

  const applyRelativeOffset = (date, amount, unit) => {
    if (!date || !amount || !unit) return null;
    const offset = Number(amount);
    if (Number.isNaN(offset) || offset <= 0) return null;
    const updated = new Date(date.getTime());
    const normalized = unit.toLowerCase();
    if (normalized.startsWith("day")) {
      updated.setDate(updated.getDate() - offset);
    } else if (normalized.startsWith("week")) {
      updated.setDate(updated.getDate() - offset * 7);
    } else if (normalized.startsWith("hour")) {
      updated.setHours(updated.getHours() - offset);
    } else if (normalized.startsWith("minute")) {
      updated.setMinutes(updated.getMinutes() - offset);
    } else {
      return null;
    }
    return updated;
  };

  const sanitizeFilenamePart = (value) =>
    (value || "Events")
      .replace(/[\\/:*?"<>|]/g, "")
      .replace(/\s+/g, " ")
      .trim() || "Events";

  const escapeHtml = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");

  const normalizeSelectValue = (value, emptySentinels = []) => {
    if (!value) return "";
    const trimmed = value.trim();
    if (emptySentinels.includes(trimmed)) return "";
    return trimmed;
  };

  const parseJsonResponse = async (response) => {
    const text = await response.text();
    try {
      return { ok: true, data: JSON.parse(text) };
    } catch (error) {
      return { ok: false, text };
    }
  };

  const setSelectValue = (select, value) => {
    if (!select) return;
    const normalized = value == null ? "" : String(value);
    if (normalized !== "") {
      const hasOption = Array.from(select.options).some(
        (option) => option.value === normalized || option.text === normalized
      );
      if (!hasOption) {
        const option = document.createElement("option");
        option.value = normalized;
        option.textContent = normalized;
        select.appendChild(option);
      }
    }
    select.value = normalized;
    select.dispatchEvent(new Event("change", { bubbles: true }));
  };

  const setInputValue = (input, value) => {
    if (!input) return;
    input.value = value == null ? "" : String(value);
    input.dispatchEvent(new Event("input", { bubbles: true }));
    input.dispatchEvent(new Event("change", { bubbles: true }));
  };

  const applyPresetToForm = (root, preset) => {
    if (!root || !preset) return;
    let data = preset.data ?? preset;
    if (typeof data === "string") {
      try {
        data = JSON.parse(data);
      } catch (error) {
        return;
      }
    }
    if (!data || typeof data !== "object") return;

    setInputValue(root.querySelector('[name="event_name"]'), data.eventName);
    setInputValue(root.querySelector('[name="event_excerpt"]'), data.eventExcerpt);
    setInputValue(root.querySelector('[name="event_tags"]'), data.eventTags);
    setInputValue(root.querySelector('[name="event_description"]'), data.eventDescription);
    setInputValue(root.querySelector('[name="event_featured_image"]'), data.eventFeaturedImage);
    setInputValue(root.querySelector('[name="event_website"]'), data.eventWebsite);

    setSelectValue(root.querySelector('[name="event_venue"]'), data.eventVenue || "");
    setSelectValue(root.querySelector('[name="event_category"]'), data.eventCategory || "");
    setSelectValue(root.querySelector('[name="event_organizer"]'), data.eventOrganizer || "");

    setInputValue(root.querySelector('[name="event_date_from"]'), data.startDate);
    setInputValue(root.querySelector('[name="event_date_to"]'), data.endDate);

    const showMap = root.querySelector('[name="show_map_link"]');
    if (showMap) showMap.checked = !!data.showMapLink;
    const hideListings = root.querySelector('[name="hide_from_listings"]');
    if (hideListings) hideListings.checked = !!data.hideFromListings;
    const stickyMonth = root.querySelector('[name="hide_from_month"]');
    if (stickyMonth) stickyMonth.checked = !!data.stickyInMonthView;
    const allowComments = root.querySelector('[name="allow_comments"]');
    if (allowComments) allowComments.checked = !!data.allowComments;

    const recurrenceDays = Array.isArray(data.recurrenceDays) ? data.recurrenceDays : [];
    root.querySelectorAll('input[name="recurrence_days[]"]').forEach((input) => {
      input.checked = recurrenceDays.includes(input.value);
    });

    const occurrenceSelect = root.querySelector("[data-occurrence-count]");
    const occurrences = Array.isArray(data.occurrences) ? data.occurrences : [];
    const occurrenceCount = Math.max(1, occurrences.length || 1);
    if (occurrenceSelect) {
      occurrenceSelect.innerHTML = buildOccurrenceOptions(String(occurrenceCount));
      occurrenceSelect.value = String(occurrenceCount);
      renderOccurrences(root, occurrenceCount);
    }

    occurrences.forEach((occurrence, index) => {
      const number = index + 1;
      setInputValue(
        root.querySelector(`input[name="occurrence_${number}_name"]`),
        occurrence.name
      );
      setSelectValue(
        root.querySelector(`select[name="occurrence_${number}_start_time"]`),
        occurrence.startTime
      );
      setSelectValue(
        root.querySelector(`select[name="occurrence_${number}_end_time"]`),
        occurrence.endTime
      );
    });

    const ticketSelect = root.querySelector("[data-ticket-count]");
    const ticketTypes = Array.isArray(data.ticketTypes) ? data.ticketTypes : [];
    const ticketCount = ticketTypes.length;
    if (ticketSelect) {
      ticketSelect.innerHTML = buildTicketOptions(String(ticketCount));
      ticketSelect.value = String(ticketCount);
      renderTickets(root, ticketCount);
    }
    const waitlistField = root.querySelector("[data-waitlist-field]");
    if (waitlistField) {
      waitlistField.classList.toggle("is-hidden", ticketCount < 1);
      if (ticketCount < 1) {
        const noneRadio = root.querySelector('input[name="waitlist_mode"][value="none"]');
        if (noneRadio) noneRadio.checked = true;
      }
    }
    const sharedCapacityInput = root.querySelector('[name="ticket_shared_capacity"]');
    const sharedCapacityField = root.querySelector("[data-shared-capacity-field]");
    const sharedCapacityControl = root.querySelector("[data-shared-capacity-control]");
    const sharedCapacityNote = root.querySelector("[data-shared-capacity-note]");
    const sharedCapacityTotalInput = root.querySelector('[name="shared_capacity_total"]');
    if (sharedCapacityField) {
      if (ticketCount > 1) {
        sharedCapacityField.classList.remove("is-hidden");
      } else {
        sharedCapacityField.classList.add("is-hidden");
        if (sharedCapacityInput) {
          sharedCapacityInput.checked = false;
        }
      }
    }
    if (sharedCapacityInput) {
      sharedCapacityInput.checked = !!data.sharedCapacity;
    }
    if (sharedCapacityControl && sharedCapacityNote) {
      const show = !!data.sharedCapacity;
      sharedCapacityControl.classList.toggle("is-hidden", !show);
      sharedCapacityNote.classList.toggle("is-hidden", !show);
    }
    if (sharedCapacityTotalInput) {
      if (data.sharedCapacityTotal != null) {
        sharedCapacityTotalInput.value = String(data.sharedCapacityTotal);
      } else {
        sharedCapacityTotalInput.value = "";
      }
      const sum = ticketTypes
        .map((ticket) => Number(ticket.quantity || 0))
        .filter((value) => !Number.isNaN(value) && value > 0)
        .reduce((total, value) => total + value, 0);
      sharedCapacityTotalInput.max = sum ? String(sum) : "";
      const current = Number(sharedCapacityTotalInput.value || 0);
      if (!sharedCapacityTotalInput.value || current > sum) {
        sharedCapacityTotalInput.value = sum ? String(sum) : "";
      }
    }

    const waitlistMode = data.waitlistMode || "none";
    const waitlistRadio = root.querySelector(`input[name="waitlist_mode"][value="${waitlistMode}"]`);
    if (waitlistRadio) {
      waitlistRadio.checked = true;
    }

    ticketTypes.forEach((ticket, index) => {
      const number = index + 1;
      setInputValue(
        root.querySelector(`input[name="ticket_${number}_name"]`),
        ticket.name
      );
      setInputValue(
        root.querySelector(`input[name="ticket_${number}_description"]`),
        ticket.description
      );
      setInputValue(
        root.querySelector(`input[name="ticket_${number}_cost"]`),
        ticket.price
      );
      setInputValue(
        root.querySelector(`input[name="ticket_${number}_quantity"]`),
        ticket.quantity
      );
      const showDesc = root.querySelector(`input[name="ticket_${number}_show_description"]`);
      if (showDesc) showDesc.checked = !!ticket.showDescription;

      const saleStart = ticket.saleStartMode || "immediate";
      const saleStartRadio = root.querySelector(
        `input[name="ticket_${number}_sale_start"][value="${saleStart}"]`
      );
      if (saleStartRadio) {
        saleStartRadio.checked = true;
        saleStartRadio.dispatchEvent(new Event("change", { bubbles: true }));
      }
      setInputValue(
        root.querySelector(`input[name="ticket_${number}_sale_start_date"]`),
        ticket.saleStartDate
      );
      setSelectValue(
        root.querySelector(`select[name="ticket_${number}_sale_start_time"]`),
        ticket.saleStartTime
      );
      setSelectValue(
        root.querySelector(`select[name="ticket_${number}_sale_start_offset"]`),
        ticket.saleStartOffset
      );
      setSelectValue(
        root.querySelector(`select[name="ticket_${number}_sale_start_unit"]`),
        ticket.saleStartUnit
      );

      const saleEnd = ticket.saleEndMode || "start";
      const saleEndRadio = root.querySelector(
        `input[name="ticket_${number}_sale_end"][value="${saleEnd}"]`
      );
      if (saleEndRadio) {
        saleEndRadio.checked = true;
        saleEndRadio.dispatchEvent(new Event("change", { bubbles: true }));
      }
      setSelectValue(
        root.querySelector(`select[name="ticket_${number}_sale_end_offset"]`),
        ticket.saleEndOffset
      );
      setSelectValue(
        root.querySelector(`select[name="ticket_${number}_sale_end_unit"]`),
        ticket.saleEndUnit
      );
    });
  };

  const renderOccurrences = (root, count) => {
    const container = root.querySelector("[data-occurrence-list]");
    if (!container) return;
    const wrapper = container.closest(".tec-expand-container");

    const times = timeOptions();
    if (count < 1) {
      container.innerHTML = "";
      if (wrapper) {
        wrapper.classList.add("is-hidden");
      }
      return;
    }

    if (wrapper) {
      wrapper.classList.remove("is-hidden");
    }

    container.innerHTML = Array.from({ length: count }, (_, i) => {
      const index = i + 1;
      return `
        <div class="tec-occurrence-item">
          <div class="tec-index">${index}</div>
          <div class="tec-occurrence-fields">
            <div class="tec-field">
              <p class="tec-label">Occurrence Name*</p>
              <div class="tec-control">
                <input class="tec-input" name="occurrence_${index}_name" type="text" placeholder="e.g. Morning" />
              </div>
            </div>
            <div class="tec-field">
              <p class="tec-label">Event start time</p>
              <div class="tec-control tec-control--select">
                <select class="tec-select" name="occurrence_${index}_start_time">
                  ${buildOptions(times, "9:00 AM")}
                </select>
              </div>
            </div>
            <div class="tec-arrow">→</div>
            <div class="tec-field">
              <p class="tec-label">Event end time</p>
              <div class="tec-control tec-control--select">
                <select class="tec-select" name="occurrence_${index}_end_time">
                  ${buildOptions(times, "10:00 AM")}
                </select>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join("");

    container.querySelectorAll('select[name^="occurrence_"][name$="_start_time"]').forEach((select) => {
      select.addEventListener("change", () => {
        const name = select.getAttribute("name") || "";
        const indexMatch = name.match(/occurrence_(\d+)_start_time/);
        if (!indexMatch) return;
        const index = indexMatch[1];
        const endSelect = container.querySelector(`select[name="occurrence_${index}_end_time"]`);
        if (!endSelect) return;
        const options = Array.from(select.options).map((option) => option.value);
        const startIndex = options.indexOf(select.value);
        const endIndex = options.indexOf(endSelect.value);
        if (startIndex >= 0 && endIndex >= 0 && endIndex <= startIndex) {
          const nextIndex = Math.min(startIndex + 1, options.length - 1);
          endSelect.value = options[nextIndex] || endSelect.value;
          endSelect.dispatchEvent(new Event("change", { bubbles: true }));
        }
      });
    });
  };

  const renderTickets = (root, count) => {
    const container = root.querySelector("[data-ticket-list]");
    if (!container) return;
    const wrapper = container.closest(".tec-expand-container");

    const times = timeOptions();

    if (count < 1) {
      container.innerHTML = "";
      if (wrapper) {
        wrapper.classList.add("is-hidden");
      }
      return;
    }

    if (wrapper) {
      wrapper.classList.remove("is-hidden");
    }

    const units = ["Days", "Hours", "Weeks", "Minutes"];

    container.innerHTML = Array.from({ length: count }, (_, i) => {
      const index = i + 1;
      return `
        <div class="tec-ticket-item" data-ticket-index="${index}">
          <div class="tec-index">${index}</div>
          <div>
            <div class="tec-ticket-top">
              <div class="tec-field">
                <p class="tec-label">Ticket Name*</p>
                <div class="tec-control">
                  <input class="tec-input" name="ticket_${index}_name" type="text" placeholder="e.g. Regular Ticket" />
                </div>
              </div>
              <div class="tec-field">
                <p class="tec-label">Ticket Description</p>
                <div class="tec-control">
                  <input class="tec-input" name="ticket_${index}_description" type="text" placeholder="e.g. Regular Reservation" />
                </div>
              </div>
              <label class="tec-checkbox">
                <input type="checkbox" name="ticket_${index}_show_description" />
                <span>Show descriptions to guests?</span>
              </label>
            </div>

            <div class="tec-ticket-mid">
              <div class="tec-field">
                <p class="tec-label">Ticket Cost*</p>
                <div class="tec-control">
                  <input class="tec-input" name="ticket_${index}_cost" type="text" placeholder="$140" />
                </div>
              </div>
              <div class="tec-field">
                <p class="tec-label">Quantity*</p>
                <div class="tec-control">
                  <input class="tec-input" name="ticket_${index}_quantity" type="number" min="0" placeholder="8" />
                </div>
              </div>
            </div>

            <div class="tec-ticket-schedule">
              <p class="tec-subheading">When should the tickets go on sale?</p>
              <div class="tec-radio-group" data-group="sale-start">
                <label class="tec-radio">
                  <input type="radio" name="ticket_${index}_sale_start" value="immediate" checked />
                  <span>Immediately</span>
                </label>
                <div class="tec-inline">
                  <label class="tec-radio">
                    <input type="radio" name="ticket_${index}_sale_start" value="set" />
                    <span>On a set date and time</span>
                  </label>
                  <div class="tec-control tec-control--date is-disabled" data-sale-start="set">
                    <input class="tec-input" type="date" name="ticket_${index}_sale_start_date" disabled />
                  </div>
                  <div class="tec-control tec-control--select is-disabled" data-sale-start="set">
                    <select class="tec-select" name="ticket_${index}_sale_start_time" disabled>
                      ${buildOptions(times, "9:00 AM")}
                    </select>
                  </div>
                </div>
                <div class="tec-inline">
                  <label class="tec-radio">
                    <input type="radio" name="ticket_${index}_sale_start" value="relative" />
                    <span>On a relative date</span>
                  </label>
                  <div class="tec-control tec-control--select is-disabled" data-sale-start="relative">
                    <select class="tec-select" name="ticket_${index}_sale_start_offset" disabled>
                      ${buildOptions(["X", "1", "2", "3", "4", "5"], "X")}
                    </select>
                  </div>
                  <div class="tec-control tec-control--select is-disabled" data-sale-start="relative">
                    <select class="tec-select" name="ticket_${index}_sale_start_unit" disabled>
                      ${buildOptions(units, "Days")}
                    </select>
                  </div>
                  <span class="tec-note">before event start</span>
                </div>
              </div>
            </div>

            <div class="tec-ticket-schedule">
              <p class="tec-subheading">When should they stop being sold?</p>
              <div class="tec-radio-group" data-group="sale-end">
                <label class="tec-radio">
                  <input type="radio" name="ticket_${index}_sale_end" value="start" checked />
                  <span>At the start time of the event</span>
                </label>
                <div class="tec-inline">
                  <label class="tec-radio">
                    <input type="radio" name="ticket_${index}_sale_end" value="relative" />
                    <span>On a relative date</span>
                  </label>
                  <div class="tec-control tec-control--select is-disabled" data-sale-end="relative">
                    <select class="tec-select" name="ticket_${index}_sale_end_offset" disabled>
                      ${buildOptions(["X", "1", "2", "3", "4", "5"], "X")}
                    </select>
                  </div>
                  <div class="tec-control tec-control--select is-disabled" data-sale-end="relative">
                    <select class="tec-select" name="ticket_${index}_sale_end_unit" disabled>
                      ${buildOptions(units, "Days")}
                    </select>
                  </div>
                  <span class="tec-note">before event start</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join("");

    container.querySelectorAll("[data-ticket-index]").forEach((ticketEl) => {
      const updateRadioGroups = (groupName, attr) => {
        const radios = ticketEl.querySelectorAll(`input[name^="ticket_"][name$="_${groupName}"]`);
        const update = () => {
          const selected = ticketEl.querySelector(`input[name^="ticket_"][name$="_${groupName}"]:checked`)?.value;
          ticketEl.querySelectorAll(`[data-${attr}]`).forEach((field) => {
            const isMatch = field.getAttribute(`data-${attr}`) === selected;
            const input = field.querySelector("select, input");
            if (input) {
              input.disabled = !isMatch;
            }
            field.classList.toggle("is-disabled", !isMatch);
          });
        };
        radios.forEach((radio) => radio.addEventListener("change", update));
        update();
      };

      updateRadioGroups("sale_start", "sale-start");
      updateRadioGroups("sale_end", "sale-end");
    });
  };

  const initFeaturedImage = (root) => {
    const input = root.querySelector("[data-featured-image-input]");
    const preview = root.querySelector("[data-featured-image-preview]");
    const button = root.querySelector("[data-featured-image-button]");
    if (!input || !preview) return;

    let objectUrl = null;
    const updatePreview = (value) => {
      const trimmed = (value ?? input.value).trim();
      if (!trimmed) {
        preview.style.backgroundImage = "";
        preview.classList.remove("is-filled");
        return;
      }
      preview.style.backgroundImage = `url("${trimmed}")`;
      preview.classList.add("is-filled");
    };

    input.addEventListener("input", () => updatePreview());
    updatePreview();

    if (button) {
      button.addEventListener("click", (event) => {
        event.preventDefault();
        if (window.wp && window.wp.media) {
          const frame = window.wp.media({
            title: "Select featured image",
            button: { text: "Use this image" },
            multiple: false,
          });
          frame.on("select", () => {
            const attachment = frame.state().get("selection").first().toJSON();
            input.value = attachment.url;
            updatePreview(attachment.url);
          });
          frame.open();
          return;
        }

        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.accept = "image/*";
        fileInput.addEventListener("change", () => {
          const file = fileInput.files?.[0];
          if (!file) return;
          if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
          }
          objectUrl = URL.createObjectURL(file);
          input.value = file.name;
          updatePreview(objectUrl);
        });
        fileInput.click();
      });
    }
  };

  const buildEventsCsv = (root) => {
    const getValue = (selector) => root.querySelector(selector)?.value?.trim() ?? "";
    const getChecked = (selector) => root.querySelector(selector)?.checked ?? false;
    const eventName = getValue('[name="event_name"]');
    const eventExcerpt = normalizeCsvText(getValue('[name="event_excerpt"]'));
    const eventVenue = normalizeSelectValue(
      getValue('[name="event_venue"]'),
      ["Select venue"]
    );
    const eventOrganizer = normalizeSelectValue(
      getValue('[name="event_organizer"]'),
      ["Select organizer"]
    );
    const eventCategory = normalizeSelectValue(
      getValue('[name="event_category"]'),
      ["Select category"]
    );
    const eventTags = normalizeCsvText(getValue('[name="event_tags"]'));
    const eventDescription = normalizeCsvText(getValue('[name="event_description"]'));
    const eventFeaturedImage = getValue('[name="event_featured_image"]');
    const eventWebsite = getValue('[name="event_website"]');
    const startDateValue = getValue('[name="event_date_from"]');
    const endDateValue = getValue('[name="event_date_to"]');

    if (!startDateValue || !endDateValue) {
      window.alert("Please select a start and end date.");
      return null;
    }

    const startDate = parseLocalDate(startDateValue);
    const endDate = parseLocalDate(endDateValue);
    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
      window.alert("Please provide valid dates.");
      return null;
    }

    const dayMap = { sun: 0, mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6 };
    const selectedDays = Array.from(
      root.querySelectorAll('input[name="recurrence_days[]"]:checked')
    ).map((input) => dayMap[input.value]).filter((value) => value !== undefined);

    const occurrenceNodes = Array.from(root.querySelectorAll(".tec-occurrence-item"));
    const occurrences = occurrenceNodes.map((node) => ({
      name: node.querySelector('input[name^="occurrence_"][name$="_name"]')?.value?.trim() ?? "",
      startTime: node.querySelector('select[name^="occurrence_"][name$="_start_time"]')?.value ?? "",
      endTime: node.querySelector('select[name^="occurrence_"][name$="_end_time"]')?.value ?? "",
    }));

    const rows = [];
    const cursor = new Date(startDate);
    while (cursor <= endDate) {
      if (selectedDays.length === 0 || selectedDays.includes(cursor.getDay())) {
        occurrences.forEach((occurrence) => {
          const computedName = eventName;
          const row = [
            computedName,
            eventExcerpt,
            eventVenue,
            eventOrganizer,
            formatDate(cursor),
            to24HourTime(occurrence.startTime),
            formatDate(cursor),
            to24HourTime(occurrence.endTime),
            "FALSE",
            "America/New_York",
            getChecked('[name="hide_from_listings"]') ? "TRUE" : "FALSE",
            getChecked('[name="hide_from_month"]') ? "TRUE" : "FALSE",
            eventCategory,
            eventTags,
            "",
            "",
            "",
            "",
            eventFeaturedImage,
            eventWebsite,
            getChecked('[name="show_map_link"]') ? "TRUE" : "FALSE",
            getChecked('[name="show_map_link"]') ? "TRUE" : "FALSE",
            getChecked('[name="allow_comments"]') ? "TRUE" : "FALSE",
            "FALSE",
            eventDescription,
          ];
          rows.push(row);
        });
      }
      cursor.setDate(cursor.getDate() + 1);
    }

    const csvLines = [
      EVENT_HEADERS.map(csvEscape).join(","),
      ...rows.map((row) => row.map(csvEscape).join(",")),
    ];

    return csvLines.join("\r\n");
  };

  const buildEventInstances = (root) => {
    const getValue = (selector) => root.querySelector(selector)?.value?.trim() ?? "";
    const startDateValue = getValue('[name="event_date_from"]');
    const endDateValue = getValue('[name="event_date_to"]');
    if (!startDateValue || !endDateValue) {
      return null;
    }

    const startDate = parseLocalDate(startDateValue);
    const endDate = parseLocalDate(endDateValue);
    if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
      return null;
    }

    const dayMap = { sun: 0, mon: 1, tue: 2, wed: 3, thu: 4, fri: 5, sat: 6 };
    const selectedDays = Array.from(
      root.querySelectorAll('input[name="recurrence_days[]"]:checked')
    )
      .map((input) => dayMap[input.value])
      .filter((value) => value !== undefined);

    const occurrenceNodes = Array.from(root.querySelectorAll(".tec-occurrence-item"));
    const occurrences = occurrenceNodes.map((node, index) => ({
      index: index + 1,
      name: node.querySelector('input[name^="occurrence_"][name$="_name"]')?.value?.trim() ?? "",
      startTime: node.querySelector('select[name^="occurrence_"][name$="_start_time"]')?.value ?? "",
      endTime: node.querySelector('select[name^="occurrence_"][name$="_end_time"]')?.value ?? "",
    }));

    const instances = [];
    const cursor = new Date(startDate);
    while (cursor <= endDate) {
      if (selectedDays.length === 0 || selectedDays.includes(cursor.getDay())) {
        occurrences.forEach((occurrence) => {
          const startTime24 = to24HourTime(occurrence.startTime);
          const endTime24 = to24HourTime(occurrence.endTime);
          const date = formatDate(cursor);
          instances.push({
            date,
            startTime: startTime24,
            endTime: endTime24,
            occurrenceName: occurrence.name,
            occurrenceIndex: occurrence.index,
            startDateTime: startTime24 ? `${date} ${startTime24}` : "",
          });
        });
      }
      cursor.setDate(cursor.getDate() + 1);
    }

    return instances;
  };

  const buildTicketsCsv = (root, foundMap) => {
    const ticketNodes = Array.from(root.querySelectorAll(".tec-ticket-item"));
    if (!ticketNodes.length) {
      window.alert("No ticket types to export.");
      return null;
    }

    const instances = buildEventInstances(root);
    if (!instances) {
      window.alert("Please provide a valid date range before creating tickets.");
      return null;
    }
    if (!instances.length) {
      window.alert("No events were generated. Check recurrence days and occurrences.");
      return null;
    }

    const missing = instances.filter((instance) => !foundMap[instance.startDateTime]);
    if (missing.length) {
      window.alert("Some events are missing IDs. Please run 'Find Imported Events' again.");
      return null;
    }

    const now = new Date();
    const nowDate = formatDate(now);
    const nowTime = formatTime24(now);

    const rows = [];
    ticketNodes.forEach((node, index) => {
      const ticketIndex = index + 1;
      const ticketName = node.querySelector(`input[name="ticket_${ticketIndex}_name"]`)?.value?.trim() ?? "";
      const ticketDescription = normalizeCsvText(
        node.querySelector(`input[name="ticket_${ticketIndex}_description"]`)?.value ?? ""
      );
      const ticketPriceRaw = node.querySelector(`input[name="ticket_${ticketIndex}_cost"]`)?.value?.trim() ?? "";
      const ticketPrice = ticketPriceRaw && !ticketPriceRaw.startsWith("$") ? `$${ticketPriceRaw}` : ticketPriceRaw;
      const ticketStock = node.querySelector(`input[name="ticket_${ticketIndex}_quantity"]`)?.value?.trim() ?? "";
      const showDescription = node.querySelector(`input[name="ticket_${ticketIndex}_show_description"]`)?.checked
        ? "Yes"
        : "No";
      const saleStartMode = node.querySelector(`input[name="ticket_${ticketIndex}_sale_start"]:checked`)?.value ?? "immediate";
      const saleEndMode = node.querySelector(`input[name="ticket_${ticketIndex}_sale_end"]:checked`)?.value ?? "start";
      const saleStartDate = node.querySelector(`input[name="ticket_${ticketIndex}_sale_start_date"]`)?.value?.trim() ?? "";
      const saleStartTime = node.querySelector(`select[name="ticket_${ticketIndex}_sale_start_time"]`)?.value ?? "";
      const saleStartOffset = node.querySelector(`select[name="ticket_${ticketIndex}_sale_start_offset"]`)?.value ?? "";
      const saleStartUnit = node.querySelector(`select[name="ticket_${ticketIndex}_sale_start_unit"]`)?.value ?? "";
      const saleEndOffset = node.querySelector(`select[name="ticket_${ticketIndex}_sale_end_offset"]`)?.value ?? "";
      const saleEndUnit = node.querySelector(`select[name="ticket_${ticketIndex}_sale_end_unit"]`)?.value ?? "";

      instances.forEach((instance) => {
        const eventId = foundMap[instance.startDateTime];
        let startSaleDate = "";
        let startSaleTime = "";
        let endSaleDate = "";
        let endSaleTime = "";

        const eventStartDateTime = toDateTime(instance.date, instance.startTime);

        if (saleStartMode === "immediate") {
          startSaleDate = nowDate;
          startSaleTime = nowTime;
        } else if (saleStartMode === "set") {
          startSaleDate = saleStartDate || "";
          startSaleTime = saleStartTime ? to24HourTime(saleStartTime) : "";
        } else if (saleStartMode === "relative" && eventStartDateTime) {
          if (saleStartOffset && saleStartOffset !== "X" && saleStartUnit) {
            const adjusted = applyRelativeOffset(eventStartDateTime, saleStartOffset, saleStartUnit);
            if (adjusted) {
              startSaleDate = formatDate(adjusted);
              startSaleTime = formatTime24(adjusted);
            }
          }
        }

        if (saleEndMode === "start" && eventStartDateTime) {
          endSaleDate = instance.date;
          endSaleTime = instance.startTime;
        } else if (saleEndMode === "relative" && eventStartDateTime) {
          if (saleEndOffset && saleEndOffset !== "X" && saleEndUnit) {
            const adjusted = applyRelativeOffset(eventStartDateTime, saleEndOffset, saleEndUnit);
            if (adjusted) {
              endSaleDate = formatDate(adjusted);
              endSaleTime = formatTime24(adjusted);
            }
          }
        }

        rows.push([
          eventId,
          ticketName,
          ticketPrice,
          "",
          ticketDescription,
          startSaleDate,
          startSaleTime,
          endSaleDate,
          endSaleTime,
          ticketStock,
          showDescription,
        ]);
      });
    });

    const csvLines = [
      TICKET_HEADERS.map(csvEscape).join(","),
      ...rows.map((row) => row.map(csvEscape).join(",")),
    ];

    return csvLines.join("\r\n");
  };

  const buildEventPayload = (root) => {
    const getValue = (selector) => root.querySelector(selector)?.value?.trim() ?? "";
    const eventName = getValue('[name="event_name"]');
    const startDateValue = getValue('[name="event_date_from"]');
    const endDateValue = getValue('[name="event_date_to"]');
    const recurrenceDays = Array.from(
      root.querySelectorAll('input[name="recurrence_days[]"]:checked')
    ).map((input) => input.value);
    const occurrenceNodes = Array.from(root.querySelectorAll(".tec-occurrence-item"));
    const occurrences = occurrenceNodes.map((node, index) => ({
      index: index + 1,
      name: node.querySelector('input[name^="occurrence_"][name$="_name"]')?.value?.trim() ?? "",
      startTime: node.querySelector('select[name^="occurrence_"][name$="_start_time"]')?.value ?? "",
      endTime: node.querySelector('select[name^="occurrence_"][name$="_end_time"]')?.value ?? "",
    }));

    const normalizeSelect = (value, emptySentinels) => {
      const trimmed = value.trim();
      return emptySentinels.includes(trimmed) ? "" : trimmed;
    };

    const ticketNodes = Array.from(root.querySelectorAll(".tec-ticket-item"));
    const ticketTypes = ticketNodes.map((node, index) => {
      const ticketIndex = index + 1;
      return {
        name: node.querySelector(`input[name="ticket_${ticketIndex}_name"]`)?.value?.trim() ?? "",
        description: node.querySelector(`input[name="ticket_${ticketIndex}_description"]`)?.value?.trim() ?? "",
        price: node.querySelector(`input[name="ticket_${ticketIndex}_cost"]`)?.value?.trim() ?? "",
        quantity: node.querySelector(`input[name="ticket_${ticketIndex}_quantity"]`)?.value?.trim() ?? "",
        showDescription: node.querySelector(`input[name="ticket_${ticketIndex}_show_description"]`)?.checked ?? false,
        saleStartMode: node.querySelector(`input[name="ticket_${ticketIndex}_sale_start"]:checked`)?.value ?? "immediate",
        saleStartDate: node.querySelector(`input[name="ticket_${ticketIndex}_sale_start_date"]`)?.value?.trim() ?? "",
        saleStartTime: node.querySelector(`select[name="ticket_${ticketIndex}_sale_start_time"]`)?.value ?? "",
        saleStartOffset: node.querySelector(`select[name="ticket_${ticketIndex}_sale_start_offset"]`)?.value ?? "",
        saleStartUnit: node.querySelector(`select[name="ticket_${ticketIndex}_sale_start_unit"]`)?.value ?? "",
        saleEndMode: node.querySelector(`input[name="ticket_${ticketIndex}_sale_end"]:checked`)?.value ?? "start",
        saleEndOffset: node.querySelector(`select[name="ticket_${ticketIndex}_sale_end_offset"]`)?.value ?? "",
        saleEndUnit: node.querySelector(`select[name="ticket_${ticketIndex}_sale_end_unit"]`)?.value ?? "",
      };
    });

    return {
      eventName,
      eventExcerpt: getValue('[name="event_excerpt"]'),
      eventVenue: normalizeSelect(getValue('[name="event_venue"]'), ["Select venue"]),
      eventOrganizer: normalizeSelect(getValue('[name="event_organizer"]'), ["Select organizer"]),
      eventCategory: normalizeSelect(getValue('[name="event_category"]'), ["Select category"]),
      eventTags: getValue('[name="event_tags"]'),
      eventDescription: getValue('[name="event_description"]'),
      eventFeaturedImage: getValue('[name="event_featured_image"]'),
      eventWebsite: getValue('[name="event_website"]'),
      showMapLink: root.querySelector('[name="show_map_link"]')?.checked ?? false,
      hideFromListings: root.querySelector('[name="hide_from_listings"]')?.checked ?? false,
      stickyInMonthView: root.querySelector('[name="hide_from_month"]')?.checked ?? false,
      allowComments: root.querySelector('[name="allow_comments"]')?.checked ?? false,
      startDate: startDateValue,
      endDate: endDateValue,
      recurrenceDays,
      occurrences,
      ticketTypes,
      sharedCapacity: root.querySelector('[name="ticket_shared_capacity"]')?.checked ?? false,
      sharedCapacityTotal: root.querySelector('[name="shared_capacity_total"]')?.value?.trim() ?? "",
      waitlistMode: root.querySelector('input[name="waitlist_mode"]:checked')?.value ?? "none",
    };
  };

  const renderImportResults = (container, data) => {
    if (!container) return;
    if (!data) {
      container.innerHTML = "";
      return;
    }

    const config = window.tecRecurringBookingsConfig || {};
    const adminUrl = (config.adminUrl || "").replace(/\/?$/, "/");
    const siteUrl = (config.siteUrl || "").replace(/\/?$/, "/");

    const found = data.found ?? [];
    const missing = data.missing ?? [];
    const errors = data.errors ?? [];
    const summaryLabel = data.summary || "events matched and updated.";
    const ticketSummary = data.ticketSummary || "";
    const ticketCount = typeof data.ticketCount === "number" ? data.ticketCount : null;
    const eventCount = typeof data.eventCount === "number" ? data.eventCount : found.length;
    let html = "";

    if (errors.length) {
      html += `<div><strong>Issues:</strong><ul>${errors
        .map((item) => `<li>${item}</li>`)
        .join("")}</ul></div>`;
    }

    html += `<div><strong>${eventCount}</strong> ${summaryLabel}</div>`;
    if (ticketCount !== null) {
      html += `<div><strong>${ticketCount}</strong> ${ticketSummary || "tickets created."}</div>`;
    }
    const hasEventIds = found.some((item) => item && item.id);
    if (found.length && hasEventIds) {
      html += `<div class="tec-inline" style="margin-top: 12px;">
        <button class="tec-button-secondary" type="button" data-open-all-events>Open all events in new tabs</button>
        ${
          config.eventsManagerUrl
            ? `<a class="tec-button-secondary tec-button-link" target="_blank" rel="noopener" href="${escapeHtml(
                config.eventsManagerUrl
              )}">Open Events Manager</a>`
            : ""
        }
      </div>`;
      html += `<ul class="tec-results-list">${found
        .map((item) => {
          const id = item.id ? String(item.id) : "";
          const slug = item.slug ? String(item.slug) : "";
          const start = item.startDateTime ? String(item.startDateTime) : "";
          const ticketEntries = Array.isArray(item.tickets) ? item.tickets : [];
          const normalizedTickets = ticketEntries
            .map((ticket) => {
              if (ticket == null) return null;
              if (typeof ticket === "object") {
                const id = ticket.id ?? ticket.ticketId ?? ticket.ticket_id ?? ticket.ID;
                if (id == null) return null;
                const name =
                  ticket.name ??
                  ticket.title ??
                  ticket.label ??
                  ticket.ticketName ??
                  ticket.ticket_name ??
                  "";
                return { id: String(id), name: name ? String(name) : "" };
              }
              const id = String(ticket).trim();
              if (!id || id === "[object Object]") return null;
              return { id, name: "" };
            })
            .filter(Boolean);

          const fallbackTickets = Array.isArray(item.ticketIds)
            ? item.ticketIds
                .map((value) => {
                  if (value == null) return null;
                  if (typeof value === "object") {
                    const id = value.id ?? value.ticketId ?? value.ticket_id ?? value.ID;
                    if (id == null) return null;
                    return { id: String(id), name: "" };
                  }
                  const id = String(value).trim();
                  if (!id || id === "[object Object]") return null;
                  return { id, name: "" };
                })
                .filter(Boolean)
            : [];
          const ticketsToShow = normalizedTickets.length ? normalizedTickets : fallbackTickets;
          const editHref =
            adminUrl && id ? `${adminUrl}post.php?post=${encodeURIComponent(id)}&action=edit` : "";
          const viewHref =
            siteUrl && slug ? `${siteUrl}event/${encodeURIComponent(slug)}/` : "";
          const ticketLinks = ticketsToShow.length
            ? ticketsToShow
                .map((ticket) => {
                  const ticketId = ticket.id;
                  const ticketName = ticket.name ? ` (${ticket.name})` : "";
                  const href = adminUrl
                    ? `${adminUrl}post.php?post=${encodeURIComponent(ticketId)}&action=edit`
                    : "";
                  const label = `${ticketId}${ticketName}`;
                  return href
                    ? `<a target="_blank" rel="noopener" href="${escapeHtml(href)}">${escapeHtml(
                        label
                      )}</a>`
                    : escapeHtml(label);
                })
                .join(", ")
            : "";
          return `<li class="tec-results-item">
            <button class="tec-results-open" type="button" data-open-event data-event-id="${escapeHtml(
              id
            )}">${escapeHtml(start)}</button>
            <span class="is-muted">Event ID ${escapeHtml(id)} — ${escapeHtml(slug)}${
              ticketLinks ? ` — Tickets: ${ticketLinks}` : ""
            }</span>
            <span class="tec-results-links">
              ${
                editHref
                  ? `<a target="_blank" rel="noopener" href="${escapeHtml(
                      editHref
                    )}">Edit</a>`
                  : ""
              }
              ${
                viewHref
                  ? `<a target="_blank" rel="noopener" href="${escapeHtml(
                      viewHref
                    )}">View</a>`
                  : ""
              }
            </span>
          </li>`;
        })
        .join("")}</ul>`;
    } else if (found.length) {
      html += `<ul>${found
        .map(
          (item) =>
            `<li>${escapeHtml(item.startDateTime || "")} — <span class="is-muted">${escapeHtml(
              item.slug || ""
            )}</span></li>`
        )
        .join("")}</ul>`;
    } else if (config.eventsManagerUrl) {
      html += `<div class="tec-inline" style="margin-top: 12px;">
        <a class="tec-button-secondary tec-button-link" target="_blank" rel="noopener" href="${escapeHtml(
          config.eventsManagerUrl
        )}">Open Events Manager</a>
      </div>`;
    }

    if (missing.length) {
      html += `<div style="margin-top:8px;"><strong>${missing.length}</strong> expected events not found.</div>`;
      html += `<ul>${missing.map((item) => `<li>${item.startDateTime}</li>`).join("")}</ul>`;
    }

    container.innerHTML = html || "<span class=\"is-muted\">No results yet.</span>";
  };

  const downloadCsv = (content, filename) => {
    const bom = "\ufeff";
    const blob = new Blob([bom + content], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
  };

  const initDateRangePicker = (root) => {
    const fromInput = root.querySelector('[name="event_date_from"]');
    const toInput = root.querySelector('[name="event_date_to"]');
    const $ = window.jQuery;

    if (!fromInput || !toInput) return;
    if (!$ || !$.fn || typeof $.fn.datepicker !== "function") return;

    const sharedOptions = {
      dateFormat: "yy-mm-dd",
      changeMonth: true,
      changeYear: true,
    };

    const applyConstraints = () => {
      const fromVal = fromInput.value.trim();
      const toVal = toInput.value.trim();

      if (fromVal) {
        $(toInput).datepicker("option", "minDate", fromVal);
      } else {
        $(toInput).datepicker("option", "minDate", null);
      }

      if (toVal) {
        $(fromInput).datepicker("option", "maxDate", toVal);
      } else {
        $(fromInput).datepicker("option", "maxDate", null);
      }

      // ISO date string comparison works for YYYY-MM-DD.
      if (fromVal && toVal && fromVal > toVal) {
        toInput.value = fromVal;
        $(toInput).datepicker("setDate", fromVal);
      }
    };

    $(fromInput).datepicker({
      ...sharedOptions,
      onSelect: (dateText) => {
        fromInput.value = dateText;
        applyConstraints();
        fromInput.dispatchEvent(new Event("change", { bubbles: true }));
      },
    });

    $(toInput).datepicker({
      ...sharedOptions,
      onSelect: (dateText) => {
        toInput.value = dateText;
        applyConstraints();
        toInput.dispatchEvent(new Event("change", { bubbles: true }));
      },
    });

    if (fromInput.value) {
      $(fromInput).datepicker("setDate", fromInput.value);
    }
    if (toInput.value) {
      $(toInput).datepicker("setDate", toInput.value);
    }

    fromInput.addEventListener("change", applyConstraints);
    toInput.addEventListener("change", applyConstraints);
    applyConstraints();
  };

  const initForm = (root) => {
    const occurrenceSelect = root.querySelector("[data-occurrence-count]");
    const ticketSelect = root.querySelector("[data-ticket-count]");
    const presetSelect = root.querySelector("[data-preset-select]");
    const savePresetButton = root.querySelector("[data-save-preset]");
    const waitlistField = root.querySelector("[data-waitlist-field]");
    const sharedCapacityField = root.querySelector("[data-shared-capacity-field]");
    const sharedCapacityInput = root.querySelector('[name="ticket_shared_capacity"]');
    const sharedCapacityControl = root.querySelector("[data-shared-capacity-control]");
    const sharedCapacityNote = root.querySelector("[data-shared-capacity-note]");
    const sharedCapacityTotalInput = root.querySelector('[name="shared_capacity_total"]');
    const configPresets = window.tecRecurringBookingsConfig?.presets || [];

    const getTicketQuantitySum = () => {
      return Array.from(root.querySelectorAll('input[name^="ticket_"][name$="_quantity"]'))
        .map((input) => Number(input.value || 0))
        .filter((value) => !Number.isNaN(value) && value > 0)
        .reduce((sum, value) => sum + value, 0);
    };

    const syncSharedCapacityTotal = () => {
      if (!sharedCapacityTotalInput) return;
      const sum = getTicketQuantitySum();
      sharedCapacityTotalInput.max = sum ? String(sum) : "";
      const current = Number(sharedCapacityTotalInput.value || 0);
      if (!sharedCapacityTotalInput.value || current > sum) {
        sharedCapacityTotalInput.value = sum ? String(sum) : "";
      }
    };

    const syncWaitlistVisibility = (count) => {
      if (!waitlistField) return;
      if (count > 0) {
        waitlistField.classList.remove("is-hidden");
        return;
      }
      waitlistField.classList.add("is-hidden");
      const noneRadio = root.querySelector('input[name="waitlist_mode"][value="none"]');
      if (noneRadio) {
        noneRadio.checked = true;
      }
    };

    if (occurrenceSelect) {
      const defaultValue = occurrenceSelect.value || "1";
      occurrenceSelect.innerHTML = buildOccurrenceOptions(defaultValue);
      occurrenceSelect.value = defaultValue;
      renderOccurrences(root, Number(defaultValue));
      occurrenceSelect.addEventListener("change", (event) => {
        renderOccurrences(root, Number(event.target.value || 1));
      });
    }

    if (ticketSelect) {
      const defaultValue = ticketSelect.value || "1";
      ticketSelect.innerHTML = buildTicketOptions(defaultValue);
      ticketSelect.value = defaultValue;
      renderTickets(root, Number(defaultValue));
      syncWaitlistVisibility(Number(defaultValue));
      ticketSelect.addEventListener("change", (event) => {
        const currentCount = Number(event.target.value || 0);
        renderTickets(root, currentCount);
        syncWaitlistVisibility(currentCount);
        if (sharedCapacityField) {
          if (currentCount > 1) {
            sharedCapacityField.classList.remove("is-hidden");
          } else {
            sharedCapacityField.classList.add("is-hidden");
            if (sharedCapacityInput) {
              sharedCapacityInput.checked = false;
            }
          }
        }
        syncSharedCapacityTotal();
      });
    }

    initDateRangePicker(root);
    initFeaturedImage(root);

    if (presetSelect && Array.isArray(configPresets)) {
      const options = [
        '<option value="">Select preset</option>',
        ...configPresets.map((preset, index) => {
          const label = preset?.name || `Preset ${index + 1}`;
          return `<option value="${index}">${escapeHtml(label)}</option>`;
        }),
      ];
      presetSelect.innerHTML = options.join("");
      presetSelect.addEventListener("change", () => {
        const index = Number(presetSelect.value);
        if (Number.isNaN(index)) return;
        const preset = configPresets[index];
        if (preset) {
          applyPresetToForm(root, preset);
        }
      });
    }

    if (sharedCapacityField && ticketSelect) {
      const initialCount = Number(ticketSelect.value || 0);
      if (initialCount > 1) {
        sharedCapacityField.classList.remove("is-hidden");
      } else {
        sharedCapacityField.classList.add("is-hidden");
      }
    }

    if (sharedCapacityInput && sharedCapacityControl && sharedCapacityNote) {
      const toggleSharedCapacity = () => {
        const show = !!sharedCapacityInput.checked;
        sharedCapacityControl.classList.toggle("is-hidden", !show);
        sharedCapacityNote.classList.toggle("is-hidden", !show);
        if (show) {
          syncSharedCapacityTotal();
        }
      };
      sharedCapacityInput.addEventListener("change", toggleSharedCapacity);
      toggleSharedCapacity();
    }

    if (root) {
      root.addEventListener("input", (event) => {
        const target = event.target;
        if (target && target.matches('input[name^="ticket_"][name$="_quantity"]')) {
          syncSharedCapacityTotal();
        }
      });
    }

    if (savePresetButton) {
      savePresetButton.addEventListener("click", async () => {
        const config = window.tecRecurringBookingsConfig;
        if (!config || !config.ajaxUrl || !config.nonce) {
          window.alert("WordPress AJAX is not available in preview mode.");
          return;
        }
        const presetName = window.prompt("Preset name");
        if (!presetName) {
          return;
        }
        const payload = buildEventPayload(root);
        try {
          const formData = new FormData();
          formData.append("action", "tec_rb_save_preset");
          formData.append("nonce", config.nonce);
          formData.append("name", presetName);
          formData.append("data", JSON.stringify(payload));
          const response = await fetch(config.ajaxUrl, {
            method: "POST",
            body: formData,
          });
          const parsed = await parseJsonResponse(response);
          if (!parsed.ok || !parsed.data?.success) {
            window.alert(parsed.data?.data?.message || "Unable to save preset.");
            return;
          }
          const presets = parsed.data.data?.presets || [];
          window.tecRecurringBookingsConfig.presets = presets;
          if (presetSelect) {
            const options = [
              '<option value="">Select preset</option>',
              ...presets.map((preset, index) => {
                const label = preset?.name || `Preset ${index + 1}`;
                return `<option value="${index}">${escapeHtml(label)}</option>`;
              }),
            ];
            presetSelect.innerHTML = options.join("");
            presetSelect.value = String(presets.length - 1);
          }
          window.alert("Preset saved.");
        } catch (error) {
          window.alert("Unable to save preset.");
        }
      });
    }

    // CSV export is intentionally omitted in admin flow.

    const resultsContainer = root.querySelector("[data-import-results]");
    if (resultsContainer) {
      resultsContainer.innerHTML = "<span class=\"is-muted\">No results yet.</span>";
      resultsContainer.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;
        const config = window.tecRecurringBookingsConfig || {};
        const adminUrl = (config.adminUrl || "").replace(/\/?$/, "/");
        if (!adminUrl) return;

        const openSingle = target.closest("[data-open-event]");
        if (openSingle) {
          const eventId = openSingle.getAttribute("data-event-id") || "";
          if (eventId) {
            window.open(`${adminUrl}post.php?post=${encodeURIComponent(eventId)}&action=edit`, "_blank", "noopener");
          }
          return;
        }

        const openAll = target.closest("[data-open-all-events]");
        if (openAll) {
          const ids = Array.from(resultsContainer.querySelectorAll("[data-open-event][data-event-id]"))
            .map((node) => node.getAttribute("data-event-id"))
            .filter(Boolean);
          ids.forEach((id) => {
            window.open(`${adminUrl}post.php?post=${encodeURIComponent(id)}&action=edit`, "_blank", "noopener");
          });
        }
      });
    }

    const dryRunButton = root.querySelector("[data-dry-run]");
    if (dryRunButton) {
      dryRunButton.addEventListener("click", async () => {
        const config = window.tecRecurringBookingsConfig;
        if (!config || !config.ajaxUrl || !config.nonce) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["WordPress AJAX is not available in preview mode."],
            summary: "events would be created.",
          });
          return;
        }

        const payload = buildEventPayload(root);
        if (!payload.eventName || !payload.startDate || !payload.endDate) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["Please provide event name, start date, and end date."],
            summary: "events would be created.",
          });
          return;
        }

        renderImportResults(resultsContainer, {
          found: [],
          missing: [],
          errors: ["Running dry preview..."],
          summary: "events would be created.",
        });

        try {
          const formData = new FormData();
          formData.append("action", "tec_rb_dry_run");
          formData.append("nonce", config.nonce);
          formData.append("payload", JSON.stringify(payload));

          const response = await fetch(config.ajaxUrl, {
            method: "POST",
            body: formData,
          });
          const parsed = await parseJsonResponse(response);
          if (!parsed.ok) {
            renderImportResults(resultsContainer, {
              found: [],
              missing: [],
              errors: [parsed.text || "Unable to run dry preview."],
              summary: "events would be created.",
            });
            return;
          }
          const data = parsed.data;
          if (!data.success) {
            renderImportResults(resultsContainer, {
              found: [],
              missing: [],
              errors: [data.data?.message || "Unable to run dry preview."],
              summary: "events would be created.",
            });
            return;
          }

          renderImportResults(resultsContainer, data.data);
        } catch (error) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["Unexpected error while running dry preview."],
            summary: "events would be created.",
          });
        }
      });
    }

    const createEventsButton = root.querySelector("[data-create-events]");
    if (createEventsButton) {
      createEventsButton.addEventListener("click", async () => {
        const config = window.tecRecurringBookingsConfig;
        if (!config || !config.ajaxUrl || !config.nonce) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["WordPress AJAX is not available in preview mode."],
            summary: "events created.",
          });
          return;
        }

        const payload = buildEventPayload(root);
        if (!payload.eventName || !payload.startDate || !payload.endDate) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["Please provide event name, start date, and end date."],
            summary: "events created.",
          });
          return;
        }

        renderImportResults(resultsContainer, {
          found: [],
          missing: [],
          errors: ["Creating events and tickets in WordPress..."],
          summary: "events created.",
        });

        try {
          const formData = new FormData();
          formData.append("action", "tec_rb_create_events_tickets");
          formData.append("nonce", config.nonce);
          formData.append("payload", JSON.stringify(payload));

          const response = await fetch(config.ajaxUrl, {
            method: "POST",
            body: formData,
          });
          const parsed = await parseJsonResponse(response);
          if (!parsed.ok) {
            renderImportResults(resultsContainer, {
              found: [],
              missing: [],
              errors: [parsed.text || "Unable to create events and tickets."],
              summary: "events created.",
            });
            return;
          }
          const data = parsed.data;
          if (!data.success) {
            renderImportResults(resultsContainer, {
              found: [],
              missing: [],
              errors: [data.data?.message || "Unable to create events and tickets."],
              summary: "events created.",
            });
            return;
          }

          renderImportResults(resultsContainer, data.data);
        } catch (error) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["Unexpected error while creating events and tickets."],
            summary: "events created.",
          });
        }
      });
    }

    const deleteButton = root.querySelector("[data-delete-batch]");
    const confirmModal = root.querySelector("[data-confirm-modal]");
    const modalCancel = root.querySelector("[data-modal-cancel]");
    const modalConfirm = root.querySelector("[data-modal-confirm]");

    const openModal = () => {
      if (!confirmModal) return;
      confirmModal.classList.add("is-open");
      confirmModal.setAttribute("aria-hidden", "false");
      confirmModal.removeAttribute("hidden");
    };

    const closeModal = () => {
      if (!confirmModal) return;
      confirmModal.classList.remove("is-open");
      confirmModal.setAttribute("aria-hidden", "true");
      confirmModal.setAttribute("hidden", "");
    };

    const executeDelete = async () => {
        const config = window.tecRecurringBookingsConfig;
        if (!config || !config.ajaxUrl || !config.nonce) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["WordPress AJAX is not available in preview mode."],
            summary: "events deleted.",
          });
          return;
        }

        renderImportResults(resultsContainer, {
          found: [],
          missing: [],
          errors: ["Deleting last batch..."],
          summary: "events deleted.",
        });

        try {
          const formData = new FormData();
          formData.append("action", "tec_rb_delete_last_batch");
          formData.append("nonce", config.nonce);

          const response = await fetch(config.ajaxUrl, {
            method: "POST",
            body: formData,
          });
          const parsed = await parseJsonResponse(response);
          if (!parsed.ok) {
            renderImportResults(resultsContainer, {
              found: [],
              missing: [],
              errors: [parsed.text || "Unable to delete last batch."],
              summary: "events deleted.",
            });
            return;
          }
          const data = parsed.data;
          if (!data.success) {
            renderImportResults(resultsContainer, {
              found: [],
              missing: [],
              errors: [data.data?.message || "Unable to delete last batch."],
              summary: "events deleted.",
            });
            return;
          }

          renderImportResults(resultsContainer, data.data);
        } catch (error) {
          renderImportResults(resultsContainer, {
            found: [],
            missing: [],
            errors: ["Unexpected error while deleting last batch."],
            summary: "events deleted.",
          });
        }
    };

    if (deleteButton) {
      deleteButton.addEventListener("click", () => {
        openModal();
      });
    }

    if (modalCancel) {
      modalCancel.addEventListener("click", () => {
        closeModal();
      });
    }

    if (modalConfirm) {
      modalConfirm.addEventListener("click", async () => {
        closeModal();
        await executeDelete();
      });
    }

    if (confirmModal) {
      confirmModal.addEventListener("click", (event) => {
        if (event.target === confirmModal) {
          closeModal();
        }
      });
    }

    const debugButton = root.querySelector("[data-debug-compare]");
    const debugResults = root.querySelector("[data-debug-results]");
    const debugCopyButton = root.querySelector("[data-debug-copy]");
    const ticketPairsContainer = root.querySelector("[data-debug-ticket-pairs]");
    const addPairButton = root.querySelector("[data-debug-add-pair]");
    let lastDebugPayloadText = "";
    if (debugResults) {
      debugResults.innerHTML = "<span class=\"is-muted\">No debug output yet.</span>";
    }
    if (debugCopyButton) {
      debugCopyButton.disabled = true;
      debugCopyButton.addEventListener("click", async () => {
        const text = lastDebugPayloadText || "";
        if (!text) return;
        try {
          if (navigator.clipboard && navigator.clipboard.writeText) {
            await navigator.clipboard.writeText(text);
            return;
          }
        } catch (error) {
          // fall through
        }
        const textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.style.position = "fixed";
        textarea.style.left = "-9999px";
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        try {
          document.execCommand("copy");
        } catch (error) {
          // ignore
        }
        document.body.removeChild(textarea);
      });
    }

    const syncRemoveButtons = () => {
      if (!ticketPairsContainer) return;
      const pairs = Array.from(ticketPairsContainer.querySelectorAll("[data-debug-ticket-pair]"));
      pairs.forEach((pair, index) => {
        const remove = pair.querySelector("[data-debug-remove-pair]");
        if (!(remove instanceof HTMLButtonElement)) return;
        remove.disabled = index === 0 && pairs.length === 1;
      });
    };

    if (ticketPairsContainer) {
      ticketPairsContainer.addEventListener("click", (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;
        const remove = target.closest("[data-debug-remove-pair]");
        if (remove) {
          const pair = remove.closest("[data-debug-ticket-pair]");
          if (pair && ticketPairsContainer.querySelectorAll("[data-debug-ticket-pair]").length > 1) {
            pair.remove();
            syncRemoveButtons();
          }
        }
      });
      syncRemoveButtons();
    }

    if (addPairButton && ticketPairsContainer) {
      addPairButton.addEventListener("click", () => {
        const pairs = Array.from(ticketPairsContainer.querySelectorAll("[data-debug-ticket-pair]"));
        if (pairs.length >= 6) return;
        const template = pairs[0];
        if (!template) return;
        const clone = template.cloneNode(true);
        clone.querySelectorAll("input").forEach((input) => {
          input.value = "";
        });
        const remove = clone.querySelector("[data-debug-remove-pair]");
        if (remove instanceof HTMLButtonElement) {
          remove.disabled = false;
        }
        ticketPairsContainer.appendChild(clone);
        syncRemoveButtons();
      });
    }

    if (debugButton) {
      debugButton.addEventListener("click", async () => {
        const config = window.tecRecurringBookingsConfig;
        if (!config || !config.ajaxUrl || !config.nonce) {
          if (debugResults) {
            debugResults.innerHTML = "<span class=\"is-muted\">WordPress AJAX is not available in preview mode.</span>";
          }
          return;
        }

        const manualEventId = root.querySelector("[data-debug-manual-event]")?.value?.trim() || "";
        const pluginEventId = root.querySelector("[data-debug-plugin-event]")?.value?.trim() || "";
        const ticketPairs = ticketPairsContainer
          ? Array.from(ticketPairsContainer.querySelectorAll("[data-debug-ticket-pair]"))
              .map((pair) => {
                const manualTicketId = pair.querySelector("[data-debug-manual-ticket]")?.value?.trim() || "";
                const pluginTicketId = pair.querySelector("[data-debug-plugin-ticket]")?.value?.trim() || "";
                return { manual_ticket_id: manualTicketId, plugin_ticket_id: pluginTicketId };
              })
              .filter((pair) => pair.manual_ticket_id || pair.plugin_ticket_id)
          : [];

        if (debugResults) {
          debugResults.innerHTML = "<span class=\"is-muted\">Running debug compare...</span>";
        }
        if (debugCopyButton) {
          debugCopyButton.disabled = true;
        }
        lastDebugPayloadText = "";

        try {
          const formData = new FormData();
          formData.append("action", "tec_rb_debug_compare");
          formData.append("nonce", config.nonce);
          formData.append("manual_event_id", manualEventId);
          formData.append("plugin_event_id", pluginEventId);
          formData.append("ticket_pairs", JSON.stringify(ticketPairs));

          const response = await fetch(config.ajaxUrl, {
            method: "POST",
            body: formData,
          });
          const data = await response.json();
          if (!data.success) {
            if (debugResults) {
              debugResults.innerHTML = `<span class="is-muted">${escapeHtml(
                data.data?.message || "Unable to run debug compare."
              )}</span>`;
            }
            return;
          }

          const payload = JSON.stringify(data.data, null, 2);
          lastDebugPayloadText = payload;
          if (debugResults) {
            debugResults.innerHTML = `<pre>${escapeHtml(payload)}</pre>`;
          }
          if (debugCopyButton) {
            debugCopyButton.disabled = false;
          }
        } catch (error) {
          if (debugResults) {
            debugResults.innerHTML = "<span class=\"is-muted\">Unexpected error while running debug compare.</span>";
          }
        }
      });
    }
  };

  document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll("[data-tec-recurring-bookings]").forEach(initForm);
  });
})();
