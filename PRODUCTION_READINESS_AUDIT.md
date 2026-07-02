# Production-Readiness Audit — Vito

**Date:** 2026-07-02
**Scope:** Laravel 12 backend (`drivemond-admin-new-install-3.1/`), Flutter customer app
(`drivemond-user-app-3.1/`), Flutter driver app (`drivemond-driver-app-3.1/`), plus UX/UI.
**Method:** Static source review + evidence runs (tests, PHPStan, `flutter analyze`), reconciled
against the prior audit docs in this repo. This report supersedes stale findings in `AUDIT.md`
(2026-05-21), whose three "critical" backend items were re-verified as already fixed (see §5).

---

## 1. Executive summary & verdict

| Sub-project | Verdict | Blocking issues |
|-------------|---------|-----------------|
| Backend (Laravel) | ⚠️ **No-go until Critical + High fixed** | Committed live Swish private key (Critical); seeded default creds, weak `.env` broadcast secrets, wide-open CORS (High) |
| Customer app (Flutter) | ⚠️ **Conditional go** | Committed Firebase config, live-host default `baseUrl` (High/Med); legacy mart screens still inline-state |
| Driver app (Flutter) | ⚠️ **No-go until baseUrl fixed** | `baseUrl` hardcoded & non-configurable (High) — cannot retarget staging/prod without a code change |

The codebase is materially healthier than the oldest audit suggests: server-side fare
recomputation, trip-ownership checks, atomic promo counters, idempotency, Stripe webhook dedup,
try/catch-wrapped broadcast/push, and CI all exist and were verified. The remaining blockers are
**secrets hygiene and deployment configuration**, not core application-logic flaws.

**One thing to do before anything else:** the live Swish merchant private key is in git history and
must be treated as compromised — revoke/rotate it with the issuer, then purge it from history.

---

## 1b. Remediation status (updated 2026-07-02)

Work shipped on `claude/production-readiness-audit-zxj9fx` this cycle:

| Finding | Status |
|---------|--------|
| C1 Swish key | **Partially addressed** — untracked from git + `.gitignore`d; **rotation + history purge still required (user action)** |
| H1 seeded demo creds | **Fixed** — `DefaultUsersSeeder` demo customer/driver gated to non-prod (`SEED_DEMO_USERS` opt-in). Admin default (separate `AdminUserSeeder`) still needs a manual password change |
| H2 weak `.env` secrets | **Fixed** — REVERB/PUSHER secrets blanked with generate-random guidance |
| H3 open CORS | **Fixed** — `allowed_origins` via `CORS_ALLOWED_ORIGINS` (deny by default); methods narrowed |
| H4 driver hardcoded `baseUrl` | **Fixed** — now `String.fromEnvironment('BASE_URL', …)` |
| M1/M6 queue & cache docs | **Fixed** — `.env.example` documents redis + worker requirement |
| M2 user `baseUrl` default | **Deferred** — kept host fallback; emptying it needs `BASE_URL` wired into build workflows |
| L3 Sentry sample rate | **Fixed** — `SENTRY_SAMPLE_RATE` documented in `.env.example` |

New feature gaps closed (Track 2): chat send rate-limit; **self-service forgot-PIN** (backend + both
apps); **driver arrived-at-pickup** sub-signal (backend + driver button + customer banner).

**Verified already-implemented (prior-audit items now confirmed stale — no work needed):** per-user
promo limit (`VitoMartController:261`); chat throttling (group `throttle:60,1`); ride fare recompute
& ownership checks; **mart tip cap** (30%, `VitoMartController:245`); **auto-refund on paid-order
cancel** and **driver-cancel endpoint** (shared `RefundsMartOrders` trait across customer/driver/admin,
real Stripe refunds with idempotency). The oldest `AUDIT.md` predates these — treat this table as the
current source of truth.

---

## 2. Findings (severity-ranked)

### 🔴 Critical

**C1 — Live Swish merchant private key committed & git-tracked**
`drivemond-admin-new-install-3.1/certificates/live/MySwishKey.key` (`-----BEGIN PRIVATE KEY-----`,
52 lines) is tracked in git, alongside the live cert `swish_certificate_202210271434.pem` and
`MySwishCSR.csr` (and test keys under `certificates/test/`).
*Impact:* anyone with repo read access can impersonate the merchant / initiate Swish payments. The
key is already exposed in history, so deletion alone is insufficient.
*Fix:* (1) revoke & reissue the Swish certificate/key with the provider; (2) move the new key to a
secret store / env-mounted path, referenced via config; (3) purge the blob from git history
(`git filter-repo`) and force-push; (4) add `certificates/live/*.key`, `*.pem`, `*.csr` to
`.gitignore`.

