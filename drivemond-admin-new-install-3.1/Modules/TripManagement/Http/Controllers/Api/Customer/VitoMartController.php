<?php

namespace Modules\TripManagement\Http\Controllers\Api\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\TripManagement\Entities\MartCategory;
use Modules\TripManagement\Entities\MartOrder;
use Modules\TripManagement\Entities\MartOrderItem;
use Modules\TripManagement\Entities\MartProduct;
use Modules\TripManagement\Entities\MartPromoCode;
use Modules\TripManagement\Entities\MartReview;
use Modules\TripManagement\Entities\StripeEvent;
use Modules\UserManagement\Entities\User;

class VitoMartController extends Controller
{
    use \Modules\TripManagement\Http\Controllers\Concerns\RefundsMartOrders;

    public function products(Request $request): JsonResponse
    {
        $search = substr((string)($request->search ?? ''), 0, 100);
        $limit = min($request->input('limit', 20), 100);

        // Sanitize search: escape LIKE special characters to prevent injection
        $escapedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);

        $query = MartProduct::where('is_active', true)
            ->when($request->category, fn($q, $cat) => $q->where('category', $cat))
            ->when($search, fn($q, $s) => $q->where('name', 'like', "%{$escapedSearch}%"))
            ->when($request->boolean('is_featured'), fn($q) => $q->where('is_featured', true))
            ->when($request->boolean('is_popular'), fn($q) => $q->where('is_popular', true))
            ->when($request->zone_id, fn($q, $zoneId) => $q->where(function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId)->orWhereNull('zone_id');
            }));

        // GoMart-style sorting; default keeps featured first then newest.
        switch ($request->input('sort')) {
            case 'price_asc':  $query->orderByRaw('COALESCE(NULLIF(discount_price,0), price) asc'); break;
            case 'price_desc': $query->orderByRaw('COALESCE(NULLIF(discount_price,0), price) desc'); break;
            case 'popular':    $query->orderByDesc('sold_count')->orderByDesc('is_popular'); break;
            default:           $query->orderByDesc('is_featured')->orderByDesc('created_at');
        }

        return response()->json(responseFormatter(DEFAULT_200, $query->paginate($limit)));
    }

    public function categories(): JsonResponse
    {
        $categories = MartCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'image']);

        return response()->json(responseFormatter(DEFAULT_200, $categories));
    }

    public function productDetails(string $id): JsonResponse
    {
        $product = MartProduct::find($id);

        if (!$product) {
            return response()->json(responseFormatter(DEFAULT_404), 404);
        }

        return response()->json(responseFormatter(DEFAULT_200, $product));
    }

    /** Toggle a product in the customer's favourites; returns the new state. */
    public function toggleFavorite(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), ['product_id' => 'required|string']);
        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        if (!MartProduct::where('id', $request->product_id)->exists()) {
            return response()->json(responseFormatter(DEFAULT_404), 404);
        }

        $existing = \Modules\TripManagement\Entities\MartFavorite::where('customer_id', $request->user()->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            $existing->delete();
            $favorited = false;
        } else {
            \Modules\TripManagement\Entities\MartFavorite::create([
                'customer_id' => $request->user()->id,
                'product_id'  => $request->product_id,
            ]);
            $favorited = true;
        }

        return response()->json(responseFormatter(DEFAULT_200, ['favorited' => $favorited]));
    }

    /** List the customer's favourite products (active only). */
    public function favorites(Request $request): JsonResponse
    {
        $products = \Modules\TripManagement\Entities\MartFavorite::where('customer_id', $request->user()->id)
            ->with('product')
            ->latest()
            ->get()
            ->pluck('product')
            ->filter(fn($p) => $p && $p->is_active)
            ->values();

        return response()->json(responseFormatter(DEFAULT_200, $products));
    }

    public function applyPromo(Request $request): JsonResponse
    {
        // Accept EITHER a full items array (subtotal recomputed server-side) OR a
        // client-provided subtotal for an in-cart preview. This is preview-only —
        // createOrder always recomputes the authoritative total from real items,
        // so trusting the preview subtotal here carries no pricing risk.
        $validator = Validator::make($request->all(), [
            'code'               => 'required|string|max:50',
            'items'              => 'required_without:subtotal|array|min:1',
            'items.*.product_id' => 'required_with:items|string',
            'items.*.quantity'   => 'required_with:items|integer|min:1|max:100',
            'subtotal'           => 'required_without:items|numeric|min:0|max:9999999.99',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        if ($request->filled('items')) {
            // Compute subtotal server-side — never trust client-sent prices.
            $subtotal = 0;
            foreach ($request->items as $item) {
                $product = MartProduct::where('id', $item['product_id'])->where('is_active', true)->first();
                if (!$product) {
                    return response()->json(responseFormatter(constant: DEFAULT_404, errors: [['message' => 'One or more products not found']]), 404);
                }
                $subtotal += $product->price * (int) $item['quantity'];
            }
        } else {
            $subtotal = (float) $request->subtotal;
        }

        $promo = MartPromoCode::where('code', strtoupper(trim($request->code)))->first();

        if (!$promo || !$promo->isValid()) {
            return response()->json(responseFormatter(constant: DEFAULT_404, errors: [['message' => 'Invalid or expired promo code']]), 404);
        }

        $discount = $promo->computeDiscount((float) $subtotal);

        if ($discount <= 0) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: [['message' => 'Order does not meet the minimum amount for this promo']]), 400);
        }

        return response()->json(responseFormatter(DEFAULT_200, [
            'code'           => $promo->code,
            'discount'       => $discount,
            'discount_type'  => $promo->discount_type,
            'discount_value' => $promo->discount_value,
        ]));
    }

    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|string',
            'items.*.quantity' => 'required|integer|min:1|max:100',
            'delivery_address' => 'required|string|max:500',
            'delivery_lat' => 'nullable|numeric|between:-90,90',
            'delivery_lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:1000',
            'tip_amount' => 'nullable|numeric|min:0|max:9999.99',
            'promo_code' => 'nullable|string|max:50',
            'payment_method' => 'nullable|in:cash,card,wallet',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        // Reject the "null island" (0,0) coordinate — almost always a client bug
        // (uninitialised location) and would break delivery routing.
        if ($request->filled('delivery_lat') && $request->filled('delivery_lng')
            && (float) $request->delivery_lat === 0.0 && (float) $request->delivery_lng === 0.0) {
            return response()->json(responseFormatter(
                constant: DEFAULT_400,
                errors: [['message' => 'Invalid delivery location.']]
            ), 422);
        }

        try {
            $order = DB::transaction(function () use ($request) {
                $subtotal = 0;
                $orderItems = [];

                // Merge duplicate product_id entries into a single line item
                $merged = [];
                foreach ($request->items as $item) {
                    $pid = $item['product_id'];
                    if (isset($merged[$pid])) {
                        $merged[$pid]['quantity'] += (int) $item['quantity'];
                    } else {
                        $merged[$pid] = ['product_id' => $pid, 'quantity' => (int) $item['quantity']];
                    }
                }
                $items = array_values($merged);

                foreach ($items as $item) {
                    if ($item['quantity'] > 100) {
                        throw new \RuntimeException('Quantity exceeds maximum (100) per product.');
                    }

                    $product = MartProduct::where('id', $item['product_id'])
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    // Items are always available — no stock gating. Only verify the
                    // product exists and is active.
                    if (!$product) {
                        throw new \RuntimeException('One or more products are unavailable.');
                    }

                    $product->increment('sold_count', $item['quantity']);
                    // Charge the effective price (sale price when set and lower than base).
                    $unitPrice = $product->effective_price;
                    $itemTotal = $unitPrice * $item['quantity'];
                    $subtotal += $itemTotal;

                    $orderItems[] = [
                        'product_id' => $product->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $unitPrice,
                        'total_price' => $itemTotal,
                    ];
                }

                // Cap tip to 30 % of subtotal to prevent price manipulation.
                $maxTip = $subtotal * 0.30;
                $tipAmount = min((float) ($request->tip_amount ?? 0), $maxTip);

                $discountAmount = 0.0;
                $appliedPromoCode = null;

                if ($request->promo_code) {
                    // Re-fetch with lockForUpdate inside the outer transaction so the
                    // isValid() check + increment is fully atomic under a row lock.
                    $promo = MartPromoCode::where('code', strtoupper(trim($request->promo_code)))
                        ->lockForUpdate()
                        ->first();

                    if ($promo && $promo->isValid()) {
                        // Per-user limit check: default 1 use per user per code.
                        $perUserLimit = $promo->per_user_limit ?? 1;
                        $userUsageCount = MartOrder::where('customer_id', $request->user()->id)
                            ->where('promo_code', $promo->code)
                            ->whereNotIn('status', ['cancelled'])
                            ->lockForUpdate()
                            ->count();

                        if ($userUsageCount < $perUserLimit) {
                            $discountAmount = $promo->computeDiscount($subtotal);
                            $appliedPromoCode = $promo->code;
                            $promo->increment('used_count');
                        }
                    }
                }

                // Config-driven delivery fee (mart_delivery_fee), default 0. No tax is charged
                // (GoMart-style: no tax line); tax_amount stays 0 for schema compatibility.
                $deliveryFee = max(0.0, (float) get_cache('mart_delivery_fee'));
                $taxAmount   = 0.0;

                $totalAmount = max(0, $subtotal - $discountAmount + $tipAmount + $deliveryFee);

                // M1: settle wallet payments atomically at order time. Without this a
                // payment_method='wallet' order was created 'unpaid' and never charged —
                // i.e. fulfilled for free. Lock the wallet row, require sufficient balance,
                // debit it, and mark the order paid; insufficient balance rolls the whole
                // transaction back (promo restored) via the RuntimeException handler.
                $paymentMethod = $request->input('payment_method', 'cash');
                $paymentStatus = 'unpaid';
                if ($paymentMethod === 'wallet') {
                    $account = $request->user()->userAccount()->lockForUpdate()->first();
                    if (!$account || (float) $account->wallet_balance < $totalAmount) {
                        throw new \RuntimeException('Insufficient wallet balance');
                    }
                    $account->decrement('wallet_balance', $totalAmount);
                    $paymentStatus = 'paid';
                }

                $order = MartOrder::create([
                    'ref_id' => 'VM-' . strtoupper(Str::random(8)),
                    'customer_id' => $request->user()->id,
                    'status' => 'pending',
                    'total_amount' => $totalAmount,
                    'tip_amount' => $tipAmount,
                    'discount_amount' => $discountAmount,
                    'delivery_fee' => $deliveryFee,
                    'tax_amount' => $taxAmount,
                    'promo_code' => $appliedPromoCode,
                    'payment_status' => $paymentStatus,
                    'payment_method' => $paymentMethod,
                    'delivery_address' => $request->delivery_address,
                    'delivery_lat' => $request->delivery_lat,
                    'delivery_lng' => $request->delivery_lng,
                    'notes' => $request->notes,
                ]);

                foreach ($orderItems as $item) {
                    MartOrderItem::create(array_merge($item, ['order_id' => $order->id]));
                }

                return $order->load('items.product');
            });

            // Notify available drivers of new mart order
            $this->notifyDriversNewOrder($order);

        } catch (\RuntimeException $e) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: [['message' => $e->getMessage()]]), 400);
        }

        return response()->json(responseFormatter(DEFAULT_200, $order));
    }

    public function orderList(Request $request): JsonResponse
    {
        $orders = MartOrder::where('customer_id', $request->user()->id)
            ->with('items.product')
            ->orderByDesc('created_at')
            ->paginate(min($request->input('limit', 20), 100));

        return response()->json(responseFormatter(DEFAULT_200, $orders));
    }

    public function orderDetails(Request $request, string $id): JsonResponse
    {
        $order = MartOrder::where('id', $id)
            ->where('customer_id', $request->user()->id)
            ->with(['items.product', 'driver'])
            ->first();

        if (!$order) {
            return response()->json(responseFormatter(DEFAULT_404), 404);
        }

        // M7: surface a rough delivery ETA once the order is out for delivery. The app
        // already renders `estimated_arrival`; the server just hadn't computed it.
        $order->estimated_arrival = $this->computeEstimatedArrival($order);

        return response()->json(responseFormatter(DEFAULT_200, $order));
    }

    /**
     * Straight-line (haversine) ETA from the driver's last reported position to the
     * delivery point, at an assumed ~25 km/h urban average. Returns "~N min" while
     * the order is out for delivery and both coordinates are known, else null.
     */
    private function computeEstimatedArrival(MartOrder $order): ?string
    {
        if ($order->status !== 'picked_up'
            || $order->driver_lat === null || $order->driver_lng === null
            || $order->delivery_lat === null || $order->delivery_lng === null) {
            return null;
        }

        $dLat = (float) $order->driver_lat;
        $dLng = (float) $order->driver_lng;
        $tLat = (float) $order->delivery_lat;
        $tLng = (float) $order->delivery_lng;

        $earthKm = 6371.0;
        $dLatR = deg2rad($tLat - $dLat);
        $dLngR = deg2rad($tLng - $dLng);
        $a = sin($dLatR / 2) ** 2
            + cos(deg2rad($dLat)) * cos(deg2rad($tLat)) * sin($dLngR / 2) ** 2;
        $km = $earthKm * 2 * atan2(sqrt($a), sqrt(1 - $a));

        $minutes = (int) ceil(($km / 25.0) * 60);
        return '~' . max(1, $minutes) . ' min';
    }

    public function cancelOrder(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $result = DB::transaction(function () use ($request, $id) {
            $order = MartOrder::where('id', $id)
                ->where('customer_id', $request->user()->id)
                ->whereIn('status', ['pending', 'accepted'])
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return null;
            }

            $previousDriverId = $order->driver_id;
            $wasPaid = $order->payment_status === 'paid';
            $wasWalletPaid = $wasPaid && $order->payment_method === 'wallet';

            // Items are always available — no stock to restore on cancel.

            if ($order->promo_code) {
                $promo = MartPromoCode::where('code', strtoupper($order->promo_code))
                    ->lockForUpdate()
                    ->first();
                if ($promo && $promo->used_count > 0) {
                    $promo->decrement('used_count');
                }
            }

            // M1: wallet-paid orders are refunded straight back to the wallet here (a local
            // DB write, atomic with the cancellation). Card orders are refunded via Stripe
            // after the transaction commits (see below) so we never hold locks during an
            // external API call.
            $cancelUpdate = [
                'status' => 'cancelled',
                'driver_id' => null,
                'cancellation_reason' => $request->input('reason'),
                'cancelled_by' => 'customer',
                'cancelled_at' => now(),
            ];
            if ($wasWalletPaid) {
                $account = $request->user()->userAccount()->lockForUpdate()->first();
                if ($account) {
                    $account->increment('wallet_balance', (float) $order->total_amount);
                }
                $cancelUpdate['payment_status'] = 'refunded';
            }

            $order->update($cancelUpdate);
            return ['order' => $order, 'driver_id' => $previousDriverId, 'card_refund' => $wasPaid && !$wasWalletPaid];
        });

        if (!$result) {
            return response()->json(responseFormatter(DEFAULT_404), 404);
        }

        // Issue a refund for already-paid orders. The Stripe call happens after the
        // transaction commits so we never hold DB locks during an external request.
        // Failures never block cancellation — the order is flagged 'refund_pending'
        // for an operator/cron to retry.
        if (!empty($result['card_refund'])) {
            $newPaymentStatus = $this->refundOrderPayment($result['order']);
            try {
                $result['order']->update(['payment_status' => $newPaymentStatus]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Mart refund status update failed: ' . $e->getMessage());
            }
        }

        try {
            broadcast(new \App\Events\MartOrderStatusUpdatedEvent(
                $result['order']->id,
                'cancelled',
                $result['order']->customer_id,
            ))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Mart status broadcast failed: '.$e->getMessage());
        }

        // Notify driver if the order was already accepted before cancellation
        if ($result['driver_id']) {
            try {
                $driver = User::find($result['driver_id']);
                if ($driver && $driver->fcm_token) {
                    sendDeviceNotification(
                        fcm_token: $driver->fcm_token,
                        title: 'Order Cancelled',
                        description: "Mart order #{$result['order']->ref_id} was cancelled by the customer.",
                        status: 'cancelled',
                        type: 'mart',
                        notification_type: 'mart',
                        action: 'mart_order_cancelled',
                        user_id: $driver->id,
                    );
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Mart driver cancel notify failed: ' . $e->getMessage());
            }
        }

        return response()->json(responseFormatter(DEFAULT_200));
    }

    public function reviewOrder(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $order = MartOrder::where('id', $id)
            ->where('customer_id', $request->user()->id)
            ->where('status', 'delivered')
            ->whereNotNull('driver_id')
            ->first();

        if (!$order) {
            return response()->json(responseFormatter(
                constant: DEFAULT_404,
                errors: [['message' => 'Order not found or not eligible for review.']]
            ), 404);
        }

        if (MartReview::where('order_id', $order->id)->exists()) {
            return response()->json(responseFormatter(
                constant: DEFAULT_400,
                errors: [['message' => 'This order has already been reviewed.']]
            ), 400);
        }

        $review = MartReview::create([
            'order_id'    => $order->id,
            'customer_id' => $order->customer_id,
            'driver_id'   => $order->driver_id,
            'rating'      => (int) $request->rating,
            'comment'     => $request->comment,
        ]);

        return response()->json(responseFormatter(DEFAULT_200, $review));
    }

    // refundOrderPayment() now lives in the shared RefundsMartOrders trait so the
    // customer, driver, and admin cancel paths all issue the same Stripe refund.

    private function notifyDriversNewOrder(MartOrder $order): void
    {
        try {
            $drivers = User::whereHas('driverDetails')
                ->where('is_active', true)
                ->whereNotNull('fcm_token')
                ->where('fcm_token', '!=', '')
                ->limit(50)
                ->pluck('fcm_token')
                ->filter()
                ->values();

            foreach ($drivers as $token) {
                sendDeviceNotification(
                    fcm_token: $token,
                    title: 'New Mart Order',
                    description: 'A new mart delivery order is available near you.',
                    status: 'pending',
                    type: 'mart',
                    notification_type: 'mart',
                    action: 'new_mart_order',
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Mart FCM notify failed: ' . $e->getMessage());
        }
    }
}
