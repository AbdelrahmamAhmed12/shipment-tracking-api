# Shipment Tracking API

A small last-mile shipping API built with Laravel 13. Clients create shipments and get a price, operations staff move each shipment through its delivery statuses, and clients are notified of every change through signed webhooks.

I built it as a compact example of how I structure a Laravel back end: thin controllers, business rules in services, and tests around the parts that involve money and state.

## Features

- **Shipments.** Create, list and view shipments. Each one gets a unique tracking number.
- **Pricing by zone and weight.** A base price covers a base weight; every started kilogram above it is charged extra.
- **Status state machine.** A shipment can only move along allowed transitions (for example `pending → picked_up`, never `pending → delivered`).
- **Status history.** Every change is logged with who made it, when, and an optional note.
- **Signed webhooks.** Each status change is sent to the client's endpoints from the queue, signed with HMAC-SHA256, with retries and a delivery log.
- **Public tracking.** Anyone with a tracking number can see the journey, without the receiver's details or the price.
- **Auth and roles.** Sanctum API tokens. Clients see only their own shipments; admins see all and change statuses.

## Design decisions

| Decision | Why |
| --- | --- |
| Money stored as integers in the smallest unit | Floats lose precision. `2500` means 25.00 SAR. |
| Status rules live in the `ShipmentStatus` enum | One place defines what is allowed, and it is unit tested without a database. |
| Status change runs in a transaction with `lockForUpdate()` | Two concurrent updates cannot both pass the check against the same old status. |
| Events are dispatched after the transaction commits | Listeners and queued jobs never read uncommitted data. |
| Unique `(user_id, reference)` | A client that sends the same order twice gets a validation error, not two shipments. |
| Webhook deliveries are stored before they are sent | Every attempt, response code and error is traceable. |
| Webhook secret is encrypted and shown only once | It cannot be read back from the API or from a database dump. |
| Shipments are addressed by tracking number | Internal IDs are never exposed in URLs. |

## Status flow

```
pending ──► picked_up ──► in_transit ──► out_for_delivery ──► delivered
   │                                        │        ▲
   ▼                                        ▼        │
cancelled                              failed_attempt ──► returned
```

`delivered`, `returned` and `cancelled` are final.

## API

All routes are under `/api/v1`. Send `Accept: application/json` and, for protected routes, `Authorization: Bearer <token>`.

| Method | Route | Who | Purpose |
| --- | --- | --- | --- |
| POST | `/auth/token` | Public | Exchange email and password for a token |
| DELETE | `/auth/token` | Authenticated | Revoke the current token |
| GET | `/track/{tracking_number}` | Public | Track a shipment |
| POST | `/rates/quote` | Authenticated | Price for two zones and a weight |
| GET | `/shipments` | Authenticated | List shipments, optional `status` filter |
| POST | `/shipments` | Authenticated | Create a shipment |
| GET | `/shipments/{tracking_number}` | Owner or admin | Shipment with full history |
| PATCH | `/shipments/{tracking_number}/status` | Admin | Move to a new status |
| GET | `/webhook-endpoints` | Authenticated | List your endpoints |
| POST | `/webhook-endpoints` | Authenticated | Register an HTTPS endpoint |
| DELETE | `/webhook-endpoints/{id}` | Owner | Remove an endpoint |

### Create a shipment

```http
POST /api/v1/shipments
```

```json
{
  "reference": "ORDER-1001",
  "origin_zone_id": 1,
  "destination_zone_id": 2,
  "receiver_name": "Sara Ali",
  "receiver_phone": "+966500000000",
  "receiver_address": "King Fahd Road, Riyadh",
  "weight_grams": 7000
}
```

An invalid status change returns `409 Conflict` with the allowed next statuses.

### Verifying a webhook

Each request carries `X-Webhook-Signature`: the HMAC-SHA256 of the raw request body, using the secret returned when the endpoint was created.

```php
$expected = hash_hmac('sha256', $rawBody, $secret);

if (! hash_equals($expected, $request->header('X-Webhook-Signature'))) {
    abort(401);
}
```

## Running locally

Requires PHP 8.3+ and Composer. It runs on SQLite by default.

```bash
git clone <this-repo> && cd shipment-tracking-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
php artisan queue:work   # in a second terminal, to send webhooks
```

The seeder creates three zones with rates and two demo users, both with the password `password`: `admin@example.com` and `client@example.com`. They are for local use only.

## Tests

```bash
php artisan test
```

The suite covers pricing, the status rules, authorization, the HTTP API and webhook signing and failure handling.

## Project layout

```
app/Enums/ShipmentStatus.php          Status values and allowed transitions
app/Services/PricingService.php       Fee calculation
app/Services/ShipmentService.php      Create a shipment, change its status
app/Events, app/Listeners, app/Jobs   Status change → webhook deliveries
app/Http/Controllers/Api/V1           Thin controllers
app/Http/Requests, Resources          Validation and response shape
app/Policies/ShipmentPolicy.php       Who can view and update
tests/                                Unit and feature tests
```

## Possible next steps

- Idempotency keys on `POST /shipments` for safe client retries
- Courier assignment and delivery run sheets
- Cash-on-delivery settlement and client invoices
- OpenAPI documentation
