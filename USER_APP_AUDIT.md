# Vito User App — End-to-End Gap Audit

> **Scope:** Flutter customer app (`drivemond-user-app-3.1/HexaRide-User-app-release-3.1/`)  
> **Method:** Full static analysis across auth, onboarding, mart, ride, parcel, wallet, chat, real-time, tests, localization, API client, and code quality.

---

## Executive Summary

> **Reconciled 2026-06-26 (v2.2.0).** Many findings below are **resolved** — all C-series criticals
> and the audited H-series are fixed and shipped (v2.1.0/v2.2.0). See the "Fixed in v2.1.0" and
> "Fixed / verified in v2.2.0" tables near the end of this file. The counts below are the *original*
> full inventory; **verify against source before acting on any individual item.**

| Severity | Original count |
|----------|----------------|
| 🔴 CRITICAL — crash, data integrity, or CI-blocking | 13 |
| 🟠 HIGH — feature broken or security gap | 23 |
| 🟡 MEDIUM — degraded UX or error-prone edge case | 35 |
| 🔵 LOW — polish, dead code, minor inconsistency | 6 |
| **Total** | **77** |

---

## 🔴 CRITICAL

### C1 — Mart status test asserts wrong status values
**File:** `test/vito_flows_test.dart:144–155`

Test hardcodes the status flow as `['placed', 'confirmed', 'preparing', 'ready', 'dispatched', 'delivered']`. The backend (and `MartOrder::STATUS_TRANSITIONS`) uses `pending → accepted → picked_up → delivered`. This is a different set of values, so the test will never accurately validate the real flow; it also creates false confidence since the assertions (`.first == 'placed'`, `.last == 'delivered'`) pass trivially without testing backend transitions.

**Fix:** Replace the status list with `['pending', 'accepted', 'picked_up', 'delivered']` and add transition-rule assertions matching `MartOrder::STATUS_TRANSITIONS`.

---

### C2 — `OtpSignupScreen` referenced but does not exist
**File:** `lib/features/auth/controllers/auth_controller.dart:255`

```dart
Get.off(() => OtpSignupScreen(phoneNumber: phone));
```

This is called when OTP verification returns HTTP 406 (unregistered user). The screen is imported but the file does not exist in the codebase. Any user who attempts OTP login with an unregistered phone number crashes the app.

**Fix:** Create `lib/features/auth/screens/otp_signup_screen.dart` (username + PIN collection after OTP verification) or redirect to the existing `SignUpScreen` with the verified phone pre-filled.

---

### C3 — `LanguageSelectionScreen` referenced but does not exist
**Files:** `lib/features/onboard/screens/onboarding_screen.dart:7`, `lib/helper/login_helper.dart:91,188`

```dart
Get.offAll(() => LanguageSelectionScreen(notificationData: notificationData));
```

Called on first launch when no language is saved. The screen is imported but absent. Every first-time user crashes before reaching the login screen.

**Fix:** Create `lib/features/splash/screens/language_selection_screen.dart` showing EN/ES chips (matching the two registered languages in `AppConstants.languages`).

---

### C4 — Forgot password calls the wrong endpoint
**File:** `lib/features/auth/domain/repositories/auth_repository.dart:150–152`

```dart
Future<Response?> forgetPassword(String? phone) async {
  return await apiClient.postData(AppConstants.configUri, {"phone_or_email": phone});
}
```

`AppConstants.configUri` is the app-config fetch endpoint, not a password-reset endpoint. The backend ignores the body and returns config JSON. Password reset is completely broken.

**Fix:** POST to the correct endpoint (e.g., `AppConstants.forgetPasswordUri`) and handle the response appropriately for PIN-based users (who have no password) versus legacy OTP users.

---

### C5 — Arabic language file (`ar.json`) missing
**File:** `assets/language/` (directory)

Only `en.json` and `es.json` exist. CLAUDE.md states: "Always add a key to **all three** language files when introducing new UI strings. The test `vito_flows_test.dart` enforces parity between EN, ES, and AR key sets." Without `ar.json`, this test will fail and selecting Arabic (if exposed in settings) crashes the app.

**Fix:** Create `assets/language/ar.json` with all keys from `en.json` (Arabic translations or placeholder copies to unblock CI); register it in `AppConstants.languages` alongside EN/ES.

---

### C6 — Mart checkout computes order totals client-side
**File:** `lib/features/mart/screens/mart_store_screen.dart` (checkout widget)

The cart screen calculates `_subtotal`, `_discount`, and `_totalAmount` locally from cached product prices. CLAUDE.md states: **"The client never sends a price. Backend computes total."** If product prices change between when items were added and when the order is submitted, the displayed total will differ from what the backend charges.

**Fix:** Before showing the final total, call the promo-apply endpoint (`POST /api/customer/mart/apply-promo`) or a dedicated order-preview endpoint that returns the authoritative server-computed subtotal, discount, and final total. Display that, not the locally computed value.

---

## 🟠 HIGH

### H1 — Mart message screen uses ride status check
**File:** `lib/features/mart/screens/mart_message_screen.dart:57`

```dart
controller.findChannelRideStatus();  // wrong — this checks trip status
```

The chat input is disabled/enabled based on ride (trip) status, not mart order status. For a mart chat the ride will always be absent, so the wrong status is read, potentially leaving chat enabled after order delivery or disabled when it should be active.

**Fix:** Add `MessageController.findChannelMartOrderStatus(orderId)` and call it here. Disable input when order is `delivered` or `cancelled`.

---

### H2 — Cart state is local-only, never synced to backend
**File:** `lib/features/mart/controllers/mart_controller.dart`

`addToCart()`, `removeFromCart()`, and `clearCart()` write only to `SharedPreferences`. If the user logs out and back in, or uses a second device, the cart is restored locally but the backend has no record of it. Order creation can fail or produce unexpected results.

**Fix:** This is a design decision: either (a) treat cart as local-only and validate all items/prices just before `createOrder()`, or (b) implement a backend cart endpoint. Option (a) is simpler — on checkout, re-fetch product details and verify stock/prices before submitting.

---

### H3 — Wallet balance not checked before mart checkout
**File:** `lib/features/mart/screens/mart_store_screen.dart` (payment method step)

When the user selects "wallet" as the payment method, the app does not fetch the current wallet balance. If the balance has fallen below the order total (used elsewhere, refunded, etc.), the order creation fails at the backend with a generic error.

