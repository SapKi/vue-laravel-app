# Review Queue

A content moderation review queue built with **Vue 3** (frontend) and **Laravel 12** (backend API).

> **The app runs on port 8000.** Open http://localhost:8000 in your browser.


To run the app:

```bash
# Terminal 1
php artisan serve       # → http://localhost:8000  (open this in your browser)

# Terminal 2
npm run dev             # → Vite HMR on port 3000 (internal, do not open directly)
```

> Full setup instructions with installation steps are below.

## Ports

| Service | Port | URL | Purpose |
|---|---|---|---|
| **App** (Laravel + Vue) | **8000** | http://localhost:8000 | Open this in your browser |
| Vite HMR asset server | 3000 | http://localhost:3000 | Internal only — provides hot reload |

> **How it works:** Laravel (port 8000) serves the HTML shell and your Vue SPA. Vite (port 3000) runs silently in the background, serving JS/CSS with hot module replacement. You never need to open port 3000 directly.

---

## Running the App

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+

### 1. Install dependencies

```bash
composer install
npm install
```

### 2. Set up environment

```bash
cp .env.example .env
php artisan key:generate
```

Make sure these are set in `.env`:

```
DB_CONNECTION=sqlite
SESSION_DRIVER=file
```

### 3. Run migrations and seed

```bash
php artisan migrate
# Optional: seed with sample items
php artisan db:seed
```

### 4. Start both servers

Open two terminals:

**Terminal 1 — Backend (port 8000):**
```bash
php artisan serve
```

**Terminal 2 — Frontend (port 3000):**
```bash
npm run dev
```

Then open http://localhost:8000.

### Running Tests

```bash
php artisan test
```

---

## Backend Implementation

### Persistence: SQLite

The app uses **SQLite** (file: `database/database.sqlite`), chosen because it requires zero infrastructure setup — no database server, no credentials, nothing to install beyond PHP itself. The file is created automatically on first migration.

**Why not in-memory?** Laravel is a traditional request/response framework where every HTTP request boots a fresh PHP process. An in-memory store (a static array or class variable) would be wiped on every request — nothing would survive between calls. SQLite gives full relational persistence with the same zero-setup convenience.

**How it works in the code:**
- Two migrations define the schema (`items` + `item_notes` tables)
- Two Eloquent models (`Item`, `ItemNote`) map rows to PHP objects with typed casts (`flags` → array, `reviewed_at` → datetime, `risk_score` → integer)
- Controllers use standard Eloquent (`Item::create()`, `$item->update()`, `$item->load('notes')`) — no raw SQL anywhere

**One SQLite quirk worked around:** SQLite does not echo back column default values after an `INSERT`. So `status: 'pending'` is passed explicitly in `Item::create()` instead of relying on the database default — otherwise the API response would have `status: null` on new submissions.

**For production:** swap to Postgres or MySQL by changing `.env` only:
```
DB_CONNECTION=pgsql
DB_HOST=your-host
DB_PORT=5432
DB_DATABASE=your-db
DB_USERNAME=your-user
DB_PASSWORD=your-pass
```
No application code changes needed — Eloquent is database-agnostic and the same models, migrations, and controllers run identically on any supported engine.

**What is better for production — PostgreSQL.**

SQLite writes are serialized (one writer at a time) and the file lives on the same server as the app, making it unsuitable once you have concurrent traffic or multiple app instances. PostgreSQL handles thousands of concurrent connections, supports row-level locking, has mature replication and backup tooling, and is the standard choice for Laravel applications in production. MySQL is a reasonable alternative but PostgreSQL is generally preferred for new projects due to stronger standards compliance, better JSON support, and superior handling of complex queries.

---

### Data Model
items — the main content record. Stores the submitted title/content, the status (pending / approved / rejected), and everything the moderation service computes automatically on submission: risk_score, flags (JSON array), suggested_action. Also stores reviewer decisions: reviewer_note and reviewed_at (both nullable, cleared on reopen).

item_notes — a separate table for free-form notes. Multiple notes can be attached to any item at any time, independent of approve/reject decisions. Foreign key to items with cascade delete.

#### `items` table

| Column | Type | Description |
|---|---|---|
| `id` | integer PK | Auto-increment |
| `title` | string | Required |
| `content` | text | Required |
| `status` | enum | `pending` / `approved` / `rejected` — default `pending` |
| `risk_score` | tinyint (0–100) | Computed by moderation service on submission |
| `flags` | JSON | Array of signal names, e.g. `["spam", "caps_heavy"]` |
| `suggested_action` | enum (nullable) | `approve` / `reject` / `null` — computed on submission |
| `reviewer_note` | text (nullable) | Note attached at review time |
| `reviewed_at` | timestamp (nullable) | Set on approve/reject, cleared on reopen |
| `created_at / updated_at` | timestamps | Automatic |

#### `item_notes` table

A separate notes table allows multiple free-form notes to be attached to any item at any time, independently of the review decision.

| Column | Type | Description |
|---|---|---|
| `id` | integer PK | Auto-increment |
| `item_id` | FK → items | Cascade delete |
| `body` | text | Note content |
| `created_at / updated_at` | timestamps | Automatic |

