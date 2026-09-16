# Implementation Guide for Dynamic Record Management

## Current Implementation Status

The record detail popup is implemented in the `Exp` module.

- `Exp/assets/js/exp.js` adds a click handler to every rendered record row.
- Clicking any part of a record outside the existing action buttons opens `showRecordDetails(row)`.
- Standard fields and every `custom_data` field are rendered dynamically.
- Both current custom-field objects (`{ type, value }`) and legacy scalar values are supported.
- Long values wrap safely, and the detail list has its own vertical scroll area.
- `Exp/assets/css/exp.css` provides the bounded popup layout and bottom pill-shaped Edit/Delete controls.
- The popup reuses the existing `editRecord(row)` and `deleteRecord(id)` flows, so no new API or database change is required.
- The standalone Expense Actions column has been removed; row actions are now available from the detail popup only.
- Savings Deposit History and Manage Savings Goals use the same scrollable detail-popup pattern with bottom pill-shaped Edit/Delete controls.
- Savings transaction editing and deletion use CSRF-protected endpoints with both transaction and goal ownership checks.

### User Behavior

For sections such as `ROOM RENT`, a user can click anywhere on a saved record to open its complete details. Even when the record contains many Add Field values, the user can scroll up and down inside the popup to see everything. The Edit and Delete controls stay at the bottom of the popup and remain usable on desktop and mobile.

The same interaction is available for Savings Deposit History and Manage Savings Goals. Deposit transactions can be edited or deleted from their detail popup, and the related goal total is recalculated from the remaining transactions. Manage Savings Goals no longer has a separate Actions column; its Edit and Delete controls are inside the goal detail popup.

### Validation Status

- Workspace diagnostics report no errors in the edited JavaScript or CSS.
- PHP and command-line JavaScript checks could not run in the current environment because `php` and `node` are not installed.

## Objective
The system should let a user:
- create custom data sections/fields
- add records under each section
- see all records in a scrollable list
- click any record to view full details
- edit existing records
- delete records safely
- support sections where each record may contain a different number of custom fields

This is a dynamic, user-friendly record management flow for a finance / custom form system.

---

## 1. Core User Flow

### 1.1 Create a Section
1. User opens the sidebar or section manager.
2. User clicks “Add Section”.
3. The app asks for a section name.
4. The system creates a section and stores it in the database.
5. The section becomes selectable in the left sidebar.

### 1.2 Add Custom Fields
1. User selects a section.
2. User clicks “Add Field”.
3. User provides:
   - field label
   - field type (text, number, date, dropdown, textarea, etc.)
   - optional placeholder / required flag
4. The app stores the field definition in the section metadata.

### 1.3 Add a Record
1. User selects the section.
2. User clicks “Add Record”.
3. The app renders the form dynamically from the section's field definitions.
4. User fills values.
5. User saves the record.
6. The new record appears in the list instantly.

### 1.4 View Record Details
1. Record list is shown in a vertical scrollable area.
2. Each row/card is clickable.
3. When clicked, the app opens a detail panel or modal.
4. The panel displays all values for that record, regardless of how many fields exist.
5. The details are shown in a clean, structured layout.

### 1.5 Edit Record
1. User clicks “Edit” on the open record.
2. The same dynamic form opens with the existing values prefilled.
3. User changes fields and saves.
4. The list refreshes and updated values appear immediately.

### 1.6 Delete Record
1. User clicks “Delete”.
2. App asks for confirmation.
3. If confirmed, the record is removed from the database.
4. The list updates automatically.

---

## 2. Required UX Behaviors

### 2.1 Scrollable Record List
The record list should be a scrollable container with enough height to show many records without breaking the page layout.

Recommended behavior:
- fixed list height (for example 60vh or 520px)
- vertical scroll enabled
- sticky header if needed
- each record card has a clear separation border

### 2.2 Clickable Cards
Each record item should be clickable and should show a selection state.

