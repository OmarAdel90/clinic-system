# Modules

The codebase is organized into **12 domain modules** under `Modules/`. Each module follows a consistent architecture: Controllers → Services → Models → Requests.

---

## Auth (`Modules/Auth/`)

Handles authentication, user management, and role/permission administration.

### Controllers
| Controller | Endpoints | Description |
|------------|-----------|-------------|
| `AuthController` | login, logout, me | Authentication |
| `UserController` | CRUD users, syncRoles | User management |
| `RoleController` | CRUD roles, syncPermissions | Role management |
| `PermissionController` | index | List all permissions |

### Models
- **User** — Extends `Authenticatable`, uses `HasApiTokens` (Sanctum), `HasRoles` (Spatie), `HasFactory`
  - Fields: name, arabic_name, email, password, SSN, phone_number, location, salary, commission, title, specialization, hired_at, whatsapp_agent_number, is_active, last_active_at, work_start, work_end, accessible_clinics
  - Relationships: assignedConversations, callCenterQueueEntry, performanceMetrics

### Key Notes
- The `accessible_clinics` JSON field controls which clinics a user can access
- User roles are synced via `PATCH /users/{user}/roles`
- Available via admin routes

---

## Clinic (`Modules/Clinic/`)

Manages clinic information.

### Models
- **Clinic** — Fields: name, arabic_name, provides_medication (boolean), departments (JSON), doctors (JSON), services (JSON), phone_number, address
  - `hasOne(Warehouse)` — each clinic has exactly one warehouse

---

## CRM (`Modules/CRM/`)

The most complex module. Handles multi-channel communication, marketing campaigns, call center operations, and webhook integration.

### Controllers
| Controller | Endpoints | Description |
|------------|-----------|-------------|
| `WebhookController` | verify, handle | Meta webhook verification & processing |
| `CallCenterController` | queue, assign, etc. | Agent queue & lead assignment |
| `AgentController` | conversations, leads, followups, sendMessage | Agent-scoped operations |
| `CampaignController` | CRUD campaigns | Marketing campaign management |
| `CampaignCostController` | CRUD campaign costs | Campaign cost tracking |

### Services
| Service | Lines | Description |
|---------|-------|-------------|
| `MetaWhatsAppService` | 438 | Send text, interactive buttons/lists, media messages; upload/download media; phone normalization |
| `MetaFacebookService` | 185 | Send text/attachments via Facebook Messenger/Instagram |
| `WebhookService` | 361 | Process incoming webhooks from WhatsApp/Facebook/Instagram; auto-creates leads, conversations, messages |
| `CallCenterService` | — | Queue management, round-robin lead assignment |
| `CampaignService` | — | Campaign CRUD logic |
| `CampaignCostService` | — | Campaign cost CRUD logic |

### Models
| Model | Description |
|-------|-------------|
| `Campaign` | Marketing campaigns with budget, dates, platform |
| `CampaignCost` | Individual cost entries per campaign |
| `Conversation` | Chat thread linked to a lead, assigned to an agent |
| `Message` | Individual messages with full WhatsApp/Facebook metadata |
| `FollowUp` | Scheduled follow-up reminders for agents |
| `AssignmentState` | Tracks lead-to-user assignments |
| `CallCenterQueueEntry` | Round-robin queue for agents |
| `CallCenterPerformanceMetrics` | Agent KPIs (response time, conversion rate, etc.) |
| `WebhookLog` | Incoming webhook audit trail |

### Webhook Flow
1. Meta sends a verification GET request → controller returns `hub_challenge`
2. Meta sends POST with message events → `WebhookService::processPayload()`
3. Service identifies the source (WhatsApp/Facebook/Instagram)
4. Auto-creates or finds the lead, creates/updates conversation, stores the message
5. All payloads are logged in `webhook_logs` for audit

### Call Center Flow
1. Agents join the queue via `POST /call-center/queue/add/{userId}`
2. New leads are assigned to the next agent in queue (round-robin)
3. Performance metrics are tracked per agent