---

### Error Handling

Every API response returns a structured JSON error — never a raw exception or HTML page.

| Scenario | HTTP status | Response body |
|---|---|---|
| Missing required field | 422 | `{ "message": "...", "errors": { "field": ["..."] } }` |
| Invalid enum value (e.g. status) | 422 | `{ "message": "...", "errors": { "status": ["..."] } }` |
| Reviewing an already-reviewed item | 422 | `{ "message": "Item has already been reviewed." }` |
| Reopening an already-pending item | 422 | `{ "message": "Item is already pending." }` |
| Item not found | 404 | Laravel default JSON 404 |
| Database write fails (`QueryException`) | 500 | `{ "message": "Failed to save item. Please try again." }` |

All critical write operations (`store`, `review`, `reopen`, `saveNote`) wrap their database calls in a `try/catch (QueryException)`. This means unexpected database errors return a structured JSON 500 with a human-readable message rather than an unhandled exception. The frontend reads `response.data.message` and displays it inline to the reviewer.

---

### API Endpoints

All routes are under `/api` — no authentication required.

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/items` | List items with filtering, search, sort, pagination |
| `POST` | `/api/items` | Submit a new item — moderation runs automatically |
| `GET` | `/api/items/{id}` | Get a single item with its notes |
| `PATCH` | `/api/items/{id}/review` | Approve or reject a pending item |
| `PATCH` | `/api/items/{id}/reopen` | Reopen a reviewed item (reset to pending) |
| `PATCH` | `/api/items/{id}/note` | Add a free-form note to an item |
| `DELETE` | `/api/items/{id}/notes/{noteId}` | Delete a specific note |
| `DELETE` | `/api/items/{id}` | Delete an item entirely |

#### `GET /api/items` — query parameters

| Parameter | Values | Default |
|---|---|---|
| `status` | `pending` / `approved` / `rejected` | all |
| `search` | string | — |
| `sort` | `created_at` / `risk_score` / `title` | `created_at` |
| `order` | `asc` / `desc` | `desc` |
| `page` | integer | `1` (10 items per page) |

---

### Moderation Heuristic

Every item is automatically analyzed by `app/Services/ModerationService.php` the moment it is submitted — before it is saved to the database. No reviewer action is required to trigger it.

#### What it produces

Three fields are computed and stored on every item:

| Field | Type | Example |
|---|---|---|
| `risk_score` | integer 0–100 | `85` |
| `flags` | JSON array | `["spam", "caps_heavy"]` |
| `suggested_action` | enum or null | `"reject"` |

#### How the score is calculated

The service runs five independent signal checks against the lowercased title + content. Each signal adds points and sets a flag:

| Signal | Points (capped) | Flag |
|---|---|---|
| Spam keywords matched (e.g. "buy now", "free money", "guaranteed") | +12 per match, max 35 | `spam` |
| Offensive keywords matched (e.g. "hate", "idiot", "worthless") | +15 per match, max 30 | `offensive` |
| >40% of letters are uppercase | +20 | `caps_heavy` |
| Content contains a URL (`http://` or `https://`) | +10 | `has_urls` |
| Content is under 15 characters | +5 | `very_short` |

Total score is capped at 100. Signals are independent — a single item can trigger multiple flags and accumulate points from all of them.

#### How the suggested action is derived

```
score = 0          → suggested_action = "approve"
score >= 35        → suggested_action = "reject"
1 ≤ score ≤ 34    → suggested_action = null  (human judgment required)
```

The threshold of 35 was chosen so that a single strong spam signal (3+ keyword matches) is enough to trigger a reject suggestion, while borderline content (e.g. a URL alone, scoring 10) stays in the grey zone.

#### Design decisions worth discussing

- **Advisory only** — the suggestion never blocks submission or auto-rejects. A human reviewer always makes the final call and can override it in either direction.
- **Keyword lists are constants in the service** — easy to extend or replace with a database-driven list without changing any other code.
- **Per-signal caps** — spam is capped at 35 points, offensive at 30, so no single signal can dominate the score on its own. This keeps the combined signals meaningful.
- **Flags are stored separately from the score** — the UI can show exactly *why* an item scored high, not just that it did.
- **Moderation result is preserved on reopen** — if a reviewer reopens a rejected item, the original `risk_score`, `flags`, and `suggested_action` stay intact. The content hasn't changed, so the analysis shouldn't either.

#### What could be improved for production

- Replace keyword lists with a proper ML classifier or third-party moderation API (e.g. OpenAI Moderation, Perspective API)
- Make thresholds and keyword lists configurable per-tenant from the database
- Add a confidence score alongside the suggested action
- Log every moderation decision for auditing and model retraining

---

## Frontend

Built with **Vue 3** (Composition API + script setup), **Pinia** for state, and **Vue Router**.

- **Queue view** (`/`) — paginated list with status tabs, search, sort, and risk badge popovers
- **Submit view** (`/submit`) — form with client + server validation and success animation
- **Item detail modal** — inline review, free-form notes, reopen functionality
- **Dark mode** — toggled via button in the navbar, persisted in `localStorage`

