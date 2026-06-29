<?php

namespace Modules\TripManagement\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\TripManagement\Entities\MartOrder;
use Modules\TripManagement\Entities\StripeEvent;

/**
 * Shared Stripe-refund logic for paid VitoMart orders, used by the customer,
 * driver, and admin cancel paths so all three issue the same refund. The Stripe
 * call must happen AFTER the cancellation transaction commits (never hold DB locks
 * during an external request); callers pass the already-cancelled order.
 */
trait RefundsMartOrders
{
    /**
     * Attempt to refund a paid mart order through Stripe.
     * Returns the new payment_status: 'refunded' on success, 'refund_pending'
     * when Stripe is unconfigured/unreachable or no payment record is found.
     * Never throws — cancellation must always succeed.
     */
    protected function refundOrderPayment(MartOrder $order): string
    {
        try {
            $stripeConfig = DB::table('settings')
                ->where('key_name', 'stripe')
                ->where('settings_type', PAYMENT_CONFIG)
                ->first();
            if (!$stripeConfig) {
                return 'refund_pending';
            }
            $stripeValues = $stripeConfig->mode === 'live'
                ? json_decode($stripeConfig->live_values, true)
                : json_decode($stripeConfig->test_values, true);
            $stripeSecret = $stripeValues['api_key'] ?? null;
            if (!$stripeSecret) {
                return 'refund_pending';
            }

            // Locate the succeeded PaymentIntent for this order.
            $event = StripeEvent::where('payment_intent_id', '!=', '')
                ->where('status', 'succeeded')
                ->whereJsonContains('metadata->order_id', $order->id)
                ->latest()
                ->first();

            if (!$event || !$event->payment_intent_id) {
                return 'refund_pending';
            }

            \Stripe\Stripe::setApiKey($stripeSecret);
            \Stripe\Refund::create(
                ['payment_intent' => $event->payment_intent_id],
                ['idempotency_key' => 'refund_order_' . $order->id]
            );

            StripeEvent::create([
                'stripe_event_id'   => 'refund_' . $order->id,
                'type'              => 'charge.refunded',
                'user_id'           => $order->customer_id,
                'amount'            => $order->total_amount,
                'currency'          => 'usd',
                'status'            => 'succeeded',
                'payment_intent_id' => $event->payment_intent_id,
                'metadata'          => ['type' => 'order_refund', 'order_id' => $order->id],
            ]);

            return 'refunded';
        } catch (\Throwable $e) {
            Log::warning('Mart order refund failed: ' . $e->getMessage());
            return 'refund_pending';
        }
    }
}
