# 1. OBJECTIVE

Comprehensive end-to-end audit of both Flutter apps (User + Driver) and the Laravel backend. Fix all issues to achieve 10/10 quality across:
- Authentication flows (QR token → registration → login → PIN)
- All screens and user flows
- Backend API integration
- User experience and edge cases
- Then push to v1.7

# 2. CONTEXT SUMMARY

**Three-part system:**
- `drivemond-admin-new-install-3.1/` - Laravel 12 backend
- `drivemond-user-app-3.1/HexaRide-User-app-release-3.1/` - Flutter customer app
- `drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1/` - Flutter driver app

**Authentication Flow:**
1. Users scan QR code or enter invitation token
2. Register with username + PIN (bcrypt-hashed)
3. Login with username + PIN
4. API auth via Laravel Passport with scopes

**Backend Components:**
- VitoFlowTest: SQLite in-memory tests covering full flows
- PHPStan level 0: Static analysis on Vito controllers

# 3. APPROACH OVERVIEW

Phase 1: Backend Audit
- Run VitoFlowTest + PHPStan
- Fix all backend issues

Phase 2: Flutter Apps Audit
- Explore both apps' structure
- Analyze authentication flow implementation
- Check all screens for completeness and UX
- Verify API integration and error handling
- Run flutter analyze

Phase 3: Fix & Release
- Fix all issues
- Final validation
- Create and push v1.6 tag

# 4. IMPLEMENTATION STEPS

## Phase 1: Backend Audit

### Step 1.1: Run Backend Tests
```
cd /workspace/project/handz/drivemond-admin-new-install-3.1
php artisan test --filter=VitoFlowTest
```

### Step 1.2: Run PHPStan
```
./vendor/bin/phpstan analyse --level=0 \
  Modules/AuthManagement/Http/Controllers/Api/VitoAuthController.php \
  Modules/AuthManagement/Http/Controllers/Api/QrTokenController.php \
  Modules/TripManagement/Http/Controllers/Api/Customer/VitoMartController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/VitoTripController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/VitoParcelController.php \
  Modules/TripManagement/Http/Controllers/Api/Driver/VitoMartDriverController.php \
  Modules/TripManagement/Http/Controllers/Api/Admin/VitoMartAdminApiController.php \
  Modules/Gateways/Http/Controllers/Api/VitoStripeController.php
```

### Step 1.3: Fix Backend Issues
- Fix VitoFlowTest failures
- Fix PHPStan errors
- Re-run tests until all pass

## Phase 2: Flutter Apps Audit

### Step 2.1: Explore App Structures
For each app (user + driver):
- List all screens under `lib/features/*/screens/`
- List all controllers under `lib/features/*/controllers/`
- List all repositories under `lib/features/*/domain/repositories/`
- Check `lib/helper/di_container.dart`

### Step 2.2: Analyze Authentication Flow
Check in order:
1. QR Token/Token Gate Screen - Token validation
2. Registration Screen - Username, PIN creation
3. Login Screen - Username + PIN login
4. Session Management - Token storage, refresh

Verify per app:
- All auth endpoints wired correctly
- Error handling for network failures
- Input validation (username format, PIN length)
- Loading states during auth
- Redirect logic after auth

### Step 2.3: Check All Screens & Flows

**User App:**
- Home/Ride booking
- Driver selection & ride request
- Trip tracking & chat
- Mart browse → order → tracking → review
- Profile & settings
- Notifications

**Driver App:**
- Online/Offline toggle
- Pending ride requests
- Accept/reject rides
- Trip navigation & status updates
- Mart delivery flow
- Earnings & payout
- Profile & documents

For each screen verify:
- Proper loading/error/empty states
- Back navigation works
- Pull-to-refresh where appropriate
- No placeholder/TODO text
- Translations exist (EN, ES, AR)

### Step 2.4: Run Flutter Analyze
```
cd drivemond-user-app-3.1/HexaRide-User-app-release-3.1
flutter analyze --no-fatal-infos

cd drivemond-driver-app-3.1/HexaRide-Driver-app-release-3.1
flutter analyze --no-fatal-infos
```

### Step 2.5: Check API Integration
Verify all API calls:
- Correct HTTP methods (GET/POST/PUT)
- Handle timeouts gracefully
- Parse responses correctly
- Handle 401 redirect to login
- Handle 422/500 with user-friendly messages

## Phase 3: Fix & Release

### Step 3.1: Compile Fixes
- Group by priority (critical > important > nice-to-have)
- Apply all fixes systematically

### Step 3.2: Final Validation
- Backend tests pass
- PHPStan zero errors
- Flutter analyze clean

### Step 3.3: Create v1.6 Release
```bash
git checkout -b claude/v1.6-full-audit
git add -A
git commit -m "v1.6: Full end-to-end audit"
git tag v1.6
git push origin claude/v1.6-full-audit
git push origin v1.6
```

# 5. TESTING AND VALIDATION

**Backend Success:**
- php artisan test --filter=VitoFlowTest: All tests green
- PHPStan level 0: Zero errors on Vito controllers

**Flutter Success:**
- flutter analyze --no-fatal-infos: No errors in either app
- All screens have proper loading/error/empty states
- All strings have EN/ES/AR translations
- Auth flows complete without errors
- API calls handle all error cases gracefully
- Navigation works correctly throughout

**Final Success:**
- All changes committed
- v1.6 tag pushed to origin
