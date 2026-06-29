# iOS Build — Vito User & Driver apps

The apps were Android-only (no committed `ios/` directory). iOS is now supported via the
`.github/workflows/build-ios.yml` workflow, which on a **macOS runner**:

1. Scaffolds the iOS platform with `flutter create --platforms=ios` (never touches `lib/`).
2. Sets the bundle identifier (`com.sixamtech.hexarideuser` / `com.sixamtech.hexariderider`).
3. Writes a `Podfile` pinned to **iOS 16.0** with `use_frameworks! / use_modular_headers!`
   (required by `flutter_stripe` and the Firebase pods) and bumps the Runner target to 16.0
   (`google_mlkit_commons` / `mobile_scanner` require iOS ≥ 15.5).
4. Adds the required `Info.plist` usage descriptions (location, camera, photo library, microphone).
5. Configures the Mapbox downloads token in `~/.netrc` (CocoaPods needs it to fetch MapboxMaps).
6. Builds an **unsigned** release `.app` (`flutter build ios --release --no-codesign`) and uploads
   it as an artifact.

This proves both apps **compile and link for iOS**. Run it from the Actions tab
(*Build iOS* → *Run workflow*) or by pushing a change to the workflow file.

## What's required for a *signed* build (TestFlight / App Store)

These need credentials only the app owner has, so they're intentionally out of scope for the
unsigned CI build:

| Need | Where |
|------|-------|
| Apple Developer account + App IDs for both bundle ids | developer.apple.com |
| Distribution certificate (`.p12`) + provisioning profiles | imported on the runner / Xcode |
| `GoogleService-Info.plist` (per app) for Firebase on iOS | `ios/Runner/GoogleService-Info.plist` |
| iOS Google Maps API key | `GMSServices.provideAPIKey(...)` in `ios/Runner/AppDelegate.swift` |
| APNs key/cert for push (firebase_messaging) | Apple Developer → Keys |

### Signed-release workflow (scaffolded)

`.github/workflows/release-ios.yml` implements the signed lane end to end: pick the app +
Team ID, it imports the cert/profile, drops in `GoogleService-Info.plist`, injects the iOS Maps
key into `AppDelegate.swift`, writes `ExportOptions.plist`, runs
`flutter build ipa --export-options-plist=…`, and uploads to TestFlight. It is **manual-dispatch
only** (Actions → *Release iOS (signed)* → *Run workflow*) and will not run — and cannot fail — until
the secrets below exist. It is therefore **not CI-verified here**; the app owner runs it once the
Apple credentials are in place.

| Secret | What it is |
|--------|------------|
| `IOS_DIST_CERT_P12` | base64 of the Apple **Distribution** `.p12` |
| `IOS_CERT_PASSWORD` | password for that `.p12` |
| `IOS_PROVISION_PROFILE` | base64 of the App Store `.mobileprovision` for the app's bundle id |
| `GOOGLE_SERVICE_INFO_PLIST` | base64 of the app's `GoogleService-Info.plist` (Firebase on iOS) |
| `IOS_MAPS_API_KEY` | iOS Google Maps key (injected into `AppDelegate.swift`) |
| `APP_STORE_CONNECT_KEY_ID` / `APP_STORE_CONNECT_ISSUER_ID` / `APP_STORE_CONNECT_KEY` | App Store Connect API key (id, issuer, base64 `.p8`) for the TestFlight upload |

> The Podfile + Info.plist usage-description steps are intentionally not duplicated in
> `release-ios.yml` — copy them from `build-ios.yml` (kept as the single source of truth) if they
> change. APNs (push) still needs an APNs key registered in the Apple Developer portal.

## Resolved — driver app ML Kit pod conflict (iOS)

The **driver** app previously couldn't resolve iOS pods because two plugins pulled in
**incompatible ML Kit native versions**:

- `google_mlkit_commons` (face verification) → `MLKitVision (~> 10.0)`
- `mobile_scanner` 6 (QR token scanner) → `GoogleMLKit/BarcodeScanning 7.0` → `MLKitBarcodeScanning 6.0`
  → `MLKitVision (~> 8.0)`

The `8.x` vs `10.x` ranges don't overlap, so CocoaPods couldn't satisfy both. **Fixed** by bumping
`mobile_scanner` to `^7.0.0`: the 7.x line uses **AVFoundation / Apple Vision** for iOS barcode
scanning and no longer depends on GoogleMLKit, so the only remaining `MLKitVision` consumer is
face-detection (`~> 10`) and the conflict disappears. The driver QR scanner screen
(`qr_scanner_screen.dart`) uses only APIs that are unchanged between 6.x and 7.x
(`facing`, `detectionSpeed`, `MobileScanner(controller:, onDetect:)`, `analyzeImage`, `toggleTorch`),
so no Dart migration was required. **Runtime QR-scan behavior on the driver app should still be smoke-
tested on a device before release**, since CI can only verify compilation, not camera/scan behavior.

## Notes / known iOS considerations for this plugin set
- **Deployment target 16.0** — `google_mlkit_commons` (driver) and `mobile_scanner` (user) require
  iOS ≥ 15.5; 16.0 also satisfies `firebase_core`, `flutter_stripe`, `mapbox_maps_flutter`,
  `google_maps_flutter`. Flutter validates each plugin's minimum against the **app target's**
  deployment version, so the Runner `IPHONEOS_DEPLOYMENT_TARGET` is bumped too (not just the Podfile).
- **`use_frameworks!`** — `flutter_stripe` will not build with static linkage.
- **Mapbox** — needs a secret downloads token (`MAPBOX_DOWNLOADS_TOKEN`) in `~/.netrc`, same secret
  the Android build uses.
- **Runtime keys** — Maps/Stripe/Mapbox runtime keys are passed via `--dart-define`; Firebase needs
  `GoogleService-Info.plist` at runtime (absent → Firebase init no-ops, build still succeeds).