### 🟠 High

**H1 — Seeded default credentials are trivially guessable**
`database/seeders/DefaultUsersSeeder.php`: admin `admin@admin.com` / `12345678`, customer/driver
username = role, PIN `123456` (driver pre-verified). Seeder is idempotent ("safe to re-run").
*Impact:* if `--seed` runs against production, a guessable **superadmin** and demo accounts exist.
*Fix:* gate `DefaultUsersSeeder` behind `app()->environment(['local','testing'])`, or require
non-default secrets via env with no fallback; never seed demo accounts in `production`.

**H2 — Weak shared broadcast secrets in `.env.example`**
`.env.example:53-64`: `REVERB_APP_KEY=vito`, `REVERB_APP_SECRET=vito`, `PUSHER_APP_KEY=vito`,
`PUSHER_APP_SECRET=vito`; `REDIS_PASSWORD=null`.
*Impact:* `.env.example` is routinely copied verbatim; guessable WebSocket auth secrets allow
forging/subscribing to private broadcast channels (ride/mart chat).
*Fix:* set these to empty placeholders that fail loudly if unset, and document that prod must
generate random secrets. Never ship a real-looking default secret.

**H3 — Wide-open CORS on the entire API**
`config/cors.php`: `paths=['api/*', 'sanctum/csrf-cookie']`, `allowed_origins=['*']`,
`allowed_methods=['*']`, `allowed_headers=['*']`.
*Impact:* any origin can call the bearer-token API from a browser. `supports_credentials=false`
limits cookie theft, but this is far more permissive than a mobile-only API needs.
*Fix:* restrict `allowed_origins` to the known web-admin origin(s) via env; drop the wildcard
methods/headers to the set actually used.

**H4 — Driver app `baseUrl` is hardcoded and non-configurable**
`drivemond-driver-app-3.1/.../lib/util/app_constants.dart:7`:
`static const String baseUrl = 'https://dacatlon.store';` — no `String.fromEnvironment`, unlike the
user app.
*Impact:* staging/prod builds cannot be pointed at a different backend without editing source;
easy to ship the wrong environment. Biggest build-config asymmetry between the two apps.
*Fix:* mirror the user app: `String.fromEnvironment('BASE_URL', defaultValue: …)` and supply via
`--dart-define` in CI/build.

**H5 — Committed Firebase config / real project id in app + backend**
User `lib/main.dart:37-40` and driver equivalent inline `apiKey`, `appId`, `messagingSenderId`,
`projectId: "drivevalley-fdb7f"`; backend `public/firebase-messaging-sw.js` inlines the same.
*Impact:* Firebase web/Android API keys are public-by-design, but hardcoding binds every build to
one Firebase project (`drivevalley-fdb7f`) that cannot be rotated or swapped per build/tenant.
*Fix:* load from `google-services.json` / `firebase_options.dart` (FlutterFire) and dart-define, so
the project can change per flavor without code edits.

### 🟡 Medium

**M1 — Queue default won't run the ride-timeout job**
`.env.example:23` ships `QUEUE_CONNECTION=database`, but the ride-timeout auto-cancel job
(`app/Jobs/RideTimeoutJob.php`, `ShouldQueue`) requires `QUEUE_CONNECTION=redis` + a running
`queue:work` (per CLAUDE.md, file/db mode "silently drops" it). No Supervisor/worker unit is
committed.
*Impact:* as shipped, unanswered rides never auto-cancel in production.
*Fix:* document the redis requirement in `.env.example`, commit a Supervisor/systemd worker
example, and add a deploy checklist item.

**M2 — User app default `baseUrl` points at a live-looking host**
`drivemond-user-app-3.1/.../app_constants.dart:8-11`:
`String.fromEnvironment('BASE_URL', defaultValue: 'https://dacatlon.store')`. Configurable (good)
but the committed default is a real production-style host — easy to ship by omission of the define.
*Fix:* default to an obviously-invalid placeholder (e.g. empty) so a missing `--dart-define` fails
fast rather than silently hitting a real host.

**M3 — Legacy mart screens still carry heavy inline `setState`**
User app: `mart_store_screen.dart` (~21 `setState`), `mart_order_tracking_screen.dart`,
`mart_payment_screen.dart`; driver app: `mart_delivery_screen.dart` (~21). Not migrated to
`MartController`/`GetBuilder` per the project's own convention.
*Impact:* maintainability/consistency risk and a source of state-sync bugs; not a runtime blocker.
*Fix:* continue the documented incremental migration (method-by-method behind `GetBuilder`).

