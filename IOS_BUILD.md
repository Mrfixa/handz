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

To extend the workflow for signed distribution: import the `.p12` + profile (e.g.
`apple-actions/import-codesign-certs`), drop in `GoogleService-Info.plist`, then
`flutter build ipa --export-options-plist=ExportOptions.plist` and upload to TestFlight with
`xcrun altool`/`fastlane`. Add the secrets (`IOS_DIST_CERT_P12`, `IOS_CERT_PASSWORD`,
`IOS_PROVISION_PROFILE`, `APP_STORE_CONNECT_*`) to the repo first.

## Known limitation — driver app ML Kit pod conflict (iOS)

The **driver** app currently cannot resolve iOS pods because two plugins pull in
**incompatible ML Kit native versions**:

- `google_mlkit_commons` (face verification) → `MLKitVision (~> 10.0)`
- `mobile_scanner` (QR token scanner) → `GoogleMLKit/BarcodeScanning 7.0` → `MLKitBarcodeScanning 6.0`
  → `MLKitVision (~> 8.0)`

The `8.x` vs `10.x` ranges don't overlap, so CocoaPods can't satisfy both. This does not affect
Android (the shipping platform) because the ML Kit Android artifacts resolve independently. Fixing iOS
means **realigning the ML Kit plugin versions** in the driver `pubspec.yaml` (e.g. bump `mobile_scanner`
to a release whose barcode pod uses `MLKitVision ~> 10`, or pin `google_mlkit_*` to a release using
`~> 8`) and re-running `flutter pub get` + the full Android build to confirm no regression. Deferred
until that dependency decision is made — the **user app** iOS build proves the pipeline end to end.

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
