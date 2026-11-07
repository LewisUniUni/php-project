# Driver and PDA client boundary

This repository does not contain a driver app or a PDA app. The choice of native, cross-platform, PWA, and scanner SDK is open. Clients that get built later call the HTTP API below.

## Common rules

- Base path `/api/v1`.
- Obtain a bearer token from `POST /oauth/token` with `grant_type=client_credentials`.
- Send `Authorization: Bearer <token>` and, on every POST, `Idempotency-Key: <uuid>`.
- Send the same key and the same raw body to retry a POST safely.
- Errors are `application/problem+json`. The token endpoint itself returns OAuth2 errors.
- The contract is [openapi.yaml](openapi.yaml).

## Driver

Intended production login is authorization code with PKCE, a short-lived access token, and a refresh token bound to a device. That flow is not implemented.

The shipped stand-in is a confidential client named `driver-mobile` with scopes `delivery.read`, `delivery.write`, and `telemetry.write`.

A driver client can:

- `GET /api/v1/delivery-stops`
- `GET /api/v1/delivery-stops/{stop}`
- `POST /api/v1/delivery-stops/{stop}/transitions` with `{"status":"dispatched"}` and the other statuses allowed by the stop
- `POST /api/v1/scan-events`

Allowed stop moves are `planned → dispatched|cancelled`, `dispatched → in_transit|cancelled`, and `in_transit → delivered|failed`. `delivered` consumes the linked reservation in MySQL.

## PDA

Intended production use is a per-device OAuth client plus a user login with PKCE, so a shared scanner can switch users quickly. That user switch is not implemented.

The shipped stand-in is `pda-scanner` with scopes `inventory.read` and `telemetry.write`.

Scan body:

```json
{
  "event_id": "6f1c0b4e-8e0a-4f0e-9c1a-2b3d4e5f6071",
  "device_id": "pda-14",
  "symbology": "code128",
  "payload": "SKU-PALLET-WRAP",
  "warehouse_code": "WH-1",
  "recorded_at": "2026-11-06T23:40:00+00:00",
  "device": {"model": "TC52", "firmware": "1.4.2"}
}
```

`payload` is at most 512 characters. `event_id` is the device's idempotency id: posting it again returns the original event and does not insert a second document. The document is not a stock movement. Stock changes go through the inventory operations.

## Partner

`partner:<name>` clients are read-only in the seed (`inventory.read`, `delivery.read`) and use a lower rate limit. Webhooks are not implemented.
