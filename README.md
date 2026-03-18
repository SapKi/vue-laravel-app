# Review Queue

A content moderation review queue built with **Vue 3** (frontend) and **Laravel 12** (backend API).

> **The app runs on port 8000.** Open http://localhost:8000 in your browser.


To run the app:


# Terminal 1
php artisan serve          # → http://localhost:8000

# Terminal 2
npm run dev                # → http://localhost:3000
--- full run instructions with installations below

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

Then open http://localhost:3000.

### Running Tests

```bash
php artisan test
```

---

## Backend Implementation

### Persistence: SQLite

The app uses **SQLite** (file: `database/database.sqlite`), chosen because it requires zero infrastructure setup — no database server to run. The file is created automatically on first migration.

**For production:** change `.env` to use Postgres or MySQL:
```
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=...
DB_USERNAME=...
DB_PASSWORD=...
```
No application code changes needed — the ORM layer is identical.

---

### Data Model

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

### Automated Moderation

Every submitted item is analyzed by `ModerationService` before being saved. It computes a **risk score (0–100)** and a set of **flags**:

| Signal | Points (cap) | Flag |
|---|---|---|
| Spam keywords matched (e.g. "buy now", "free money") | +12 each, max 35 | `spam` |
| Offensive keywords matched | +15 each, max 30 | `offensive` |
| >40% of letters are uppercase | +20 | `caps_heavy` |
| Content contains a URL | +10 | `has_urls` |
| Content is under 15 characters | +5 | `very_short` |

A **suggested action** is derived from the score:

- Score = 0 → `approve`
- Score >= 35 → `reject`
- Otherwise → `null` (human judgment required)

The suggestion is advisory only — reviewers can always override it. Reopening a reviewed item resets the decision while preserving the original moderation analysis.

---

## Frontend

Built with **Vue 3** (Composition API + script setup), **Pinia** for state, and **Vue Router**.

- **Queue view** (`/`) — paginated list with status tabs, search, sort, and risk badge popovers
- **Submit view** (`/submit`) — form with client + server validation and success animation
- **Item detail modal** — inline review, free-form notes, reopen functionality
- **Dark mode** — toggled via button in the navbar, persisted in `localStorage`
