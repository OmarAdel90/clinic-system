# Architecture

## Overview

The Clinic Management System follows a **modular monolithic** architecture built on top of Laravel. Each business domain is isolated into an independent **Module** with its own Controllers, Services, Models, Form Requests, and Routes.

```
                    ┌─────────────────────────────┐
                    │     HTTP Request (API)       │
                    └─────────────┬───────────────┘
                                  │
                    ┌─────────────▼───────────────┐
                    │    bootstrap/app.php         │
                    │  Middleware: auth:sanctum     │
                    │  JSON forcing for /api/*      │
                    └─────────────┬───────────────┘
                                  │
                    ┌─────────────▼───────────────┐
                    │       routes/api.php         │
                    │   Includes all module routes │
                    └─────────────┬───────────────┘
                                  │
             ┌────────────────────┼────────────────────┐
             ▼                    ▼                    ▼
      ┌──────────┐         ┌──────────┐         ┌──────────┐
      │  Auth    │         │  CRM     │         │  Visit   │
      │ Module   │         │ Module   │  ...     │ Module   │
      └──────────┘         └──────────┘         └──────────┘
```

---

## Module Structure

Every module follows a consistent pattern:

```
Modules/{ModuleName}/
├── Controllers/
│   └── {Entity}Controller.php      # Thin HTTP layer
├── Services/
│   └── {Entity}Service.php         # Business logic
├── Models/
│   └── {Entity}.php                # Eloquent model
├── Requests/
│   ├── Store{Entity}Request.php    # Create validation
│   └── Update{Entity}Request.php   # Update validation
└── Providers/
    └── {Module}ServiceProvider.php # Service registration
```

### Controller Pattern

Controllers are thin — they inject a Service via constructor and delegate all logic:

```php
class LeadController extends Controller
{
    public function __construct(protected LeadService $service) {}

    public function index(IndexLeadRequest $request): JsonResponse
    {
        return response()->json($this->service->getAll($request->user()));
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        return response()->json($this->service->create($request->validated()), 201);
    }
}
```

### Service Pattern

Services contain all business logic, permission checks, and database operations:

```php
class LeadService
{
    public function getAll(?User $user = null): Collection
    {
        $query = Lead::with(['leadStatus', 'campaign']);
        if ($user && !$user->can('view_any_lead')) {
            $leadIds = $user->assignedConversations()->pluck('lead_id');
            $query->whereIn('id', $leadIds);
        }
        return $query->get();
    }
}
```

---

## Authentication

Uses **Laravel Sanctum** for token-based authentication.

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/login` | POST | No | Returns a Sanctum token |
| `/logout` | POST | Yes | Revokes current token |
| `/me` | GET | Yes | Returns authenticated user + roles |

**Token Configuration:**
- Expiration: 48 hours (`config/sanctum.php`)
- All API routes (except `/login` and webhooks) are protected by `auth:sanctum` middleware

---

## Authorization

Uses **Spatie Laravel Permission** for role-based access control (RBAC).

### Permission Model

182 permissions are generated following this convention:

```
{action}_{model}
```

Where **actions** are: `view_any`, `view`, `create`, `update`, `delete`, `restore`, `force_delete`

And **models** include: `lead`, `user`, `role`, `clinic`, `visit`, `report`, `invoice`, `treatment_plan`, `pharmaceutical`, `warehouse`, `warehouse_inventory`, `supplier`, `supplier_payment`, `transaction`, `campaign`, `campaign_cost`, `conversation`, `message`, `follow_up`, `medical_record`, `patient_feedback`, `call_center_queue`, `call_center_metrics`, `assignment_state`, `webhook_log`, `lead_status`

### Enforcement Pattern

Services check permissions dynamically:

```php
if ($user && !$user->can('view_any_lead')) {
    // Scope to user's assigned records only
}
```

### Middleware Aliases

Registered in `bootstrap/app.php`:

| Alias | Purpose |
|-------|---------|
| `role` | Require specific role |
| `permission` | Require specific permission |
| `role_or_permission` | Require either role or permission |

---

## Request/Response Flow

```
Client
  │
  ▼
Router (routes/api.php → routes/modules/{module}.php)
  │
  ▼
Middleware Stack (auth:sanctum, role/permission)
  │
  ▼
Form Request (validation)
  │
  ▼
Controller (delegates to Service)
  │
  ▼
Service (business logic, permission checks, DB operations)
  │
  ▼
Eloquent Model (relationships, scopes, casts)
  │
  ▼
Database
```

### Error Handling

Configured in `bootstrap/app.php`:
- All `/api/*` routes return JSON responses automatically
- Exceptions are converted to JSON error responses

Service methods use try-catch blocks for:
- `QueryException` — database errors
- `ModelNotFoundException` — missing records
- Generic `Throwable` — unexpected errors

---

## Module Dependencies

```
Auth ───────────────────────────────────┐
  │                                     │
  ├── User ──── Clinic                  │
  │    │                                │
  │    ├── Lead ──────── Campaign       │
  │    │    │                           │
  │    │    ├── Conversation ── Message │
  │    │    │    │                      │
  │    │    │    └── FollowUp           │
  │    │    │                           │
  │    │    ├── MedicalRecord           │
  │    │    ├── PatientFeedback         │
  │    │    │                           │
  │    │    ├── TreatmentPlan           │
  │    │    │    └── Visit              │
  │    │    │         └── Report        │
  │    │    │                           │
  │    │    └── Invoice                 │
  │    │                               │
  │    └── Visit ──── Report            │
  │                                   │
  ├── Warehouse ──── Clinic            │
  │    │                              │
  │    ├── WarehouseInventory          │
  │    │    └── Pharmaceutical         │
  │    │                              │
  │    └── Transaction ── Supplier     │
  │                                   │
  └── CallCenter ──── Queue            │
       └── PerformanceMetrics         │
```

---

## Queue & Jobs

The system uses the `database` queue driver for:
- Processing incoming webhooks
- Sending messages via WhatsApp/Facebook APIs
- Other asynchronous operations

Run the queue worker:

```bash
php artisan queue:listen --tries=1
```

When using `composer run dev`, the queue worker starts automatically.

---

## Events & Listeners

| Event | Listener | Description |
|-------|----------|-------------|
| `ReportCompleted` | `NotifyAgent` | Creates a follow-up when a visit report is completed |
