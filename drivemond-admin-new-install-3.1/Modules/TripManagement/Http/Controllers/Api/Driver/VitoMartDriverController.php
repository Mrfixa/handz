<?php

namespace Modules\TripManagement\Http\Controllers\Api\Driver;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\TripManagement\Entities\MartOrder;
use Modules\TripManagement\Entities\MartOrderItem;
use Modules\TripManagement\Entities\MartPromoCode;
use Modules\TripManagement\Http\Controllers\Concerns\ChecksDriverApproval;

class VitoMartDriverController extends Controller
{
    use \Modules\TripManagement\Http\Controllers\Concerns\RefundsMartOrders;

    use ChecksDriverApproval;

    public function pendingOrders(Request $request): JsonResponse
    {
        $driver = $request->user()->driverDetails;
        if (!$this->driverApproved($driver)) {
            return response()->json(responseFormatter(DEFAULT_403), 403);
        }

        $orders = MartOrder::where('status', 'pending')
            ->with('items.product', 'customer')
            ->orderByDesc('created_at')
            ->paginate(min($request->input('limit', 20), 100));

        return response()->json(responseFormatter(DEFAULT_200, $orders));
    }

    public function acceptOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $driver = $request->user()->driverDetails;
        if (!$this->driverApproved($driver)) {
            return response()->json(responseFormatter(DEFAULT_403), 403);
        }

