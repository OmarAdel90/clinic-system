# Getting Started

## Prerequisites

- PHP ^8.3
- Composer
- MySQL (or compatible database)
- Node.js & npm (for frontend asset building)
- A Meta developer account (for WhatsApp/Facebook/Instagram integration)

---

## Installation

### 1. Clone & Install Dependencies

```bash
git clone <repository-url> clinic-system
cd clinic-system
composer install
npm install
```

### 2. Environment Configuration

```bash
copy .env.example .env
```

Edit `.env` with your settings:

**Database:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clinic_system
DB_USERNAME=root
DB_PASSWORD=root
```

**Meta Integration (CRM features):**
```env
META_WHATSAPP_VERIFY_TOKEN=clinic_verify
META_FACEBOOK_VERIFY_TOKEN=clinic_verify
META_APP_SECRET=your-app-secret-from-meta-dashboard
META_FACEBOOK_PAGE_ACCESS_TOKEN=your-facebook-page-access-token
META_WHATSAPP_ACCESS_TOKEN=your-whatsapp-access-token
META_PHONE_NUMBER_ID=1099590573233710
META_WABA_ID=829367053547881
META_API_VERSION=v20.0
```

### 3. Generate Key & Run Migrations

```bash
php artisan key:generate
php artisan migrate --seed
```

The seeder creates:
- 5 lead statuses: New, Contacted, Qualified, Converted, Lost
- 182 permissions (26 models × 7 actions)
- An `admin` role with all permissions
- A super admin user: `super@clinic.com` / `password123`

### 4. Build Frontend Assets

```bash
npm run build
```

### 5. Start the Application

```bash
composer run dev
```

This runs three processes concurrently:
- `php artisan serve` — development HTTP server
- `php artisan queue:listen --tries=1` — queue worker (required for webhook processing)
- `npm run dev` — Vite hot module replacement

---

## Configuration Reference

### Sanctum (Token Auth)

Configured in `config/sanctum.php`:
- Token expiration: **48 hours** (2880 minutes)

### Session, Cache, Queue

All use the `database` driver by default. Configure via `.env`:

```env
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

### Spatie Permission

Configured in `config/permission.php`. Middleware aliases registered in `bootstrap/app.php`:

| Alias | Middleware Class |
|-------|-----------------|
| `role` | `RoleMiddleware` |
| `permission` | `PermissionMiddleware` |
| `role_or_permission` | `RoleOrPermissionMiddleware` |

---

## Running Tests

```bash
composer test
```

Tests run with SQLite in-memory database (see `phpunit.xml`). Currently includes basic example tests:

```bash
php artisan test              # Run all tests
php artisan test --filter=Example   # Run specific test
```

---

## Directory Overview

```
├── app/                    # Laravel core (Http, Providers)
├── bootstrap/              # App bootstrap, middleware, providers
├── config/                 # Configuration files
├── database/
│   ├── migrations/         # 47 migration files
│   └── seeders/            # Database seeders
├── Modules/                # Domain modules (main codebase)
├── routes/
│   ├── api.php             # API route aggregation
│   ├── modules/            # Per-module route files
│   └── web.php             # Web routes
├── tests/                  # Pest PHP tests
├── public/                 # Public entry point
├── resources/              # Views, assets, lang files
├── storage/                # Logs, cache, uploaded files
└── vendor/                 # Composer dependencies
```