---

## Lead (`Modules/Lead/`)

Manages leads and their status lifecycle.

### Models
- **Lead** — Core entity linked to campaigns
- **LeadStatus** — Configurable statuses (New, Contacted, Qualified, Converted, Lost)
- **LeadStatusHistory** — Tracks status changes with user attribution

### Service: `LeadService`
- CRUD with permission-based filtering
- Users with `view_any_lead` see all leads; others see only their assigned leads

---

## Patient (`Modules/Patient/`)

Manages medical records and patient feedback.

### Controllers
| Controller | Endpoints |
|------------|-----------|
| `MedicalRecordController` | CRUD + file view/download |
| `PatientFeedbackController` | CRUD |

### Models
- **MedicalRecord** — File uploads linked to leads with type, file metadata, and notes
- **PatientFeedback** — Feedback linked to lead, user, and clinic

---

## Visit (`Modules/Visit/`)

Appointment scheduling and visit lifecycle management.

### Controllers
| Controller | Endpoints |
|------------|-----------|
| `VisitController` | CRUD |
| `VisitFlowController` | confirm, complete, cancel, miss |

### Models
- **Visit** — Tracks scheduling, confirmation, supplies reservation, costs
- **Report** — Clinical report created on visit completion

### Services
| Service | Description |
|---------|-------------|
| `VisitService` | Report CRUD with inventory deduction and invoice creation |
| `VisitFlowService` | Visit state machine (schedule → confirm → complete / cancel / miss) |

### Visit Lifecycle
```
Scheduled ──→ Confirmed ──→ Completed
                                  │
                    ┌─────────────┘
                    ▼
              Report Created
              Inventory Deducted
              Invoice Generated
```

### Event: `ReportCompleted`
Dispatched when a visit is completed. Triggers `NotifyAgent` listener which creates a follow-up.

---

## TreatmentPlan (`Modules/TreatmentPlan/`)

Multi-visit treatment plans.

### Models
- **TreatmentPlan** — Linked to lead, user, clinic; tracks diagnosis, type, total visits, status
  - `hasMany(Visit)`

---

## Invoice (`Modules/Invoice/`)

Billing and payment tracking.

### Models
- **Invoice** — Tracks services/supplies costs, amount paid, status (unpaid/partial/paid)
  - Unique auto-generated `invoice_number`

### Service: `InvoiceService`
- Payment recording with overpayment validation

---

## Pharmaceutical (`Modules/Pharmaceutical/`)

Medication / pharmaceutical catalog.

### Models
- **Pharmaceutical** — Uses **string primary key (SKU)**, not auto-increment
  - Fields: SKU, name, arabic_name, photo, sale_price, description, attribute (JSON)

---

## Warehouse (`Modules/Warehouse/`)

Inventory management.

### Models
- **Warehouse** — Linked to clinic (one-to-one)
- **WarehouseInventory** — Tracks quantity and reserved_quantity per SKU

### Service: `WarehouseService`
- `deductInventory(visit)` — Deducts supplies on visit completion
- `releaseReservation(visit)` — Releases reserved stock on cancellation
- `checkSufficiency(visit)` — Validates stock availability

**Available stock formula:** `quantity - reserved_quantity`

---

## Supplier (`Modules/Supplier/`)

Supplier management and payment tracking.

### Models
- **Supplier** — Name, phone_number
- **SupplierPaymentHistory** — Tracks batch payments with status tracking

### Services
| Service | Description |
|---------|-------------|
| `SupplierService` | Supplier CRUD |
| `SupplierPaymentService` | Payment CRUD + payment recording |

---

## Transaction (`Modules/Transaction/`)

Warehouse-supplier transactions (purchase orders).

### Models
- **WarehouseSupplierTransaction** — Tracks goods from supplier to warehouse
  - **UUID primary key** (`transaction_id`)
  - Fields: warehouse_id, supplier_id, batch_number, items_bought (JSON), transaction_date
