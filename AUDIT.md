# Vito — End-to-End Audit Report

> **Scope:** Laravel 12 backend · Flutter customer app · Flutter driver app  
> **Date:** 2026-05-21  
> **Method:** Full source-tree static analysis across all three sub-systems

Findings are grouped by severity. Each entry has the format:  
`file:line — problem — impact`

---

## Severity Legend

| Label | Meaning |
|---|---|
| 🔴 CRITICAL | Data integrity, security breach, or silent data loss |
| 🟠 HIGH | Feature broken, crash, or serious UX failure |
| 🟡 MEDIUM | Degraded experience, edge-case crash, or inconsistency |
| 🔵 LOW | Polish, minor UX gap, missing convenience feature |

---

## Part 1 — Laravel Backend

### 1.1 QR Token Gate

- 🔴 **Token reuse — no atomicity on validation**  
  `QrTokenController.php` — `validateToken()` reads the token, checks it, then marks it used in two separate queries with no transaction. A second concurrent request can read the token before it is marked used, allowing the same token to be redeemed twice. Fix: wrap read+mark in `DB::transaction()` with `lockForUpdate()`.

- 🔴 **No cross-role enforcement**  
  `QrTokenController.php` — A driver-scoped token (7-day window) can be submitted to the customer validation endpoint with no role check, and vice versa. The `type` / `role` column on `qr_tokens` is never validated against the intended usage.

- 🟠 **Race condition on `pinRegister()`**  
  `VitoAuthController.php:89-97` — Token validity is checked then the user record is created; another thread can invalidate or reuse the same token between the two steps. Needs to be atomic.

### 1.2 Authentication

- 🔴 **Username TOCTOU race condition**  
  `VitoAuthController.php:126-172` — Uniqueness is checked with a plain `exists()` query then the user is inserted separately. Two simultaneous registrations with the same username both pass the check and one silently clobbers the other, or both succeed depending on DB timing. Fix: unique constraint on the column + catch `UniqueConstraintViolationException`.

- 🟠 **No scope enforcement on routes**  
  A Passport token issued with `AccessToDriver` scope can call customer-only API routes. Middleware only checks `auth:api`, not `scope:AccessToCustomer`. A driver can submit ride requests as a customer.

- 🟠 **No token revocation / rotation**  
  There is no logout endpoint that calls `$token->revoke()`. Stolen tokens remain valid for the full 1-hour (customer) or 7-day (driver) lifetime. Add a `POST /api/auth/logout` that revokes the current token.

- 🟡 **Login error leaks user existence**  
  `VitoAuthController.php` — Wrong PIN returns `"Incorrect PIN"` while unknown username returns `"User not found"`. These two distinct messages allow username enumeration. Use a single generic message for both cases.

### 1.3 Trip State Machine

- 🟠 **TOCTOU on trip cancellation**  
  `TripRequestController.php:712-821` — `current_status` is checked and then updated in two separate queries. Under concurrency, a trip could be cancelled after a driver already accepted it in parallel. Wrap in a transaction with `lockForUpdate()`.

- 🟠 **No "arrived at pickup" intermediate state**  
  The state machine jumps directly from `accepted` → `picked_up`. There is no state for the driver being at the pickup location. Without it, customers have no ETA signal and drivers have no way to indicate arrival, making the "Customer isn't here" timeout flow impossible to implement.

- 🟠 **Silent disambiguation between "already accepted" and "not found"**  
  Both cases return a generic 404. The customer app cannot tell whether their trip was taken or simply doesn't exist, so it shows the same error message for opposite situations.

- 🟡 **No real-time driver location endpoint**  
  Customers cannot poll or receive the driver's live location before a driver accepts. The tracking screen only activates post-acceptance, leaving a dead waiting screen during the matching phase.

### 1.4 Parcel Flow

- 🟡 **No zone validation at booking time**  
  Sender and receiver addresses are accepted without checking they fall within the same serviceable zone. The job is only rejected later, leaving customers confused about why their booked parcel fails to get a driver.

