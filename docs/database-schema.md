# Database Schema

The system uses **MySQL** (production) / **SQLite** (testing) with **47 migration files**.

---

## Core Tables

### `users`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | |
| arabic_name | varchar(255) | nullable |
| email | varchar(255) | unique |
| password | varchar(255) | bcrypt hashed |
| SSN | varchar(255) | nullable |
| phone_number | varchar(255) | nullable |
| location | text | nullable |
| salary | decimal | nullable |
| commission | decimal | nullable |
| title | varchar(255) | nullable |
| specialization | varchar(255) | nullable |
| hired_at | datetime | nullable |
| whatsapp_agent_number | varchar(255) | nullable |
| is_active | boolean | default true |
| last_active_at | datetime | nullable |
| work_start | time | nullable |
| work_end | time | nullable |
| accessible_clinics | json | nullable |
| timestamps | | |

### `personal_access_tokens` (Sanctum)

Stores API tokens linked to users. Token expires after 2880 minutes (48 hours).

### `sessions` (Laravel Sessions)

Database-driven session storage.

---

## Business Tables

### `clinics`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | |
| arabic_name | varchar(255) | nullable |
| provides_medication | boolean | default false |
| departments | json | nullable |
| doctors | json | nullable |
| services | json | nullable |
| phone_number | varchar(255) | nullable |
| address | text | nullable |
| timestamps | | |

**Relationships:**
- `hasOne(Warehouse)`

---

### `leads`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| campaign_id | bigint unsigned FK | nullable → campaigns |
| platform | varchar(255) | e.g. whatsapp, facebook, instagram |
| whatsapp_id | varchar(255) | nullable |
| phone | varchar(255) | nullable |
| name | varchar(255) | nullable |
| profile_name | varchar(255) | nullable |
| metadata | json | nullable |
| lead_status_id | bigint unsigned FK | nullable → lead_status |
| timestamps | | |

**Relationships:**
- `belongsTo(LeadStatus)`
- `belongsTo(Campaign)`
- `hasMany(Conversation)`
- `hasMany(Visit)`
- `hasMany(MedicalRecord)`

### `lead_status`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| label | varchar(255) | Display name |
| key | varchar(255) | Internal key |
| color | varchar(255) | nullable |
| is_qualified | boolean | default false |
| is_active | boolean | default true |
| sort_order | int | |
| timestamps | | |

**Seed Data:** New, Contacted, Qualified, Converted, Lost

### `lead_status_history`

Tracks changes to a lead's status over time.

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| conversation_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | |
| from_status | bigint unsigned FK | → lead_status |
| to_status | bigint unsigned FK | → lead_status |
| changed_at | timestamp | |

---

### `campaigns`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | |
| platform | varchar(255) | |
| description | text | nullable |
| start_date | date | nullable |
| end_date | date | nullable |
| budget | decimal | nullable |
| currency | varchar(255) | nullable |
| status | varchar(255) | nullable |
| timestamps | | |

### `campaign_cost`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| campaign_id | bigint unsigned FK | |
| cost | decimal | |
| currency | varchar(255) | nullable |
| customer_cost | decimal | nullable |
| converted_lead_count | int | nullable |
| notes | text | nullable |
| timestamps | | |

---

### `conversations`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| assigned_user_id | bigint unsigned FK | nullable → users |
| first_message_time | datetime | nullable |
| last_message_time | datetime | nullable |
| platform | varchar(255) | |
| status | varchar(255) | nullable |
| lead_status | varchar(255) | nullable |
| unread_amount | int | default 0 |
| converted_at | datetime | nullable |
| visit_id | bigint unsigned FK | nullable |
| timestamps | | |

**Relationships:**
- `belongsTo(Lead)`
- `belongsTo(User, 'assigned_user_id')`
- `hasMany(Message)`
- `hasMany(FollowUp)`

### `messages`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| conversation_id | bigint unsigned FK | |
| lead_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | nullable |
| reply_to_message_id | varchar(255) | nullable |
| wa_message_id | varchar(255) | WhatsApp message ID |
| direction | varchar(255) | inbound / outbound |
| type | varchar(255) | text, image, interactive, etc. |
| body | text | |
| media_url | text | nullable |
| media_caption | text | nullable |
| media_mime | varchar(255) | nullable |
| media_size | bigint | nullable |
| payload | json | nullable |
| status | varchar(255) | sent, delivered, read, failed |
| sent_at | datetime | nullable |
| delivered_at | datetime | nullable |
| read_at | datetime | nullable |
| failed_at | datetime | nullable |
| error_message | text | nullable |
| timestamps | | |

### `follow_up`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| conversation_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | |
| due_at | datetime | |
| completed_at | datetime | nullable |
| body | text | nullable |
| timestamps | | |

### `assignment_state`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | |
| timestamps | | |

---

### `treatment_plans`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | |
| clinic_id | bigint unsigned FK | |
| diagnosis | text | nullable |
| notes | text | nullable |
| type | varchar(255) | nullable |
| total_visits | int | nullable |
| status | varchar(255) | nullable |
| timestamps | | |

**Relationships:**
- `belongsTo(Lead)`
- `belongsTo(User)`
- `belongsTo(Clinic)`
- `hasMany(Visit)`

