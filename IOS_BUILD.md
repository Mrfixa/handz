# iOS Build — Vito User & Driver apps

The apps were Android-only (no committed `ios/` directory). iOS is now supported via the
`.github/workflows/build-ios.yml` workflow, which on a **macOS runner**:

1. Scaffolds the iOS platform with `flutter create --platforms=ios` (never touches `lib/`).
2. Sets the bundle identifier (`com.sixamtech.hexarideuser` / `com.sixamtech.hexariderider`).
3. Writes a `Podfile` pinned to **iOS 15.0** with `use_frameworks! / use_modular_headers!`
   (required by `flutter_stripe` and the Firebase pods).
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

## Notes / known iOS considerations for this plugin set
- **Deployment target 15.0** — required by current `firebase_core` (Firebase iOS SDK) and safe for
  `flutter_stripe`, `mapbox_maps_flutter`, `google_maps_flutter`.
- **`use_frameworks!`** — `flutter_stripe` will not build with static linkage.
- **Mapbox** — needs a secret downloads token (`MAPBOX_DOWNLOADS_TOKEN`) in `~/.netrc`, same secret
  the Android build uses.
- **Runtime keys** — Maps/Stripe/Mapbox runtime keys are passed via `--dart-define`; Firebase needs
  `GoogleService-Info.plist` at runtime (absent → Firebase init no-ops, build still succeeds).
