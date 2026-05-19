# Contributing to VITO

## Constraints

1. **Preserve existing behavior** — Do not alter DriveMond base screens, UI, or architecture. Only add/fix VITO-specific functionality.
2. **No OTP** — VITO uses username + 6-digit PIN authentication. No phone numbers, no OTP.
3. **Atomic acceptance** — All ride/parcel/mart acceptance must use `WHERE driver_id IS NULL` to prevent race conditions.
4. **No hardcoded secrets** — API keys, Stripe keys, and credentials must come from environment variables or GitHub Secrets.
5. **Localization** — All user-facing strings must be in translation files (`assets/language/` for Flutter, `lang/` for Laravel). EN and ES must have parity.
6. **PIN security** — PINs are bcrypt-hashed; never stored or logged in plaintext.

## Development Workflow

1. Create a feature branch from `master`.
2. Make changes following existing code conventions.
3. Run static analysis:
   ```bash
   # Flutter
   flutter analyze --no-fatal-infos

   # Laravel
   ./vendor/bin/phpstan analyse --level=0
   ```
4. Run tests:
   ```bash
   php artisan test --filter=VitoFlowTest
   flutter test test/vito_flows_test.dart
   ```
5. Ensure EN/ES string parity for any new user-facing strings.
6. Open a PR against `master`. CI will run automatically.

## Code Style

- Flutter: Follow existing GetX patterns, use `.tr` for all strings.
- Laravel: Follow existing module structure (nwidart/laravel-modules).
- Use existing design tokens and widget components.

## Test Coverage

Required test areas:
- QR token generation/validation/expiry
- Client & driver registration with token
- PIN login with lockout
- Atomic ride acceptance (race condition)
- Mart product CRUD
- Wallet/Stripe payment intent
- Webhook idempotency
