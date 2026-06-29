# AUDIT_TRACKER.md — living audit ledger

Single source of truth for end-to-end audit findings across the three sub-projects
(Laravel backend, Flutter user app, Flutter driver app). Each finding is recorded once,
with a stable ID, a severity, the area, the finding, a status, and the fix commit (short SHA).

**Status legend**
- `fixed` — code change landed; Fix column has the commit SHA.
- `accepted` — reviewed and deliberately not changed; the Finding column states why (false positive,
  by-design, or out-of-scope per the wave boundaries).
- `open` — confirmed, not yet fixed.

**ID prefixes** — `B` backend, `U` user app, `D` driver app, `C` cross-cutting / CI.

---

## Findings

| ID | Severity | Area | Finding | Status | Fix |
|----|----------|------|---------|--------|-----|
| B1 | High | Backend / TripManagement (safety alert) | Customer & Driver `SafetyAlertController` `resend`/`markAsSolved`/`show`/`delete` looked up the alert by `trip_request_id` + `user_type` only — **not** scoped to the authenticated user. Any authenticated customer/driver could view, resend, resolve, or delete another user's panic-button alert by supplying a different trip UUID (IDOR). Fixed by scoping every lookup to `sent_by = auth user` (safe because `SafetyAlertService::create` forces `sent_by` to the auth user). | fixed | `e7c5c67` |
| B2 | Medium | Backend / TripManagement (parcel refund) | `ParcelRefundController::createParcelRefundRequest` never verified the authenticated customer owned `trip_request_id`. A customer could open a (pending, admin-reviewed) refund and push a "amount deducted" notification to the driver against an arbitrary parcel trip by UUID. Added a `TripRequest where id + customer_id = auth` ownership guard (404 otherwise). | fixed | `e7c5c67` |
| B3 | High | Backend / AuthManagement (registration) | Legacy self-registration passed `$request->all()` straight into `customerService/driverService->create`, allowing mass-assignment of privileged `User` columns (`loyalty_points`, `role_id`, `user_level_id`, …) that aren't in the register validation rules but are `fillable`. Replaced with `$request->except([...privileged list...])` at both register call sites. | fixed | `7cedf07` |
| B4 | Medium | Backend / config | `env('APP_MODE')` is read at ~100 legacy call sites; under `php artisan config:cache` the `.env` is no longer loaded so these return `null`, which (e.g.) forces trip/parcel OTPs to the demo value `'0000'` in a cached prod deploy. Captured `app.app_mode` in `config/app.php` and re-hydrated `putenv`/`$_ENV`/`$_SERVER` in `AppServiceProvider::register()`. | fixed | `2a9e817` |
| U1 | Medium | User app / chat | Chat attachment file-name rendering used `substring(fileName.length - 7)` with no length guard → `RangeError` crash on names shorter than 7 chars. Replaced with a length-guarded ternary. | fixed | `ec6d46a` |
| D1 | Medium | Driver app / auth | Verification screen masked the phone with an unguarded substring → `RangeError` on short numbers. Now `number.length >= 8 ? mask : number`. | fixed | `ec6d46a` |
| D2 | Medium | Driver app / chat | Same unguarded `substring(fileName.length - 7)` crash in the admin-conversation bubble. Length-guarded. | fixed | `ec6d46a` |
| D3 | Medium | Driver app / chat | A *third* unguarded `substring(fileName!.length - 7)` (missed by D1/D2) survived in `features/chat/widgets/message_bubble_widget.dart` — `RangeError` crash when a chat attachment file name is shorter than 7 chars. Replaced with the same guarded ternary used by the user app's `message_bubble.dart`. | fixed | `0d2fc9a` |
| D4 | Med (UX) | Driver app / vehicle form | Vehicle **brand / model / category** used plain `DropdownButton`s — long, scroll-only, unsearchable lists. Converted all three to a reusable `SearchableDropdownField` (type-to-filter via `flutter_typeahead`): typing a brand filters to matching brands; selecting a brand loads only that brand's models; typing a model filters to matching models. Localized in en/es. | fixed | `927d885` |
| C1 | Medium | CI / iOS | `build-ios.yml` failed at `flutter create --platforms=ios` (`Failed to copy plugin firebase_messaging … build/ios/SourcePackages … No such file or directory`). Flutter 3.29+ enables Swift Package Manager by default; the implicit `pub get` rsyncs the Firebase plugin into a not-yet-created `SourcePackages` dir and aborts. Disabled SPM (`flutter config --no-enable-swift-package-manager`) so the build stays on CocoaPods. | fixed | `9c76e78` |
| C2 | Medium | CI / iOS | After C1, `pod install` failed: `mobile_scanner` (user) and `google_mlkit_commons` (driver) require iOS ≥ 15.5 but the Runner target was 15.0. Bumped the Podfile platform + post_install **and** the Runner `IPHONEOS_DEPLOYMENT_TARGET` (Flutter validates each plugin's minimum against the app target) to 16.0. | fixed | `4e858ee` |
| C3 | Med | CI / iOS | After C2 the **user** app reached the Xcode build but both apps hit `connectivity_plus 7.2.0` using `NWPath.isUltraConstrained` (iOS 18 SDK only) — the macos-14 Xcode was too old. Moved the runner to `macos-15` and pinned the newest Xcode via `setup-xcode` (`latest-stable`) to get the iOS 18.4+ SDK that defines `isUltraConstrained`. **Verified:** the user-app iOS job builds green (run 28352365183); only the driver job remains, isolated to C4. | fixed | `209496a` |
| C4 | Med | CI / iOS (driver) | Driver `pod install` couldn't resolve ML Kit: `google_mlkit_commons` needs `MLKitVision ~>10` while `mobile_scanner 6`→`GoogleMLKit/BarcodeScanning 7.0`→`MLKitVision ~>8` (non-overlapping). Fixed by bumping `mobile_scanner` to `^7.0.0`, which uses **Apple Vision** on iOS and drops the GoogleMLKit pod entirely — removing one side of the conflict. The QR scanner screen uses only APIs unchanged in 7.x (`facing`/`detectionSpeed`/`onDetect`/`analyzeImage`/`toggleTorch`), so no Dart changes were needed. | fixed | _this batch_ |

## Accepted (reviewed, intentionally not changed)

| ID | Severity | Area | Finding & rationale | Status |
|----|----------|------|---------------------|--------|
| A1 | Low | Both apps / Firebase | `AIzaSy…` Android API key hardcoded in `lib/main.dart`. This is the Firebase **Android** API key, which is public-by-design (restricted by SHA-1 + package name in the Firebase console, not a secret). No action. | accepted |
| A2 | Low | Both apps / null-safety | ~286 `!.data!` force-unwraps across both apps. Sampled on the critical paths (payment, order create, auth) — the overwhelming majority are immediately preceded by a null/`isEmpty`/`statusCode` guard. Blanket-rewriting them is churn with regression risk and no test coverage; left as-is. Genuinely unguarded crash sites are tracked individually (e.g. U1/D1/D2). | accepted |
| A3 | Low | Backend / FareManagement | `TripFareController@store` uses `$request->all()`. It is an **admin-only** web controller (already-privileged actor, no privilege escalation surface) and the `TripFare` model has no sensitive columns. Not user-reachable. | accepted |
| A4 | Info | Backend / config | The ~100 `env('APP_MODE')` runtime call sites are not individually rewritten; B4's provider re-hydration makes them all correct under both cached and non-cached config without touching each site. | accepted |
| A5 | Info | Backend / Vito API | IDOR sweep of the customer/driver Vito surface: mart **products** are a public catalog (`MartProduct::find` is intentionally unscoped); mart **orders**, rides, and parcels are owner-scoped in their services. Safety alert (B1) and parcel refund (B2) were the gaps and are fixed. | accepted |

---

## How findings are verified

- **Backend:** `php artisan test --filter=VitoFlowTest` (124 passing) stays green after each change;
  PHPStan level 0 clean on the edited controllers. `php -l` on every edited PHP file.
- **Flutter:** no local Flutter/macOS runner — changes are verified by the CI debug-APK build
  (`vito-ci.yml`), which compiles the edited widgets/screens, plus `flutter analyze` and unit tests.
- **iOS:** the `build-ios.yml` macOS workflow builds an unsigned release `.app` for both apps.
