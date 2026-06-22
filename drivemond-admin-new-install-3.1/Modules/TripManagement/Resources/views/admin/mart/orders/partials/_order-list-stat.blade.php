@php
    $cards = [
        ['key' => 'all', 'label' => 'total_orders', 'color' => 'primary', 'icon' => 'bi-bag-check-fill'],
        ['key' => 'pending', 'label' => 'order_status_pending', 'color' => 'warning', 'icon' => 'bi-hourglass-split'],
        ['key' => 'accepted', 'label' => 'order_status_accepted', 'color' => 'info', 'icon' => 'bi-check-circle'],
        ['key' => 'picked_up', 'label' => 'order_status_picked_up', 'color' => 'primary', 'icon' => 'bi-truck'],
        ['key' => 'delivered', 'label' => 'order_status_delivered', 'color' => 'success', 'icon' => 'bi-box-seam'],
        ['key' => 'cancelled', 'label' => 'order_status_cancelled', 'color' => 'danger', 'icon' => 'bi-x-circle'],
    ];
@endphp
<div class="row g-3">
    @foreach($cards as $card)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-{{$card['color']}} bg-opacity-10 text-{{$card['color']}}" style="width:42px;height:42px">
                        <i class="bi {{$card['icon']}} fs-5"></i>
                    </span>
                    <div>
                        <div class="fs-20 fw-bold">{{ $orderCounts[$card['key']] ?? 0 }}</div>
                        <small class="text-muted text-capitalize">{{ translate($card['label']) }}</small>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    <div class="col-12 col-md-4 col-xl-2">
        <div class="card h-100 border-success">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width:42px;height:42px">
                    <i class="bi bi-cash-stack fs-5"></i>
                </span>
                <div>
                    <div class="fs-20 fw-bold">{{ getCurrencyFormat($orderCounts['revenue'] ?? 0) }}</div>
                    <small class="text-muted text-capitalize">{{ translate('delivered_revenue') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
