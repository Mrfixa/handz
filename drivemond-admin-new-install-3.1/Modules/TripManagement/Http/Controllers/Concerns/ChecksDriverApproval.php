<?php

namespace Modules\TripManagement\Http\Controllers\Concerns;

/**
 * Shared driver-approval gate for driver-facing acceptance endpoints.
 *
 * The production `driver_details` table gates approval via `is_verified`
 * (older/test fixtures use `is_approved`) — accept either, and always reject
 * suspended drivers. Accessing a column that does not exist on the model
 * returns null, so this stays safe across schema variants.
 */
trait ChecksDriverApproval
{
    protected function driverApproved($driver): bool
    {
        if (!$driver) {
            return false;
        }
        if ($driver->is_suspended ?? false) {
            return false;
        }
        return (bool) ($driver->is_verified ?? false) || (bool) ($driver->is_approved ?? false);
    }
}
