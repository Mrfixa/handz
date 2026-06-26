# Vito User App — Screen Flow Map

End-to-end trace of every user journey in the Flutter customer app
(`drivemond-user-app-3.1/HexaRide-User-app-release-3.1/`).
Each section shows: entry screen → key screens → terminal state, with the
API calls and real-time channels at each step.

---

## 1. App Launch / Routing

```
main.dart
  └─ Firebase.initializeApp()
  └─ Get.lazyPut() for all DI layers (di_container.dart)
  └─ SplashScreen
       ├─ ConfigController.getConfigData()   GET /api/config (15s timeout)
       ├─ connectivity check
       └─ LoginHelper.route()
            ├─→ AppVersionWarningScreen      (force-update flag in config)
            ├─→ LanguageSelectionScreen      (first install, no lang saved)  ⚠️ C3 — screen missing
            ├─→ OnBoardingScreen             (first install, not logged in)
            │     └─→ SignInScreen
            ├─→ AccessLocationScreen         (logged in, permission not granted)
            └─→ DashboardScreen             (logged in, all good)
```

Key helpers: `login_helper.dart` (`route()`, `forNotLoginUserRoute()`, `checkLoginMedium()`),
`firebase_helper.dart` (FCM init + topic subscriptions).

---

## 2. Authentication Flows

### 2A — PIN Login (returning user)

```
SignInScreen
  ├─ AuthController.login(countryCode, username, pin)
  │     POST /api/customer/auth/pin-login
  │     ├─ 200 → save token, updateToken(), getProfileInfo()
  │     │         → DashboardScreen
  │     ├─ 202 → phone not verified
  │     │         → VerificationScreen (VerificationForm.login)
  │     │               → DashboardScreen (on OTP verify)
  │     └─ 408 → maintenance mode dialog
  ├─→ ForgotPasswordScreen  (forgot PIN link)
  ├─→ OtpLoginScreen        (login with OTP link)
  └─→ TokenGateScreen       (sign up — new user)
```

### 2B — PIN Registration (QR-gated, new user)

```
SignInScreen → TokenGateScreen
  ├─ Manual token entry OR QrScannerScreen
  ├─ POST /api/qr-token/validate  {"token": …}
  └─ Valid → SignUpScreen (qrToken passed as param)
       ├─ AuthController.checkOAuth(countryCode, phone)
       │     POST /api/customer/auth/check  {"phone_or_email": phone}
       │     200 → phone taken, show error
       │     404 → proceed
       ├─ AuthController.sendOtp(phone)  POST /api/customer/auth/send-otp
       └─ VerificationScreen (VerificationForm.verifyUser)
             ├─ AuthController.otpVerification(phone, otp, …)
             │     POST /api/customer/auth/otp-verification
             └─ OtpSignupScreen (profile: first/last name, referral)  ⚠️ C2
                   ├─ AuthController.registrationFromOtp(SignUpBody)
                   │     POST /api/customer/auth/registration-from-otp
                   └─ DashboardScreen
```

### 2C — OTP Login (alternative path)

```
SignInScreen → OtpLoginScreen
  ├─ AuthController.checkOAuth(countryCode, phone)
  │     POST /api/customer/auth/check
  │     200 → existing user
  │     404 → new user (proceed to register via OTP)
  ├─ AuthController.sendOtp(phone) OR firebaseOtpSend(phone)
  └─ VerificationScreen
       ├─ POST /api/customer/auth/otp-verification  (SMS gateway)
       │   or POST /api/customer/auth/firebase-otp-verification (Firebase)
       ├─ 200 → DashboardScreen
       └─ 406 → OtpSignupScreen (unregistered number)  ⚠️ C2
                 → DashboardScreen
```

### 2D — PIN Reset

```
SignInScreen → ForgotPasswordScreen
  ├─ AuthController.forgetPassword(phone)
  │     POST [wrong endpoint — AppConstants.configUri]  ⚠️ C4
  └─ VerificationScreen (VerificationForm.resetPassword)
       └─ ResetPasswordScreen
             ├─ AuthController.resetPassword(phone, newPin)
             │     POST /api/customer/auth/change-pin
             └─ SignInScreen
```

---

## 3. Dashboard / Home Dispatch