- 🟡 **Missing dimension validation**  
  Weight is validated server-side but no validation exists for parcel dimensions (length × width × height). A parcel can be booked with dimensions of zero.

### 1.5 Mart Order Flow

- 🔴 **Promo code `used_count` race condition**  
  `VitoMartController.php:127-135` — `used_count` is incremented with a plain `increment()` with no `lockForUpdate()` guard. Under concurrent checkouts with the same promo code, `usage_limit` can be exceeded. The stock decrement correctly uses `lockForUpdate()`; promo code must do the same.

- 🟠 **No per-user promo code limit**  
  `MartPromoCode.php:33-45` — Only a global `usage_limit` is tracked. A single user can apply the same promo code multiple times (once per order) until the global limit is exhausted.

- 🟡 **No driver cancellation endpoint**  
  Once a driver accepts a mart order, there is no way for the driver to cancel it. If the driver accepted by mistake or cannot fulfil the order, they are stuck — only admin can resolve it.

- 🟡 **Tip is uncapped and client-controlled**  
  The tip amount is sent by the client and persisted without a server-side maximum. A client could send an arbitrarily large tip value, inflating the order total in unexpected ways.

### 1.6 Trip Fare — Client Sends All Prices

- 🔴 **Trip fares are entirely client-controlled — price manipulation**  
  `TripRequestController.php:140-157` — The backend never recomputes trip fares from distance, time, or rate cards. All fare values (`estimated_fare`, `actual_fare`, `extra_estimated_fare`, `extra_return_fee`, `extra_cancellation_fee`) are read directly from the request body and persisted as-is. A customer can send `estimated_fare: 0.01` and pay almost nothing. This is the opposite of the server-side total principle followed by the mart module. Fix: remove all fare fields from the request, recalculate on the backend using the stored origin/destination coordinates and the zone's rate card.

- 🔴 **Surge and extra fees are also client-sent**  
  `TripRequestController.php:152-157` — `request['extra_estimated_fare']` and `request['extra_return_fee']` come from the client with no server-side recalculation. The final charge in `finalFareCalculation()` sums values that were already manipulated at creation time.

### 1.7 Stripe / Payments

- 🟠 **No idempotency key on PaymentIntent creation**  
  `VitoStripeController.php:14-105` — Retried requests create duplicate PaymentIntents. Add `idempotencyKey` equal to a hash of `(user_id + order_id)` on every `PaymentIntents::create()` call.

- 🟠 **No automatic refund on cancellation**  
  When an order is cancelled after a PaymentIntent has been confirmed, there is no refund logic. The customer is charged and must request a refund manually through admin.

- 🟡 **Potential double wallet credit on webhook retry**  
  If the webhook handler succeeds at writing to the DB but fails to return a `200` before Stripe retries, the wallet credit is applied twice. Add an idempotency check against the Stripe event ID before crediting.

### 1.7 Chat / Reverb

- 🟡 **No rate limiting on message sending**  
  `ChattingController.php` — Any authenticated user can flood the chat endpoint. Add `throttle:60,1` to the message-send route.

- 🟢 **Channel auth correctly checks membership** — no issue.  
- 🟢 **Reverb down degrades gracefully; messages persisted in DB** — no issue.

### 1.8 Authorization

- 🔴 **`rideDetails()` has no ownership check**  
  `TripRequestController.php:365-377` — Any authenticated customer can fetch the details of any trip by ID, including trips belonging to other customers. Add `where('customer_id', auth()->id())` to the query.

- 🟡 **Driver can enumerate global pending trips**  
  No zone filter is enforced at the query level; a driver can see pending trips from all zones if they craft a direct API request, bypassing the app's zone-based UI.

### 1.9 Missing Endpoints

| Status | Endpoint |
|---|---|
| 🔴 Missing | `POST /api/auth/logout` — revoke current Passport token |
| 🟠 Missing | `PATCH /api/driver/status` — explicit go-online / go-offline |
| 🟠 Missing | `POST /api/mart/orders/{id}/driver-cancel` — driver cancels accepted mart order |
| 🟡 Missing | `GET /api/reviews/given` — reviews the authenticated user has written |
| 🟡 Missing | Batch status endpoints for admin bulk operations |

