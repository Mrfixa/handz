# Vito — Complete UX/UI and Authentication Overhaul Plan

## 1. OBJECTIVE

Fix all UX/UI issues, authentication flows, and user experience problems in both Flutter apps (customer and driver) to ensure a smooth, professional user experience from onboarding to trip completion.

## 2. CONTEXT SUMMARY

After deep analysis of both Flutter apps, the following critical UX/UI and authentication issues were identified:

### CUSTOMER APP ISSUES:
1. **FCM Token Refresh Missing** — Push notifications stop working after token rotation
2. **Pusher Channel Memory Leak** — Channels subscribed but never unsubscribed on chat close
3. **No Loading States** — Buttons show no loading feedback during API calls
4. **Silent API Failures** — Network errors show generic messages
5. **Stale Balance Display** — Wallet balance doesn't refresh after transactions
6. **No Offline Queue** — Failed payments/orders lost without retry
7. **Splash Screen UX** — Shows generic snackbar instead of smooth loading
8. **Map Fallback (0,0)** — No proper fallback when location unavailable

### DRIVER APP ISSUES:
1. **FCM Token Refresh Missing** — Same as customer app
2. **Pusher Channel Memory Leak** — Same issue
3. **No Loading States on Buttons** — Accept/decline buttons not disabled during API calls
4. **Online State Not Persisted** — App crash leaves driver marked as available
5. **No Heartbeat Mechanism** — Backend can't detect driver disconnection
6. **Silent Delivery Proof Upload** — Failed uploads leave orders stuck
7. **No Withdrawal Confirmation** — Immediate submit without "Are you sure?"
8. **Stale Earnings Balance** — Not refreshed after trip completion
9. **No Camera Fallback** — Blank screen if camera permission revoked

### AUTHENTICATION ISSUES:
1. **PIN Validation UI** — No visual feedback on PIN entry
2. **No Remember Me Persistence** — State lost on app restart
3. **Token Not Refreshed** — FCM token sent once, never updated
4. **Logout Cleanup Incomplete** — Pusher channels not properly disconnected

## 3. APPROACH OVERVIEW

Implement fixes systematically across all three subsystems:
1. **Customer App** — Notification helpers, auth flow, state management, UI feedback
2. **Driver App** — Same fixes plus online state persistence, heartbeat
3. **Backend** — Authorization checks, state machine validation

## 4. IMPLEMENTATION STEPS

### PHASE 1: Authentication & Notifications (BOTH APPS)

#### Step 1.1: Add FCM Token Refresh Listener
**File:** `lib/helper/notification_helper.dart` (BOTH APPS)

Add this after FirebaseMessaging initialization:
```dart
// Listen for FCM token refresh and send new token to backend
FirebaseMessaging.instance.onTokenRefresh.listen((String newToken) async {
  customPrint('FCM Token refreshed: $newToken');
  await _sendFcmTokenToBackend(newToken);
});

Future<void> _sendFcmTokenToBackend(String token) async {
  try {
    final apiClient = Get.find<ApiClient>();
    await apiClient.postData(
      AppConstants.fcmTokenUpdate,
      {"_method": "put", "fcm_token": token}
    );
  } catch (e) {
    customPrint('Failed to update FCM token: $e');
  }
}
```

#### Step 1.2: Fix Logout to Properly Disconnect Pusher
**File:** `lib/helper/pusher_helper.dart` (BOTH APPS)

Add unsubscribe methods and call on logout:
```dart
void cleanup() {
  try {
    pusherClient?.disconnect();
    pusherClient = null;
  } catch (_) {}
}
```

### PHASE 2: Customer App UX Fixes

#### Step 2.1: Add Loading States to Buttons
**Files:** All button widgets and screens with API calls

Add `isLoading` state to all action buttons:
```dart
// In controllers:
RxBool isLoading = false.obs;

void submitAction() async {
  isLoading.value = true;
  try {
    await apiCall();
  } finally {
    isLoading.value = false;
  }
}

// In UI:
ButtonWidget(
  buttonText: 'submit'.tr,
  isLoading: controller.isLoading.value,
  onPressed: () => controller.submitAction(),
)
```

#### Step 2.2: Fix Splash Screen UX
**File:** `lib/features/splash/screens/splash_screen.dart`

Replace generic snackbar with professional loading UI:
```dart
// Remove connectivity snackbar
// Add smooth transition to next screen
// Show loading indicator until config is fetched
```

#### Step 2.3: Add Proper Error Handling
**File:** `lib/data/api_checker.dart`

Enhance error messages:
```dart
static void checkApi(Response response) {
  if (response.statusCode == 401) {
    showCustomSnackBar('session_expired_please_login_again'.tr);
    Get.offAll(() => const SignInScreen());
  } else if (response.statusCode == 0) {
    showCustomSnackBar('no_internet_connection'.tr);
  } else {
    showCustomSnackBar(response.body['message'] ?? 'something_went_wrong'.tr);
  }
}
```

#### Step 2.4: Fix Wallet Balance Refresh
**File:** `lib/features/wallet/screens/wallet_screen.dart`

Add pull-to-refresh and auto-refresh after transactions:
```dart
RefreshIndicator(
  onRefresh: () => controller.refreshBalance(),
  child: ...,
)
```

#### Step 2.5: Fix Map Location Fallback
**File:** `lib/features/map/screens/map_screen.dart`

Add proper fallback for (0,0) coordinates:
```dart
LatLng getInitialPosition() {
  final coords = locationController.initialPosition;
  // Check for invalid coordinates (0,0)
  if (coords.latitude == 0 && coords.longitude == 0) {
    // Show permission prompt or city center fallback
    return ConfigController.defaultLocation;
  }
  return coords;
}
```