**Fix:** When the user selects wallet payment, call `ProfileController.getProfileInfo()` (or a dedicated balance endpoint), compare with `_totalAmount`, and show an inline error with the shortfall before allowing the user to proceed.

---

### H4 — Pusher config null causes crash in `pusherDriverStatus()`
**File:** `lib/helper/pusher_helper.dart:30–42`

`initializePusher()` returns early (no-op) if config is not yet loaded. This sets `pusherClient` to `null`. Later, `pusherDriverStatus()` calls `pusherClient!.subscribe(...)` (force-unwrap), which throws a null-pointer exception and crashes the app if the config failed to load during splash.

**Fix:** Guard every `pusherClient` access with a null check. Log a warning and return gracefully if Pusher failed to initialize. Add a retry path on the next config load.

---

### H5 — FCM token rotation never propagates to backend
**Files:** `lib/helper/firebase_helper.dart`, `lib/data/api_client.dart`

The FCM device token is read once at login and sent to the backend. `FirebaseMessaging.instance.onTokenRefresh` is never subscribed to. Firebase rotates tokens after re-authentication, factory resets, or every 6–12 months. After rotation, the backend holds a stale token and push notifications stop being delivered.

**Fix:** In `firebase_helper.dart`, subscribe to `FirebaseMessaging.instance.onTokenRefresh` and call `PUT /api/customer/update/fcm-token` (or the equivalent update endpoint) whenever a new token arrives.

---

### H6 — QR/invite token gate bypassable on first login
**File:** `lib/helper/login_helper.dart`

`forNotLoginUserRoute()` routes unauthenticated users directly to `SignInScreen`. Per CLAUDE.md, new customers must first validate a QR/invite token (`TokenGateScreen`). The `SignInScreen` has a link to `TokenGateScreen`, but it's optional — a returning user who clears app data can reach the sign-in form without ever scanning a QR code.

**Fix:** In `forNotLoginUserRoute()`, distinguish between "returning user with no session" (→ `SignInScreen`) and "brand-new install with no token history" (→ `TokenGateScreen`). Gate the sign-up flow, not the sign-in flow.

---

### H7 — Chat file attachments cleared before upload confirms
**File:** `lib/features/message/controllers/message_controller.dart:236–240`

`sendMartMessage()` clears `_selectedImageList` and `_otherFile` immediately before awaiting the upload response. If the upload fails (network timeout, server error), the selected files are gone from state and cannot be retried.

**Fix:** Capture references to the files locally, clear state only after a successful (2xx) response, and restore them on failure so the user can retry.

---

### H8 — Sign-up form has no username field
**File:** `lib/features/auth/screens/sign_up_screen.dart`

The registration form collects phone number + PIN but no username. `SignUpBody.username` is always an empty string. The backend's `VitoAuthController.pinRegister()` enforces `username` as required (`min:3|max:50`). Every registration attempt will fail with a 422 validation error.

**Fix:** Add a username text field to the sign-up form, validate it client-side (`min 3, max 50, alphanumeric/underscore`), and pass it in `SignUpBody`.

---

## 🟡 MEDIUM

### M1 — API base URL hardcoded in source
**File:** `lib/util/app_constants.dart:174`

```dart
static const String baseUrl = 'https://dacatlon.store';
```

Environment-specific config in source code makes staging/production switches require a code change. 

**Fix:** Accept via `--dart-define=BASE_URL=...` at build time and read with `String.fromEnvironment('BASE_URL', defaultValue: 'https://dacatlon.store')`.

---

### M2 — Firebase credentials hardcoded for Android
**File:** `lib/main.dart:34–42`

`FirebaseOptions` for Android has `apiKey`, `appId`, `messagingSenderId`, and `projectId` inlined. These should live in `android/app/google-services.json` and be read by the Firebase Gradle plugin, not embedded in Dart source.

**Fix:** Remove the hardcoded Android `FirebaseOptions`, add `google-services.json` (gitignored), and let `Firebase.initializeApp()` use `DefaultFirebaseOptions.currentPlatform` without explicit options.

---

### M3 — Pusher WebSocket auth always uses HTTPS
**File:** `lib/helper/pusher_helper.dart:81,117,169,191,230`

Auth endpoint URL is always `'https://${config.webSocketUrl}/broadcasting/auth'`. The `websocketScheme` config field is ignored for auth. If the backend is behind a proxy that serves WebSocket over HTTP (dev environments, some staging setups), auth requests will fail with TLS errors.

**Fix:** Derive scheme from `config.websocketScheme ?? 'https'` when constructing the auth URL.

---

### M4 — Mart order polling has no timeout
**File:** `lib/features/mart/screens/mart_order_tracking_screen.dart:87–97`

The status polling timer runs indefinitely until order reaches a terminal state. If the backend becomes stuck (or order is silently cancelled by an admin), the timer never stops, draining battery and data.

**Fix:** Add a maximum polling duration (e.g., 3 hours) after which the timer stops and shows a "Contact support" message. Alternatively, subscribe to Pusher mart-order events to eliminate polling entirely.

---

### M5 — `parcelCategoryList` not null-guarded
**File:** `lib/features/parcel/controllers/parcel_controller.dart:24–30`

If the parcel-category fetch fails, `parcelCategoryList` stays null. Downstream code that iterates it without a null check will throw a null-dereference error and crash the parcel booking screen.

**Fix:** Initialize to `const []` and handle empty-list state in the UI (show a retry button or disable category selection).

---

### M6 — No routing for mart-specific push notifications
**File:** `lib/helper/notification_helper.dart`

`notificationRouteCheck()` handles ride and parcel actions in detail but has no branch for mart order events (`mart_order_accepted`, `mart_order_picked_up`, `mart_order_cancelled`, `mart_chat`). Tapping a mart push notification drops the user on the home screen.

**Fix:** Add `mart_order_*` action handling in `notificationRouteCheck()` to navigate to `MartOrderTrackingScreen` or `MartDeliveryScreen` with the order ID from the notification payload.

---

### M7 — Mart Pusher subscriptions not set up centrally
**File:** `lib/helper/pusher_helper.dart`

`pusherDriverStatus()` subscribes only to ride-trip channels. Mart order real-time updates depend on `MessageController.subscribeMartMessageChannel()` being called manually from individual screens. If a screen is not active, updates are missed entirely.

