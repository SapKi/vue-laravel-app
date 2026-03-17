# Review Queue

A full-stack content moderation app built with **Vue 3** + **Laravel 12**.
Users submit items; reviewers approve or reject them, with automated risk scoring to assist the decision.

---

## How to Run

**Requirements:** PHP 8.3+, Composer, Node 18+, npm

```bash
# 1. Install dependencies
composer install
npm install

# 2. Environment setup (already committed with defaults)
cp .env.example .env      # skip if .env exists
php artisan key:generate  # skip if APP_KEY is set

# 3. Run migrations (SQLite, no config needed)
php artisan migrate

# 4. Start the backend (terminal 1)
php artisan serve          # → http://127.0.0.1:8000

# 5. Start the frontend (terminal 2)
npm run dev                # → HMR server (Vite)
```

Open **http://127.0.0.1:8000** in your browser.

### Run tests
```bash
php artisan test
```

---

## Key Decisions

### Data Model

| Field | Type | Reason |
|---|---|---|
| `title` | string(255) | Required; short identifier for the item |
| `content` | text | The full submission body |
| `status` | enum(pending, approved, rejected) | Three-state lifecycle; pending is the only actionable state |
| `risk_score` | tinyint(0–100) | Normalized score makes it sortable and comparable |
| `flags` | JSON array | Variable number of flags without needing a pivot table |
| `suggested_action` | enum(approve, reject, null) | Pre-computed on insert; null means "no clear signal" |
| `reviewer_note` | text, nullable | Optional context the reviewer adds at decision time |
| `reviewed_at` | timestamp, nullable | Audit field; null means still pending |

A single flat table is appropriate here. There are no users or roles yet, and normalizing flags into a separate table would add complexity without benefit at this scale.

### API Design

| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/items` | List items — accepts `status`, `search`, `sort`, `order` query params |
| POST | `/api/items` | Submit a new item; moderation runs synchronously |
| GET | `/api/items/{id}` | Fetch a single item |
| PATCH | `/api/items/{id}/review` | Approve or reject; idempotency guard rejects double-review with 422 |

Chose `PATCH /items/{id}/review` over `PUT /items/{id}` to make the intent explicit — this is a state transition, not a general update. Moderation runs on `POST /items` rather than asynchronously because the score is cheap to compute and the frontend needs it immediately to show the suggestion.

### Persistence

**SQLite** — already configured in the boilerplate, zero setup for the reviewer, sufficient for the scope. The database file lives at `database/database.sqlite`.

**For production** I'd switch to PostgreSQL:
- `DB_CONNECTION=pgsql` in `.env`
- Add index on `status` and `created_at` for queue queries
- Consider running moderation asynchronously (Laravel job) if the heuristic becomes more expensive (e.g., calling an external API)
- Add soft deletes if audit history matters

---

## Moderation Heuristic

`App\Services\ModerationService` computes three outputs on each submission:

**Risk score (0–100)** — additive:
- +12 per spam keyword hit (capped at 35): `buy now`, `click here`, `free money`, `prize`, etc.
- +15 per offensive keyword hit (capped at 30): `hate`, `stupid`, `idiot`, etc.
- +20 if >40% of letters are uppercase (caps-heavy)
- +10 if content contains a URL
- +5 if content is shorter than 15 characters

**Flags** — array of strings explaining why the score is elevated: `spam`, `offensive`, `caps_heavy`, `has_urls`, `very_short`.

**Suggested action**:
- `reject` if score ≥ 35
- `approve` if score = 0
- `null` otherwise (ambiguous; reviewer decides)

The threshold of 35 for reject was calibrated so that a single confident spam signal (e.g., 3 spam keywords) triggers a suggestion without false-positiving on borderline content.

---

## Assumptions

1. No authentication — all users can submit and review. In a real system you'd separate submitter and reviewer roles.
2. Review is a one-way transition: `pending → approved` or `pending → rejected`. Re-opening is out of scope.
3. Items are not paginated — the full list loads at once. Fine for a demo; see tradeoffs below.
4. The moderation heuristic is keyword-based and deterministic. No ML or external services.
5. A single `reviewer_note` field is sufficient; no comment threads.

---

## Tradeoffs

### What I optimized for
- **End-to-end correctness** — the full submit → review flow works, including edge cases (double review returns 422).
- **Clarity** — explicit state machine (`pending/approved/rejected`), a dedicated `review` endpoint, and a service class for the heuristic make the code easy to reason about and extend.
- **Testability** — the heuristic is a pure service class, making unit tests straightforward without hitting the database.

### What I intentionally didn't build
- **Pagination** — would be the first thing to add in production. The query already supports `sort`/`order` so adding `limit`/`offset` is mechanical.
- **Authentication** — no reviewer identity is stored. Would add Laravel Sanctum tokens and a `reviewed_by` foreign key.
- **Optimistic UI updates** — the store updates the item in-place after the API confirms, but there's no rollback on failure.
- **Bulk review actions** — the UI and API are item-at-a-time; bulk would require a `PATCH /api/items/bulk-review` endpoint.
- **Frontend tests** — given the timebox I prioritized backend tests where the logic lives. The Vue components are thin wrappers over API calls.

---

## Testing

### What was tested and why

**`tests/Unit/ModerationServiceTest`** — 8 tests covering every scoring path (spam, offensive, caps, URL, short content, caps at 100, clean → approve, high risk → reject). The heuristic is the most domain-specific logic in the codebase, so unit tests here give the highest signal per line of test code.

**`tests/Feature/ItemApiTest`** — 13 tests covering:
- Happy-path submit and field structure
- Validation rejection (missing fields, invalid status)
- Moderation integration (spam content gets flagged, clean content gets approved)
- List filtering by status and search
- Single item fetch and 404
- Approve/reject flow
- Guard against double-review

These are feature tests (full HTTP stack with `RefreshDatabase`) because they verify the whole contract the frontend relies on.

### What was not tested and why
- **Frontend components** — no Vue component tests. The components are presentational with minimal logic. The risk was low and the timebox was tight.
- **Sort/order query params** — covered implicitly by the filter tests; explicit ordering assertions would add noise without much value.
- **Network error handling in the UI** — the error state is rendered but not unit-tested.
