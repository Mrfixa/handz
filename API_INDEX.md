# Vito — API Endpoint Index

Complete map of the **Vito-specific** API surface so a backend task can locate the exact route → controller method without reading route files. Legacy DriveMond routes are not enumerated here (see "Legacy routes" at bottom).

Base path for all routes: `/api/...`. Auth via Laravel Passport. `scope:` = required token scope. All authed routes also carry `maintenance_mode`.

---

## Authentication — `AuthManagement`

Routes: `Modules/AuthManagement/Routes/api.php`

### PIN auth — `VitoAuthController`
| Method | Path | Controller@method | Auth / throttle |
|--------|------|-------------------|-----------------|
| POST | `/customer/auth/pin-login` | `VitoAuthController@pinLogin` | throttle 20/min |
| POST | `/customer/auth/pin-register` | `VitoAuthController@pinRegister` | throttle 20/min |
| POST | `/driver/auth/pin-login` | `VitoAuthController@pinLogin` | throttle 20/min |
| POST | `/driver/auth/pin-register` | `VitoAuthController@pinRegister` | throttle 20/min |
| POST | `/auth/logout` | `VitoAuthController@logout` | auth:api |
| POST | `/customer/auth/change-pin` | `VitoAuthController@changePin` | auth:api |
| POST | `/driver/auth/change-pin` | `VitoAuthController@changePin` | auth:api |
| POST | `/customer/auth/forgot-pin/send-otp` | `VitoAuthController@forgotPinSendOtp` | throttle 5/min |
| POST | `/customer/auth/forgot-pin/reset` | `VitoAuthController@resetPinWithOtp` | throttle 5/min |
| POST | `/driver/auth/forgot-pin/send-otp` | `VitoAuthController@forgotPinSendOtp` | throttle 5/min |
| POST | `/driver/auth/forgot-pin/reset` | `VitoAuthController@resetPinWithOtp` | throttle 5/min |

> `pinLogin`/`pinRegister` infer customer vs driver from the route prefix. `changePin` revokes all other sessions.

### QR tokens — `QrTokenController`
| Method | Path | Controller@method | Auth / throttle |
|--------|------|-------------------|-----------------|
| POST | `/qr-token/validate` | `QrTokenController@validateToken` | throttle 10/min |
| GET | `/qr/validate/{token}` | `QrTokenController@validateTokenPublic` | throttle 10/min (public, used by landing page) |
| POST | `/qr-token/generate` | `QrTokenController@generateToken` | scope:AccessToSuperAdmin, throttle 10/min |
| POST | `/qr-token/revoke` | `QrTokenController@revokeToken` | scope:AccessToSuperAdmin, throttle 10/min |

### OTP auth — `ClientOtpAuthController`
| Method | Path | Controller@method | Auth / throttle |
|--------|------|-------------------|-----------------|
| POST | `/customer/auth/check` | `ClientOtpAuthController@checkUser` | throttle 5/min |
| POST | `/customer/auth/send-otp` | `ClientOtpAuthController@sendOtp` | throttle 5/min |
| POST | `/customer/auth/otp-verification` | `ClientOtpAuthController@verifyOtp` | throttle 5/min |
| POST | `/customer/auth/registration-from-otp` | `ClientOtpAuthController@registrationFromOtp` | throttle 5/min |

---

## VitoMart — `TripManagement`

Routes: `Modules/TripManagement/Routes/vito_api.php`

### Customer — `VitoMartController` — prefix `/customer/mart`, scope:AccessToCustomer
| Method | Path | Controller@method | Throttle / middleware |
|--------|------|-------------------|-----------------------|
| GET | `/customer/mart/products` | `VitoMartController@products` | — |
| GET | `/customer/mart/categories` | `VitoMartController@categories` | — |
| GET | `/customer/mart/products/{id}` | `VitoMartController@productDetails` | — |
| POST | `/customer/mart/apply-promo` | `VitoMartController@applyPromo` | throttle 30/min |
| POST | `/customer/mart/order` | `VitoMartController@createOrder` | throttle 10/min, **idempotent** |
| GET | `/customer/mart/orders` | `VitoMartController@orderList` | — |
| GET | `/customer/mart/orders/{id}` | `VitoMartController@orderDetails` | — |
| PUT | `/customer/mart/orders/{id}/cancel` | `VitoMartController@cancelOrder` | throttle 20/min |
| POST | `/customer/mart/orders/{id}/review` | `VitoMartController@reviewOrder` | throttle 20/min |

