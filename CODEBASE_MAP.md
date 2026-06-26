# Vito — Codebase Map

Quick-reference structural map of the entire repository. For conventions and commands see `CLAUDE.md`; for known gaps see `USER_APP_AUDIT.md`.

---

## Repository Layout

```
handz/
├── drivemond-admin-new-install-3.1/   Laravel 12 backend   (1 489 PHP files, 198 migrations)
├── drivemond-user-app-3.1/            Flutter customer app (429 Dart files)
├── drivemond-driver-app-3.1/          Flutter driver app   (409 Dart files)
├── landing/index.html                 Vanilla HTML QR-validation landing page
├── .github/workflows/                 CI (vito-ci, build-apk, build-apk-hands, ui-goldens)
└── *.md                               CLAUDE, README, CONTRIBUTING, DEPLOY,
                                       PRODUCTION_DEPLOYMENT, AUDIT, AUTH_AUDIT,
                                       VITO_AUDIT, USER_APP_AUDIT, CODEBASE_MAP
```

---

## Backend (`drivemond-admin-new-install-3.1/`)

### Module list

| Module | Purpose |
|--------|---------|
| **AuthManagement** | QR tokens, PIN login/register, OTP auth, username management |
| **TripManagement** | Rides (VitoRide), parcels (VitoSend), VitoMart orders + admin panel |
| **Gateways** | Stripe PaymentIntent, idempotent webhooks, wallet top-up |
| **ChattingManagement** | Real-time chat (polymorphic: TripRequest or MartOrder) |
| **UserManagement** | Customer/driver profiles, levels, referrals |
| **ZoneManagement** | Service zones |
| **VehicleManagement** | Vehicle types and assignment |
| **FareManagement** | Pricing rules per zone/vehicle |
| **PromotionManagement** | Coupon/promo code engine |
| **TransactionManagement** | Wallet ledger, transaction history |
| **ParcelManagement** | Parcel categories and pricing config |
| **ReviewModule** | Ratings/reviews for rides and drivers |
| **BusinessManagement** | Admin business settings (SMS gateway, payment, etc.) |
| **AdminModule** | Role-permission system, dashboard |
| **AiModule** | AI-assisted features |
| **BlogManagement** | CMS blog for marketing pages |

### Vito-specific controllers (the only files PHPStan covers)

```
AuthManagement/Http/Controllers/Api/
  VitoAuthController.php         PIN login/register, PIN change, token revocation
  QrTokenController.php          QR token generate/validate/revoke (admin-gated)
  ClientOtpAuthController.php    SMS OTP path (send-otp → verify → register-from-otp)

AuthManagement/Http/Controllers/Web/
  VitoQrAdminController.php      Admin QR management panel

TripManagement/Http/Controllers/Api/
  Customer/VitoMartController.php        Browse, cart, order, promo, cancel, review
  Driver/VitoTripController.php          Accept/reject rides atomically
  Driver/VitoParcelController.php        Accept/reject parcels
  Driver/VitoMartDriverController.php    Pending orders, accept, status, upload proof
  Admin/VitoMartAdminApiController.php   Product CRUD via API
  Admin/VitoSystemController.php         GET /api/health  GET /api/admin/metrics

TripManagement/Http/Controllers/Web/
  VitoMartAdminController.php            Product CRUD (admin panel)
  MartOrderAdminController.php           Orders list/details/status/export
  MartPromoCodeAdminController.php       Promo code CRUD
  MartReviewAdminController.php          Read-only review list
  MartCategoryAdminController.php        Category CRUD
  MartDashboardController.php            Mart analytics dashboard

Gateways/Http/Controllers/Api/
  VitoStripeController.php               Create PaymentIntent, handle webhook
```

### Routing model

Every module has `Routes/api.php` + `Routes/web.php` (auto-loaded via module service provider).  
TripManagement additionally has `Routes/vito_api.php` — **use this file for all new Vito API routes**.

```
auth:api + maintenance_mode + scope:Access{Customer|Driver|SuperAdmin}
                       ↓
  api.php  (legacy base routes — rides, driver status, etc.)
  vito_api.php  (Vito routes — mart, health, metrics)
```

### Key `app/` directories

