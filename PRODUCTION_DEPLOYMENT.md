# Production Deployment Guide — Vito

## Required Secrets / Environment Variables

| Variable | Where Used | Notes |
|---|---|---|
| `APP_KEY` | Laravel | `php artisan key:generate` |
| `PASSPORT_*` keys | OAuth token signing | `php artisan passport:keys --force` |
| `DB_*` | Database | PostgreSQL recommended for production |
| `REDIS_URL` | Cache / Queue | Required to activate idempotency replay, ride-timeout job |
| `QUEUE_CONNECTION` | Laravel jobs | Set to `redis` to activate `RideTimeoutJob` |
| `SENTRY_LARAVEL_DSN` | Backend error reporting | Optional; no-op without this |
| `MAPS_API_KEY` | Flutter apps (build-time) | `--dart-define=MAPS_API_KEY=<key>` |
| `STRIPE_PUBLISHABLE_KEY` | Flutter apps (build-time) | `--dart-define=STRIPE_PUBLISHABLE_KEY=<key>` |
| `STRIPE_SECRET_KEY` | Backend Stripe calls | |
| `STRIPE_WEBHOOK_SECRET` | Webhook signature verification | |
| `TWILIO_*` | SMS OTP | Optional; only needed for OTP login path |
| `LOG_CHANNEL` | Structured logging | Set to `json_stderr` for JSON log output |

## Rollout Steps

1. **Deploy backend:**
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan config:cache
   php artisan route:cache
   php artisan migrate --force
   php artisan passport:keys --force   # only needed on first deploy
   php artisan queue:work --queue=default,high --tries=3 &
   ```

2. **Build Flutter apps:**
   ```bash
   flutter build apk --release \
     --dart-define=MAPS_API_KEY=$MAPS_API_KEY \
     --dart-define=STRIPE_PUBLISHABLE_KEY=$STRIPE_PUBLISHABLE_KEY
   ```

3. **Configure reverse proxy** to pass `X-Forwarded-For` (TrustProxies is already wired).

4. **Register Stripe webhook** pointing to `POST /api/customer/stripe/webhook` with events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`

5. **Start queue worker** (required for `RideTimeoutJob` auto-cancel):
   ```bash
   php artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=90
   ```
   Without a queue worker the job is dispatched but never consumed — rides will not
   auto-cancel; everything else continues to function normally.

## Scaffolded Features (need infra to activate)

| Feature | Activation |
|---|---|
| Idempotency replay | Works with file/array cache; upgrade to Redis for multi-node correctness |
| Ride-timeout auto-cancel | `QUEUE_CONNECTION=redis` + queue worker running |
| Backend Sentry | Set `SENTRY_LARAVEL_DSN` |
| Structured JSON logs | Set `LOG_CHANNEL=json_stderr` |

## Health Check

```
GET /api/health
```

Returns `200 {"status":"ok"}` when DB + cache are reachable; `503` with component name on failure.
Wire this to your load-balancer or k8s liveness probe.

## Rollback

1. Re-deploy the previous release tag.
2. Run `php artisan migrate:rollback` only if the new release added migrations (check `db:status`).
3. Queue workers restart automatically on most platforms; force-restart if the job class changed.

## Monitoring

- `GET /api/admin/metrics` (requires `AccessToSuperAdmin`) — active rides, wallet volume, mart SLA.
- Laravel logs: `storage/logs/laravel.log` or stderr (JSON) when `LOG_CHANNEL=json_stderr`.
- Every request carries `X-Request-Id` in the response header for log correlation.
