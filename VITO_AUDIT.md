# Vito Admin ↔ Apps Audit (v2.11.0)

Cross-check of the admin panel against the customer & driver apps, focused on:
test logins, SMS-on-registration, QR-code image download, and admin-flow gaps.

## ✅ Fixed in this release

### 1. Test logins now actually work in both apps
- **Backend (already present):** `DefaultUsersSeeder` (called by `DatabaseSeeder`) seeds:
  - Customer — username `customer`, PIN `123456`
  - Driver — username `driver`, PIN `123456` (verified)
  - Admin — `admin@admin.com`, password `12345678` (`AdminUserSeeder`)
- **Bug found & fixed:** the **customer app** sign-in screen labelled the first field
  "phone", showed a country-code picker, and validated it with `isPhoneNumber()` —
  but `pin-login` expects a **username**. So `customer` failed client-side validation
  ("phone not valid") and the seeded account could not log in.
  `sign_in_screen.dart` is now a proper **username + 6-digit PIN** form
  (`login('', username, pin)`). The **driver app** was already correct.

### 2. SMS on registration — now production-ready
- **Backend (already present & tested):** `ClientOtpAuthController`
  (`send-otp` → `otp-verification` → `registration-from-otp`) with hashed OTPs in
  `vito_otps`, 30s resend cooldown, 5-attempt lock, 5-min expiry. App side
  (`AuthController.sendOtp/otpVerification/registrationFromOtp`) is wired to these.
- **Bug found & fixed:** OTP SMS was sent **only via Twilio env vars**, ignoring the
  admin's **Business Settings → SMS Gateway** config. `dispatchSms()` now calls
  `SMSGateway::send()` first (Twilio/Nexmo/2Factor/MSG91/Releans, using each gateway's
  template), then falls back to Twilio-env, then logs. The admin SMS settings page now
  actually drives registration SMS.
- **To go live:** enable a gateway in Admin → Business Settings → SMS Gateway (or set
  `TWILIO_ACCOUNT_SID/AUTH_TOKEN/FROM_NUMBER`). Without either, OTPs are only logged.

### 3. QR code download as image — fixed for GD-only servers
- Admin QR page (`Admin → QR Invitation Tokens`, linked in sidebar) generates tokens,
  shows an inline QR, copy, revoke, and **download**.
- **Bug found & fixed:** `download()` used `QrCode::format('png')`, which requires the
  **Imagick** PHP extension; `composer.json` only requires `ext-gd`, so the Download
  button 500'd on typical servers while the on-screen QR (SVG) worked. It now serves
  **PNG when Imagick is available, else SVG** — the download always works.

### 4. Deploy blocker — duplicate `vito_otps` migration
- Two migrations created `vito_otps`; the later one
  (`2026_06_03_100002_create_vito_otps_table`) ran `Schema::create` **unguarded**, so a
  real `php artisan migrate` (incl. the v1.5→v2.10 upgrade) failed with "table already
  exists". Tests didn't catch it (they build schema manually). Now guarded with
  `Schema::hasTable()`.

## ⚠️ Recommendations (not yet changed — need your call)

- **Legacy auth surface still present.** The apps still ship Firebase-OTP and
  phone/password screens, and the admin still exposes **Login Settings / Firebase-OTP /
  OTP-login-attempts** config. Now that PIN + SMS-OTP are the real flows, these are
  partly redundant. Options: keep (harmless, more flexibility) or hide to reduce admin
  confusion. Say the word and I'll prune the dead menus/screens.
- **In-app demo-login hint.** Test accounts work but aren't surfaced. Optional: show a
  "Demo: customer / 123456" hint on the sign-in screen when demo mode is on.

## Verification
- Backend: `php artisan test --filter=VitoFlowTest` → **92 passed** (incl. OTP flow +
  default-users seeder).
- Apps: covered by CI (`flutter analyze` + `vito_flows_test.dart` language-parity) and
  the UI-Goldens artifact.
