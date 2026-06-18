<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey || !in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
            return $next($request);
        }

        // Scope the cache key to the authenticated user + endpoint to prevent cross-user replay.
        $cacheKey = 'idempotency:' . sha1(
            $idempotencyKey . ':' . ($request->user()?->id ?? 'anon') . ':' . $request->path()
        );

        if (Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            return response()->json(json_decode($cached['body'], true), $cached['status'])
                ->header('Idempotency-Replayed', 'true');
        }

        $response = $next($request);

        // Cache only successful responses; never cache 4xx/5xx so callers can fix and retry.
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            Cache::put($cacheKey, [
                'body'   => $response->getContent(),
                'status' => $response->getStatusCode(),
            ], now()->addHours(24));
        }

        return $response;
    }
}
