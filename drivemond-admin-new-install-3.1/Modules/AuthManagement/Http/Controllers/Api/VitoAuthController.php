<?php

namespace Modules\AuthManagement\Http\Controllers\Api;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\UniqueConstraintViolationException;
use Modules\AuthManagement\Entities\QrToken;
use Modules\AuthManagement\Service\Interfaces\AuthServiceInterface;
use Modules\UserManagement\Entities\User;
use Modules\UserManagement\Service\Interfaces\CustomerLevelServiceInterface;
use Modules\UserManagement\Service\Interfaces\CustomerServiceInterface;
use Modules\UserManagement\Service\Interfaces\DriverLevelServiceInterface;
use Modules\UserManagement\Service\Interfaces\DriverServiceInterface;

class VitoAuthController extends Controller
{
    protected $authService;
    protected $customerService;
    protected $driverService;
    protected $customerLevelService;
    protected $driverLevelService;

    public function __construct(
        AuthServiceInterface         $authService,
        CustomerServiceInterface     $customerService,
        DriverServiceInterface       $driverService,
        CustomerLevelServiceInterface $customerLevelService,
        DriverLevelServiceInterface  $driverLevelService,
    ) {
        $this->authService = $authService;
        $this->customerService = $customerService;
        $this->driverService = $driverService;
        $this->customerLevelService = $customerLevelService;
        $this->driverLevelService = $driverLevelService;
    }

    public function pinLogin(Request $request): JsonResponse
    {
        $request->merge(['username' => trim((string) $request->username)]);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50',
            'pin' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $isCustomerRoute = str_contains($request->route()->getPrefix(), 'customer');
        $userType = $isCustomerRoute ? CUSTOMER : DRIVER;

        $user = User::where('username', $request->username)
            ->where('user_type', $userType)
            ->first();

        if (!$user) {
            return response()->json(responseFormatter(constant: AUTH_LOGIN_401), 403);
        }

        $hitLimit = businessConfig('maximum_login_hit')?->value ?? 5;
        $blockTime = businessConfig('temporary_login_block_time')?->value ?? 60;

        $loginResult = DB::transaction(function () use ($user, $request, $hitLimit, $blockTime) {
            // Re-fetch with row lock for atomicity
            $user = \Modules\UserManagement\Entities\User::where('id', $user->id)->lockForUpdate()->first();

            if ($user->is_temp_blocked || $user->pin_blocked_at) {
                $blockedAt = $user->pin_blocked_at;
                if ($blockedAt) {
                    $secondsPassed = Carbon::now()->diffInSeconds(Carbon::parse($blockedAt), true);
                    if ($secondsPassed <= $blockTime) {
                        $time = $blockTime - $secondsPassed;
                        return ['blocked' => true, 'time' => $time];
                    }
                }
                $user->pin_attempts = 0;
                $user->is_temp_blocked = 0;
                $user->pin_blocked_at = null;
                $user->save();
            }

            if (!$user->pin_hash || !Hash::check($request->pin, $user->pin_hash)) {
                $user->pin_attempts = ($user->pin_attempts ?? 0) + 1;
                if ($user->pin_attempts >= (int)$hitLimit) {
                    $user->is_temp_blocked = 1;
                    $user->pin_blocked_at = now();
                }
                $user->save();
                return ['blocked' => false, 'failed' => true];
            }

            return ['user' => $user, 'blocked' => false, 'failed' => false];
        });

        if ($loginResult['blocked'] ?? false) {
            return response()->json([
                'response_code' => 'too_many_attempt_405',
                'message' => translate('please_try_again_after_') . CarbonInterval::seconds($loginResult['time'])->cascade()->forHumans(),
            ], 403);
        }
        if ($loginResult['failed'] ?? false) {
            return response()->json(responseFormatter(AUTH_LOGIN_401), 403);
        }
        $user = $loginResult['user'];

        if (!$user->is_active) {
            $user->update(['pin_attempts' => 0]);
            if ($user->user_type === 'driver') {
                return response()->json(responseFormatter(DEFAULT_USER_UNDER_REVIEW_DISABLED_401), 403);
            }
            return response()->json(responseFormatter(DEFAULT_USER_DISABLED_401), 403);
        }

        $userData = [
            'pin_attempts' => 0,
            'is_temp_blocked' => 0,
            'pin_blocked_at' => null,
        ];
        $user = $this->authService->update(id: $user->id, data: $userData);

        foreach ($user->tokens as $token) {
            $token->revoke();
        }

        $accessType = $user->user_type == CUSTOMER ? CUSTOMER_PANEL_ACCESS : DRIVER_PANEL_ACCESS;

        return response()->json(responseFormatter(AUTH_LOGIN_200, $this->authenticate($user, $accessType)));
    }

    public function logout(Request $request): JsonResponse
    {
        $token = auth('api')->user()->token();
        $token->revoke();

        return response()->json(responseFormatter(DEFAULT_200));
    }