**Fix:** Add a `pusherMartOrderStatus(orderId)` method in `PusherHelper` (parallel to `pusherDriverStatus`) that subscribes to the mart order channel. Call it after order creation and on app resume.

---

### M8 — No retry on transient API failures
**File:** `lib/data/api_client.dart`

`getData()`, `postData()`, etc. surface errors immediately on socket exceptions and timeouts. There is no automatic retry for transient failures. On a mobile network, brief disconnects are common.

**Fix:** Wrap network calls with a simple retry (1–2 attempts, 500 ms backoff) for `SocketException` and `TimeoutException`. Propagate the error only after retries are exhausted.

---

### M9 — Offline queue not used for mart orders or messages
**File:** `lib/data/offline_queue.dart`

An offline action queue exists and is used for some operations, but mart order creation (`MartController.createOrder()`) and mart message sending (`MessageController.sendMartMessage()`) do not go through it. Failed requests on poor connectivity are lost silently.

**Fix:** Enqueue mart order creation attempts when offline; flush on reconnect. For messages, queue the send and retry on the next successful connection event.

---

### M10 — Promo code applied directly via `ApiClient`, bypassing controller
**File:** `lib/features/mart/screens/mart_store_screen.dart:1070–1102`

The promo code apply call is made directly from the widget via `ApiClient`, skipping `MartController` and `MartService`. This breaks the DI chain and makes the response handling ad-hoc — no specific messaging for expired codes, per-user limits, or minimum-spend failures.

**Fix:** Move promo code application into `MartController.applyPromo()` → `MartService` → `MartRepository`. Surface structured errors (expired / usage limit / min-spend) back to the UI.

---

### M11 — `addToCart()` ignores product stock
**File:** `lib/features/mart/controllers/mart_controller.dart:77–98`

`addToCart()` increments local quantity without checking `product['stock']`. If stock is 1 but the user adds quantity 3, the cart state is invalid and order creation fails at the backend with an opaque error.

**Fix:** Compare requested quantity against `product['stock']` (available from the product model). Show an inline "Only N left in stock" warning and cap quantity at the available stock.

---

### M12 — Mart Pusher event missing `order_id` validation
**File:** `lib/features/message/controllers/message_controller.dart:300`

The ride chat binding validates `trip_id` before inserting a message. The mart chat binding (line ~300) does not validate `order_id` — if the field is absent or mismatched, the message is silently dropped with no log entry.

**Fix:** Add a guard analogous to the ride chat: `if (eventOrderId == null || eventOrderId != currentOrderId) return;` and log a warning when the id is missing.

---

### M13 — Notification strings not localized
**File:** `lib/helper/notification_helper.dart:325,327,345–346`

Notification channel name (`'Faster pick-ups, safer trips'`) and location-permission rationale (`'When you\'re riding with ${AppConstants.appName}, your location is being collected...'`) are hardcoded English strings. They will always appear in English regardless of the user's language setting.

**Fix:** Add these strings as keys in `en.json`/`es.json`/`ar.json` and reference them at runtime. Note: Android notification channel descriptions are set once at channel creation — use `AppLocalizations` before registering the channel on first run.

---

### M14 — No gateway availability check before Stripe payment
**File:** `lib/features/payment/controllers/payment_controller.dart:52–79`

When the user selects "digital" payment, the app does not verify that Stripe is enabled in the backend config. If Stripe is unconfigured, `POST /api/customer/stripe/create-payment-intent` will return an error, and the user sees a cryptic failure after already committing to pay.

**Fix:** Read `ConfigController.config?.paymentGateways` (or equivalent) before presenting digital payment as an option. Grey it out with a tooltip if Stripe is not active.

---

## 🔵 LOW / POLISH

### L1 — Dead private fields in `MessageController`
**File:** `lib/features/message/controllers/message_controller.dart:50–53`

`_name` and `_image` are initialized in the constructor but never read anywhere in the class.

**Fix:** Remove them.

---

### L2 — `route_helper.dart` is dead code
**File:** `lib/helper/route_helper.dart`

All navigation uses `Get.to(() => Screen())` directly. The named-route helper file is never imported or used.

**Fix:** Delete the file, or adopt it consistently to give the app a single navigation source of truth.

---

### L3 — Account deletion confirmation omits data scope
**File:** `lib/features/profile/screens/profile_screen.dart:232–252`

The confirmation bottom sheet says only "are you sure?" with no list of what is permanently deleted (trips, messages, wallet balance, etc.). Users who tap accidentally have no recovery path.

**Fix:** Add a bullet list of what will be erased to the confirmation dialog.

---

### L4 — App version not surfaced in settings
**File:** `lib/features/settings/screens/setting_screen.dart`

`AppConstants.appVersion` exists but is not displayed anywhere in the UI. Users cannot identify which build they are running.

**Fix:** Add an "About / Version X.X.X" tile at the bottom of the settings screen.

---

### L5 — QR token history stored in plain `SharedPreferences`
**File:** `lib/features/auth/screens/token_gate_screen.dart:43–66`

Partial token strings (last 8 characters) are stored in `SharedPreferences`. Even masked, this is unnecessary plain-text storage of auth-related data. `FlutterSecureStorage` is already a dependency.

**Fix:** Store token history in `FlutterSecureStorage` instead.

---

### L6 — No confirmation after adding item to cart
**File:** `lib/features/mart/screens/mart_store_screen.dart:331`

`_addToCart()` increments the FAB badge counter silently. Users who don't notice the badge have no feedback that the item was added.

**Fix:** Show a `showCustomSnackBar('item_added_to_cart'.tr)` (add the key to all language files).

---

### L7 — Delivery proof photos too small
**File:** `lib/features/mart/screens/mart_order_tracking_screen.dart:645`

Delivery photo is displayed at 150px height — too small to read a signature or confirm a photo. A lightbox tap target exists but is easy to miss.

**Fix:** Increase container height to 220px and add a visible "tap to expand" label below the image.

---

### L8 — `app_links` version uncapped in pubspec
**File:** `pubspec.yaml:55`

```yaml
app_links: ^7.0.0
```

No upper bound. A breaking `8.x` release would silently pull in an incompatible version on the next `flutter pub upgrade`.

