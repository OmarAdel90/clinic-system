# Clinic Management System

A modular, role-based backend API for managing a medical clinic's full operational workflow — from lead acquisition via social media webhooks to appointment scheduling, clinical reporting, inventory management, and billing.

Built with **Laravel 13**, **Sanctum** (token auth), **Spatie Permissions** (RBAC), and **Pest PHP** (testing).

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Language** | PHP ^8.3 |
| **Framework** | Laravel ^13.8 |
| **Auth** | Laravel Sanctum (token-based) |
| **Authorization** | Spatie Laravel Permission ^8.0 |
| **Database** | MySQL (prod), SQLite (testing) |
| **Queue / Cache / Session** | Database driver |
| **Testing** | Pest PHP ^4.7 |
| **Frontend assets** | Vite + TailwindCSS ^4.0 |
| **Meta Integration** | WhatsApp Cloud API, Facebook Messenger, Instagram |

---

## Quick Start

```bash
# 1. Install PHP dependencies
composer install

# 2. Configure environment
copy .env.example .env
# Edit .env with your database credentials and Meta API keys

# 3. Generate app key & run migrations
php artisan key:generate
php artisan migrate --seed

# 4. Install & build frontend assets (optional)
npm install
npm run build

# 5. Start development server
composer run dev
# Starts: php artisan serve + queue:listen + npm run dev
```

### Default Super Admin

After seeding:
- **Email:** `super@clinic.com`
- **Password:** `password123`

---

## Project Structure

```
├── Modules/                  # Domain modules (12 modules)
│   ├── Auth/                 # Authentication, users, roles, permissions
│   ├── Clinic/               # Clinic CRUD
│   ├── CRM/                  # Campaigns, webhooks, call center, messaging
│   ├── Lead/                 # Lead management & status tracking
│   ├── Patient/              # Medical records & patient feedback
│   ├── Visit/                # Appointment scheduling & visit lifecycle
│   ├── TreatmentPlan/        # Multi-visit treatment plans
│   ├── Invoice/              # Billing & payment tracking
│   ├── Pharmaceutical/       # Medication/pharmaceutical catalog
│   ├── Warehouse/            # Inventory management
│   ├── Supplier/             # Supplier & payment management
│   └── Transaction/          # Warehouse-supplier transactions
├── routes/
│   ├── api.php               # Main API router (includes all module routes)
│   ├── modules/              # 14 route files (one per module)
│   └── web.php               # Single welcome route
├── database/
│   ├── migrations/           # 47 migration files
│   └── seeders/              # Role/permission & test data seeders
├── tests/                    # Pest PHP tests
├── bootstrap/app.php         # Middleware aliases & JSON forcing
└── bootstrap/providers.php   # Service provider registration
```

---

## Key Features

- **Multi-Channel CRM** — WhatsApp Cloud API, Facebook Messenger, Instagram integration with webhook handling
- **Call Center** — Round-robin lead assignment, agent queue management, performance metrics
- **Lead Lifecycle** — Configurable statuses, status history, conversion tracking
- **Visit Management** — Schedule, confirm, complete, cancel, or miss appointments
- **Inventory Control** — Warehouse stock with reservations, auto-deduction on visit completion
- **Role-Based Access** — 26 models × 7 actions (182 permissions) with granular user authorization
- **Billing** — Auto-generated invoices from visits, partial payment support
- **Supplier Management** — Purchase orders, payment tracking

---

## Documentation

Detailed documentation is available in the [`docs/`](./docs) directory:

| Document | Description |
|----------|-------------|
| [Getting Started](./docs/getting-started.md) | Installation, configuration, running |
| [Architecture](./docs/architecture.md) | Module pattern, auth, permissions, error handling |
| [API Reference](./docs/api-reference.md) | Complete endpoint listing |
| [Database Schema](./docs/database-schema.md) | Tables, columns, relationships |
| [Modules](./docs/modules.md) | Per-module detailed breakdown |
| [Business Workflows](./docs/business-workflows.md) | End-to-end process documentation |

---

## Running Tests

```bash
composer test
```

Uses SQLite in-memory database. Tests are located in `tests/Feature/` and `tests/Unit/`.

---

## Commands

| Command | Description |
|---------|-------------|
| `composer run setup` | Full initial setup (install, env, key, migrate, build) |
| `composer run dev` | Start dev server + queue worker + Vite HMR |
| `composer run test` | Run tests |
| `php artisan queue:listen` | Process queued jobs (webhook handling, etc.) |
