@extends('adminmodule::layouts.master')

@section('title', translate('vito_mart_dashboard'))

@php
    $statusMeta = [
        'pending' => ['warning', 'bi-hourglass-split'],
        'accepted' => ['info', 'bi-check-circle'],
        'picked_up' => ['primary', 'bi-truck'],
        'delivered' => ['success', 'bi-box-seam'],
        'cancelled' => ['danger', 'bi-x-circle'],
    ];
@endphp

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex flex-wrap justify-content-between align-items-center mt-30 mb-3 gap-3">
                <h2 class="fs-22 text-capitalize">{{translate('vito_mart_dashboard')}}</h2>
                <form action="{{url()->current()}}" method="GET">
                    <select name="data" class="form-control" onchange="this.form.submit()">
                        @foreach(['today','this_week','this_month','this_year','all_time'] as $opt)
                            <option value="{{$opt}}" @selected(($range ?? '') === $opt)>{{ translate($opt) }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            {{-- Headline stats --}}
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width:48px;height:48px"><i class="bi bi-bag-check-fill fs-4"></i></span>
                        <div><div class="fs-24 fw-bold">{{ $totalOrders }}</div><small class="text-muted">{{translate('total_orders')}}</small></div>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width:48px;height:48px"><i class="bi bi-cash-stack fs-4"></i></span>
                        <div><div class="fs-24 fw-bold">{{ getCurrencyFormat($revenue ?? 0) }}</div><small class="text-muted">{{translate('delivered_revenue')}}</small></div>
                    </div></div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100"><div class="card-body d-flex align-items-center gap-3">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-info bg-opacity-10 text-info" style="width:48px;height:48px"><i class="bi bi-shop fs-4"></i></span>
                        <div><div class="fs-24 fw-bold">{{ $activeProducts }}</div><small class="text-muted">{{translate('active_products')}}</small></div>
                    </div></div>
                </div>
            </div>

            <div class="row g-4">
                {{-- Orders by status --}}
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h6 class="mb-0">{{translate('orders_by_status')}}</h6></div>
                        <div class="card-body">
                            @foreach($statusMeta as $status => $meta)
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <span class="d-flex align-items-center gap-2">
                                        <i class="bi {{$meta[1]}} text-{{$meta[0]}}"></i>
                                        {{ translate('order_status_'.$status) }}
                                    </span>
                                    <span class="badge bg-{{$meta[0]}}">{{ $statusCounts[$status] ?? 0 }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Top products --}}
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header"><h6 class="mb-0">{{translate('top_products')}}</h6></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{translate('product')}}</th>
                                            <th class="text-end">{{translate('sold')}}</th>
                                            <th class="text-end">{{translate('revenue')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($topProducts as $tp)
                                            <tr>
                                                <td>{{ $tp->name }}</td>
                                                <td class="text-end">{{ (int) $tp->qty }}</td>
                                                <td class="text-end">{{ getCurrencyFormat($tp->revenue ?? 0) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="text-center text-muted py-3">{{translate('no_data_available')}}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
