# Vito — Authentication Audit (v2.18.0)

Full review of the auth surface: backend (`VitoAuthController`, `ClientOtpAuthController`,
`QrTokenController`, `AuthManagement/Routes/api.php`, `CustomerService::create`, users migrations) and both
Flutter apps.

## ✅ Sound (verified)
- **QR / invite gate** — single-use, `lockForUpdate`, redeemed atomically inside a `DB::transaction`;
  expiry + revoked + role checks enforced (`pinRegister`).
- **PIN security** — stored as `pin_hash` (`Hash::make`); `password` column is bcrypt'd in
  `CustomerService::create` (no plaintext); 5 failed attempts → temporary block with timed unlock.
- **Uniqueness** — `users.username` and `users.phone` both have UNIQUE constraints.
- **OTP** — 6-digit, hashed at rest in `vito_otps`, 5-min expiry, 30s resend cooldown, 5-attempt lock;
  sends via the admin-configured SMS gateway (Twilio/Nexmo/2Factor/MSG91/Releans) with env-Twilio + log
  fallbacks.
- **Session reset on login** — `pinLogin` revokes all prior tokens.
- **Rate limiting** — `throttle:20,1` on pin/OTP auth, `throttle:10,1` on QR endpoints; QR generate/revoke
  require `scope:AccessToSuperAdmin`.

## 🔧 Fixed in v2.18.0
- **`changePin` now revokes other sessions.** Previously a PIN change left tokens on other devices valid
  (a stolen/old session survived). It now revokes every token except the current one, so other devices
  must re-authenticate with the new PIN. (Tested: `test_change_pin_revokes_other_sessions`.)
- **Username is trimmed** in `pinLogin` / `pinRegister`, so leading/trailing whitespace can't cause a
  register-vs-login mismatch. (Tested: `test_pin_login_trims_username`.)

## 📋 Open items (need a product decision — intentionally not changed)
- **No self-service "forgot PIN".** PIN accounts register with username + PIN + QR and have **no phone on
  file**, so the SMS-OTP flow can't identify them for a reset; the sign-in "forgot password" link is the
  legacy phone/password flow and does not apply to PIN users. There is currently **no recovery path** for a
  forgotten PIN. Recommended options:
  1. **Admin-assisted PIN reset** — an action in the admin panel to reset a user's PIN (smallest, safest).
  2. **Optional phone capture at PIN registration** — then reuse the existing OTP flow for self-service
     reset (`reset-pin` endpoint: verify a recent OTP for the phone → set new `pin_hash` → revoke tokens).
  Say which you want and I'll build it.
- **OTP registration is gate-free (by your decision).** A verified phone creates an account without a QR
  token, so the "must scan QR/invite" rule applies to PIN signup only. Kept as-is per your call.
- **No Passport token expiry** configured (long-lived tokens). Standard for mobile; flagged only. Can add
  `Passport::personalAccessTokensExpireIn(...)` + refresh if you want rotation.
- **Legacy auth retained (by your decision).** The pre-Vito `AuthController` routes (phone/password,
  social-login, firebase-OTP, reset-password) remain live alongside PIN + OTP. Kept; bugs only.

## Verification
- `php artisan test --filter=VitoFlowTest` → **95 passed**.
- `phpstan analyse --level=0` on `VitoAuthController.php` → **no errors**.
