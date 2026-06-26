# Vito Driver App — End-to-End Gap Audit

> **Scope:** Flutter driver app (`drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/`)  
> **Method:** Full static analysis of all 55 screens, 24 feature controllers, and helper utilities.  
> For cross-cutting backend issues see `AUDIT.md`; for customer app see `USER_APP_AUDIT.md`.

---

## Executive Summary

| Severity | Count |
|----------|-------|
| 🔴 CRITICAL — crash, data integrity, or auth bypass | 2 |
| 🟠 HIGH — feature broken, silent failure, or session leak | 9 |
| 🟡 MEDIUM — degraded UX or error-prone edge case | 19 |
| 🔵 LOW — polish, dead code, minor inconsistency | 4 |
| **Total** | **34** |

---

## 🔴 CRITICAL

### D1 — MartDeliveryScreen bypasses GetX architecture entirely
**File:** `lib/features/mart/screens/mart_delivery_screen.dart:20–27`

`MartDeliveryScreen` is a raw `StatefulWidget` with all business logic inline. It calls `Get.find<ApiClient>()` directly (bypassing Repository → Service → Controller layers), holds mutable local state for photo, signature, offline flag, and order status, and has no corresponding controller registered in `di_container.dart`. This makes the screen untestable, breaks the codebase's stated architecture, and means state is silently lost if the OS kills the app mid-delivery.

**Fix:** Create `MartDeliveryController extends GetxController` with all API calls and state; register it in `di_container.dart`; refactor `MartDeliveryScreen` to `GetBuilder<MartDeliveryController>`.

---

### D2 — OTP auth path bypasses QR/invite gate for driver registration
**File:** `lib/features/auth/screens/sign_in_screen.dart` (OTP flow entry)

The `ClientOtpAuthController` path (`send-otp → verify → register-from-otp`) does not require a QR invite token before creating a driver account. A bad actor can self-register as a driver with no approval, bypassing the admin-gated QR registration flow. Mirrors USER_APP_AUDIT H6 on the customer app.

**Fix:** Backend must enforce `require_invite_token` check in `ClientOtpAuthController::registerFromOtp()` for driver scope, or remove the OTP path entirely from the driver app if PIN+QR is the sole intended flow.

---

## 🟠 HIGH

### D3 — Delivery proof lost on network failure between upload and status update
**File:** `lib/features/mart/screens/mart_delivery_screen.dart:536–562`

After `_deliveryProofUploaded = true` is set (proof uploaded to server), a subsequent `_submitDeliveredStatus()` call may fail. The proof is orphaned on the server but the order never transitions to `delivered`. The driver sees an unresponsive retry button with no clear guidance. No idempotent re-try key is stored, so the driver cannot prove they delivered.

**Fix:** Persist the proof-upload completion flag to SharedPreferences/Hive. On retry, skip re-uploading and only call the status update. Add idempotency key to the status update call.

---

### D4 — Silent location failure during delivery proof
**File:** `lib/features/mart/screens/mart_delivery_screen.dart:601–609`

Code uses `Get.find<dynamic>()` to retrieve location — an unsafe cast that throws if no matching type is registered. Wrapped in a bare `try/catch` that swallows the exception and continues without logging. If location is unavailable, delivery coordinates default to 0.0 without any user warning.

**Fix:** Use the typed controller (`Get.find<LocationController>()`) and explicitly handle null GPS coordinates by blocking proof submission and showing a location-required prompt.

---

### D5 — Pusher channels not unsubscribed on logout
**File:** `lib/helper/pusher_helper.dart:66–114`

`driverTripSubscribe()`, `customerInitialTripCancelChannel`, and related channels are subscribed during a trip but never explicitly unsubscribed when the driver logs out. Only `pusherClient.disconnect()` is called (via `AuthController`), but active `PrivateChannel` objects remain in subscribed state. On the next login, stale channels can fire events belonging to the previous session.

**Fix:** Call `channel.unsubscribe()` on each active channel in `pusherDisconnectPusher()` before calling `pusherClient!.disconnect()`.

---

### D6 — Large identity photo uploaded without size validation
**File:** `lib/features/auth/screens/additional_sign_up_screen_2.dart:381–434`

Identity/vehicle photos are selected by the driver and passed directly to the multipart upload without any pre-flight file size check. A 50 MB camera image will be encoded into memory in full, potentially OOM-crashing the app on low-RAM devices during driver registration.

**Fix:** Validate file size (e.g., max 5 MB) and dimensions immediately after `pickImage()`. Resize or reject oversized files before encoding.

