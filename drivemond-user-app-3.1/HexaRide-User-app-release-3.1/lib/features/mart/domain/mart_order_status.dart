/// Single source of truth for VitoMart order-status presentation logic, mirroring
/// the backend `MartOrder::STATUS_TRANSITIONS` (pending → accepted → picked_up →
/// delivered; `cancelled` is terminal). Pure and unit-testable — extracted from
/// `mart_order_tracking_screen` as the first, safe step of moving that screen's
/// inline logic off the widget (the risky Timer/connectivity state stays put for
/// a later, device-verified pass).

const List<String> kMartOrderSteps = ['pending', 'accepted', 'picked_up', 'delivered'];

/// Step index for the delivery timeline; `cancelled` → -1; unknown → 0.
int martOrderStepIndex(String status) {
  final int i = kMartOrderSteps.indexOf(status);
  if (i >= 0) return i;
  return status == 'cancelled' ? -1 : 0;
}

/// Terminal statuses no longer change (hide live tracking, stop polling).
bool isMartOrderTerminal(String status) => status == 'delivered' || status == 'cancelled';

/// A customer may cancel only before pickup.
bool canCancelMartOrder(String status) => status == 'pending' || status == 'accepted';