---

## Part 2 — Flutter Customer App

### 2.1 Onboarding / QR Gate

- 🟠 **Camera permission denial is a silent failure**  
  `token_gate_screen.dart:115-122` — `QrScannerScreen` does not return any signal when the user denies camera permission. The parent screen waits indefinitely for a token that never arrives, with no message or retry prompt.

- 🟠 **Token stored unencrypted in SharedPreferences**  
  `token_gate_screen.dart:257-262` — The full invitation token is written to SharedPreferences in plaintext. On rooted devices this is trivially readable. Store only a hashed or truncated form for history display.

- 🟡 **Expired token shows generic error**  
  API returns a specific status code for an expired token but the handler maps it to the same generic `"token_validation_failed"` message as every other error. User has no idea to request a new invitation.

- 🟡 **`Get.off()` to SignUpScreen destroys the gate**  
  `token_gate_screen.dart:275` — Using `Get.off()` means the token gate is popped from the stack. If the user hits Back from the signup screen, they cannot return to the gate to try a different token without restarting the app.

### 2.2 Registration & Login

- 🔴 **`setCountryCode()` assigns to itself**  
  `auth_controller.dart:53` — `countryDialCode = countryDialCode` is a self-assignment. The parameter is never stored. The country code is stuck at its initialisation value (`+880`) regardless of what the user selects.

- 🟠 **PIN field accepts non-digit characters**  
  `sign_in_screen.dart:134` — Validation only checks that the PIN is exactly 6 characters long, not that it is all digits. A user can enter `"abc123"` and not be corrected client-side.

- 🟠 **Null crash on invalid phone format**  
  `auth_controller.dart:124-125` — `CountryCodeHelper.getCountryCode()` can return `null` for a malformed number; the next line calls `.substring()` on the result without a null guard, causing a crash.

- 🟡 **Double country code on auto-login after registration**  
  `auth_controller.dart:129` — After registration succeeds, `login()` is called with the full phone (which already includes the country code), and `login()` concatenates the country code again, producing an invalid phone string.

- 🟡 **No loading state during async PIN submission**  
  Tapping Login/Register fires the API call but does not disable the button or show a spinner. Users who tap twice submit two requests.

### 2.3 Home / Map Screen

- 🟠 **App crashes when location permission is denied**  
  `home_screen.dart:131-134` — `getNearestDriverList()` is called with the result of `LocationController.position` without a null check. If permission was denied, `position` is null and the call throws.

- 🟠 **Map centres on (0, 0) when location is unavailable**  
  `home_map_view.dart:70-71` — The fallback position is `LatLng(0, 0)` — the middle of the Gulf of Guinea. Should default to the app's configured city centre or show a permission prompt.

- 🟡 **No empty state when no drivers are nearby**  
  The map renders with no pins and no message. Users do not know whether no drivers exist in their zone or whether the app is still loading.

### 2.4 Ride Booking

- 🟠 **No booking confirmation dialog**  
  `ride_controller.dart:301-335` — `submitRideRequest()` fires immediately on the first button tap with no "Confirm?" step. A user who double-taps accidentally creates duplicate trip requests.

- 🟠 **API error clears state without showing reason**  
  `ride_controller.dart:323-329` — A 403 response (e.g. out-of-zone) clears `tripDetails` and `rideDetails` silently. The screen goes blank. The user doesn't know why.

- 🟡 **Back button during booking clears all entered data without warning**  
  No `WillPopScope` guard asks "Are you sure?" before discarding an in-progress booking.

### 2.5 Live Trip Tracking

- 🟠 **Location polling has no backoff**  
  `live_location_screen.dart:32-34` — Driver location is polled every 10 seconds unconditionally. On a slow connection, multiple in-flight requests pile up. Should use debounce or cancel the previous request before issuing a new one.

