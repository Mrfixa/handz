<?php

namespace Modules\TripManagement\Http\Controllers\Web;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\TripManagement\Entities\MartOrder;
use Modules\TripManagement\Entities\MartProduct;

class MartDashboardController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): View
    {
        $this->authorize('vito_mart_view');

        $from = null;
        $to = null;
        $range = $request->get('data', 'this_year');
        $dates = getDateRange($range);
        $from = $dates['start'] ?? null;
        $to = $dates['end'] ?? null;

        $scoped = fn () => MartOrder::when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));

        // Counts by status.
        $statusCounts = [];
        foreach (MartOrder::STATUSES as $status) {
            $statusCounts[$status] = $scoped()->where('status', $status)->count();
        }
        $totalOrders = array_sum($statusCounts);
        $revenue = $scoped()->where('status', 'delivered')->sum('total_amount');
        $activeProducts = MartProduct::where('is_active', true)->count();

        // Top 5 products by quantity sold (joined through order items).
        $topProducts = DB::table('mart_order_items')
            ->join('mart_products', 'mart_products.id', '=', 'mart_order_items.product_id')
            ->select('mart_products.name', DB::raw('SUM(mart_order_items.quantity) as qty'), DB::raw('SUM(mart_order_items.total_price) as revenue'))
            ->groupBy('mart_products.id', 'mart_products.name')
            ->orderByDesc('qty')
            ->limit(5)
            ->get();

        return view('tripmanagement::admin.mart.dashboard.index', compact(
            'statusCounts', 'totalOrders', 'revenue', 'activeProducts', 'topProducts', 'range'
        ));
    }
}
