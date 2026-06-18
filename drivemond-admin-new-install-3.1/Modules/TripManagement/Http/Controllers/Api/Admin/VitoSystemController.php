<?php

namespace Modules\TripManagement\Http\Controllers\Api\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\TripManagement\Entities\MartOrder;
use Modules\TripManagement\Entities\TripRequest;
use Modules\UserManagement\Entities\UserAccount;

class VitoSystemController extends Controller
{
    /**
     * GET /api/health
     *
     * Unauthenticated lightweight liveness probe for load balancers and k8s.
     * Returns 200 when all critical services are healthy, 503 on any failure.
     */
    public function health(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database connectivity
        try {
            DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Throwable $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
            $healthy = false;
        }

        // Cache connectivity (Redis if configured, file otherwise)
        try {
            Cache::put('_health_check', 1, 5);
            $checks['cache'] = 'ok';
        } catch (\Throwable $e) {
            $checks['cache'] = 'error: ' . $e->getMessage();
            $healthy = false;
        }

        $status = $healthy ? 200 : 503;
        return response()->json([
            'status'    => $healthy ? 'ok' : 'degraded',
            'timestamp' => now()->toISOString(),
            'checks'    => $checks,
        ], $status);
    }

    /**
     * GET /api/admin/metrics
     *
     * Read-only business metrics for the admin panel. Requires AccessToSuperAdmin.
     * Cached 60 seconds to avoid N+1 on high-traffic dashboards.
     */
    public function metrics(): JsonResponse
    {
        $data = Cache::remember('vito_metrics', 60, function () {
            $now = now();

            // Active rides (accepted/ongoing, last 24h to bound the query)
            $activeRides = TripRequest::whereIn('current_status', ['accepted', 'ongoing'])
                ->where('created_at', '>=', $now->copy()->subDay())
                ->count();

            // Wallet transaction volume last 24h
            $walletVolume = UserAccount::sum('wallet_balance');

            // Mart fulfillment SLA: % of mart orders delivered within 2h of creation (last 7d)
            $totalMart = MartOrder::where('created_at', '>=', $now->copy()->subDays(7))->count();
            $withinSla = MartOrder::where('created_at', '>=', $now->copy()->subDays(7))
                ->where('status', 'delivered')
                ->whereRaw('TIMESTAMPDIFF(MINUTE, created_at, updated_at) <= 120')
                ->count();
            $slaPercent = $totalMart > 0 ? round(($withinSla / $totalMart) * 100, 1) : null;

            return [
                'active_rides'              => $activeRides,
                'total_wallet_balance'      => $walletVolume,
                'mart_sla_percent_7d'       => $slaPercent,
                'mart_total_orders_7d'      => $totalMart,
                'mart_delivered_within_sla' => $withinSla,
                'generated_at'              => $now->toISOString(),
            ];
        });

        return response()->json(responseFormatter(DEFAULT_200, $data));
    }
}