---

### D7 — Profile controller not cleared on logout; data leaks to next session
**File:** `lib/features/profile/screens/profile_screen.dart` + `AuthController` logout path

`ProfileController.profileModel` and driver vehicle data are never nulled out on logout. If a second driver logs in on the same device, they briefly see the previous driver's name, photo, earnings, and vehicle info until the first `getProfileInfo()` response arrives. Mirrors USER_APP_AUDIT H15.

**Fix:** In the logout flow (before `Get.offAll(() => SignInScreen())`), call `Get.find<ProfileController>().profileModel = null; update();` and clear cached vehicle state.

---

### D8 — Background FCM messages silently dropped
**File:** `lib/helper/notification_helper.dart`

Like the customer app (USER_APP_AUDIT H16), the driver app's background FCM handler is an empty stub. New ride/parcel/mart requests delivered while the app is backgrounded are lost — the driver sees no badge, no sound, no routing on re-open.

**Fix:** Implement `myBackgroundMessageHandler` to persist the message payload to local storage and show a local notification so the driver is alerted regardless of app state.

---

### D9 — Ride acceptance has no double-accept guard
**File:** `lib/features/map/screens/map_screen.dart` + `MapController`

A driver can tap "Accept" multiple times before the first API response arrives. No button debounce, no in-flight flag, and no idempotency key on the acceptance POST. If the network is slow, two acceptance requests fire, potentially causing a backend race condition where two drivers accept the same trip.

**Fix:** Set a local `_isAccepting` flag on first tap, disable the button, and clear it only on API response (success or error). Matches the pattern from `VitoTripController` which already uses DB-level `lockForUpdate` — the app must not rely solely on backend dedup.

---

### D10 — Online/offline toggle state not persisted across app restart
**File:** `lib/features/home/screens/home_screen.dart:62–95`

The driver's online/offline state is held in-memory by `HomeController`. If the app is killed (by OS or user), the driver comes back online by default on next launch regardless of their last chosen state. Drivers who want to stay offline must manually toggle again every restart.

**Fix:** Persist the online state to SharedPreferences in the toggle method and restore it in `initState` before the first API call.

---

### D11 — `_fetchOrderDetails()` in MartDeliveryScreen has no request deduplication
**File:** `lib/features/mart/screens/mart_delivery_screen.dart:55–76`

`_fetchOrderDetails()` is called on pull-to-refresh and on screen init. No in-flight guard exists; rapid pulls will fire multiple concurrent GET requests. If the first response arrives after the second, stale data is displayed.

**Fix:** Add an `_isFetching` bool; early-return if already in flight.

---

## 🟡 MEDIUM

### D12 — No error handling in home screen load sequence
**File:** `lib/features/home/screens/home_screen.dart:98–123`

`loadData()` fires multiple `async` calls (`getCategoryList`, `getProfileInfo`, `getDailyLog`, `getLastRideDetail`, etc.) without `await` or error callbacks. If any call fails, downstream logic may read null state (e.g., Pusher subscription fires without valid trip ID).

**Fix:** `await` each call in sequence (or use `Future.wait`) and catch errors individually; show a retry affordance if critical calls fail.

---

### D13 — Stale earnings balance on wallet tab switch
**File:** `lib/features/wallet/screens/wallet_screen.dart:56–81`

Tab switching triggers `setPayableTypeIndex()` / `setSelectedHistoryIndex()` but never re-fetches balance or transaction lists. Drivers see cached figures from initial load; new collections and payouts are invisible until manual pull-to-refresh.

**Fix:** Refresh balance on tab focus (`didChangeDependencies` or tab controller listener) or add a visible last-updated timestamp with a manual refresh button.

---

### D14 — No loading state during initial wallet data fetch
**File:** `lib/features/wallet/screens/wallet_screen.dart:56–66`

Five async calls fire on init with no `isLoading` guard. The screen renders immediately with empty/zero values, causing a brief flash before data arrives. No skeleton loader is shown.

**Fix:** Set `isLoading = true` in init, show `SkeletonWidget` while pending, clear flag on all calls complete.

---

### D15 — Username uniqueness not validated in real time
**File:** `lib/features/auth/screens/additional_sign_up_screen_1.dart:149–176`

Username length is checked client-side (line 158) but the form never calls the server to verify uniqueness during typing. The driver passes local validation, continues to form 2, submits, and only then receives an API error about duplicate username — forcing them back to re-enter.