```
DashboardScreen  (4 bottom-nav tabs, PageStorageBucket preserves scroll)
  │  Tab 0: HomeScreen
  │  Tab 1: TripScreen (activity / history)
  │  Tab 2: NotificationScreen
  │  Tab 3: ProfileScreen
  └─ Back: double-tap to exit (PopScope)

HomeScreen  (loadData() fires all at once)
  ├─ ConfigController.getConfigData()
  ├─ ParcelController.getUnpaidParcelList()
  ├─ BannerController.getBannerList()
  ├─ CategoryController.getCategoryList()
  ├─ AddressController.getAddressList(1)
  ├─ OfferController.getOfferList(1)
  ├─ ProfileController.getProfileInfo()
  ├─ RideController.getRunningRideList()
  ├─ RideController.getBiddingList(tripId, 1)
  ├─ RideController.getNearestDriverList(lat, lon)
  └─ ParcelController.getRunningParcelList()
  
  Quick-action buttons:
  ├─→ SetDestinationScreen → MapScreen   (book ride)
  ├─→ ParcelScreen → MapScreen           (send parcel)
  ├─→ MartStoreScreen                    (shop mart)
  ├─→ RideListViewScreen                 (active ride badge)
  └─→ ParcelListViewScreen               (active parcel badge)
  
  Bidding notification:
  └─→ DriverRideRequestDialog (accept / reject driver bid)
```

---

## 4. Ride Journey

```
SetDestinationScreen
  ├─ LocationController: geocoding, reverse-geocode, zone detection
  ├─ RideController.getEstimatedFare(origin, dest, categoryId)
  │     GET /api/customer/ride/estimated-fare
  └─ [confirm] → MapScreen

MapScreen
  ├─ RideController.submitRideRequest(RideRequestBody)
  │     POST /api/customer/trip/create-ride-request
  ├─ Pusher: subscribe private-customer-ride-chat.{tripId}
  │     events: driver_accept, driver_location, ride_cancelled
  ├─ RideController.getRunningRideList()  (polling fallback)
  ├─ [cancel] → RideController.cancelRide(tripId)
  │     PUT /api/customer/trip/cancel-ride/{id}
  └─ [ride complete] → PaymentScreen

PaymentScreen
  ├─ Payment method: Cash / Digital (Stripe) / Wallet
  ├─ Cash → PaymentController.confirmPayment(tripId, 'cash')
  │           GET /api/payment?trip_request_id=…&payment_method=cash
  ├─ Wallet → same endpoint with payment_method=wallet
  ├─ Digital → DigitalPaymentScreen
  │              POST /api/customer/stripe/payment-intent (idempotent)
  │              Stripe.presentPaymentSheet()
  └─ [done] → ReviewScreen

ReviewScreen
  ├─ RideController.submitReview(ReviewBody)
  │     POST /api/customer/submit-review
  └─ DashboardScreen
```

---

## 5. Mart Order Journey

```
MartStoreScreen
  ├─ MartController.getProducts(categoryId?, search?)
  │     GET /api/customer/mart/products
  ├─ MartController.getCategories()
  │     GET /api/customer/mart/categories
  ├─ Cart: addToCart/removeFromCart/clearCart → SharedPreferences (local-only)  ⚠️ H2
  ├─→ MartProductDetailsScreen
  │     MartController.getProductDetails(id)
  │     GET /api/customer/mart/products/{id}
  └─ [go to cart] → MartCartScreen (inline widget)

MartCartScreen
  ├─ Qty adjust, tip amount
  ├─ POST /api/customer/mart/apply-promo  {"code": …, "subtotal": …}  ⚠️ C6 (client total)
  └─ [place order] → MartController.createOrder(MartOrderBody)
       POST /api/customer/mart/order  (idempotency key attached)
       ├─ Success → MartPaymentScreen (if payment needed)
       │             or MartOrderTrackingScreen (if COD/wallet)
       └─ Failure → error shown, cart NOT cleared  ⚠️ M15

MartPaymentScreen
  ├─ POST /api/customer/stripe/order-payment-intent (idempotent)
  ├─ Stripe.initPaymentSheet() + Stripe.presentPaymentSheet()
  └─ Success → MartOrderTrackingScreen

MartOrderTrackingScreen
  ├─ Poll: GET /api/customer/mart/orders/{id}  every 15s, max 240 polls  ⚠️ M4
  ├─ Status flow: pending → accepted → picked_up → delivered
  │     (or cancelled from pending / accepted)
  ├─ Chat button (enabled only when driver assigned)
  │     MessageController.createMartChannel(driverId, orderId, driverName)
  │     POST /api/channel/create
  │     → MessageScreen
  │          subscribeMartMessageChannel(orderId)
  │          Pusher: private-customer-mart-chat.{orderId}
  └─ Delivered → [review] → MartController.reviewOrder(id, ReviewBody)
                              POST /api/customer/mart/orders/{id}/review

MartOrderHistoryScreen (entry from HomeScreen or Profile)
  ├─ MartController.getOrders()  GET /api/customer/mart/orders
  └─ Tap → MartOrderTrackingScreen (read-only for completed)

Cancellation:
  └─ MartController.cancelOrder(id)  PUT /api/customer/mart/orders/{id}/cancel
```

---

## 6. Parcel Journey