```
app/
├── Http/Middleware/     IdempotencyKey, RequestId, SecurityHeaders, MaintenanceModeMiddleware,
│                        Localization, GlobalMiddleware (+ Laravel defaults)
├── Providers/           AuthServiceProvider (gates), GlobalDataServiceProvider (view composers)
├── Jobs/                Ride-timeout auto-cancel (needs QUEUE_CONNECTION=redis + worker)
├── Lib/                 Constant.php (MODULES list, permission keys)
├── Library/             Shared helpers (SMS dispatch, notification, etc.)
└── WebSockets/          Reverb channel definitions
```

### Mart entities (`TripManagement/Entities/`)

`MartProduct` · `MartCategory` · `MartOrder` · `MartOrderItem` · `MartPromoCode` · `MartReview` · `StripeEvent`

Order status state machine (source of truth: `MartOrder::STATUS_TRANSITIONS`):
```
pending → accepted → picked_up → delivered
       ↘ cancelled (from pending or accepted)
```

### Auth flows

| Path | Entry point | Gate |
|------|-------------|------|
| PIN (primary) | `POST /api/customer/auth/pin-login` · `VitoAuthController` | QR/invite token required for registration |
| OTP (alternative) | `ClientOtpAuthController` send-otp → verify → register | No QR required |
| Legacy | `AuthController` (phone/password, Firebase-OTP, social) | Keep, do not remove |

---

## User App (`drivemond-user-app-3.1/HexaRide-User-app-release-3.1/lib/`)

### Architecture layers

```
ApiClient  (lib/data/api_client.dart)
    ↓
Repository (lib/features/{f}/domain/repositories/)
    ↓
Service    (lib/features/{f}/domain/services/)
    ↓
Controller (lib/features/{f}/controllers/)   ← GetX, registered in di_container.dart
    ↓
Screen     (lib/features/{f}/screens/)       ← Get.to(() => Screen()) navigation
```

All four layers must be registered with `Get.lazyPut()` in `lib/helper/di_container.dart` when adding a feature.

### Feature list (29 features)

**Auth & onboarding**
- `auth` — sign-in (PIN), sign-up, OTP login, forgot-password, change-PIN, token gate (QR scan/enter)
- `onboard` — onboarding slides → language selection (⚠ `LanguageSelectionScreen` missing — see USER_APP_AUDIT C3)
- `splash` — connectivity check, config load, route dispatch

**Core ride & delivery**
- `ride` — booking, driver matching, real-time tracking, cancellation, rating
- `parcel` — parcel booking, category selection, status tracking
- `trip` — active/past trip history
- `set_destination` — address picker + map
- `map` — shared map widget layer

**VitoMart**
- `mart` — product browse, cart, checkout, order history, tracking, delivery proof view
  - `mart/controllers/MartController` — GetX state for all mart actions
  - `mart/domain/models/` — MartProduct, MartOrder, MartCartItem, MartPromoCode, MartReview
  - `mart/domain/repositories/MartRepository` + `mart/domain/services/MartService`
  - `mart/screens/` — mart_store_screen, mart_order_tracking_screen, mart_delivery_screen, mart_order_history_screen, mart_product_details_screen, mart_payment_screen, mart_message_screen

**Payments & wallet**
- `wallet` — balance display, top-up history
- `payment` — Stripe PaymentSheet, payment method selection
- `coupon` — promotion/coupon listing

**Communication**
- `message` — ride chat + mart chat (Pusher channels), `MessageController`
- `notification` — FCM notification list, deep-link routing (`notification_helper.dart`)
- `support` — help/support tickets

**Profile & account**
- `profile` — profile edit, account deletion
- `settings` — language picker, theme toggle, PIN change
- `address` — saved address CRUD
- `refund_request` — raise a refund

**Social / gamification**
- `refer_and_earn` — referral code sharing
- `my_level` — customer loyalty level
- `my_offer` — personalised offers
- `dashboard` / `home` — main home screen + widgets

**Misc**
- `location` — real-time location tracking
- `realtime_location_trac` — background location worker
- `safety_setup` — emergency contacts
- `maintainance_mode` — maintenance overlay screen

### Key helpers

| File | Role |
|------|------|
| `lib/helper/di_container.dart` | **All** GetX dependency registration — edit here for new features |
| `lib/helper/login_helper.dart` | Post-splash routing (authenticated vs new-user path) |
| `lib/helper/pusher_helper.dart` | Pusher client init, ride/trip channel subscriptions |
| `lib/helper/notification_helper.dart` | FCM message handling + deep-link routing |
| `lib/helper/firebase_helper.dart` | Firebase init, topic subscriptions |
| `lib/helper/route_helper.dart` | Named-route constants (currently unused — navigation uses `Get.to`) |
| `lib/data/api_client.dart` | HTTP wrapper (getData/postData/putData/postMultipartData) |
| `lib/data/offline_queue.dart` | SQLite-backed offline action queue |
| `lib/util/app_constants.dart` | API endpoint constants, app config values |

