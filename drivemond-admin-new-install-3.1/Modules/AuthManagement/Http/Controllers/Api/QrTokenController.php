<?php

namespace Modules\AuthManagement\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\AuthManagement\Entities\QrToken;

class QrTokenController extends Controller
{
    public function generateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:driver,customer',
            'length' => 'nullable|integer|min:8|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $tokenLength = $request->input('length', $request->role === 'customer' ? 16 : 32);
        $tokenValue = Str::random($tokenLength);

        // Ensure uniqueness
        $attempts = 0;
        while (QrToken::where('token', $tokenValue)->exists() && $attempts < 10) {
            $tokenValue = Str::random($tokenLength);
            $attempts++;
        }

        $token = QrToken::create([
            'token' => $tokenValue,
            'role' => $request->role,
            'created_by' => $request->user()->id,
            'expires_at' => $request->role === 'customer' ? now()->addHour() : now()->addDays(7),
        ]);

        return response()->json(responseFormatter(DEFAULT_200, [
            'token' => $token->token,
            'length' => strlen($token->token),
            'role' => $token->role,
            'expires_at' => $token->expires_at->toISOString(),
            'qr_data' => json_encode([
                'token' => $token->token,
                'role' => $token->role,
            ]),
        ]));
    }

    public function validateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|min:6|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $qrToken = QrToken::where('token', $request->token)->first();

        if (!$qrToken || !$qrToken->isValid()) {
            // Structured error body for parity with validateTokenPublic so clients
            // can distinguish invalid/expired/revoked from a generic 404.
            return response()->json(responseFormatter(
                constant: DEFAULT_404,
                errors: [['message' => 'Token is invalid or expired']]
            ), 404);
        }

        return response()->json(responseFormatter(DEFAULT_200, [
            'valid' => true,
            'role' => $qrToken->role,
        ]));
    }

    public function redeemToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|min:6|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $qrToken = DB::transaction(function () use ($request) {
            $token = QrToken::where('token', $request->token)
                ->lockForUpdate()
                ->first();

            if (!$token || !$token->isValid()) {
                return null;
            }

            $token->update([
                'redeemed_at' => now(),
                'redeemed_by' => $request->user()?->id,
            ]);

            return $token;
        });

        if (!$qrToken) {
            return response()->json(responseFormatter(constant: DEFAULT_404), 404);
        }

        return response()->json(responseFormatter(DEFAULT_200, [
            'redeemed' => true,
            'role' => $qrToken->role,
        ]));
    }

    public function validateTokenPublic(string $token): JsonResponse
    {
        // Accept tokens from 6 to 128 characters
        if (strlen($token) < 6 || strlen($token) > 128) {
            return response()->json(responseFormatter(constant: DEFAULT_404, errors: [['message' => 'Token is invalid or expired']]), 404);
        }
        
        $qrToken = QrToken::where('token', $token)->first();

        if (!$qrToken || !$qrToken->isValid()) {
            return response()->json(responseFormatter(constant: DEFAULT_404, errors: [['message' => 'Token is invalid or expired']]), 404);
        }

        $data = [
            'valid' => true,
            'role' => $qrToken->role,
        ];

        return response()->json(responseFormatter(DEFAULT_200, $data));
    }

    public function revokeToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|min:6|max:128',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $token = DB::transaction(function () use ($request) {
            $token = QrToken::where('token', $request->token)
                ->where('created_by', $request->user()->id)
                ->lockForUpdate()
                ->first();
            if ($token) {
                $token->update(['is_revoked' => true]);
            }
            return $token;
        });

        if (!$token) {
            return response()->json(responseFormatter(constant: DEFAULT_404), 404);
        }

        return response()->json(responseFormatter(DEFAULT_200));
    }
}