- 🟠 **Polyline crash on empty route**  
  `location_tracking_controller.dart:43` — `_polylineCoordinateList.first` throws if the server returns an empty route (e.g., driver is in an unmapped area).

- 🟡 **Tracking URL parsing is fragile**  
  `live_location_screen.dart:31` — The tracking ID is extracted with `.split('/').last`. Any URL with a trailing slash or unexpected format silently extracts the wrong ID.

### 2.6 Parcel Flow

- 🟡 **Weight/dimension fields accept non-numeric input**  
  No client-side `inputFormatters` restrict parcel weight/dimension fields to numbers. The API rejects invalid input, but no helpful message is shown.

- 🟡 **No estimated delivery time shown before confirmation**  
  Users see price but not time. They book without knowing a cross-town parcel takes 2 hours.

### 2.7 Mart Flow

- 🟠 **Chat button not guarded before driver assignment**  
  `mart_order_tracking_screen.dart:69` — `_driverId` can be null before a driver accepts, but the chat button is not disabled in all code paths. Tapping it before assignment creates an orphaned chat channel.

- 🟡 **Order total not shown on the cart / checkout screen**  
  The final server-computed total (products + delivery + tip − promo) is only visible on the payment screen, not on the cart or checkout confirmation.

- 🟡 **Promo code validated only at payment, not at input**  
  Users enter a promo code at checkout but do not receive validation feedback until the final payment step. Invalid codes surface at the worst possible moment.

- 🟡 **Product category null crash**  
  `mart_store_screen.dart:197` — `.where()` filter accesses `_products[i]['category']` without a null guard. Any product with a missing `category` field crashes the screen.

### 2.8 Stripe / Payment

- 🟠 **No 3DS authentication handling**  
  `payment_controller.dart` — When a card requires 3D Secure authentication, the PaymentIntent requires further action. The app does not handle `requires_action` status, leaving the payment stuck indefinitely.

- 🟠 **Double force-unwrap on config**  
  `payment_controller.dart:124` — `config!.reviewStatus!` — both are force-unwrapped. If the config fetch hasn't completed or `reviewStatus` is absent from the API response, this crashes.

### 2.9 Chat / Messaging

- 🔴 **Pusher channel is never unsubscribed**  
  `message_controller.dart:349-357` — No `onClose()` method unsubscribes from the channel. Each time the user opens a chat screen and leaves, a new subscription is added but the old one persists. After several round-trips, the app holds many live channel subscriptions, draining battery and memory.

- 🟠 **Event data decoded without structure validation**  
  `message_controller.dart:352-356` — `jsonDecode(event.data!)['channel_conversation']['channel']['trip_id']` throws if the server sends an unexpected payload shape (e.g., a system message or error event).

- 🟡 **No reconnection on connection drop**  
  If the Pusher/Reverb connection drops mid-conversation, the app shows no warning and messages typed are silently lost.

- 🟡 **Keyboard covers input on small screens**  
  `message_screen.dart` — No `resizeToAvoidBottomInset` handling or bottom padding adjustment when the system keyboard appears.

### 2.10 Global Error Handling

- 🟠 **`response.statusText` force-unwrapped on 500 errors**  
  `api_checker.dart:32` — `response.statusText!` will throw if the server returns a 500 with no status text header, converting a server error into a client crash.

- 🟡 **No 429 (rate limit) handling**  
  `api_checker.dart:9-35` — Rate-limit responses are treated the same as unknown errors. Users see a generic message with no indication they should wait before retrying.

- 🟡 **Raw API error messages displayed without `.tr`**  
  `auth_controller.dart:313` — `showCustomSnackBar(response.body['message'])` renders server text verbatim. If the server returns a message in another language, it appears in that language regardless of app locale.

### 2.11 Localization Gaps

The following keys are referenced in code but **absent from all language files** (`en.json`, `es.json`, `ar.json`):