Example states:
- default: neutral background
- hover: highlight
- selected: accent border or shadow
- detail panel opens on click

### 2.3 Dynamic Field Rendering
The system should not hard-code a fixed form layout.

Instead:
- fetch field metadata from the section
- loop through the fields
- render each input based on its type
- reuse the same logic for both create and edit forms

### 2.4 Flexible Field Count
A record may have only 2 fields or 20 fields. The detail view must support both.

Implementation approach:
- store record values as JSON in a `custom_data` field or equivalent structured column
- when rendering details, iterate over the object keys
- display each field label and value dynamically

---

## 3. Data Model

### 3.1 Section Table
```sql
CREATE TABLE user_categories (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  category_name VARCHAR(255) NOT NULL,
  budget DECIMAL(12,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3.2 Record Table
```sql
CREATE TABLE expenses (
  id INT PRIMARY KEY AUTO_INCREMENT,
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  entry_date DATE NOT NULL,
  entry_time TIME NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  custom_data JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3.3 Why JSON for Custom Data?
Because each record can have different custom fields, the best approach is to store them as structured JSON, for example:

```json
{
  "vendor": { "label": "Vendor", "type": "text", "value": "ABC Shop" },
  "payment_mode": { "label": "Payment Mode", "type": "select", "value": "Cash" },
  "reference": { "label": "Reference", "type": "text", "value": "INV-2048" },
  "notes": { "label": "Notes", "type": "textarea", "value": "Lunch meeting" }
}
```

or simpler:

```json
{
  "vendor": "ABC Shop",
  "payment_mode": "Cash",
  "reference": "INV-2048",
  "notes": "Lunch meeting"
}
```

The app can merge the section schema with the record value data during rendering.

---

## 4. Backend Requirements

### 4.1 Fetch Records
Endpoint:
- `GET /api.php?action=get_records&category_id=...`

Response:
```json
{
  "status": "success",
  "data": [
    {
      "id": 12,
      "amount": 250,
      "entry_date": "2026-09-16",
      "entry_time": "15:40:00",
      "description": "Lunch",
      "custom_data": {
        "vendor": "ABC Shop",
        "payment_mode": "Cash"
      }
    }
  ],
  "schema": {
    "vendor": "text",
    "payment_mode": "select"
  }
}
```

### 4.2 Add Record
Endpoint:
- `POST /api.php?action=add_record`

Payload:
```json
{
  "category_id": 5,
  "entry_date": "2026-09-16",
  "entry_time": "15:40:00",
  "amount": 250,
  "description": "Lunch",
  "custom_data": {
    "vendor": "ABC Shop",
    "payment_mode": "Cash"
  }
}
```

### 4.3 Update Record
Endpoint:
- `POST /api.php?action=update_record`

### 4.4 Delete Record
Endpoint:
- `POST /api.php?action=delete_record`

### 4.5 Ownership/Permissions
Every request should verify:
- user is logged in
- category belongs to the current user
- record belongs to the current user
- admin requests must use target user ID validation if applicable

---

## 5. Frontend Implementation

### 5.1 HTML Structure
You need these containers:
- sidebar for sections
- top header with month filter and add record button
- summary cards
- records list container
- detail modal or side panel
- form modal for add/edit

Example layout:
```html
<div class="record-list-panel">
  <div class="record-list-header">Records</div>
  <div id="recordList" class="record-list-scroll"></div>
</div>

<div id="recordDetailModal" class="modal hidden"></div>
<div id="recordFormModal" class="modal hidden"></div>
```

### 5.2 Rendering Records
Pseudo-code:
```js
async function loadRecords(categoryId) {
  const res = await fetch(`/api.php?action=get_records&category_id=${categoryId}`);
  const json = await res.json();

  const records = json.data || [];
  const schema = json.schema || {};

  renderRecordList(records, schema);
}
```

```js
function renderRecordList(records, schema) {
  recordList.innerHTML = '';

  records.forEach(record => {
    const card = document.createElement('div');
    card.className = 'record-card';
    card.addEventListener('click', () => openRecordDetails(record));

    card.innerHTML = `
      <div class="record-top">
        <strong>${escapeHtml(record.description || 'Untitled Record')}</strong>
        <span>${formatCurrency(record.amount)}</span>
      </div>
      <div class="record-meta">${record.entry_date} • ${record.entry_time}</div>
    `;

    recordList.appendChild(card);
  });
}
```

### 5.3 Detail Panel Rendering
```js
function openRecordDetails(record) {
  const customData = record.custom_data || {};
  const fields = Object.entries(customData)
    .map(([key, value]) => `<div class="detail-row"><span>${escapeHtml(key)}</span><strong>${escapeHtml(value)}</strong></div>`)
    .join('');

  detailModal.innerHTML = `
    <div class="modal-content">
      <h3>Record Details</h3>
      <div class="detail-grid">${fields}</div>
      <div class="actions">
        <button onclick="editRecord(${record.id})">Edit</button>
        <button class="danger" onclick="deleteRecord(${record.id})">Delete</button>
      </div>
    </div>
  `;
}
```

### 5.4 Dynamic Form Rendering
```js
function renderDynamicForm(schema, initialValues = {}) {
  const form = document.createElement('form');

  Object.entries(schema).forEach(([key, type]) => {
    const field = createFieldElement(key, type, initialValues[key] || '');
    form.appendChild(field);
  });

  return form;
}
```

### 5.5 Save Flow
```js
async function saveRecord(recordData, isEdit = false) {
  const endpoint = isEdit ? 'update_record' : 'add_record';

  const res = await fetch('/api.php?action=' + endpoint, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(recordData)
  });

  const result = await res.json();
  if (result.status === 'success') {
    closeModal();
    loadRecords(currentCategoryId);
  }
}
```

---

## 6. Important Implementation Rules

### Rule 1: Never assume a fixed field set
The system must work if a record has 1 field, 5 fields, or 20 fields.

### Rule 2: Treat records as objects
Do not render them as rigid table columns only. Use data-driven display.

### Rule 3: Use one shared renderer for add/edit/detail
This keeps the system consistent and avoids duplicated logic.

### Rule 4: Always refresh after create/edit/delete
User feedback should be immediate and the list must update after every operation.

### Rule 5: Use confirmations for destructive actions
Delete should require a second confirmation before removing data.

---

## 7. Best Practice for Filtering and Sorting
Add these next-level behaviors:
- sort by newest / oldest / highest amount / lowest amount
- filter by month / date range
- search by description or custom field value
- show summary cards for total, spent, remaining budget

This allows users to find records quickly even when the list is large.

---

## 8. Validation and Error Handling

### Required validation
- category must exist
- amount must be numeric
- date and time must be present
- user must own the record or category
- invalid JSON/custom data should be ignored safely

### UI feedback
- show success toast after save
- show error toast after failed operation
- show loading spinner while fetching records
- show empty state when no records exist

---

## 9. Acceptance Criteria
The feature is complete when:
- user can create a section
- user can add custom fields
- user can add records with data for those fields
- records appear in a scrollable list
- clicking a record opens its full details
- record can be edited
- record can be deleted
- all operations work even when field count differs per record
- list updates immediately after any change

---

## 10. Recommended Implementation Order
1. Create section model and API
2. Create field definition storage
3. Render dynamic form builder
4. Add record save endpoint
5. Implement list rendering
6. Add clickable record detail area
7. Add edit flow
8. Add delete confirmation
9. Improve UX with sorting/filtering and empty states

---

## 11. Final Recommendation
The most robust approach is:
- store section field definitions separately
- store record data as JSON in the main record table
- render records and forms dynamically from metadata
- keep the record list scrollable and clickable
- show detail modal or drawer for each selected record

This architecture is scalable and works for records with different numbers of fields without changing database tables for each new field.

This is the correct foundation for a user-friendly, dynamic, scalable custom record system.