---

### `visits`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | |
| clinic_id | bigint unsigned FK | |
| treatment_plan_id | bigint unsigned FK | nullable |
| conversation_id | bigint unsigned FK | nullable |
| visit_number | int | nullable |
| scheduled_date | datetime | |
| confirmed_at | datetime | nullable |
| actual_date | datetime | nullable |
| status | varchar(255) | scheduled, confirmed, completed, cancelled, missed |
| supplies_reserved | json | nullable |
| services_cost | decimal | nullable |
| supplies_cost | decimal | nullable |
| total_cost | decimal | nullable |
| report_id | bigint unsigned FK | nullable → reports |
| timestamps | | |

### `reports`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | |
| clinic_id | bigint unsigned FK | |
| visit_id | bigint unsigned FK | |
| visit_date | datetime | nullable |
| diagnosis | text | nullable |
| treatment_notes | text | nullable |
| supplies_used | json | nullable |
| body | text | nullable |
| status | varchar(255) | nullable |
| cost_known | boolean | default false |
| timestamps | | |

---

### `invoices`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| clinic_id | bigint unsigned FK | |
| report_id | bigint unsigned FK | nullable |
| treatment_plan_id | bigint unsigned FK | nullable |
| invoice_number | varchar(255) | unique, auto-generated |
| services_cost | decimal | |
| supplies_cost | decimal | |
| total_cost | decimal | |
| amount_paid | decimal | default 0 |
| status | varchar(255) | unpaid, partial, paid |
| issued_at | datetime | nullable |
| due_date | date | nullable |
| timestamps | | |

---

### `pharmaceuticals`

| Column | Type | Notes |
|--------|------|-------|
| SKU | varchar(255) PK | String primary key |
| name | varchar(255) | |
| arabic_name | varchar(255) | nullable |
| photo | varchar(255) | nullable |
| sale_price | decimal | nullable |
| description | text | nullable |
| attribute | json | nullable |
| timestamps | | |

---

### `warehouses`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| clinic_id | bigint unsigned FK | unique |
| name | varchar(255) | |
| timestamps | | |

**Relationships:**
- `belongsTo(Clinic)`
- `hasMany(WarehouseInventory)`

### `warehouse_inventories`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| warehouse_id | bigint unsigned FK | |
| sku | varchar(255) | → pharmaceuticals.SKU |
| name | varchar(255) | |
| arabic_name | varchar(255) | nullable |
| quantity | decimal | default 0 |
| reserved_quantity | decimal | default 0 |

**Key Logic:** Available stock = `quantity - reserved_quantity`

---

### `suppliers`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| name | varchar(255) | |
| phone_number | varchar(255) | nullable |
| timestamps | | |

### `supplier_payment_history`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| transaction_id | bigint unsigned FK | nullable |
| supplier_id | bigint unsigned FK | |
| batch_id | varchar(255) | nullable |
| total_amount | decimal | |
| total_paid | decimal | default 0 |
| payment_status | varchar(255) | unpaid, partial, paid |
| timestamps | | |

### `warehouse_supplier_transactions`

| Column | Type | Notes |
|--------|------|-------|
| transaction_id | uuid PK | UUID primary key |
| warehouse_id | bigint unsigned FK | |
| supplier_id | bigint unsigned FK | |
| batch_number | varchar(255) | nullable |
| items_bought | json | nullable |
| transaction_date | datetime | nullable |
| timestamps | | |

---

### `medical_records`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| type | varchar(255) | |
| file_path | varchar(255) | |
| original_name | varchar(255) | |
| mime_type | varchar(255) | |
| notes | text | nullable |
| uploaded_by | bigint unsigned FK | → users |
| timestamps | | |

### `patient_feedback`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| lead_id | bigint unsigned FK | |
| user_id | bigint unsigned FK | |
| clinic_id | bigint unsigned FK | |
| feedback_body | text | |
| timestamps | | |

---

### Call Center Tables

#### `call_center_queue_entries`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| user_id | bigint unsigned FK | unique |
| position | int | Queue ordering |
| is_active | boolean | default true |
| timestamps | | |

#### `call_center_performance_metrics`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| user_id | bigint unsigned FK | |
| average_response_time | decimal | nullable |
| total_number_of_leads | int | default 0 |
| total_converted_leads | int | default 0 |
| total_reminders | int | default 0 |
| total_customer_attendance | int | default 0 |
| date | date | |
| timestamps | | |

---

### `webhook_logs`

| Column | Type | Notes |
|--------|------|-------|
| id | bigint unsigned PK | |
| source | varchar(255) | whatsapp, facebook, instagram |
| event_type | varchar(255) | nullable |
| payload | json | |
| headers | json | nullable |
| processed_at | timestamp | nullable |
| error | text | nullable |
| timestamps | | |

---

## Permission Tables (Spatie)

### `roles`
### `permissions` 
### `model_has_roles`
### `model_has_permissions`
### `role_has_permissions`

Standard Spatie Laravel Permission table structure.

---

## System Tables

### `cache`
### `cache_locks`
### `jobs`
### `job_batches`
### `failed_jobs`

Standard Laravel queue/cache tables using database driver.