### Localization

`assets/language/en.json` · `assets/language/es.json` (1 118 keys each, verified in parity).  
`ar.json` is **missing** — required by CLAUDE.md and `vito_flows_test.dart`.  
All UI strings: `'key'.tr` — add every key to both existing files (and `ar.json` once created).

---

## Driver App (`drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/lib/`)

Same architecture as user app (ApiClient → Repo → Service → Controller → Screen, same DI pattern).

### Feature list (24 features)

**Differs from user app:**

| Driver-only | User-only |
|-------------|-----------|
| `face_verification` — liveness check | `coupon` |
| `leaderboard` — driver ranking | `refund_request` |
| `out_of_zone` — zone boundary alert | `set_destination` |
| `help_and_support` | `my_level`, `my_offer` |
| `review` — view customer reviews | `support` |
| `html` — in-app HTML viewer | |

**Shared features:** auth, chat, dashboard, home, location, maintainance_mode, map, mart, notification, profile, realtime_location_trac, refer_and_earn, ride, safety_setup, setting, splash, trip, wallet.

### Driver mart flow

Driver-side mart: `MartController` (driver-scoped endpoints) — pending orders, accept, status update, upload delivery proof (photo + signature).  
Chat: `ChatController.createMartChannel()` → `MartDriverMessageScreen`.

---

## Cross-cutting

### Real-time channels

| Flow | Backend event | Customer channel | Driver channel |
|------|--------------|-----------------|----------------|
| Ride chat | CustomerRideChatEvent / DriverRideChatEvent | `private-customer-ride-chat.{tripId}` | `private-driver-ride-chat.{tripId}` |
| Mart chat | CustomerMartOrderChatEvent / DriverMartOrderChatEvent | `private-customer-mart-chat.{orderId}` | `private-driver-mart-chat.{orderId}` |

Backend broadcast: Laravel Reverb (`BROADCAST_DRIVER=reverb`, port 6015).  
Apps: `dart_pusher_channels` package. Always validate `trip_id` / `order_id` in event payload before inserting message.

### CI workflows

| Workflow | Trigger | What it does |
|----------|---------|-------------|
| `vito-ci.yml` | push/PR to `master` | PHPStan level 0 on 8 Vito controllers, VitoFlowTest, Flutter analyze + tests + debug APK (both apps) |
| `build-apk.yml` | `v*` tag or manual | Release APK builds (both apps) |
| `build-apk-hands.yml` | manual | Ad-hoc APK build |
| `ui-goldens.yml` | push | Flutter golden screenshot tests |

Required secrets: `MAPS_API_KEY`, `STRIPE_PUBLISHABLE_KEY`.

---

## Where to Look (quick reference)

| Task | Where |
|------|-------|
| Add a new Vito API route | `Modules/TripManagement/Routes/vito_api.php` (or relevant module's `api.php`) |
| Add a new mart order status | `MartOrder::STATUS_TRANSITIONS` in `TripManagement/Entities/MartOrder.php` |
| Add a Flutter feature | `lib/features/{name}/` + register all 4 layers in `lib/helper/di_container.dart` |
| Add a translation key | `assets/language/en.json` + `es.json` (+ `ar.json` once created) |
| Change Stripe logic | `Modules/Gateways/Http/Controllers/Api/VitoStripeController.php` |
| Add admin permission gate | `app/Lib/Constant.php` (MODULES) + `app/Providers/AuthServiceProvider.php` |
| Add sidebar badge count | `app/Providers/GlobalDataServiceProvider.php` |
| Write a backend test | `tests/Feature/VitoFlowTest.php` — update `tearDown` dropIfExists if adding tables |
| Debug push notifications | `lib/helper/notification_helper.dart` (routing) + `lib/helper/firebase_helper.dart` (token) |
| Debug Pusher connection | `lib/helper/pusher_helper.dart` |
| Change auth flow | `Modules/AuthManagement/Http/Controllers/Api/VitoAuthController.php` |
| Seed dev accounts | `php artisan db:seed` (DefaultUsersSeeder — customer/driver/admin) |
| Smoke-test backend locally | `GET /api/health` (unauthenticated) |
| Known user app gaps | `USER_APP_AUDIT.md` |