### PHASE 3: Driver App UX Fixes

#### Step 3.1: Persist Online State
**File:** `lib/features/home/screens/home_screen.dart`

```dart
// On toggle online:
sharedPreferences.setBool('isOnline', true);
apiClient.postData('driver/update-online-status', {'is_online': true});

// On app start:
if (sharedPreferences.getBool('isOnline') == true) {
  // Sync with backend
  apiClient.postData('driver/sync-online-status', {});
}
```

#### Step 3.2: Add Heartbeat Mechanism
**File:** `lib/main.dart`

```dart
Timer.periodic(Duration(seconds: 30), (timer) {
  if (driverIsOnline && hasActiveTrip) {
    apiClient.getData('driver/heartbeat');
  }
});
```

#### Step 3.3: Fix Status Update Button States
**File:** `lib/features/ride/controllers/ride_controller.dart`

```dart
RxBool isUpdatingStatus = false.obs;

Future<void> updateTripStatus(String status) async {
  if (isUpdatingStatus.value) return;
  isUpdatingStatus.value = true;
  
  try {
    final result = await updateStatusAPI(status);
    if (result.statusCode != 200) {
      // Revert UI state on failure
      showCustomSnackBar('failed_to_update_status'.tr);
    }
  } finally {
    isUpdatingStatus.value = false;
  }
}
```

#### Step 3.4: Fix Delivery Proof Upload Error Handling
**File:** `lib/features/mart/screens/mart_delivery_screen.dart`

```dart
Future<void> uploadProof(XFile file) async {
  isUploading.value = true;
  try {
    final result = await uploadMultipart(file);
    if (result.statusCode == 200) {
      showCustomSnackBar('delivery_confirmed'.tr, isError: false);
    }
  } catch (e) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('upload_failed'.tr),
        content: Text('please_try_again'.tr),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('retry'.tr),
          ),
        ],
      ),
    );
  } finally {
    isUploading.value = false;
  }
}
```

#### Step 3.5: Add Withdrawal Confirmation
**File:** `lib/features/wallet/screens/wallet_screen.dart`

```dart
void initiateWithdraw(double amount) {
  showDialog(
    context: context,
    builder: (ctx) => AlertDialog(
      title: Text('confirm_withdrawal'.tr),
      content: Text('withdraw_amount_format'.tr.format(amount)),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(ctx),
          child: Text('cancel'.tr),
        ),
        ElevatedButton(
          onPressed: () {
            Navigator.pop(ctx);
            processWithdraw(amount);
          },
          child: Text('confirm'.tr),
        ),
      ],
    ),
  );
}
```

### PHASE 4: Backend Authorization Fixes

#### Step 4.1: Add Ownership Check to coordinateArrival
**File:** `Modules/TripManagement/Http/Controllers/Api/Driver/TripRequestController.php`

```php
public function coordinateArrival(Request $request) {
    $validator = Validator::make($request->all(), [
        'trip_request_id' => 'required|uuid',
        'is_reached' => 'required|in:coordinate_1,coordinate_2,destination',
    ]);

    if ($validator->fails()) {
        return response()->json([...], 422);
    }

    // Add ownership check
    $trip = TripRequest::where('id', $request->trip_request_id)
        ->where('driver_id', auth('api')->id())
        ->first();

    if (!$trip) {
        return response()->json(responseFormatter(DEFAULT_403), 403);
    }

    // ... rest of the method
}
```

#### Step 4.2: Add Ownership Check to editScheduledTrip
**File:** `Modules/TripManagement/Http/Controllers/Api/Customer/TripRequestController.php`

```php
public function editScheduledTrip(Request $request, $trip_request_id) {
    $trip = $this->tripRequestService->findOneBy([
        'id' => $trip_request_id,
        'customer_id' => auth('api')->id(),  // Add this
    ]);

    if (!$trip) {
        return response()->json(responseFormatter(TRIP_REQUEST_404), 404);
    }
    // ... rest
}
```

#### Step 4.3: Add Rate Limiting to Chat
**File:** `Modules/ChattingManagement/Routes/api.php`

```php
Route::middleware(['auth:api', 'throttle:60,1'])->group(function () {
    Route::post('send-message', [ChattingController::class, 'sendMessage']);
});
```

## 5. TESTING AND VALIDATION

### Customer App
```bash
cd drivemond-user-app-3.1/HexaRide-User-app-release-3.1
flutter pub get
flutter analyze --no-fatal-infos
flutter test test/vito_flows_test.dart
```

### Driver App
```bash
cd drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1
flutter pub get
flutter analyze --no-fatal-infos
flutter test test/vito_flows_test.dart
```

### Backend
```bash
cd drivemond-admin-new-install-3.1
php artisan test --filter=VitoFlowTest
./vendor/bin/phpstan analyse --level=0 \
  Modules/AuthManagement/Http/Controllers/Api/VitoAuthController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/TripRequestController.php
```

## Success Criteria

1. ✅ FCM token refresh works after Android/iOS token rotation
2. ✅ Pusher channels properly cleaned up on logout
3. ✅ All buttons show loading state during API calls
4. ✅ Error messages are user-friendly and localized
5. ✅ Driver online state persists across app restarts
6. ✅ Failed uploads show error dialog with retry option
7. ✅ Withdrawal requires confirmation
8. ✅ Wallet/earnings balance refreshes after transactions
9. ✅ Map handles (0,0) fallback gracefully
10. ✅ Backend endpoints validate ownership before updates

## BRANCH: vito

All changes should be committed to branch `vito`.
