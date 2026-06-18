<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\TripManagement\Entities\TripRequest;

/**
 * Ride acceptance timeout/fallback.
 *
 * Dispatch immediately after broadcasting a new ride to drivers:
 *   RideTimeoutJob::dispatch($tripId)->delay(now()->addSeconds(60));
 *
 * Requires a queue worker and (optionally) Redis for the QUEUE_CONNECTION.
 * With QUEUE_CONNECTION=sync this runs inline and defeats the delay — activate
 * only in environments with a real async queue (Redis/database driver).
 *
 * Fallback timeline:
 *  - 60s: re-broadcast to all online drivers
 *  - 180s: cancel ride + notify user (dispatched as a second delay chain)
 */
class RideTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly string $tripRequestId,
        public readonly int $attemptNumber = 1,
    ) {}

    public function handle(): void
    {
        $trip = TripRequest::find($this->tripRequestId);

        if (!$trip || $trip->current_status !== 'pending' || $trip->driver_id !== null) {
            // Already accepted or cancelled — nothing to do.
            return;
        }

        if ($this->attemptNumber === 1) {
            // 60s elapsed with no acceptance: re-broadcast to all online drivers.
            Log::info('RideTimeoutJob: no driver accepted at 60s, re-broadcasting', [
                'trip_id' => $this->tripRequestId,
            ]);

            // TODO: call your broadcast helper here, e.g.:
            // broadcast(new RideBroadcastEvent($trip));

            // Schedule the final cancellation check at 3 minutes total.
            static::dispatch($this->tripRequestId, 2)->delay(now()->addSeconds(120));
        } else {
            // 180s total elapsed — cancel the ride and notify the customer.
            Log::warning('RideTimeoutJob: ride unaccepted at 3m, cancelling', [
                'trip_id' => $this->tripRequestId,
            ]);

            $trip->current_status = 'cancelled';
            $trip->cancelled_by   = 'system';
            $trip->save();

            // TODO: send push notification to $trip->customer_id here.
        }
    }
}