        $order = DB::transaction(function () use ($request) {
            $order = MartOrder::where('id', $request->order_id)
                ->where('status', 'pending')
                ->whereNull('driver_id')
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return null;
            }

            $order->update([
                'driver_id' => $request->user()->id,
                'status' => 'accepted',
            ]);

            return $order->load('items.product', 'customer');
        });

        if (!$order) {
            return response()->json(responseFormatter(TRIP_REQUEST_404), 404);
        }

        // Notify customer their order was accepted
        $this->notifyCustomer(
            $order,
            title: 'Order Accepted',
            description: "Your mart order #{$order->ref_id} has been accepted by a driver.",
            action: 'mart_order_accepted',
        );

        return response()->json(responseFormatter(DEFAULT_200, $order));
    }

    public function updateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'status' => 'required|in:picked_up,delivered,cancelled',
            'reason' => 'nullable|string|max:255',
            'driver_lat' => 'nullable|numeric|between:-90,90',
            'driver_lng' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        // Delivery requires proof (photo or signature) to have been uploaded first.
        // Only enforce when the order is in picked_up (the valid pre-delivery state).
        if ($request->status === 'delivered') {
            $proofCheck = MartOrder::where('id', $request->order_id)
                ->where('driver_id', $request->user()->id)
                ->where('status', 'picked_up')
                ->first();
            if ($proofCheck && !$proofCheck->delivery_photo && !$proofCheck->signature_image) {
                return response()->json(responseFormatter(
                    constant: DEFAULT_400,
                    errors: [['message' => 'Delivery proof (photo or signature) must be uploaded before marking as delivered.']]
                ), 422);
            }
        }

        // Canonical transition map (shared with the admin panel). The driver_id
        // filter below additionally guarantees a driver can only act on an order
        // they own, so they can never cancel a still-pending (unclaimed) order.
        $allowedTransitions = MartOrder::STATUS_TRANSITIONS;

        $order = DB::transaction(function () use ($request, $allowedTransitions) {
            $order = MartOrder::where('id', $request->order_id)
                ->where('driver_id', $request->user()->id)
                ->whereIn('status', $allowedTransitions[$request->status])
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return null;
            }

            $updateData = ['status' => $request->status];

            // Update driver location if provided (for real-time tracking)
            if ($request->filled('driver_lat') && $request->filled('driver_lng')) {
                $updateData['driver_lat'] = $request->driver_lat;
                $updateData['driver_lng'] = $request->driver_lng;
            }

            if ($request->status === 'cancelled') {
                // Restore product stock atomically under the same transaction lock.
                foreach ($order->items as $item) {
                    $lockedProduct = $item->product()->withTrashed()->lockForUpdate()->first();
                    if ($lockedProduct) {
                        $lockedProduct->increment('stock', $item->quantity);
                    }
                }
                // If a promo code was used, decrement its global used_count.
                if ($order->promo_code) {
                    $promo = MartPromoCode::where('code', $order->promo_code)
                        ->lockForUpdate()
                        ->first();
                    if ($promo && $promo->used_count > 0) {
                        $promo->decrement('used_count');
                    }
                }
                // M1: refund wallet-paid orders straight back to the customer's wallet
                // (atomic with the cancellation). Card orders paid via Stripe are refunded
                // through the customer-cancel path; a driver/admin cancel of a *card* order
                // is flagged in AUDIT_TRACKER (M2) for the Stripe-refund follow-up.
                if ($order->payment_status === 'paid' && $order->payment_method === 'wallet') {
                    $customer = \Modules\UserManagement\Entities\User::find($order->customer_id);
                    $account = $customer?->userAccount()->lockForUpdate()->first();
                    if ($account) {
                        $account->increment('wallet_balance', (float) $order->total_amount);
                    }
                    $updateData['payment_status'] = 'refunded';
                } elseif ($order->payment_status === 'paid') {
                    // M2: flag card (Stripe) orders 'refund_pending' inside the txn; the actual
                    // Stripe refund is issued after commit (see below) so we never hold locks
                    // during an external API call.
                    $updateData['payment_status'] = 'refund_pending';
                }
                // Release driver so they can accept another order.
                $updateData['driver_id'] = null;
                $updateData['cancellation_reason'] = $request->input('reason');
                $updateData['cancelled_by'] = 'driver';
                $updateData['cancelled_at'] = now();
            }

            $order->update($updateData);

            return $order->fresh();
        });

        if (!$order) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: [
                ['message' => "Cannot transition to '{$request->status}': order not found or transition not allowed from its current status."],
            ]), 400);
        }

        // M2: issue the Stripe refund for card orders after the transaction commits
        // (the cancel branch flagged them 'refund_pending'); never hold DB locks during
        // the external Stripe call. Wallet orders were already refunded in-txn.
        if ($order->status === 'cancelled' && $order->payment_status === 'refund_pending') {
            try {
                $order->update(['payment_status' => $this->refundOrderPayment($order)]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Mart driver-cancel refund failed: ' . $e->getMessage());
            }
        }

        try {
            broadcast(new \App\Events\MartOrderStatusUpdatedEvent(
                $order->id,
                $request->status,
                $order->customer_id,
            ))->toOthers();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Mart status broadcast failed: '.$e->getMessage());
        }

        $messages = [
            'picked_up' => ['title' => 'Order Picked Up', 'description' => "Your mart order #{$order->ref_id} has been picked up and is on the way.", 'action' => 'mart_order_picked_up'],
            'delivered' => ['title' => 'Order Delivered', 'description' => "Your mart order #{$order->ref_id} has been delivered. Enjoy!", 'action' => 'mart_order_delivered'],
            'cancelled' => ['title' => 'Order Cancelled', 'description' => "Your mart order #{$order->ref_id} was cancelled by the driver. We are finding another driver.", 'action' => 'mart_order_cancelled'],
        ];

        $this->notifyCustomer(
            $order,
            title: $messages[$request->status]['title'],
            description: $messages[$request->status]['description'],
            action: $messages[$request->status]['action'],
        );

        return response()->json(responseFormatter(DEFAULT_200, $order));
    }

    public function uploadDeliveryProof(Request $request): JsonResponse
    {
        // Accept order ID from route param (alias routes) or request body
        $request->merge(['order_id' => $request->route('id') ?? $request->input('order_id')]);

        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            // Accept image file upload for delivery photo (field can be delivery_photo or proof_photo)
            'delivery_photo' => 'nullable|image|max:2048',
            'proof_photo' => 'nullable|image|max:2048',
            // Accept base64 string for canvas-drawn signature (cap at ~75 KB decoded)
            'signature_base64' => 'nullable|string|max:100000',
        ]);

        if ($validator->fails()) {
            return response()->json(responseFormatter(constant: DEFAULT_400, errors: errorProcessor($validator)), 422);
        }

        $order = MartOrder::where('id', $request->order_id)
            ->where('driver_id', $request->user()->id)
            ->whereIn('status', ['accepted', 'picked_up'])
            ->first();

        if (!$order) {
            return response()->json(responseFormatter(DEFAULT_404), 404);
        }

        $updateData = [];

        // Accept either field name for the delivery photo
        $photoFile = $request->file('delivery_photo') ?? $request->file('proof_photo');
        if ($photoFile) {
            $updateData['delivery_photo'] = $photoFile->store('mart/photos', 'public');
        }

        // Signature comes as base64 from the canvas painter
        if ($request->filled('signature_base64')) {
            $base64 = $request->input('signature_base64');
            // Strip data-URI prefix if present (data:image/png;base64,...)
            if (str_contains($base64, ',')) {
                $base64 = substr($base64, strpos($base64, ',') + 1);
            }
            $decoded = base64_decode($base64, strict: true);
            if ($decoded !== false && strlen($decoded) > 0) {
                if (strlen($decoded) > 5242880) {
                    return response()->json(responseFormatter(DEFAULT_400, null, null, 'Signature too large'), 400);
                }
                if (@getimagesizefromstring($decoded) === false) {
                    return response()->json(responseFormatter(DEFAULT_400, null, null, 'Invalid image data'), 400);
                }
                $filename = 'mart/signatures/' . $order->id . '_' . time() . '.png';
                Storage::disk('public')->put($filename, $decoded);
                $updateData['signature_image'] = $filename;
            }
        }

        if (!empty($updateData)) {
            $order->update($updateData);
        }

        return response()->json(responseFormatter(DEFAULT_200, $order));
    }

    public function myOrders(Request $request): JsonResponse
    {
        $orders = MartOrder::where('driver_id', $request->user()->id)
            ->with('items.product', 'customer')
            ->orderByDesc('created_at')
            ->paginate(min($request->input('limit', 20), 100));

        return response()->json(responseFormatter(DEFAULT_200, $orders));
    }

    public function orderDetails(Request $request, string $id): JsonResponse
    {
        if (!Str::isUuid($id)) {
            return response()->json(responseFormatter(DEFAULT_404), 404);
        }

        $order = MartOrder::where('id', $id)
            ->where('driver_id', $request->user()->id)
            ->with(['items.product', 'customer'])
            ->first();

        if (!$order) {
            return response()->json(responseFormatter(DEFAULT_404), 404);
        }

        return response()->json(responseFormatter(DEFAULT_200, $order));
    }

    private function notifyCustomer(MartOrder $order, string $title, string $description, string $action): void
    {
        try {
            $customer = $order->customer;
            if ($customer && $customer->fcm_token) {
                sendDeviceNotification(
                    fcm_token: $customer->fcm_token,
                    title: $title,
                    description: $description,
                    status: $order->status,
                    ride_request_id: $order->id,
                    type: 'mart',
                    notification_type: 'mart',
                    action: $action,
                    user_id: $customer->id,
                );
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Mart customer notify failed: ' . $e->getMessage());
        }
    }
}
