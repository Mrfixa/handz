# VITO — Ride-Hailing, Delivery & Marketplace Platform

[![VITO CI](https://github.com/Mrfixa/Vito/actions/workflows/vito-ci.yml/badge.svg)](https://github.com/Mrfixa/Vito/actions/workflows/vito-ci.yml)

VITO is a full-stack ride-hailing platform built on Laravel (admin backend) and Flutter (user & driver mobile apps). It extends the DriveMond base with QR-based invitation flow, PIN authentication, VitoMart marketplace, Stripe wallet, and atomic ride acceptance.

## Architecture

```
Vito/
├── drivemond-admin-new-install-3.1/   # Laravel 12 admin backend (PHP 8.2+)
│   ├── Modules/                       # Modular architecture (nwidart/laravel-modules)
│   │   ├── AuthManagement/            # QR tokens, PIN login/register
│   │   ├── TripManagement/            # Rides, parcels, VitoMart
│   │   ├── Gateways/                  # Stripe PaymentIntent, webhooks
│   │   └── ...                        # 13+ modules
│   └── tests/Feature/VitoFlowTest.php # 8 PHPUnit tests covering all VITO flows
├── drivemond-user-app-3.1/            # Flutter user app (GetX state management)
├── drivemond-driver-app-3.1/          # Flutter driver app
├── landing/                           # Standalone landing page (vanilla HTML)
└── .github/workflows/vito-ci.yml      # CI pipeline
```

## Core Flows

1. **QR Invitation** — Admin/driver generates referral QR tokens (1h client / 7d driver). Landing page validates and shows download link.
2. **Registration** — Username + 6-digit PIN (no phone/OTP). Token-gated entry.
3. **Login** — Username + PIN with 5-attempt lockout (15 min).
4. **VitoRide** — Ride booking with atomic driver acceptance (`WHERE driver_id IS NULL`).
5. **VitoSend** — Parcel delivery with same atomic acceptance.
6. **VitoMart** — Admin product CRUD, client browse/cart/order, driver delivery with photo+signature proof.
7. **Wallet & Stripe** — In-app top-up via Stripe PaymentSheet, idempotent webhook crediting.
8. **Security** — bcrypt PIN hashing, lockout, no API keys in code, haptic feedback on CTAs.

## Setup

### Prerequisites

- PHP 8.2+, Composer 2
- Flutter 3.24+ (Dart 3.5+)
- MySQL 8 (or SQLite for testing)
- Android SDK (for APK builds)

### Backend

```bash
cd drivemond-admin-new-install-3.1
composer install --no-interaction --no-scripts --ignore-platform-reqs
cp .env.example .env
php artisan key:generate
php artisan passport:keys --force
php artisan migrate
php artisan serve
```

### Flutter Apps

```bash
# User app
cd drivemond-user-app-3.1/HexaRide-User-app-release-3.1
flutter pub get
flutter run

# Driver app
cd drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1
flutter pub get
flutter run
```

### Running Tests

```bash
# Laravel (8 VITO flow tests)
cd drivemond-admin-new-install-3.1
php artisan test --filter=VitoFlowTest

# Flutter user app
cd drivemond-user-app-3.1/HexaRide-User-app-release-3.1
flutter test test/vito_flows_test.dart

# Flutter driver app
cd drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1
flutter test test/vito_flows_test.dart
```

## Production Checklist

| Item | Status | Notes |
|---|---|---|
| Payment / wallet atomicity | ✅ Done | Triple-layer: app logic + DB unique + webhook dedup |
| Stripe idempotency | ✅ Done | `stripe_event_id` unique constraint + IdempotencyKey middleware |
| PIN + QR auth (Passport) | ✅ Done | 6-digit PIN, bcrypt, 5-attempt lockout |
| RFC 7807 error responses | ✅ Done | Additive — legacy keys preserved for Flutter clients |
| Structured logging + Request-ID | ✅ Done | `json_stderr` channel, `X-Request-Id` header propagation |
| Backend Sentry | ⚙️ Needs `SENTRY_LARAVEL_DSN` secret | No-op without DSN |
| Health endpoint `GET /api/health` | ✅ Done | Unauthenticated, throttled; checks DB + cache |
| Business metrics `GET /api/admin/metrics` | ✅ Done | Admin-only, 60s cache |
| Idempotency replay (POST/PUT/PATCH) | ✅ Done | Works with file cache; Redis recommended for multi-node |
| External call retry (Stripe, Twilio) | ✅ Done | `retry(3, …, 500ms backoff)` |
| Ride-timeout auto-cancel job | ⚙️ Needs queue worker + Redis | `QUEUE_CONNECTION=redis` |
| Flutter offline action queue | ✅ Done | SQLite-backed, flushes on reconnect |
| Flutter Crashlytics | ✅ Done | Both apps wired; user app added parity with driver app |
| Security headers middleware | ✅ Done | X-Content-Type-Options, X-Frame-Options, Referrer-Policy |
| `security.txt` | ✅ Done | `public/.well-known/security.txt` |
| PIN validation (client) | ✅ Done | Fixed: `^\d{6}$` regex matches backend `digits:6` rule |
| Dark/light theme consistency | ✅ Done | Hardcoded colors replaced with theme tokens |
| Shimmer skeleton loaders | ✅ Done | Mart tracking + delivery screens |
| EN/ES/AR localization parity | ✅ Done | Test enforces parity across all three files |
| CI coverage artifacts | ✅ Done | PHP (clover) + Flutter (lcov) uploaded; ratcheted floor |
| 80% test coverage gate | ⬜ Deferred | Ratchet `PHP_COVERAGE_FLOOR`/`FLUTTER_COVERAGE_FLOOR` upward as tests grow |
| K6 load test | ⬜ Deferred | Needs target infra; scripts can be written against `/api/health` |

See `PRODUCTION_DEPLOYMENT.md` for rollout steps, required secrets, and rollback procedures.

## CI/CD

The GitHub Actions workflow (`.github/workflows/vito-ci.yml`) runs on every push/PR to `master`:

- PHPStan static analysis (level 0) on all 8 Vito controllers
- PHPUnit tests (73 Vito flow tests) with Xdebug coverage artifact
- Flutter analyze on both apps
- Flutter tests with lcov coverage artifact
- Debug APK builds (uploaded as artifacts, 14-day retention)
- Ratcheted coverage floor check (raise `PHP_COVERAGE_FLOOR`/`FLUTTER_COVERAGE_FLOOR` as coverage grows)

## Environment Secrets (GitHub Actions)

| Secret | Purpose |
|--------|---------|
| `MAPS_API_KEY` | Google Maps API key for Flutter apps |
| `STRIPE_PUBLISHABLE_KEY` | Stripe publishable key for payment flows |

## Localization

Both apps support EN, ES, and AR translations. Translation files are in `assets/language/`. All Vito-specific strings are translated in all supported languages. Language picker shows EN + ES only; AR translations are maintained for parity but not surfaced in the picker.