`invitation_required` · `scan_qr_or_enter_token` · `enter_invitation_token` · `validate_token` · `token_history` · `invalid_token_length` · `token_is_required` · `invalid_or_expired_token` · `token_validation_failed` · `ready_to_ride` · `enter_username_and_pin` · `username` · `enter_6_digit_pin` · `pin_is_required` · `pin_must_be_6_digits` · `username_is_required` · `username_min_3_characters`

All of these render as their raw key strings (e.g. `"pin_is_required"`) in Spanish and Arabic.

---

## Part 3 — Flutter Driver App

### 3.1 Onboarding / QR Gate

- 🟡 **Same camera permission silent failure as customer app**  
  Driver QR gate screen does not communicate camera denial to the user.

- 🟡 **No guidance during account review wait**  
  After submitting registration documents, the driver sees a generic "pending" screen with no indication of what is being reviewed, how long it takes, or how they'll be notified of approval.

### 3.2 Registration

- 🟠 **Vehicle photo upload failure is silent**  
  If the multipart upload of license or vehicle photos fails, the form proceeds as if it succeeded. The backend then rejects the registration with a confusing "missing documents" error that doesn't map to any form field.

### 3.3 Online / Offline Toggle

- 🔴 **Location permission not enforced before going online**  
  The driver can toggle online without location permission being granted. The backend marks the driver as available, but no location updates are sent, so they appear available to customers but will never receive trip assignments based on proximity.

- 🟠 **Online state not persisted across app restart**  
  If the app is killed while the driver is online, on relaunch the toggle defaults to offline. The backend still has the driver marked as online. State is now split.

- 🟠 **No graceful offline signal when app is killed**  
  There is no background task or heartbeat that tells the backend the driver went offline when the OS kills the app. The driver remains "available" in the backend indefinitely until they next open the app.

### 3.4 Trip Request Acceptance

- 🟠 **Double-submit possible on slow network**  
  The Accept button is not disabled after the first tap. On a slow connection the driver can tap multiple times, sending multiple acceptance requests. The backend may handle this, but the UI gives no feedback of the in-flight request.

- 🟠 **Race condition not communicated to driver**  
  If another driver accepts first, the API returns an error but the UI does not clearly communicate "this trip was taken by another driver." The driver sees a generic error and doesn't know whether to wait for another request.

- 🟡 **No audio/vibration for incoming requests when screen is off**  
  Incoming trip request notifications depend on FCM push, but there is no wake-lock or high-priority channel configuration ensuring the notification plays a sound when the device screen is off.

### 3.5 Live Trip Status Updates

- 🟠 **Status update button not disabled after tap**  
  Like the accept button, status update buttons (en_route → arrived → picked_up → completed) are not disabled after being tapped. A driver can accidentally double-tap and attempt to skip a state, which the backend rejects, but the UI is left in an inconsistent state.

- 🟠 **API failure leaves UI in wrong state**  
  If a status update call fails (network error), the UI has already moved to the next state visually. There is no rollback to the previous state, leaving driver UI and backend permanently out of sync until the app is restarted.

- 🟡 **No retry mechanism for failed status updates**  
  Status updates are fire-and-forget. A single network timeout causes a lost state transition with no queued retry.

### 3.6 Parcel / Mart Delivery Proof

- 🟠 **Proof photo upload failure is silent**  
  `MartDeliveryScreen` — If the multipart upload of the delivery proof photo fails, the delivery is not marked as complete on the backend, but the driver is given no error message. They believe the delivery is done but it is stuck in `picked_up` state.

- 🟡 **No fallback if camera is unavailable**  
  If the device camera fails to open (permission revoked after first use), the proof capture screen shows a blank camera preview with no error message or alternative (e.g., gallery pick).

### 3.7 Earnings / Wallet

- 🟡 **Earnings balance is cached and stale**  
  The wallet balance displayed on the earnings screen is the value fetched at screen entry. It does not refresh after a completed trip or withdrawal. Driver can see an incorrect balance without pulling to refresh.

- 🟡 **Withdrawal flow has no confirmation step**  
  Tapping "Withdraw" immediately submits the request. There is no "Are you sure?" confirmation, and no minimum balance check client-side.