    /**
     * Change the authenticated user's 6-digit PIN.
     * Verifies the current PIN against pin_hash, then stores the new hash and
     * clears any failed-attempt lockout state (mirrors pinLogin's reset).
     */
    public function changePin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_pin' => 'required|string|digits:6',
            'new_pin' => 'required|string|digits:6|confirmed|different:current_pin',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $user = auth('api')->user();

        if (!$user || !$user->pin_hash || !Hash::check($request->current_pin, $user->pin_hash)) {
            return response()->json(responseFormatter(AUTH_LOGIN_401), 403);
        }

        $this->authService->update(id: $user->id, data: [
            'pin_hash' => Hash::make($request->new_pin),
            'pin_attempts' => 0,
            'is_temp_blocked' => 0,
            'pin_blocked_at' => null,
        ]);

        // Security: a PIN change invalidates every other session — revoke all of
        // the user's tokens except the one making this request, so other devices
        // must re-authenticate with the new PIN.
        $currentTokenId = auth('api')->user()->token()->id ?? null;
        foreach ($user->tokens as $token) {
            if ($token->id !== $currentTokenId) {
                $token->revoke();
            }
        }

        return response()->json(responseFormatter(DEFAULT_200));
    }

    public function pinRegister(Request $request): JsonResponse
    {
        $request->merge(['username' => trim((string) $request->username)]);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_-]+$/|unique:users,username',
            'pin' => 'required|string|digits:6|confirmed',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'referral_code' => 'nullable|string',
            'qr_token' => 'required|string|size:64',
            'car_photo' => 'nullable|image|max:5120',
            'profile_image' => 'nullable|image|max:5120',
            'identity_images' => 'nullable|array',
            'identity_images.*' => 'nullable|image|max:5120',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'nullable|image|max:5120',
            'service' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $isCustomerRoute = str_contains($request->route()->getPrefix(), 'customer');

        if (!$isCustomerRoute && !businessConfig('driver_self_registration')?->value) {
            return response()->json(responseFormatter(SELF_REGISTRATION_400), 400);
        }

        $firstLevel = $isCustomerRoute
            ? $this->customerLevelService->findOneBy(['user_type' => CUSTOMER, 'sequence' => 1])
            : $this->driverLevelService->findOneBy(['user_type' => DRIVER, 'sequence' => 1]);

        if (!$firstLevel) {
            return response()->json(responseFormatter(LEVEL_403), 403);
        }

        $data = array_merge($validator->validated(), [
            'pin_hash' => Hash::make($request->pin),
            'password' => $request->pin,
        ]);

        if ($request->hasFile('car_photo')) {
            $data['car_photo'] = $request->file('car_photo');
        }
        if ($request->hasFile('profile_image')) {
            $data['profile_image'] = $request->file('profile_image');
        }
        if ($request->hasFile('identity_images')) {
            $data['identity_images'] = $request->file('identity_images');
        }

        try {
            DB::transaction(function () use ($request, $data, $isCustomerRoute) {
                $expectedRole = $isCustomerRoute ? 'customer' : 'driver';

                $qrToken = QrToken::where('token', $request->qr_token)
                    ->where('role', $expectedRole)
                    ->where('expires_at', '>', now())
                    ->whereNull('redeemed_at')
                    ->where('is_revoked', false)
                    ->lockForUpdate()
                    ->first();

                if (!$qrToken) {
                    throw new \RuntimeException('invalid_qr_token');
                }

                $user = $isCustomerRoute
                    ? $this->customerService->create($data)
                    : $this->driverService->create(array_merge($data, ['car_photo_approved' => true]));

                $qrToken->update(['redeemed_at' => now(), 'redeemed_by' => $user->id]);
            });
        } catch (UniqueConstraintViolationException $e) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: [['error_code' => 'username', 'message' => 'Username already taken']]), 409);
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'invalid_qr_token') {
                return response()->json(responseFormatter(constant: DEFAULT_400, errors: [['error_code' => 'qr_token', 'message' => 'Invalid or expired invitation token']]), 400);
            }
            throw $e;
        }

        return response()->json(responseFormatter(REGISTRATION_200));
    }

    /**
     * GET /api/{customer|driver}/auth/check-username?username=foo
     * D15: lets the sign-up form check username availability before submit so the
     * user isn't bounced back after a full registration attempt. Usernames are
     * globally unique (matching the `unique:users,username` rule on pinRegister).
     */
    public function checkUsername(Request $request): JsonResponse
    {
        $request->merge(['username' => trim((string) $request->username)]);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9_-]+$/',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $available = !User::where('username', $request->username)->exists();

        return response()->json([
            'available' => $available,
            'username'  => $request->username,
            'message'   => $available ? 'Username available' : 'Username already taken',
        ]);
    }

    private function authenticate($user, $accessType): array
    {
        $token = $user->createToken($user->phone ?? $user->username, [$accessType])->accessToken;

        return [
            'token' => $token,
            'is_active' => $user->is_active,
            'is_phone_verified' => $user->phone_verified_at ? 1 : 0,
        ];
    }

    /**
     * POST /api/{customer|driver}/auth/forgot-pin
     * AUTH-SEC-04: Initiates PIN recovery by sending OTP to user's registered phone.
     * Requires the user to have a verified phone number on file.
     */
    public function forgotPin(Request $request): JsonResponse
    {
        $request->merge(['username' => trim((string) $request->username)]);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $isCustomerRoute = str_contains($request->route()->getPrefix(), 'customer');
        $userType = $isCustomerRoute ? CUSTOMER : DRIVER;

        $user = User::where('username', $request->username)
            ->where('user_type', $userType)
            ->first();

        if (!$user) {
            // Return 200 to prevent user enumeration - same response whether user exists or not
            return response()->json([
                'response_code' => 'otp_sent',
                'message' => translate('If your account exists, you will receive an OTP'),
            ]);
        }

        // User must have a verified phone number for PIN recovery
        if (!$user->phone || !$user->phone_verified_at) {
            return response()->json([
                'response_code' => 'pin_recovery_unavailable',
                'message' => translate('PIN recovery requires a verified phone number. Please contact support.'),
            ], 400);
        }

        // Generate OTP and store it
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete any existing PIN recovery OTPs for this user
        DB::table('vito_otps')
            ->where('phone', $user->phone)
            ->where('type', 'pin_recovery')
            ->delete();

        DB::table('vito_otps')->insert([
            'id'         => \Illuminate\Support\Str::uuid(),
            'phone'      => $user->phone,
            'otp_hash'   => Hash::make($otp),
            'type'       => 'pin_recovery',
            'expires_at' => now()->addMinutes(5)->toDateTimeString(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ]);

        // Send OTP via SMS
        $this->dispatchSms($user->phone, $otp);

        return response()->json([
            'response_code' => 'otp_sent',
            'message' => translate('If your account exists, you will receive an OTP'),
            // Only include for testing/development
            '_debug_otp' => app()->environment('testing', 'local') ? $otp : null,
        ]);
    }

    /**
     * POST /api/{customer|driver}/auth/reset-pin
     * AUTH-SEC-04: Resets PIN after OTP verification for PIN recovery flow.
     */
    public function resetPin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|min:3|max:50',
            'otp' => 'required|string|digits:6',
            'new_pin' => 'required|string|digits:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $isCustomerRoute = str_contains($request->route()->getPrefix(), 'customer');
        $userType = $isCustomerRoute ? CUSTOMER : DRIVER;

        $user = User::where('username', $request->username)
            ->where('user_type', $userType)
            ->first();

        if (!$user || !$user->phone) {
            return response()->json([
                'response_code' => 'invalid_request',
                'message' => translate('Invalid request'),
            ], 400);
        }

        // Verify the OTP
        $record = DB::table('vito_otps')
            ->where('phone', $user->phone)
            ->where('type', 'pin_recovery')
            ->where('expires_at', '>', now()->toDateTimeString())
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$record) {
            return response()->json([
                'response_code' => 'otp_expired',
                'message' => translate('OTP has expired or was not found. Please request a new one.'),
            ], 404);
        }

        if (!Hash::check($request->otp, $record->otp_hash)) {
            return response()->json([
                'response_code' => 'invalid_otp',
                'message' => translate('Invalid OTP'),
            ], 400);
        }

        // Mark OTP as verified
        DB::table('vito_otps')
            ->where('id', $record->id)
            ->update(['verified_at' => now()->toDateTimeString(), 'updated_at' => now()->toDateTimeString()]);

        // Reset the PIN
        $this->authService->update(id: $user->id, data: [
            'pin_hash' => Hash::make($request->new_pin),
            'pin_attempts' => 0,
            'is_temp_blocked' => 0,
            'pin_blocked_at' => null,
        ]);

        // Revoke all existing sessions
        foreach ($user->tokens as $token) {
            $token->revoke();
        }

        return response()->json(responseFormatter(DEFAULT_200));
    }

    private function dispatchSms(string $phone, string $otp): void
    {
        // Reuse SMS dispatch logic from ClientOtpAuthController
        $message = "Your VITO PIN recovery code is: {$otp}";

        try {
            $status = \Modules\BusinessManagement\Lib\SMSGateway::send($phone, $otp);
            if ($status === 'success') {
                return;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Configured SMS gateway failed: ' . $e->getMessage());
        }

        // Fallback to Twilio env vars
        try {
            $sid   = env('TWILIO_ACCOUNT_SID');
            $token = env('TWILIO_AUTH_TOKEN');
            $from  = env('TWILIO_FROM_NUMBER');

            if ($sid && $token && $from) {
                $client = new \Twilio\Rest\Client($sid, $token);
                retry(3, fn() => $client->messages->create($phone, ['from' => $from, 'body' => $message]), 1000);
                return;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Twilio env SMS dispatch failed: ' . $e->getMessage());
        }

        // Last resort: log only
        \Illuminate\Support\Facades\Log::info("PIN Recovery SMS to {$phone}: {$message}");
    }
}