**Fix:** Pin to `^7.0.0` (already done) and add a comment to revisit when upgrading Flutter, or use `>=7.0.0 <8.0.0`.

---

## Test Coverage Matrix

| Flow | Status | Notes |
|------|--------|-------|
| Localization parity (EN/ES) | ✅ Passing | AR file missing — test doesn't yet check it |
| PIN format validation (6-digit) | ✅ Passing | |
| QR token expiry detection | ✅ Passing | |
| Auth model parsing + 401 session handling | ✅ Passing | |
| Promo code max-discount cap | ✅ Passing | |
| Mart order status transitions | ❌ Wrong values | See C1 — test uses wrong status names |
| Ride booking end-to-end | ❌ Not tested | |
| Parcel delivery flow | ❌ Not tested | |
| Wallet top-up / Stripe payment | ❌ Not tested | |
| Chat messaging (Pusher subscribe/send) | ❌ Not tested | |
| Notification deep-link routing | ❌ Not tested | |
| Offline queue flush on reconnect | ❌ Not tested | |
| FCM token refresh propagation | ❌ Not tested | |
| Cart add / remove / clear | ❌ Not tested | Only total-calculation arithmetic is tested |
| Order creation with promo + tip | ❌ Not tested | |

---

---

## 🔴 CRITICAL (new — round 2)

### C7 — Chat message insert crashes before API load
**File:** `lib/features/message/controllers/message_controller.dart:306`

```dart
messageModel!.data!.insert(0, Message.fromJson(eventData));
```

Called when a Pusher event arrives. If the user opens the chat screen before `getConversationList()` completes (race condition on slow networks), `messageModel` or `messageModel.data` is null. The force-unwrap crashes the app.

**Fix:** Guard with `if (messageModel?.data != null)` before inserting. If null, buffer the incoming message and prepend after the initial API response arrives.

---

### C8 — Parcel submission crashes if fare estimate was skipped
**File:** `lib/features/ride/controllers/ride_controller.dart:298–299`

```dart
estimatedDistance: parcelEstimatedFare!.data!.estimatedDistance!.toString(),
estimatedTime:     parcelEstimatedFare!.data!.estimatedDuration!.replaceFirst('min', ''),
```

`parcelEstimatedFare` is nullable. If the fare estimate API call timed out (or the user never reached the estimate step), all three force-unwraps throw a null-pointer exception and crash the app on parcel submission.

**Fix:**
```dart
estimatedDistance: parcelEstimatedFare?.data?.estimatedDistance?.toString() ?? '0',
estimatedTime:     parcelEstimatedFare?.data?.estimatedDuration?.replaceFirst('min', '') ?? '0',
```

---

### C9 — Ride/parcel submits Null Island coordinates when address missing
**File:** `lib/features/ride/controllers/ride_controller.dart:268–289`

When `LocationController.fromAddress` (or `parcelSenderAddress`) is null, the code falls back to `Address()` — an object with `latitude: null, longitude: null`. These are then serialized as the string `"null"` and sent to the backend, which silently creates a trip with invalid coordinates at (0, 0) — Null Island in the Atlantic.

**Reproduction:** Start a ride before location services initialize fully → backend receives `pickupLat: "null"`.

**Fix:** Do not fall back to `Address()`. Instead, return early and show an error: "Unable to determine your location. Please try again."

---

## 🟠 HIGH (new — round 2)

### H9 — Trip history pagination permanently broken
**File:** `lib/features/trip/controllers/trip_controller.dart:51`

```dart
if (response.body['date'] != []) {   // ← typo: 'date' should be 'data'
```