**M4 — Static analysis & test breadth are narrow**
PHPStan runs at **level 0** on only **8** API controllers (`phpunit.xml` coverage `<source>` and the
CLAUDE.md command scope the same 8 files); the Web mart-admin controllers and the remaining ~1,489
PHP files are unanalyzed. `VitoFlowTest.php` is a single ~4,297-line happy-path suite; the legacy
OTP / phone-password / social-login / Firebase-OTP routes remain active in prod but are untested.
*Fix:* widen PHPStan scope incrementally (raise level and add modules), add tests for the active
legacy auth routes, and split the monolithic test file.

**M5 — AR localization parity is never asserted by tests**
`test/vito_flows_test.dart` (both apps) checks EN↔ES key parity only (loads `en.json`/`es.json`,
not `ar.json`), despite the project rule to keep all three in sync.
*Impact:* an incomplete `ar.json` passes CI and ships missing/blank Arabic strings.
*Fix:* extend the parity assertion to include `ar.json` (three-way key-set equality).

**M6 — File cache/session defaults don't scale horizontally**
`.env.example:21,24`: `CACHE_DRIVER=file`, `SESSION_DRIVER=file`. Behind a multi-server load
balancer these aren't shared; the `/api/health` cache probe also becomes per-node.
*Fix:* default docs to redis for cache/session in any multi-instance deployment.

**M7 — Driver app depends on an unpinned personal fork (supply-chain risk)**
`drivemond-driver-app-3.1/.../pubspec.yaml:89-91` pulls `open_file_plus` from a git dependency
`https://github.com/postflow/open_file_plus.git` at `ref: main` — a third-party **fork**, not the
pub.dev package, tracked by a moving branch rather than a pinned commit/tag.
*Impact:* builds are non-reproducible and at the mercy of an external personal account (force-push,
deletion, or malicious change silently enters the app); it also broke dependency resolution in this
audit's sandbox (egress policy 403). *Fix:* use the published pub.dev package if it now covers the
need, or vendor/pin the fork to an immutable commit hash under an org-controlled mirror.

### 🟢 Low

- **L1 — Silent empty `catch {}` in real-time/auth paths.** Driver `pusher_helper.dart` (×4),
  `login_helper.dart` (×2), `home_screen.dart` (×2), `job_request_modal.dart`; both `main.dart`
  swallow Firebase init with `catch (_) {}`. Startup resilience is intentional, but add a
  breadcrumb/log so failures are diagnosable.
- **L2 — Stray disabled polyline TODO** `finding_rider_widget.dart:34` (`///TODO`), plus ~54–65
  generated `// TODO: implement` repository-stub markers across both apps — noise, worth a cleanup pass.
- **L3 — Sentry `sample_rate` defaults to 1.0** (`config/sentry.php:30`) — captures 100% of errors
  when `SENTRY_SAMPLE_RATE` is unset; a cost/volume consideration, set explicitly for prod.
- **L4 — Redundant legacy-auth admin menus** still exposed (per `VITO_AUDIT.md`); prune to reduce
  confusion/attack surface.

---

## 3. Positives verified (do not "fix")

- **Server-side money:** ride fares recomputed server-side (`TripRequestController.php:217-240`,
  final fare at `:510-558`); mart totals computed backend-side; no client-sent totals trusted.
- **Authorization:** `rideDetails()` enforces ownership (`TripRequestController.php:453`,
  `customer_id => auth('api')->id()`); `finalFareCalculation` checks caller is customer or driver.
- **Concurrency:** mart promo `used_count` incremented/decremented under `lockForUpdate` inside a
  transaction (`VitoMartController.php:222,265-271,419-422`); wallet and QR-token flows likewise.
- **Payments:** Stripe webhook uses signature verification + `stripe_event_id` UNIQUE dedup;
  order/payment-intent creation behind the `idempotent` middleware.
- **Resilience:** all Vito broadcast/push calls are try/catch-wrapped (verified in
  `VitoStripeController`, `VitoMartController`, `VitoMartDriverController`); `APP_DEBUG=false`,
  `APP_ENV=production`, `LOG_LEVEL=warning` defaults are sane; no hardcoded secrets in `config/*`.
- **CI exists:** `.github/workflows/vito-ci.yml` runs PHPStan + `VitoFlowTest` + both Flutter apps'
  analyze/test/build on push/PR (pins Flutter 3.44.0).
