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
| Known driver app gaps | `DRIVER_APP_AUDIT.md` |
| Customer journeys (flow map) | `CLIENT_APP_FLOWS.md` |

---

## UI Screen Inventory

> Token-economy cache — read this section instead of globbing 400+ Dart files.  
> Line counts are approximate at the time of last audit. "w" = companion widget files in `features/{f}/widgets/`.

### User App — 83 screens across 29 features

| Feature | Screens | Notable widgets | Largest file (lines) |
|---------|---------|-----------------|----------------------|
| **mart** | mart_store_screen, mart_order_tracking_screen, mart_payment_screen, mart_order_history_screen, mart_product_details_screen, mart_message_screen | — (all UI inline) | mart_store_screen 1245 |
| **auth** | sign_in_screen, sign_up_screen, token_gate_screen, qr_scanner_screen, otp_log_in_screen, otp_signup_screen, forgot_password_screen, verification_screen, reset_password_screen, change_pin_screen | manual_auth_waring_bottom_sheet_widget, test_field_title | sign_in_screen 337 |
| **home** | home_screen | banner_view, category_view, home_my_address, home_search_widget, visit_to_mart_widget, coupon_home_widget, best_offers_widget, home_referral_view_widget, home_map_view, voice_search_dialog + shimmer set (4) | home_screen 578 |
| **map** | map_screen | initial_widget (249), accepting_ongoing_bottomsheet (266), otp_sent_bottomsheet (172), share_location_bottom_sheet (178), discount_coupon_bottomsheet (268), parcel_accept_rider_widget, parcel_info_details_widget, parcel_ongoing_bottomsheet_widget, parcel_otp_bottomsheet_widget, parcel_cancelation_list, ride_cancelation_radio_button | map_screen 417 |
| **trip** | trip_screen, trip_details_screen, schedule_trip_map_view | trip_details_top_section_widget (286), trip_item_view (276), trip_details, trip_route_widget, rider_info, parcel_details_widget (176), refund_details_widget (218), parcel_returning_process_widget | trip_details_screen 253 |
| **payment** | payment_screen, digital_payment_screen, review_screen | tips_widget (180), payment_item_info_widget, apply_coupon, digital_card_payment_widget, payment_type_item_widget, successfully_reviewed_screen | payment_screen 406 |
| **wallet** | wallet_screen, digital_add_fund_screen, loyality_point_screen | wallet_money_screen (181), wallet_filter_bottom_sheet (389), point_to_wallet_money_widget (205), add_fund_dialog (187), transfer_money_dialog_widget, use_voucher_code, transaction_card_widget, wallet_money_amount_widget, loyalty_point_help_widget, loyalty_point_card, coupon_use_result_dialog | wallet_filter_bottom_sheet 389 |
| **parcel** | parcel_screen, parcel_list_view_screen | parcel_info_widget (316), finding_rider_widget (308), parcel_details_input_view (166), parcel_item (170), route_widget (165), otp_widget (140), fare_input_widget (143), parcel_category_screen, who_will_pay_button, choose_efficicent_vehicle_widget | parcel_info_widget 316 |
| **set_destination** | set_destination_screen | time_picker_spinner (444!), schedule_date_time_picker_widget (226), input_field_for_set_route, pickup_time_date_widget, process_button_widget, reservation_note_widget, saved_and_recent_item, pick_location_widget | set_destination_screen 524 |
| **ride** | ride_list_view_screen | trip_fare_summery (258), rise_fare_widget (197), ride_expendable_bottom_sheet (167), rider_details_widget (155), ride_item_widget (143), estimated_fare_and_distance, ride_category, confirmation_trip_dialog | trip_fare_summery 258 |
| **refund_request** | refund_request_screen, image_video_viewer | pick_button_widget, refund_request_send_success_bottomsheet | refund_request_screen 511 |
| **safety_setup** | safety_setup_screen | predefine_alert_content_widget (171), after_send_alert_bottomsheet (166), safety_alert_delay_widget (94), safety_alert_bottomsheet_widget (79), other_emergency_number_widget, safety_alert_content_widget, safety_radio_button | safety_setup_screen 107 |
| **my_level** | my_level_screen | level_complete_dialog_widget | my_level_screen 571 |
| **my_offer** | my_offer_screen, discount_screen | discount_cart_widget (113), best_offer_shimmer_widget, offer_type_button_widget, no_coupon_widget | discount_screen 312 |
| **refer_and_earn** | refer_and_earn_screen, referral_details_screen, referral_earning_screen | referral_earn_bottomsheet_widget (172), earning_cart_widget, referral_type_button_widget | referral_details_screen 222 |
| **message** | message_screen, message_list | message_bubble (238), message_item | message_screen 273 |
| **profile** | profile_screen, edit_profile_screen | edit_profile_account_info (294), profile_item | profile_screen 293 |
| **address** | add_new_address, my_address, search_and_pick_location_screen | address_item_card | add_new_address 323 |
| **notification** | notification_screen | notification_card (275), notification_shimmer | notification_card 275 |
| **settings** | setting_screen, policy_screen | language_select_bottomsheet, theme_button | setting_screen 151 |
| **onboard** | onboarding_screen | pager_content | onboarding_screen 238 |
| **support** | support_screen | contact_us_view (125), help_support_type_button_widget | support_screen 107 |
| **dashboard** | dashboard_screen | — | dashboard_screen 150 |
| **splash** | splash_screen, app_version_warning_screen | — | splash_screen 118 |
| **realtime_location_trac** | live_location_screen | — | live_location_screen 230 |
| **coupon** | — (widget-only) | offer_coupon_card_widget (421), coupon_widget (171), custom_coupon_bg | offer_coupon_card_widget 421 |
| **location** | — (widget-only) | location_search_dialog | — |