### Driver — `VitoMartDriverController` — prefix `/driver/mart`, scope:AccessToDriver
| Method | Path | Controller@method | Throttle / middleware |
|--------|------|-------------------|-----------------------|
| GET | `/driver/mart/pending-orders` | `VitoMartDriverController@pendingOrders` | — |
| POST | `/driver/mart/accept-order` | `VitoMartDriverController@acceptOrder` | throttle 30/min, **idempotent** |
| PUT | `/driver/mart/update-status` | `VitoMartDriverController@updateStatus` | throttle 60/min |
| POST | `/driver/mart/upload-proof` | `VitoMartDriverController@uploadDeliveryProof` | throttle 10/min |
| GET | `/driver/mart/my-orders` | `VitoMartDriverController@myOrders` | — |
| GET | `/driver/mart/orders/{id}` | `VitoMartDriverController@orderDetails` | — |
| POST | `/driver/mart/orders/{id}/photo` | `VitoMartDriverController@uploadDeliveryProof` | alias |
| POST | `/driver/mart/orders/{id}/signature` | `VitoMartDriverController@uploadDeliveryProof` | alias |

> `updateStatus` enforces `MartOrder::STATUS_TRANSITIONS` (pending→accepted→picked_up→delivered).

### Admin product API — `VitoMartAdminApiController` — prefix `/admin/mart`, scope:AccessToSuperAdmin, throttle 30/min
| Method | Path | Controller@method |
|--------|------|-------------------|
| GET | `/admin/mart/products` | `VitoMartAdminApiController@index` |
| POST | `/admin/mart/products` | `VitoMartAdminApiController@store` |
| PUT | `/admin/mart/products/{id}` | `VitoMartAdminApiController@update` |
| DELETE | `/admin/mart/products/{id}` | `VitoMartAdminApiController@destroy` |

---

## System health — `VitoSystemController`

Routes: `Modules/TripManagement/Routes/vito_api.php`
| Method | Path | Controller@method | Auth |
|--------|------|-------------------|------|
| GET | `/health` | `VitoSystemController@health` | public, throttle 60/min (DB + cache check) |
| GET | `/admin/metrics` | `VitoSystemController@metrics` | scope:AccessToSuperAdmin (60s cache) |

---

## Payments — `Gateways`

Routes: `Modules/Gateways/Routes/api.php`
| Method | Path | Controller@method | Auth / middleware |
|--------|------|-------------------|-------------------|
| POST | `/customer/stripe/payment-intent` | `VitoStripeController@createPaymentIntent` | scope:AccessToCustomer, **idempotent** (wallet top-up) |
| POST | `/customer/stripe/order-payment-intent` | `VitoStripeController@createOrderPaymentIntent` | scope:AccessToCustomer, **idempotent** (mart order) |
| POST | `/stripe/webhook` | `VitoStripeController@webhook` | public (verified by Stripe signature + `stripe_event_id` UNIQUE dedup) |

---

## Driver ride / parcel (Vito-extended)

`VitoTripController` and `VitoParcelController` (`Modules/TripManagement/Http/Controllers/Api/Driver/`) provide atomic accept/reject (`WHERE driver_id IS NULL`). Their routes live in `Modules/TripManagement/Routes/api.php` alongside legacy trip routes — grep that file for `VitoTripController` / `VitoParcelController` to find current bindings.

---

## Legacy routes (not indexed here)

The pre-Vito DriveMond API is large and stable. When working on legacy features, read the relevant module's `Routes/api.php` directly:

```
Modules/{Module}/Routes/api.php   — 16 modules, see CODEBASE_MAP.md for the list
Modules/AuthManagement/Routes/api.php  — top of file: legacy AuthController (phone/password, social, firebase-otp)
```

Legacy auth (`AuthController`): `/customer/auth/registration`, `/customer/auth/login`, `/customer/auth/social-login`, `/customer/auth/otp-login`, `/customer/auth/forget-password`, `/customer/auth/reset-password`, `/customer/auth/firebase-otp-verification`, `/user/logout`, `/user/delete`, `/user/change-password`. Kept live — do not remove.