---

## Assumptions

- **No authentication required.** The queue is treated as an internal tool used by a trusted team. Adding auth (e.g. Laravel Sanctum) would be straightforward but was out of scope.
- **Single reviewer role.** There is no concept of multiple reviewer permissions or assignment. Any user of the tool can approve, reject, or reopen any item.
- **Text-only submissions.** Items contain a title and text content only — no file uploads, images, or rich formatting.
- **A review decision is reversible.** Reviewers can reopen an approved or rejected item and re-decide. This was assumed to be necessary for correcting mistakes.
- **Notes are independent of review decisions.** Free-form notes can be added at any time by any reviewer, and are not deleted when an item is reopened or re-reviewed.
- **Moderation is synchronous.** The heuristic runs inline at submission time. For a high-traffic system this would move to a background job, but for this scale it is fine to block the response for a few milliseconds.
- **Pagination is server-side.** The frontend does not load all items at once — the backend paginates and the frontend requests pages as needed.

---

## Tradeoffs

### What we optimized for

- **Reviewer experience.** The UI prioritises making decisions fast — risk badge popovers explain *why* an item was flagged without leaving the queue, the modal shows all context inline, and the approve/reject flow is one click.
- **Zero infrastructure setup.** SQLite means the app runs with `php artisan serve` and `npm run dev` and nothing else. No Docker, no database server, no environment variables beyond the defaults.
- **Code clarity over cleverness.** The controller is thin (validation → service → model → response). The moderation service is a single class with no dependencies. The data model maps directly to what the UI needs — no transformation layer required.
- **Testability.** The moderation logic is isolated in a service class with no framework dependencies, making it trivially unit-testable. API behaviour is tested end-to-end with a real in-memory SQLite database.

### What we intentionally didn't build

- **Authentication and authorisation** — would be the first thing to add in production (Laravel Sanctum for API tokens, or session-based auth for a web-only tool).
- **Real-time updates** — the queue does not auto-refresh when another reviewer acts on an item. WebSockets (Laravel Echo + Reverb) would solve this.
- **Bulk actions** — approving or rejecting multiple items at once. Useful for high-volume queues but adds UI complexity.
- **Audit log** — no history of who reviewed what and when beyond `reviewed_at`. A proper audit trail would record every status transition with a timestamp and reviewer identity.
- **Rate limiting on submission** — the `POST /api/items` endpoint is unprotected. In production it would need rate limiting to prevent spam flooding the queue.
- **Soft deletes** — deleted items are permanently removed. Soft deletes (`deleted_at`) would allow recovery and audit.
- **ML-based moderation** — the heuristic is keyword-based and easy to bypass. A real classifier (or a third-party API like Perspective or OpenAI Moderation) would be far more accurate.

---

## Testing

Run the full test suite with:
```bash
php artisan test
```

### What was tested and why

**Unit tests** — `tests/Unit/ModerationServiceTest.php` (8 tests)

The moderation service is the only piece of non-trivial business logic in the backend. It has no framework dependencies, so it can be tested with plain PHPUnit — no database, no HTTP. Each signal is tested in isolation:

| Test | What it verifies |
|---|---|
| Clean content → score 0, suggested approve | Happy path — legitimate content passes cleanly |
| Spam keywords → spam flag + elevated score | Core spam detection works |
| Offensive keywords → offensive flag | Offensive detection works |
| >40% caps → caps_heavy flag | Caps heuristic threshold is correct |
| URL in content → has_urls flag | URL regex matches correctly |
| Very short content → very_short flag | Length threshold is correct |
| High score → suggested reject | The threshold of 35 triggers a reject suggestion |
| Score capped at 100 | Multiple signals cannot overflow the scale |

**Feature tests** — `tests/Feature/ItemApiTest.php` (15 tests)

These hit the full HTTP stack against a fresh in-memory SQLite database (`RefreshDatabase`), testing the API exactly as the frontend calls it:

| Area | Tests |
|---|---|
| Submit | Valid submission returns 201 with correct structure; missing fields return 422; spam content is flagged; clean content gets approve suggestion |
| List | Returns all items; filters by status; searches by title |
| Show | Returns single item; returns 404 for missing ID |
| Review | Approve with note; reject without note; double-review returns 422; invalid status returns 422 |
| Delete | Deletes item and returns 204; missing ID returns 404 |

### What was not tested and why

- **Frontend components** — no Vue component tests (Vitest/Vue Test Utils) were set up. Within the timebox, end-to-end manual testing in the browser covered the UI behaviour.
- **Reopen endpoint** — the reopen flow is tested manually but not in the automated suite. It would follow the same pattern as the review tests.
- **Notes endpoints** — `PATCH /note` and `DELETE /notes/{id}` are not in the automated suite for the same reason.
- **Pagination** — the list endpoint paginates at 10 items but the pagination behaviour (correct page counts, boundary conditions) is not explicitly tested.
- **Concurrent writes** — SQLite serialises writes, so concurrency is not tested. With PostgreSQL in production, this would warrant dedicated tests.
