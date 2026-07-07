# API Reference

**Base URL:** `{APP_URL}/api`

All endpoints (except login and webhooks) require `Authorization: Bearer {token}` header.

---

## Authentication

### POST `/login`

Authenticate with email/password.

**Request:**
```json
{
    "email": "super@clinic.com",
    "password": "password123"
}
```

**Response (200):**
```json
{
    "token": "1|abc123...",
    "user": { ... }
}
```

### POST `/logout`

Revoke the current token. Requires auth.

### GET `/me`

Get the authenticated user with roles and permissions.

---

## Users & Admin

All endpoints in this section require `auth:sanctum`.

### GET `/users` — List users
### POST `/users` — Create user
### GET `/users/{user}` — Show user
### PATCH `/users/{user}` — Update user
### DELETE `/users/{user}` — Delete user
### PATCH `/users/{user}/roles` — Sync user roles

### GET `/roles` — List roles
### POST `/roles` — Create role
### GET `/roles/{role}` — Show role
### PATCH `/roles/{role}` — Update role
### DELETE `/roles/{role}` — Delete role
### PATCH `/roles/{role}/permissions` — Sync role permissions

### GET `/permissions` — List all permissions

---

## Clinics

### GET `/clinics` — List clinics
### POST `/clinics` — Create clinic
### GET `/clinics/{clinic}` — Show clinic
### PATCH `/clinics/{clinic}` — Update clinic
### DELETE `/clinics/{clinic}` — Delete clinic

---

## Leads

### GET `/leads` — List leads (permission-scoped)
### POST `/leads` — Create lead
### GET `/leads/{id}` — Show lead
### PATCH `/leads/{lead}` — Update lead
### DELETE `/leads/{lead}` — Delete lead

---

## Campaigns

### GET `/campaigns` — List campaigns
### POST `/campaigns` — Create campaign
### GET `/campaigns/{campaign}` — Show campaign
### PATCH `/campaigns/{campaign}` — Update campaign
### DELETE `/campaigns/{campaign}` — Delete campaign

## Campaign Costs

### GET `/campaign-costs` — List campaign costs
### POST `/campaign-costs` — Create campaign cost
### GET `/campaign-costs/{campaignCost}` — Show campaign cost
### PATCH `/campaign-costs/{campaignCost}` — Update campaign cost
### DELETE `/campaign-costs/{campaignCost}` — Delete campaign cost

---

## Call Center

### GET `/call-center/queue` — Get agent queue (ordered by position)
### GET `/call-center/queue/next` — Get the next agent in queue
### POST `/call-center/queue/add/{userId}` — Add an agent to the queue
### DELETE `/call-center/queue/remove/{userId}` — Remove an agent from the queue
### POST `/call-center/leads/assign` — Assign a specific lead to a user
### POST `/call-center/leads/{leadId}/assign-next` — Assign a lead to the next agent in queue

---

## Agent Endpoints

Agent-scoped endpoints for the authenticated user.

### GET `/agent/conversations` — Current user's conversations
### GET `/agent/leads` — Current user's assigned leads
### GET `/agent/followups` — Current user's pending follow-ups
### POST `/agent/messages/send` — Send a message (WhatsApp/Facebook)

---

## Webhooks (No Auth)

### GET `/webhook/meta` — Meta webhook verification (GET with `hub_challenge`)
### POST `/webhook/meta` — Receive incoming webhooks (WhatsApp, Facebook, Instagram)

---

## Visits

### GET `/visits` — List visits
### POST `/visits` — Create visit
### GET `/visits/{visit}` — Show visit
### PATCH `/visits/{visit}` — Update visit
### DELETE `/visits/{visit}` — Delete visit

### Visit Flow Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| PATCH | `/visits/{visit}/confirm` | Confirm an appointment |
| POST | `/visits/{visit}/complete` | Complete a visit (creates report, deducts inventory) |
| PATCH | `/visits/{visit}/cancel` | Cancel a visit |
| PATCH | `/visits/{visit}/miss` | Mark a visit as missed |