**Fix:** Add a debounced `GET /api/auth/check-username` call on field blur and show an inline "already taken" message before the driver proceeds.

---

### D16 — Form 2 (vehicle registration) allows duplicate submission
**File:** `lib/features/auth/screens/additional_sign_up_screen_2.dart:388–406`

The Submit button is not disabled during the multipart upload. On slow networks, the driver can tap multiple times, queuing duplicate registration requests. Combined with D6 (large image), the first duplicate can cause an OOM crash mid-upload.

**Fix:** Disable the button on first tap; re-enable only on API response.

---

### D17 — Trip history filter state resets on re-entry
**File:** `lib/features/trip/screens/trip_screen.dart:50–55`

`TripController` filter state (`selectedFilterTypeName`, `selectedStatusName`) is in-memory only. Navigating away and returning resets filters to defaults, frustrating drivers who set specific date/status filters.

**Fix:** Persist filter state in controller instance (it is `Get.lazyPut`'d so it survives navigation) by not re-calling `initFilters()` if data is already loaded.

---

### D18 — Location permission denied silently continues without prompt
**File:** `lib/features/map/screens/map_screen.dart:100–125`

When `PERMISSION_DENIED` is caught, only `debugPrint` is called. The map marker is null and the app never prompts the driver to enable location. A driver without location permission cannot navigate to pickups.

**Fix:** Show an `AlertDialog` directing the driver to Settings and blocking map interaction until permission is granted.

---

### D19 — Pusher config-null failure is silent
**File:** `lib/helper/pusher_helper.dart:25–28`

If `ConfigController.config` is null (config API failed during splash), `PusherHelper.init()` returns early without setting any error state. The driver has no real-time ride notifications for the entire session.

**Fix:** Surface a banner/snackbar ("Real-time connection unavailable — please restart") and schedule a config retry.

---

### D20 — Foreground notifications re-initialize plugin on every message
**File:** `lib/helper/notification_helper.dart:73–87`

`flutterLocalNotificationsPlugin.initialize()` is called inside `FirebaseMessaging.onMessage` listener — once per received notification. Each message re-runs initialization with potential side effects (channel recreation, listener duplication).

**Fix:** Call `initialize()` once during app startup in `NotificationHelper.initialize()`, not in the message handler.

---

### D21 — Signature rendering throws unhandled exception on PNG encode failure
**File:** `lib/features/mart/screens/mart_delivery_screen.dart:774–806`

`toByteData()` returning null causes `throw Exception('Failed to encode signature image')`. This unhandled exception crashes the delivery dialog rather than showing a retry prompt.

**Fix:** Wrap in try/catch; show a user-facing "Signature capture failed — please try again" message.

---

### D22 — Offline delivery state not persisted (photo/signature lost on app kill)
**File:** `lib/features/mart/screens/mart_delivery_screen.dart:30–36`

`_isOffline`, `_hasSignature`, `_hasDeliveryPhoto`, and `_deliveryProofUploaded` are local widget state. If the OS kills the app mid-delivery (e.g., while driver is offline), all state is lost on relaunch and the driver must recapture proof.

**Fix:** Persist these flags to SharedPreferences/Hive keyed by `orderId` on every state change. Restore on `initState` if a matching order is still in-progress.

---

### D23 — Weak signature acceptance threshold
**File:** `lib/features/mart/screens/mart_delivery_screen.dart:777`

Minimum stroke length of 50 pixels accepts single swipes and scribbles that are not recognizable signatures. No visual guidance during drawing and no re-capture prompt if quality is insufficient.

**Fix:** Raise the threshold to at least 200 px of total stroke length and add a minimum number of strokes (≥ 2). Show an inline "Signature too short — please sign again" hint.

---

### D24 — Tooltip controllers not hidden before dispose
**File:** `lib/features/home/screens/home_screen.dart:62–95`

`rideShareToolTip` and `parcelDeliveryToolTip` controllers are disposed (lines 90–91) without first calling `.hide()`. If a tooltip is active at the moment the driver navigates away, the tooltip overlay persists in memory.

**Fix:** Call `tooltipController.hide()` before `tooltipController.dispose()` in the State's `dispose()` method.

---

### D25 — Trip acceptance race condition: status check after accept
**File:** `lib/features/map/controllers/map_controller.dart` (inferred from AUDIT.md §3.4)

After a driver accepts a ride, the app polls for updated trip status. If the backend rejects the acceptance (another driver was faster), the app may still display an "accepted" UI state because the local state is set optimistically before confirmation arrives.

**Fix:** Set local state only after a successful API response; show a loading state during the acceptance window.

---

### D26 — Hardcoded strings not using translation keys
**File:** `lib/features/auth/` + various screens (inferred from AUDIT.md §3.10)

Several driver app screens contain hardcoded English strings (registration form labels, error messages, tooltip text) that use `.tr` on literal keys missing from `es.json`. Driver app only supports EN + ES (no `ar.json`); missing ES keys fall back to the raw key string.

**Fix:** Audit all `.tr` usages against `assets/language/es.json`; add any missing keys. Run `vito_flows_test.dart` parity check.

---

## 🔵 LOW

### D27 — Unused `didChangeAppLifecycleState` callback
**File:** `lib/features/map/screens/map_screen.dart:59–64`

`didChangeAppLifecycleState` is overridden and checks `AppLifecycleState.resumed`, but calls an undefined or no-op method. The lifecycle hook adds noise without effect.

**Fix:** Either implement the intended reconnection logic (re-subscribe Pusher, re-fetch trip status) or remove the override.

---

### D28 — StreamController closed before potential async callback
**File:** `lib/features/auth/screens/additional_sign_up_screen_1.dart:25–33`

`_confirmPinErrorController` (a `StreamController`) is closed in `dispose()`. If a background async validator fires after the widget is disposed, `.add()` will throw on a closed stream.

**Fix:** Guard `.add()` calls with `if (!_confirmPinErrorController.isClosed)`.

---

### D29 — Pusher socket ID null-check is inconsistent with disconnect logic
**File:** `lib/helper/pusher_helper.dart:51–52`

`pusherChannelId` is set from a potentially null `socketId`. The subsequent null-check sets status `'Connected'`, but if `connect()` failed (line 47 sets `'Disconnected'`), `pusherChannelId` may remain null while status appears connected on reconnect.

**Fix:** Unify the status-setting logic: only set `'Connected'` when `socketId` is confirmed non-null AND the connection event fires.

---

### D30 — `mart_delivery_screen.dart` has no `idempotency-key` header on proof upload
**File:** `lib/features/mart/screens/mart_delivery_screen.dart`

The delivery proof upload (multipart POST) does not set the `Idempotency-Key` header used by the backend's `IdempotencyKey` middleware. Network retries or the duplicate-submission issue (D11) can create duplicate proof records.

**Fix:** Generate a UUID per-delivery-attempt on `initState` and pass it as `Idempotency-Key` on every proof-upload and status-update call for that delivery.

---

## Suggested Fix Priority

### Immediate (data integrity or auth bypass)
1. **D2** — Enforce QR gate on OTP driver registration (auth bypass)
2. **D3** — Persist proof-upload flag to prevent orphaned proof records
3. **D1** — Begin MartDeliveryController migration (architectural debt, untestable)
4. **D9** — Add double-accept guard to trip acceptance

### Next sprint (silent failures and session leaks)
5. **D5** — Unsubscribe Pusher channels on logout
6. **D7** — Clear ProfileController on logout
7. **D8** — Implement background FCM handler
8. **D4** — Fix silent location failure in delivery proof
9. **D6** — Add file size validation before identity photo upload
10. **D10** — Persist online/offline toggle across restarts

### Backlog (UX degradation)
- D11–D26: stale data, missing loading states, filter reset, weak signature, tooltip leak
- D27–D30: low-severity polish and dead code

---

## ✅ Fixed in v2.1.0 (released 2026-06-26)

| ID | Description |
|----|-------------|
| D5 | Pusher channels unsubscribed on logout; `pusherClient` nulled |
| D7 | ProfileController cleared on logout |
| D8 | Background FCM handler with `@pragma('vm:entry-point')` |
| D9 | Double-accept guard in ride acceptance |
| D10 | Online/offline toggle persisted to SharedPreferences |
| D12 | Home `loadData()` wrapped in try/catch to prevent blank screen on network error |
| D14 | Withdrawal confirmation dialog before submitting balance update |
| D17 | Trip filter tab restored to saved position on re-entry |
| D18 | `permission_handler` import added to map_screen for `openAppSettings()` |
| D19 | Pusher disconnection banner with retry button in home screen |
| D20 | FCM plugin re-init removed from foreground message listener |
| D24 | Tooltip controllers hidden before dispose |
| D27 | Map screen lifecycle reconnects Pusher on app resume when disconnected |
| D28 | StreamController `isClosed` guard before `add()` in sign-up flow |