**User app common widgets** (`lib/common_widgets/` — 36 files, ~3 400 lines):  
`app_bar_widget` (285) · `custom_text_field` (282) · `expandable_bottom_sheet` (426) · `drop_down_widget` (278) · `country_picker_widget` (319) · `vito_map` (258) · `paginated_list_widget` (102) · `skeleton_widget` · `offline_banner_widget` · `button_widget` · `calender_widget` · `confirmation_dialog_widget` · `confirmation_bottomsheet_widget` · `card_widget` · `image_dialog_widget` · `loader_widget` · `no_data_widget` · `error_retry_widget` · `custom_snackbar` · `body_widget` · `swipable_button_widget/slider_button_widget` (180) · `animated_dialog_widget` · `digital_payment_dialog` · `profile_type_button_widget` · `popup_banner/{popup_banner,slider_item_widget,dialog_item_widget}`

**User app helpers** (`lib/helper/` — 20 files):  
`di_container` · `login_helper` · `pusher_helper` · `notification_helper` · `firebase_helper` · `route_helper` · `responsive_helper` · `date_converter` · `price_converter` · `map_bound_helper` · `home_screen_helper` · `ride_controller_helper` · `trip_details_helper` · `email_checker` · `file_validation_helper` · `image_size_checker` · `display_helper` · `svg_image_helper` · `country_code_helper` · `extensin_helper`

---

### Driver App — 55 screens across 24 features