---

## Treatment Plans

### GET `/treatment-plans` — List treatment plans
### POST `/treatment-plans` — Create treatment plan
### GET `/treatment-plans/{treatmentPlan}` — Show treatment plan
### PATCH `/treatment-plans/{treatmentPlan}` — Update treatment plan
### DELETE `/treatment-plans/{treatmentPlan}` — Delete treatment plan

---

## Invoices

### GET `/invoices` — List invoices
### POST `/invoices` — Create invoice
### GET `/invoices/{invoice}` — Show invoice
### PATCH `/invoices/{invoice}` — Update invoice
### DELETE `/invoices/{invoice}` — Delete invoice
### PATCH `/invoices/{invoice}/pay` — Record a payment against an invoice

---

## Pharmaceuticals

### GET `/pharmaceuticals` — List pharmaceuticals
### POST `/pharmaceuticals` — Create pharmaceutical (SKU as string PK)
### GET `/pharmaceuticals/{pharmaceutical}` — Show pharmaceutical
### PATCH `/pharmaceuticals/{pharmaceutical}` — Update pharmaceutical
### DELETE `/pharmaceuticals/{pharmaceutical}` — Delete pharmaceutical

---

## Warehouses

### GET `/warehouses` — List warehouses
### POST `/warehouses` — Create warehouse
### GET `/warehouses/{warehouse}` — Show warehouse
### PATCH `/warehouses/{warehouse}` — Update warehouse
### DELETE `/warehouses/{warehouse}` — Delete warehouse

---

## Suppliers

### GET `/suppliers` — List suppliers
### POST `/suppliers` — Create supplier
### GET `/suppliers/{supplier}` — Show supplier
### PATCH `/suppliers/{supplier}` — Update supplier
### DELETE `/suppliers/{supplier}` — Delete supplier

## Supplier Payments

### GET `/supplier-payments` — List supplier payments
### POST `/supplier-payments` — Create supplier payment
### GET `/supplier-payments/{supplierPayment}` — Show supplier payment
### PATCH `/supplier-payments/{supplierPayment}` — Update supplier payment
### DELETE `/supplier-payments/{supplierPayment}` — Delete supplier payment
### PATCH `/supplier-payments/{supplierPayment}/pay` — Record a payment to a supplier

---

## Transactions (Warehouse-Supplier)

### GET `/transactions` — List warehouse-supplier transactions
### POST `/transactions` — Create transaction
### GET `/transactions/{transaction}` — Show transaction
### PATCH `/transactions/{transaction}` — Update transaction
### DELETE `/transactions/{transaction}` — Delete transaction

---

## Medical Records

### GET `/leads/{lead}/medical-records` — List medical records for a lead
### POST `/leads/{lead}/medical-records` — Upload a medical record
### GET `/medical-records/{medicalRecord}` — Show medical record
### PATCH `/medical-records/{medicalRecord}` — Update medical record
### DELETE `/medical-records/{medicalRecord}` — Delete medical record
### GET `/medical-records/{medicalRecord}/file` — View file inline
### GET `/medical-records/{medicalRecord}/download` — Download file

---

## Patient Feedback

### GET `/patient/feedback` — List feedback
### POST `/patient/feedback` — Create feedback
### GET `/patient/feedback/{patientFeedback}` — Show feedback
### PATCH `/patient/feedback/{patientFeedback}` — Update feedback
### DELETE `/patient/feedback/{patientFeedback}` — Delete feedback

---

## Response Format

All endpoints return JSON. Successful responses follow this structure:

```json
{
    "id": 1,
    "name": "Example",
    "created_at": "2026-01-01T00:00:00.000000Z",
    "updated_at": "2026-01-01T00:00:00.000000Z",
    "relationships": { ... }
}
```

Error responses:

```json
{
    "message": "Route [login] not defined.",
    "exception": "AuthenticationException",
    ...
}
```