```
ParcelScreen
  ├─ ParcelController.getParcelCategoryList()
  │     GET /api/customer/parcel-categories
  ├─ Form: sender address, receiver address, weight, category
  └─ [proceed] → MapScreen (type='parcel')

MapScreen (parcel mode)
  ├─ RideController.getEstimatedFare(origin, dest, 'parcel')
  │     GET /api/customer/ride/estimated-fare?type=parcel
  ├─ RideController.submitRideRequest(body, parcel: true)
  │     POST /api/customer/trip/create-ride-request
  │     body includes parcelCategoryId, senderAddress, receiverAddress
  ├─ Pusher: private-customer-ride-chat.{tripId}  (same channel as ride)
  └─ [complete] → PaymentScreen → back to Dashboard

ParcelListViewScreen
  ├─ ParcelController.getRunningParcelList()
  └─ ParcelController.getUnpaidParcelList()
```

---

## 7. Supporting Journeys

| Journey | Entry point(s) | Key screens & API calls | Terminal |
|---------|---------------|-------------------------|----------|
| **Wallet** | Profile / notification | `WalletScreen` (2 tabs: Money / Loyalty) · `GET transactionListUri` · `GET loyaltyPointListUri` · `DigitalAddFundScreen` → Stripe top-up `POST stripe/payment-intent` | back |
| **Chat (ride)** | HomeScreen active ride | `MessageListScreen` → `MessageScreen` · `POST channel/create` · `GET conversationList` · Pusher `private-customer-ride-chat.{tripId}` | back |
| **Chat (mart)** | MartOrderTrackingScreen | `MessageScreen` · Pusher `private-customer-mart-chat.{orderId}` | back |
| **Notifications** | Bell icon / FCM tap | `NotificationScreen` (paginated `GET notificationList`) → `notificationRouteCheck()` routes by action type · ⚠️ M6 mart actions unhandled | routed |
| **Trip history** | Dashboard Activity tab | `TripScreen` (5 status tabs) · `GET tripList?type=ride_request&status=…` · date range filter · `TripDetailsScreen` | back |
| **Refer & Earn** | Profile | `ReferAndEarnScreen` (2 tabs) · `GET referralDetails` · `GET referralEarningList` | back |
| **Coupons / Offers** | Profile | `MyOfferScreen` (Discounts + Coupons tabs) · `GET bestOfferList` · `GET couponList` · `POST customerAppliedCoupon` | back |
| **Refund request** | TripDetailsScreen | `RefundRequestScreen` · `GET getParcelRefundReasonList` (⚠️ H17 not called in initState) · multipart proof upload | back |
| **Safety** | Active ride screen | `SafetySetupScreen` · `GET getOtherEmergencyNumberList` · `GET getSafetyAlertReasonList` · `GET getPrecautionList` · `POST storeSafetyAlert` (lat/lng) | back |
| **Support** | Profile | `HelpAndSupportScreen` (Contact Us / Terms HTML from config) | back |
| **Settings** | Profile | `SettingScreen` → `ChangePinScreen` (POST `change-pin`) · `ResetPasswordScreen` | back |
| **My Level** | Profile | `MyLevelScreen` · `GET getProfileLevel` (progress, next target) | back |
| **Edit Profile** | Profile | `EditProfileScreen` · multipart profile photo upload | back |

---

## 8. Real-Time Channel Reference

| Channel | Direction | Events |
|---------|-----------|--------|
| `private-customer-ride-chat.{tripId}` | Customer receives | `driver_accept`, `driver_location`, `ride_cancelled`, `chat_message` |
| `private-customer-mart-chat.{orderId}` | Customer receives | `mart_order_status_updated`, `chat_message` |

Pusher setup: `lib/helper/pusher_helper.dart`. Auth endpoint: `https://{webSocketUrl}/broadcasting/auth` (Bearer token).

---

## 9. State Management & DI Chain

```
ApiClient (lib/data/api_client.dart)
  └─ attaches Authorization: Bearer {token} to every request
  └─ 30s timeout, no auto-retry  ⚠️ M8
  └─ 401 → clears token, reroutes to SignInScreen

Repository (lib/features/{feature}/domain/repositories/)
  └─ calls ApiClient.getData / postData / postMultipartData

Service (lib/features/{feature}/domain/services/)
  └─ delegates to Repository

Controller (lib/features/{feature}/controllers/)
  └─ GetxController — holds observable state, calls Service
  └─ Registered via Get.lazyPut() in lib/helper/di_container.dart

Navigation: always Get.to(() => Screen()) or Get.off() / Get.offAll()
State: GetBuilder<Controller> rebuild on update()
```

---

*For known bugs at each step, see `USER_APP_AUDIT.md`. Finding IDs (C1–C9, H1–H18, M1–M28, L1–L8) cross-reference the audit.*