### 3.8 Chat

- 🔴 **Same Pusher subscription memory leak as customer app**  
  `ChatController` — Channel subscriptions are added on each mart/ride chat open and never removed on screen close. Same battery and memory drain as the customer app.

- 🟠 **Wrong channel name used for mart chat in some paths**  
  The mart chat subscribe method uses `order_id` for the channel, but in one code path the event handler checks `trip_id` instead of `order_id`, silently dropping all incoming mart messages.

- 🟡 **No offline message queue**  
  Messages sent while the connection is down are lost. There is no local queue that retries when connectivity is restored.

### 3.9 Push Notifications

- 🟠 **FCM token registered only once at login, never refreshed**  
  FCM tokens can be rotated by the OS. There is no listener for `FirebaseMessaging.instance.onTokenRefresh` to push the new token to the backend. Drivers with rotated tokens stop receiving push notifications silently.

- 🟡 **Foreground notification not displayed**  
  When the app is in the foreground and a new trip request arrives via push, no in-app notification or sound is triggered. The request is only visible in the request list if the driver happens to be looking at it.

### 3.10 Localization

- 🟡 **Hardcoded English strings in delivery screens**  
  Several labels in `MartDeliveryScreen` and the earnings screen are hardcoded English strings rather than translation keys (e.g., `"Proof of Delivery"`, `"Upload Photo"`, `"Withdraw Earnings"`).

---

## Summary — Fix Priority Matrix

### Fix immediately (production blockers)

| # | Area | Issue |
|---|---|---|
| 1 | Backend — Fares | **Trip fares are 100% client-controlled — trivial price manipulation** |
| 2 | Backend — QR | Token reuse via non-atomic validation |
| 3 | Backend — Auth | `rideDetails()` exposes any customer's trip to any other customer |
| 4 | Backend — Promo | `used_count` increment not atomic — budget can be exceeded |
| 5 | Backend — Auth | Username uniqueness not DB-enforced (TOCTOU) |
| 6 | Customer app | `setCountryCode()` self-assignment — country code never changes |
| 7 | Customer app | Pusher channel never unsubscribed — memory leak per chat opened |
| 8 | Driver app | Location permission not enforced before going online |
| 9 | Driver app | Pusher channel never unsubscribed — same leak |
| 10 | Driver app | FCM token never refreshed — push notifications silently stop |
| 11 | Driver app | Delivery proof upload failure is silent — trips stuck in wrong state |

### Fix before first real users

| # | Area | Issue |
|---|---|---|
| 11 | Backend — Auth | No logout endpoint / token revocation |
| 12 | Backend — Auth | Passport scopes not enforced on routes |
| 13 | Backend — Stripe | No idempotency key → duplicate PaymentIntents on retry |
| 14 | Backend — Stripe | No refund on order cancellation post-payment |
| 15 | Backend | No `arrived_at_pickup` state |
| 16 | Customer app | Crash when location permission denied (null pointer) |
| 17 | Customer app | No ride booking confirmation — accidental double booking |
| 18 | Customer app | 3DS card authentication not handled |
| 19 | Customer app | PIN field accepts non-digits |
| 20 | Driver app | Status update button not disabled after tap — state skipping |
| 21 | Driver app | Failed status update leaves UI/backend out of sync |
| 22 | Driver app | App-kill does not set driver offline on backend |
| 23 | Both apps | 17 missing i18n keys render as raw strings in ES/AR |

### Polish / improve before scaling

- Back-button confirmation on mid-booking screens
- Map (0, 0) fallback replaced with city centre or permission prompt
- Empty states for "no drivers nearby" and "no orders"
- 429 rate-limit error handling with retry countdown
- Withdrawal confirmation dialog in driver app
- Earnings balance live refresh after trip completion
- Foreground push notification display in driver app
- Promo code validated eagerly on input (not only at payment)
- Order total shown on cart screen, not only at payment
- Server-sent error messages wrapped in `.tr` or mapped to translation keys