| Feature | Screens | Notable widgets | Notes |
|---------|---------|-----------------|-------|
| **auth** | sign_in_screen, sign_up_screen, additional_sign_up_screen_1, additional_sign_up_screen_2, token_gate_screen, qr_scanner_screen, verification_screen, forgot_password_screen, reset_password_screen, change_pin_screen | manual_auth_waring_bottom_sheet_widget, approve_dialog_widget, dotted_box_widget, signup_appbar_widget, text_field_title_widget | 2-step registration (profile → vehicle) |
| **home** | home_screen (ZoomDrawer), ride_list_screen, parcel_list_screen, vehicle_add_screen | activity_card_widget, custom_menu/{button,widget}, home_bottom_sheet_widget, home_referral_view_widget, ongoing_ride_card_widget, profile_info_card_widget, vehicle_pending_widget + shimmer | Online/offline toggle + ZoomDrawer nav |
| **map** | map_screen | 24 widgets: accepted_ride_widget, bid_accepting_dialog_widget, bidding_dialog_widget, calculating_sub_total_widget, customer_info_widget, customer_ride_request_card_widget, driver_header_info_widget, expendale_bottom_sheet_widget, otp_verification_widget, out_for_pickup_widget, parcel_cancelation_list, parcel_card_widget, ride_cancelation_list, ride_ongoing_widget, rider_details_widget, route_calculation_widget, route_widget, share_location_bottom_sheet, stay_online_widget, trip_accept_warning_dialog_widget, user_details_widget | Multi-state machine for active trip |
| **trip** | trip_screen (ZoomDrawer), trip_details_screen, payment_received_screen, review_this_customer_screen, image_video_viewer | 16 widgets: chart_widget, customer_details_widget, fare_widget, header_title_widget, parcel_refund_details_widget, payment_details_widget, return_dialog_widget, sub_total_header, trip_card_widget, trip_overview_widget, trip_route_widget, trip_safety_sheet_details_widget, trips_widget | Trip history + earnings chart |
| **mart** | mart_pending_orders_screen, mart_delivery_screen ⚠, mart_driver_message_screen, mart_order_history_screen | — | ⚠ mart_delivery_screen is StatefulWidget (no GetX controller) — see DRIVER_APP_AUDIT D1 |
| **wallet** | wallet_screen, payable_history_screen, digital_payment_screen, add_payment_info_screen, update_payment_info_screen, bank_info_edit_screen, payment_info_screen | 20 widgets: cash_in_hand_warning_widget, history_list_widget, income_statement_card_widget, loyalty_point_card_widget, payment_method_bottomsheet_widget, pending_settled_card_widget, point_to_wallet_money_widget, transaction_card_widget, wallet_amount_type_card_widget, withdraw_bottom_sheet_widget, withdraw_successful_dialog_widget | Earnings + loyalty + withdrawals |
| **chat** | message_screen, chat_screen | message_bubble_widget, message_item_widget, user_type_button_widget | Polymorphic: ride or mart |
| **profile** | profile_screen (ZoomDrawer), edit_profile_screen, profile_menu_screen, setting_screen, leaderboard_screen | 8 widgets: level_congratulations_dialog_widget, profile_details_widget, profile_level_details_widget, profile_level_widget, profile_type_button_widget (tabs: Profile/Level/Vehicles), vehicle_details_widget | Unified profile + level + vehicles |
| **refer_and_earn** | refer_and_earn_screen, referral_details_screen, referral_earning_screen | referral_earn_bottomsheet_widget, earning_card_widget, referral_type_button_widget, custom_itle | — |
| **notification** | notification_screen | notification_card_widget, notification_shimmer_widget, receipt_confirmation_bottomsheet | — |
| **safety_setup** | safety_setup_screen | 7 widgets (same set as user app) | — |
| **face_verification** | face_verification_screen, face_verification_result_screen | camera_instruction_widget, face_verifing_dialog, home_face_verification_warning_widget, verification_suspend_widget | Driver KYC — blocks ride acceptance if pending |
| **review** | review_screen | review_card_widget, review_type_button_widget | View customer ratings |
| **leaderboard** | leaderboard_screen | leader_board_card_widget, today_leaderboard_status_widget | Driver rank/earnings |
| **location** | access_location_screen | — | GPS permission request |
| **realtime_location_trac** | live_location_screen | — | GPS broadcast via Pusher |
| **out_of_zone** | out_of_zone_map_screen | out_of_zone_bottoms_sheet_widget | Zone boundary enforcement |
| **help_and_support** | help_and_support_screen, support_chat_screen | admin_conversation_bubble_widget | FAQ + admin chat |
| **splash** | splash_screen, app_version_warning_screen | — | — |
| **dashboard** | dashboard_screen | bottom_navigation_widget | Bottom nav container |

**Driver app common widgets** (`lib/common_widgets/` — 53 files):  
Same core set as user app plus: `animated_wifi_widget` · `bottom_navigation_widget` · `custom_date_picker` · `custom_time_picker` · `date_picker_widget` · `digital_payment_dialog_widget` · `drop_down_item_widget` · `ride_completation_dialog_widget` · `sliver_delegate` · `snackbar_widget` · `text_field_widget` · `title_widget` · `type_button_widget` · `vito_pin_field` (6-digit PIN) · `weather_assistant_widget` · `zoom_drawer_context_widget`

**Driver app helpers** (`lib/helper/` — 23 files):  
Same core set as user app plus: `currency_text_input_formatter_helper` · `image_file_helper` · `map_helper` · `profile_helper` · `string_helper` · `support_chat_helper` · `toaster`

---

### Key UX Patterns (both apps)

| Pattern | Where used |
|---------|-----------|
| ZoomDrawer slide-out menu | Driver: Home, Trip, Profile screens |
| `PaginatedListViewWidget` | Notifications, wallet history, trip history, mart orders |
| `GetBuilder<Controller>` at body level | Nearly all screens — reactive without full rebuild |
| Expandable bottom sheets | Ride/parcel details, filters, payment methods |
| Shimmer loading skeletons | Every list screen has `*_shimmer.dart` counterpart |
| `swipable_button_widget` (slide-to-confirm) | Ride accept/start, delivery proof submit |
| Pusher private channels | Ride chat, mart chat, live location |
| `offline_banner_widget` | All screens check connectivity |