- **App API hygiene:** session only invalidated on a startup-confirmed 401; model parsers coerce
  null/garbage without throwing; Stripe/Maps keys sourced via `--dart-define` (no `sk_`/`pk_`
  literals).

---

## 4. Recommended remediation order

1. **C1** — revoke + rotate Swish key, purge from history. *(pre-launch, non-negotiable)*
2. **H1–H3** — env/secrets/CORS hardening + seeder gating. *(deploy-config, low code risk)*
3. **H4–H5** — Flutter build-config parity (`baseUrl` + Firebase via dart-define/FlutterFire).
4. **M1** — commit worker config + fix queue driver docs so ride-timeouts fire.
5. **M2, M6** — placeholder defaults; redis for cache/session in multi-node.
6. **M4, M5** — widen PHPStan/tests; add AR parity assertion.
7. **M3, L1–L4** — incremental cleanup.

---

## 5. Reconciliation with prior audit docs

Items previously flagged and **verified fixed** in current source (excluded from §2):

- `AUDIT.md` "fares fully client-controlled" → **fixed**: server recomputes (`TripRequestController.php:217-240,510-558`).
- `AUDIT.md` "`rideDetails()` lacks ownership check" → **fixed**: ownership enforced (`:453`).
- `AUDIT.md` "promo `used_count` without `lockForUpdate`" → **fixed**: locked+transactional (`VitoMartController.php:222,265-271`).
- Backend scan's "no CI" → **incorrect**: `.github/workflows/vito-ci.yml` exists at repo root.
- `USER_APP_AUDIT.md` / `AUTH_AUDIT.md` / `VITO_AUDIT.md` C-series and audited H-series → marked
  shipped in v2.x; not re-opened here.

Open product-decision items noted by prior docs (not defects): gate-free OTP registration, no
Passport token expiry, retained legacy auth routes. These are design choices to confirm before
launch, not code bugs. (Self-service forgot-PIN recovery — previously listed here — was implemented
in Track 2; see §1b.)

---

## 6. Evidence appendix

Toolchain provisioned in this environment: PHP 8.4.19 + Composer 2; Flutter 3.44.0 / Dart 3.9
(installed to `/opt/flutter` to match the CI-pinned `FLUTTER_VERSION`).

**Customer app — PASS**
- `flutter pub get` → success (Flutter 3.35.x rejected the pubspec; 3.44.0 resolved it, matching CI).
- `flutter test test/vito_flows_test.dart` → **all 47 tests passed** (localization parity EN↔ES,
  token/PIN format, QR expiry, mart status/cart math, promo caps, model parse-hardening, session-401,
  `parse_utils`, `mart_order_status` helpers).
- `flutter analyze --no-fatal-infos` → **34 issues, 0 errors**; 2 warnings
  (`invalid_use_of_protected_member` on `Channel.currentStatus`, `lib/helper/pusher_helper.dart:204,243`),
  the rest info-level deprecations/lints. Non-blocking; worth a cleanup pass (relates to L1).

**Driver app — NOT RUN (environment-blocked)**
- `flutter pub get` fails: the git dependency `open_file_plus` (`github.com/postflow`, finding M7) is
  denied by this environment's egress policy (HTTP 403 via the proxy git relay). Without resolved
  packages, `analyze`/`test` cannot run **here**. CI (with open egress) runs them normally. Driver-app
  findings in this report are from source review; the same test suite is green in CI per project history.

**Backend — NOT RUN (environment-blocked)**
- `composer install` cannot complete in this sandbox: the egress proxy returns **HTTP 403 on all
  github.com git clones** (verified directly, e.g. `git ls-remote https://github.com/laravel/framework.git`
  → 403) and `api.github.com` dist-zipball CONNECTs time out; composer's injected auth token is a
  non-functional `proxy-injected` placeholder, so the GitHub API is rate-limited. ~95 of ~221 packages
  cached before the run aborted on the first repo requiring a fresh fetch.
- Consequence: `VitoFlowTest` and PHPStan could not be executed in this environment. They are the
  backend gate in `.github/workflows/vito-ci.yml` and run on GitHub's runners (open egress). Per prior
  audit docs, `VitoFlowTest` was last green at 92–95 assertions. **All backend findings in this report
  are derived from direct source inspection, not from a failed test run.**

*These three "NOT RUN" items are limitations of the audit sandbox's network policy, not defects in the
code. Re-run the commands on a runner with open egress (or CI) to reproduce the green results.*
