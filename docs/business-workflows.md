# Business Workflows

## 1. Lead Acquisition & CRM

```
                    ┌──────────────────┐
                    │  Marketing       │
                    │  Campaigns       │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  Social Media    │
                    │  (WhatsApp, FB,  │
                    │   Instagram)     │
                    └────────┬─────────┘
                             │ Incoming Message
                    ┌────────▼─────────┐
                    │  Webhook Service  │
                    │  - Verifies       │
                    │  - Processes      │
                    │  - Logs payload   │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  Auto-creates:    │
                    │  - Lead (if new)  │
                    │  - Conversation   │
                    │  - Message        │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  Call Center      │
                    │  Assign to Agent  │
                    │  (Round Robin)    │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │  Agent Responds   │
                    │  via Platform     │
                    └──────────────────┘
```

### Steps:
1. Marketing campaigns run on social media platforms
2. When a user sends a message via WhatsApp, Facebook Messenger, or Instagram, Meta sends a webhook
3. The `WebhookService` verifies the payload, determines the source, and processes the message
4. If the sender is new, a **Lead** is created with status "New"
5. A **Conversation** is created/updated, and the **Message** is stored
6. The **Call Center** assigns the lead to an agent via round-robin queue
7. The agent can respond through the platform's `POST /agent/messages/send` endpoint

---

## 2. Appointment Scheduling & Visit Lifecycle

```
Lead Converted
      │
      ▼
Treatment Plan Created
      │
      ▼
Visit Scheduled
      │
      ▼
Visit Confirmed ──── (Supplies Reserved)
      │
      ├── Completed ──→ Report Created
      │                  │
      │                  ├── Inventory Deducted
      │                  │
      │                  └── Invoice Generated
      │
      ├── Cancelled ──→ Reservation Released
      │
      └── Missed
```

### Visit States:
| State | Description |
|-------|-------------|
| `scheduled` | Initial state after creation |
| `confirmed` | Appointment confirmed by clinic (via PATCH confirm) |
| `completed` | Visit done, report created, inventory deducted, invoice generated |
| `cancelled` | Appointment cancelled, inventory reservation released |
| `missed` | Patient did not show up |

### Visit Completion Flow (POST `/visits/{visit}/complete`):
1. Creates a **Report** with diagnosis and treatment notes
2. Deducts used supplies from warehouse inventory
3. Generates an **Invoice** for services and supplies
4. Dispatches `ReportCompleted` event → creates a **FollowUp** for the agent

---

## 3. Inventory Management

```
Supplier ──→ Transaction ──→ Warehouse Inventory
                                  │
                                  │
Pharmaceutical Catalog ◄──────────┤
                                  │
                                  │
                    Visit Scheduled
                           │
                           ▼
                    Supplies Reserved
                    (reserved_quantity += qty)
                           │
                           ├── Visit Completed
                           │   (quantity -= qty,
                           │    reserved_quantity -= qty)
                           │
                           └── Visit Cancelled
                               (reserved_quantity -= qty)
```

### Warehouse Service Methods:
- `checkSufficiency(visit)` — Ensures enough stock (available = quantity - reserved_quantity)
- `deductInventory(visit)` — On visit completion, reduces quantity and reserved_quantity
- `releaseReservation(visit)` — On cancellation, releases reserved stock

---

## 4. Billing & Payments

```
Visit Completed
      │
      ▼
Invoice Created
- services_cost
- supplies_cost
- total_cost
- status: unpaid
      │
      ▼
Payment Received (PATCH /invoices/{invoice}/pay)
      │
      ├── Full payment → status: paid
      └── Partial payment → status: partial
```

### Invoice Statuses:
- `unpaid` — No payment recorded
- `partial` — Some payment recorded, balance remaining
- `paid` — Fully paid

### Overpayment Protection:
The `InvoiceService` validates that the cumulative `amount_paid` does not exceed `total_cost`.

---

## 5. Supplier Management

```
Supplier Created
      │
      ▼
Warehouse Transaction Created
- Items bought recorded
- Batch number tracked
      │
      ▼
Supplier Payment History Created
- Total amount set
- Status: unpaid
      │
      ▼
Payment Made (PATCH /supplier-payments/{payment}/pay)
      ├── Full → paid
      └── Partial → partial
```

---

## 6. Role & Permission System

```
Super Admin creates Roles
      │
      ▼
Permissions assigned to Roles
(182 permissions: 26 models × 7 actions)
      │
      ▼
Roles assigned to Users
      │
      ▼
Permission checks in Services:
- Can view_any_{model}? → See all records
- Otherwise → Only assigned records
```

### Actions per Model:
| Action | Description |
|--------|-------------|
| `view_any` | List all records |
| `view` | View single record |
| `create` | Create new record |
| `update` | Update existing record |
| `delete` | Delete record |
| `restore` | Restore soft-deleted record |
| `force_delete` | Permanently delete record |

### Models with Permissions (26 total):
lead, user, role, clinic, visit, report, invoice, treatment_plan, pharmaceutical, warehouse, warehouse_inventory, supplier, supplier_payment, transaction, campaign, campaign_cost, conversation, message, follow_up, medical_record, patient_feedback, call_center_queue, call_center_metrics, assignment_state, webhook_log, lead_status