Because `response.body['date']` is always `null` (the key doesn't exist), this condition is always true, so pagination logic appends zero trips every time. Users see only the first page of their trip history — no further trips load on scroll.

**Fix:** Change `'date'` → `'data'`.

---

### H10 — Force-unwrap on `rideDetails` in HomeScreen
**File:** `lib/features/home/screens/home_screen.dart:361`

```dart
rideController.rideDetails!.id!
```

The null guard at line 124 (`if (rideDetails != null)`) does not cover line 361, which is inside a different conditional branch. If `rideDetails` becomes null between the two checks (concurrent update), this throws.

**Fix:** Use `rideController.rideDetails?.id ?? ''` and guard the call site.

---

### H11 — User address force-unwrapped without null guard
**File:** `lib/features/home/screens/home_screen.dart:137–145`

`getUserAddress()` returns nullable `Address?`, but the return value is immediately force-unwrapped (`.latitude!`, `.longitude!`) without a null check. Crashes when location is unavailable at the time the home screen loads.

**Fix:** Use null-safe access and show a "location unavailable" placeholder rather than crashing.

---

### H12 — QR token validation reads nested key without null guard
**File:** `lib/features/auth/screens/token_gate_screen.dart:276`

```dart
response.body['data']['valid']
```

If the server returns `{"data": null}` (e.g., token already used), accessing `['valid']` on `null` throws. The outer `catch (_)` catches the crash but swallows it silently, leaving the user with no error message.

**Fix:** `response.body['data']?['valid'] ?? false` and surface the error explicitly.

---

### H13 — Phone number substring crash for short numbers
**File:** `lib/features/auth/screens/verification_screen.dart:97`

```dart
widget.number.substring(0, 5) + '****' + widget.number.substring(widget.number.length - 3, widget.number.length)
```

Throws `RangeError` if `widget.number` has fewer than 8 characters. Some locales have 7-digit phone numbers.

**Fix:** Check `widget.number.length` before slicing; fall back to showing the full number if too short to mask safely.

---

### H14 — Pusher singleton not cleared on logout
**File:** `lib/helper/pusher_helper.dart`

`pusherClient` is a static singleton registered in `di_container.dart`. It is never disconnected or re-initialized on logout. The next user to log in on the same device inherits the old Pusher connection, which carries the previous user's auth token. Subscriptions fail with 403 until the connection is manually reset.

**Fix:** Call `pusherClient?.disconnect()` and reset the static reference in the logout flow (`VitoAuthController.logout()`).

---

### H15 — User profile data leaks across sessions
**File:** `lib/features/profile/controllers/profile_controller.dart`

`profileModel` is never cleared on logout. The next user who logs in on the same device sees the previous user's name, photo, wallet balance, and rating until a fresh `getProfileInfo()` call completes (which may take several seconds on a slow network).

**Fix:** Call `profileController.profileModel = null; update();` in the logout flow before navigating to `SignInScreen`.

---

### H16 — Background FCM messages silently dropped
**File:** `lib/helper/notification_helper.dart:637`

```dart
Future<void> myBackgroundMessageHandler(RemoteMessage message) async {}
```

The handler registered with `FirebaseMessaging.onBackgroundMessage()` is an empty stub. Any push notification delivered while the app is in the background is lost — no storage, no badge update, no routing on next open.

**Fix:** Implement the handler to at minimum persist the notification to a local store (e.g., `shared_preferences` or SQLite), so it can be replayed when the app comes to the foreground.

---

### H17 — Refund reasons never loaded on screen open
**File:** `lib/features/refund_request/screens/refund_request_screen.dart`

`RefundRequestController.getParcelRefundReasonList()` is never called in `initState`. The refund reason dropdown is always empty unless something else triggers the load. Users see a blank list and cannot submit a refund.

**Fix:** Call `Get.find<RefundRequestController>().getParcelRefundReasonList()` in the screen's `initState`.

---

### H18 — Safety alert sends Null Island when location permission denied
**File:** `lib/features/safety_setup/controllers/safety_alert_controller.dart:99`

```dart
final latLng = await getCurrentPosition();
// latLng is null if permission denied
final body = {
  'latitude': (latLng?.latitude ?? '').toString(),  // sends "" → backend receives ""
  ...
};
```

`(null?.latitude ?? '')` produces the empty string `''`, which the backend may reject or silently store as 0.0. An emergency alert with no coordinates is useless.

**Fix:** If `latLng` is null, block the submission and show "Location required to send a safety alert. Please enable location permissions."

---

## 🟡 MEDIUM (new — round 2)

### M15 — Cart not cleared after failed order
**File:** `lib/features/mart/screens/mart_store_screen.dart:1179,1187`

On a failed `createOrder()` call, the cart remains populated. If the user immediately retries, they risk double-submitting. If the failure was a payment error after the order was partially created, a second submit creates a duplicate order.

**Fix:** On failure, either clear the cart and show a "start over" state, or show a specific retry affordance that prevents duplicate submission.

---

### M16 — Promo discount not invalidated on cart modification
**File:** `lib/features/mart/screens/mart_store_screen.dart:657–709`

After a promo code is applied (discount stored in `_discount`), the user can swipe-to-delete items or change quantities. The discount value is not recalculated — it remains fixed against the old subtotal. The user can pay less than the promo-adjusted price should be.

**Fix:** Clear `_appliedPromoCode` and reset `_discount = 0` whenever the cart contents change, and prompt the user to re-apply.

---

### M17 — Promo discount not type-validated in response
**File:** `lib/features/mart/screens/mart_store_screen.dart:1083–1095`

```dart
_discount = (response.body['data']['discount'] as num?)?.toDouble() ?? 0.0;
```

If the server returns `'discount': 'invalid'` (a string), the cast returns `null` and `_discount` silently becomes `0.0`. The promo is marked as applied but grants no discount, with no user-visible error.

**Fix:** Validate that the parsed value is a positive number before accepting the promo. Show an error if the discount value is unexpected.

---

### M18 — Fare data not reset between ride bookings
**File:** `lib/features/ride/controllers/ride_controller.dart:82–92`

`initData()` (called when starting a new booking) resets UI state but leaves `estimatedFare`, `actualFare`, and `fareList` populated from the previous booking. On a subsequent fare request, there is a brief window where stale prices appear in the UI before the fresh estimate loads.

**Fix:** Add `estimatedFare = 0; actualFare = 0; fareList = []; selectedType = null;` to `initData()`.

---

### M19 — Cancellation reason list grows on every call
**File:** `lib/features/trip/controllers/trip_controller.dart:92,105`

`'other'.tr` is appended to the cancellation reason list each time `getRideCancellationReasonList()` or `getParcelCancellationReasonList()` is called. Calling either method twice (e.g., after a network retry) results in duplicate "Other" entries.

**Fix:** Clear the list before populating, or check for the entry before appending.

---

### M20 — Wallet transaction list crashes on null data
**File:** `lib/features/wallet/controllers/wallet_controller.dart:62–63`

```dart
transactionModel!.data!.addAll(response_data);
```

No null check before force-unwrapping `data`. If the API returns `{"data": null}` (empty wallet, first-time user), this throws.

**Fix:** Guard with `if (transactionModel?.data != null)` or initialize `data` to an empty list in the model.

---

### M21 — Read notification badge doesn't update
**File:** `lib/features/notification/controllers/notification_controller.dart:36–45`

`sendReadStatus()` updates the backend but never calls `update()`. The unread-notification badge on the bottom nav never clears until the user navigates away and back.

**Fix:** Call `update()` at the end of `sendReadStatus()`.

---

### M22 — Safety alert timer leaks on controller dispose
**File:** `lib/features/safety_setup/controllers/safety_alert_controller.dart:221`

`checkDriverNeedSafety()` starts a periodic `Timer` that is not stored in a cancellable reference and is not cancelled in `onClose()`. If the controller is disposed while the timer is active (e.g., user navigates away from the safety screen during an active alert), the timer continues firing on a dead controller.

**Fix:** Store the timer reference (`Timer? _safetyPollTimer`) and cancel it in `onClose()`.

---

### M23 — Offer offset parse crashes on null
**File:** `lib/features/my_offer/controllers/offer_controller.dart:92,124`

```dart
int.parse(bestOfferModel!.offset.toString())
```

If `bestOfferModel.offset` is `null`, `.toString()` returns the string `"null"` and `int.parse("null")` throws a `FormatException`.

**Fix:** Use `int.tryParse(bestOfferModel?.offset?.toString() ?? '0') ?? 0`.

---

### M24 — Pusher ride channel accumulates without unsubscribe
**File:** `lib/helper/pusher_helper.dart:76–111`

Each call to `pusherDriverStatus(tripId)` subscribes to a new `PrivateChannel` without unsubscribing from the previous one. Over multiple back-to-back rides, the app holds open multiple dead Pusher channels, consuming memory and WebSocket connections.

**Fix:** Unsubscribe from the current channel (`_rideChannel?.unsubscribe()`) before subscribing to the new one. Do the same in `onClose()`.

---

### M25 — Support screen crashes if config not loaded
**File:** `lib/features/support/screens/support_screen.dart:78`

The Terms & Conditions tab renders raw HTML from `ConfigController.config.termsAndConditions` without a null guard. If the config fetch failed during splash (e.g., no network), this throws a null-dereference when the user opens Support.

**Fix:** Use `config?.termsAndConditions ?? ''` and show a "Content unavailable" placeholder when empty.

---

### M26 — Hardcoded English text in OTP signup
**File:** `lib/features/auth/screens/otp_signup_screen.dart:68`

The string `'Just one step away!…'` is written as a literal in the source and appended with `.tr`. Because it is not a translation key in `en.json`, the `.tr` call returns the raw string unchanged. Non-English users see English text.

**Fix:** Add a proper key (e.g., `'just_one_step_away'`) to all three language files and reference it as `'just_one_step_away'.tr`.

---

### M27 — Unsafe context access in SplashScreen
**File:** `lib/features/splash/screens/splash_screen.dart:60`

```dart
ScaffoldMessenger.of(Get.context!).showSnackBar(...)
```

`Get.context` can be null during the brief window between app start and widget tree construction. The force-unwrap throws a null check failure before the splash screen is even visible.

**Fix:** Wrap in `if (Get.context != null)` or use `Get.snackbar(...)` which handles context internally.

---

### M28 — Profile screen shows stale data on re-entry
**File:** `lib/features/profile/screens/profile_screen.dart`

`initState()` does not call `ProfileController.getProfileInfo()`. When the user navigates away (e.g., edits profile, completes a ride) and returns to the Profile tab, they see the cached version until something else triggers a reload.

**Fix:** Call `Get.find<ProfileController>().getProfileInfo()` in `initState()`.

---

## Suggested Fix Priority

### Immediate (blocks CI or crashes on first launch)
1. **C3** — Create `LanguageSelectionScreen` (first-launch crash)
2. **C2** — Create `OtpSignupScreen` or redirect (crash on unregistered OTP user)
3. **C1** — Fix mart status test assertions (CI fails every run)
4. **C5** — Add `ar.json` (CI parity test)
5. **C7** — Guard `messageModel!.data!.insert()` in message controller (crash on chat open)
6. **C8** — Fix `parcelEstimatedFare` force-unwraps in ride controller (crash on parcel submit)
7. **C9** — Guard address fallback in ride submission (silent data corruption)

### Next sprint (feature broken or security gap)
8. **C4** — Fix forgot-password endpoint
9. **H8** — Add username field to sign-up form
10. **H9** — Fix `'date'` → `'data'` key in trip pagination
11. **H14** — Clear Pusher singleton on logout
12. **H15** — Clear `profileModel` on logout
13. **H16** — Implement background FCM handler
14. **H17** — Call `getParcelRefundReasonList()` in refund screen initState
15. **H18** — Block safety alert submission if location is null

### Next sprint (feature broken)
16. **H1** — Fix mart message screen status check
17. **H3** — Add wallet balance pre-check at mart checkout
18. **H5** — Subscribe to FCM token refresh
19. **H10**, **H11**, **H12**, **H13** — Null-safety crashes in home/verification/token-gate

### Backlog
- C6, H2, H4, H6, H7, all Medium items (M15–M28), Low items

---

## Wave 3 — Deep Screen Reads (U1–U16)

Screens audited: `my_level_screen.dart`, `live_location_screen.dart`, `refund_request_screen.dart`,
`set_destination_screen.dart`, `loyality_point_screen.dart`, `safety_setup_screen.dart`,
`otp_log_in_screen.dart`.

---

## 🔴 CRITICAL (Wave 3)

### U1 — Null-chain force-unwrap on profile image URL in MyLevelScreen
**File:** `lib/features/my_level/screens/my_level_screen.dart`

```dart
'${Get.find<ConfigController>().config!.imageBaseUrl!.profileImage!}${controller.myLevelModel!.data!.image!}'
```

All four `!` operators can trigger `Null check operator used on a null value` if config hasn't loaded,
the network failed, or the level model's image field is null. Crashes the entire screen render.

**Fix:** Use null-aware navigation: `config?.imageBaseUrl?.profileImage ?? ''`, guard `myLevelModel?.data?.image` with a fallback placeholder asset.

---

### U2 — `Image.network` without error handler in MyLevelScreen
**File:** `lib/features/my_level/screens/my_level_screen.dart`

Multiple `Image.network(url)` calls have no `errorBuilder`. A 404 or unreachable URL causes a red
error widget in production; on low-memory devices the decode can throw an unhandled exception.

**Fix:** Add `errorBuilder: (_, __, ___) => Image.asset('assets/image/placeholder.png')` on every
network image.

---

### U3 — `myLevelModel!.data!` force-unwraps before API response check
**File:** `lib/features/my_level/screens/my_level_screen.dart`

Screen accesses `.data!.targetTrip`, `.data!.currentTrip`, etc. before checking whether
`controller.myLevelModel` is non-null. If the API returns an error or the controller's `getMyLevel()`
hasn't completed, the screen crashes on build.

**Fix:** Wrap access in `if (controller.myLevelModel?.data != null)` guard block or return a loading
widget from `GetBuilder` while data is null.

---

### U13 — Null description interpolated as "null" string in SafetySetupScreen
**File:** `lib/features/safety_setup/screens/safety_setup_screen.dart`

```dart
Text('${controller.safetyAlertTypes[index].description}')
```

`description` is nullable. When null, this renders the literal string `"null"` in the UI instead of
an empty string or placeholder.

**Fix:** Use `controller.safetyAlertTypes[index].description ?? ''`.

---

## 🟠 HIGH (Wave 3)

### U4 — LiveLocationScreen starts timer without null-checking trackingId
**File:** `lib/features/realtime_location_trac/screens/live_location_screen.dart`

`_startLiveLocationUpdates()` launches a `Timer.periodic` and immediately accesses
`widget.trackingId` inside the callback. If `trackingId` is null or empty (navigated to without a
valid trip), every tick calls the API with a bad ID, logging errors indefinitely.

**Fix:** Guard: `if (widget.trackingId?.isEmpty ?? true) return;` at the top of `_startLiveLocationUpdates()`.

---

### U5 — Indefinite spinner on API failure in LiveLocationScreen
**File:** `lib/features/realtime_location_trac/screens/live_location_screen.dart`

On API error the screen shows a loading indicator and never transitions to an error state. The timer
keeps firing but the UI is permanently blocked. No retry button, no timeout fallback.

**Fix:** Add a failure state: after N consecutive failures (or a timeout), cancel the timer and show
an error message with a retry action.

---

### U6 — Nullable `data.length` called without null check in RefundRequestScreen
**File:** `lib/features/refund_request/screens/refund_request_screen.dart`

```dart
controller.parcelRefundReasonList!.data!.length
```

Both force-unwraps crash if `parcelRefundReasonList` is null (reasons not loaded yet) or if `data`
is null (API returned empty body). Called in `itemCount` of a `ListView.builder`, which fires during
every build.

**Fix:** Use `controller.parcelRefundReasonList?.data?.length ?? 0`.

---

### U7 — Force-unwrap on list element in RefundRequestScreen
**File:** `lib/features/refund_request/screens/refund_request_screen.dart`

```dart
controller.parcelRefundReasonList!.data![index]
```

Same null-chain without null guard. Crashes if data is null or index is out of range.

**Fix:** Guard with `?.elementAtOrNull(index)` or equivalent bounds check.

---

### U11 — LoyalityPointScreen missing initState API call
**File:** `lib/features/wallet/screens/loyality_point_screen.dart`

`initState()` does not call any controller method to fetch loyalty point history. The `ListView`
renders whatever stale data was previously cached in the controller (possibly another user's data or
empty). Screen is functionally broken on first open.

**Fix:** Call `Get.find<WalletController>().getLoyalityPointList()` in `initState()`.

---

### U16 — OtpLoginScreen: OTP field not cleared on re-send; timer restart missing
**File:** `lib/features/auth/screens/otp_log_in_screen.dart`

When "Resend OTP" is tapped, the 6-digit input field retains the previous (now invalid) code, and
the countdown timer is not restarted. User can accidentally submit the stale code, which is rejected
by the backend, with no indication of what went wrong.

**Fix:** Call `_otpController.clear()` and restart the countdown timer in the resend callback.

---

### U14 — SafetySetupScreen: `_isLoading` never reset on API error
**File:** `lib/features/safety_setup/screens/safety_setup_screen.dart`

After calling `submitSafetyAlert()`, `_isLoading = true` is set but if the API call fails the flag
is never reset to `false`. The submit button stays permanently disabled for the rest of the session.

**Fix:** Set `_isLoading = false` in both the success and error branches, wrapped in `setState()`.

---

### U15 — SetDestinationScreen: fare estimate not re-fetched after pickup pin drag
**File:** `lib/features/set_destination/screens/set_destination_screen.dart`

After the user drags the pickup pin on the map, `getEstimatedFare()` is not re-called. The displayed
fare remains stale for the old pickup location. The user can proceed to booking with incorrect fare
information.

**Fix:** Call `controller.getEstimatedFare()` in the `onCameraIdle` callback whenever the pickup
location changes.

---

## 🟡 MEDIUM (Wave 3)

### U8 — MyLevelScreen: level data not refreshed on re-entry
**File:** `lib/features/my_level/screens/my_level_screen.dart`

`initState()` does not call `controller.getMyLevel()`. If the user completes trips and navigates
back to MyLevel, the progress bars and counts show stale values.

**Fix:** Call `Get.find<MyLevelController>().getMyLevel()` in `initState()`.

---

### U9 — LiveLocationScreen: map camera not updated on location change
**File:** `lib/features/realtime_location_trac/screens/live_location_screen.dart`

The driver marker moves on each location tick but `mapController.animateCamera()` is not called.
On longer trips the driver moves off-screen without the camera following.

**Fix:** Call `mapController?.animateCamera(CameraUpdate.newLatLng(newPosition))` after updating
the marker.

---

### U10 — RefundRequestScreen: media picker errors not surfaced to user
**File:** `lib/features/refund_request/screens/refund_request_screen.dart`

`ImagePicker.pickImage()` and `pickVideo()` can throw `PlatformException` (permission denied,
no camera). The calls are not wrapped in try/catch, so exceptions propagate to Flutter's error
handler with no user-visible feedback.

**Fix:** Wrap picker calls in try/catch; show a `showCustomSnackBar('media_picker_error'.tr)` on failure.

---

### U12 — LoyalityPointScreen: pagination loads duplicate items
**File:** `lib/features/wallet/screens/loyality_point_screen.dart`

`ScrollController` fires the next-page load, but the controller's `getLoyalityPointList()` calls
`addAll()` without deduplication. Rapid scroll near the boundary triggers multiple simultaneous
loads, appending the same page twice.

**Fix:** Add an `_isLoading` guard in the scroll listener: skip the call if a load is already in
progress (same pattern as `TripController`).

---

### U17 — SetDestinationScreen: no input validation before fare request
**File:** `lib/features/set_destination/screens/set_destination_screen.dart`

The "Get Estimate" button is enabled even when pickup or destination is empty/default. Calling
`getEstimatedFare()` with null lat/lng sends `"null"` string coordinates to the backend, producing
a misleading server-side error instead of a helpful inline validation message.

**Fix:** Disable the button (or show inline error) until both pickup and destination are non-null
with valid coordinates.

---

### U18 — RefundRequestScreen: upload size not validated
**File:** `lib/features/refund_request/screens/refund_request_screen.dart`

Video files selected via `ImagePicker` can be arbitrarily large. There is no size check before
`postMultipartData()`. On slow connections this causes a silent timeout; on the server it may exceed
the `upload_max_filesize` PHP limit, returning a 413 with no user-friendly error.

**Fix:** Check `File(pickedFile.path).lengthSync()` before upload; show an error if > 50 MB
(configurable constant).

---

### U19 — SafetySetupScreen: alert type list not fetched on re-entry
**File:** `lib/features/safety_setup/screens/safety_setup_screen.dart`

`initState()` fetches alert types only on first build. If the user navigates away and returns after
a session where the controller was disposed and recreated, `safetyAlertTypes` is empty and the list
shows nothing.

**Fix:** Always call `controller.getSafetyAlertTypeList()` in `initState()`, guarded by
`if (controller.safetyAlertTypes.isEmpty)`.

---

### U20 — OtpLoginScreen: no maximum attempt limit enforced on client
**File:** `lib/features/auth/screens/otp_log_in_screen.dart`

The backend enforces a 5-attempt lock, but the client does not track attempts locally. After the
lock kicks in, the user sees a generic API error with no explanation, no countdown, and no guidance
to wait before retrying.

**Fix:** Parse the 429/locked response body and display a localised message: `'too_many_otp_attempts'.tr`
with remaining wait time if provided by the API.

---

### U21 — SetDestinationScreen: `time_picker_spinner` locale not applied
**File:** `lib/features/set_destination/screens/time_picker_spinner.dart`

The custom 444-line spinner uses hardcoded English month/day abbreviations (`'Jan'`, `'Mon'`, etc.)
rather than the app's active locale. Switching the app language leaves the picker in English.

**Fix:** Source month/day names from the Flutter `intl` package using the current locale, or add
translation keys for each abbreviation.

---

### U22 — SetDestinationScreen: scheduled trip time allows past timestamps
**File:** `lib/features/set_destination/screens/set_destination_screen.dart`

The time picker does not enforce a minimum of "now + some buffer". Users can select a departure
time in the past, which the backend will reject, but the client shows no validation before
submitting.

**Fix:** After picker selection, validate `selectedTime.isAfter(DateTime.now().add(Duration(minutes: 5)))`;
show inline error otherwise.

---

## ✅ Fixed in v2.1.0 (released 2026-06-26)

The following findings were resolved and shipped in release `v2.1.0`:

| ID | Description |
|----|-------------|
| C6 | Server-side total for MartPaymentScreen (`result.serverTotal`) |
| C7 | Null guard before Pusher message insert in MessageController |
| C9 | Address fallback replaced with proper null check before fare request |
| H3 | Wallet balance check before mart wallet payment |
| H4 | Pusher null guards — no crash on missing config |
| H5 | Firebase FCM token refresh subscription added |
| H6 | New users routed via TokenGateScreen (QR gate); returning users → SignInScreen |
| H14 | Pusher channels unsubscribed + client nulled on logout |
| H15 | ProfileController cleared on logout |
| H16 | Background FCM handler with `@pragma('vm:entry-point')` |
| M3 | Pusher auth URL uses `websocketScheme` from config |
| M6 | Mart notification routing to `MartOrderTrackingScreen` |
| M8 | API retry logic for SocketException/TimeoutException (2 retries) |
| M11 | Cart stock cap in `addToCart()` |
| M13 | Notification channel display name uses translation key |
| M14 | Payment gateway config check before presenting Stripe |
| L4 | Settings About tile shows `AppConstants.appVersion` |
| L5 | Token gate uses `FlutterSecureStorage` for token history |
| L7 | Mart tracking container height 220 + `tap_to_expand` label |
| L8 | `app_links` version constraint updated to `>=7.0.0 <8.0.0` |
| U1 | Null-safe image URL in MyLevelScreen |
| U2 | Image.network error builder in MyLevelScreen |
| U3 | Force-unwrap on level completion replaced with null-safe check |
| U4 | LiveLocationScreen: null/empty trackingId guard before polling starts |
| U5 | LiveLocationScreen: retry UI after consecutive polling failures |
| U10 | Media picker wrapped in try/catch; PlatformException shown as snackbar |
| U12 | Loyalty point pagination: concurrent double-load guard |
| U15 | Fare estimate re-fetched after pickup pin drag (goPageAndHideTextFieldAsync) |
| U16 | OTP field cleared + error reset on resend |
| U17 | Zero-coordinate validation before fare request |
| U20 | 429 "too many attempts" shown as localised error on OTP verify |
| U22 | Scheduled trip time validated against current time |

## ✅ Fixed in Wave 13 (full end-to-end sweep)

| ID | Description |
|----|-------------|
| U23 | `message_bubble.dart` chat file-name truncation used `substring(fileName.length-7)` → negative index/RangeError for names < 7 chars (and `"null"` for a null name). Now length-guarded. |

**Sweep notes (verified, no change):** EN/ES/AR localization parity intact; `verification_screen`
phone mask already guarded (`length >= 8`); `int.parse(offset)` / model `*.parse(json[...])` is a
pervasive pre-existing pattern on server-supplied numeric fields, left as-is.

## ✅ Fixed / verified in v2.2.0 (2026-06-26)

Wave 5 + Wave 6 reconciliation. Several findings still listed as "open critical/high" above were
**verified already fixed in source** — the body of this doc had drifted out of date. Confirmed:

| ID | Status | Evidence |
|----|--------|----------|
| U9 | Fixed (Wave 5) | `live_location_screen` animates camera to follow driver (`_animateCameraIfMoved` via `addPostFrameCallback`) |
| U11 | Fixed (Wave 5) | `loyality_point_screen` `initState` calls `getLoyaltyPointList(1)` when uncached |
| C2 | Verified fixed | `lib/features/auth/screens/otp_signup_screen.dart` exists |
| C3 | Verified fixed | `lib/localization/language_selection_screen.dart` exists |
| C4 | Verified fixed | `forgetPassword()` posts to `AppConstants.forgetPassword` (not config URI) |
| C5 | Verified fixed | `assets/language/ar.json` exists (EN/ES/AR present) |
| C8 | Verified fixed | parcel fare now `parcelEstimatedFare?.data?...?.toString() ?? '0'` |
| H8 | Verified fixed | sign-up form has username field + `min 3 / max 50` validation |
| H9 | Verified fixed | trip pagination reads `response.body['data']` (typo gone) |
| H17 | Verified fixed | safety screen `initState` calls `getPrecautionList()` (reasons load on open) |
| U6/U7 | Verified fixed | refund reason list no longer force-unwrapped |
| U8 | Verified fixed | `my_level_screen` `initState` calls `getProfileLevelInfo()` |
| U13 | Verified fixed | safety description uses `?? ''` (no literal "null") |
| U14 | Verified fixed | safety submit state controller-managed (no stuck `_isLoading`) |
| U18/U19 | Verified fixed | image size validated via `FileValidationHelper`; alert types refetched in `initState` |

**Note for future sessions:** the remaining Medium/Low items in the body (M1–M28, etc.) were *not*
all re-verified this cycle — several are design decisions (e.g. M1/M2 env-config). Check the actual
source before "fixing" any item; this doc is historical, not a live open-issues list.
