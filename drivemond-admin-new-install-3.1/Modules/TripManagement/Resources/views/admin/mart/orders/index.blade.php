@extends('adminmodule::layouts.master')

@section('title', translate('mart_orders'))

@php
    $statuses = ['all', 'pending', 'accepted', 'picked_up', 'delivered', 'cancelled'];
    $statusColors = [
        'pending' => 'warning', 'accepted' => 'info', 'picked_up' => 'primary',
        'delivered' => 'success', 'cancelled' => 'danger',
    ];
@endphp

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-30 mb-3 gap-3">
                        <h2 class="fs-22 text-capitalize">{{translate('mart_orders')}}</h2>
                        @can('vito_mart_export')
                            <div class="dropdown">
                                <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="bi bi-download"></i> {{translate('export')}}
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{route('admin.mart.orders.export', ['file' => 'excel', 'type' => $type])}}">{{translate('excel')}}</a></li>
                                    <li><a class="dropdown-item" href="{{route('admin.mart.orders.export', ['file' => 'csv', 'type' => $type])}}">{{translate('csv')}}</a></li>
                                </ul>
                            </div>
                        @endcan
                    </div>

                    @if($orderCounts)
                        <div id="order-stats" class="mb-3">
                            @include('tripmanagement::admin.mart.orders.partials._order-list-stat', ['orderCounts' => $orderCounts, 'type' => $type])
                        </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            {{-- Status tabs --}}
                            <ul class="nav nav-pills flex-wrap gap-2 mb-3">
                                @foreach($statuses as $st)
                                    <li class="nav-item">
                                        <a class="nav-link {{ $type === $st ? 'active' : '' }}"
                                           href="{{route('admin.mart.orders.index', $st)}}">
                                            {{ translate($st === 'all' ? 'all' : 'order_status_'.$st) }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{url()->current()}}" class="search-form search-form_style-two">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon"><i class="bi bi-search"></i></span>
                                        <input type="search" name="search" value="{{$search ?? ''}}" class="theme-input-style search-form__input" placeholder="{{translate('search_by_order_id_or_customer')}}">
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{translate('search')}}</button>
                                </form>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-borderless align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{translate('sl')}}</th>
                                            <th>{{translate('order_id')}}</th>
                                            <th>{{translate('date')}}</th>
                                            <th>{{translate('customer')}}</th>
                                            <th>{{translate('driver')}}</th>
                                            <th>{{translate('items')}}</th>
                                            <th>{{translate('total')}}</th>
                                            <th>{{translate('payment')}}</th>
                                            <th>{{translate('status')}}</th>
                                            <th class="text-center">{{translate('action')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($orders as $key => $order)
                                            <tr>
                                                <td>{{$orders->firstItem() + $key}}</td>
                                                <td>
                                                    <a href="{{route('admin.mart.orders.show', $order->id)}}" class="text-primary">#{{$order->ref_id}}</a>
                                                </td>
                                                <td>{{date('d M Y', strtotime($order->created_at))}}<br><small class="text-muted">{{date('h:i a', strtotime($order->created_at))}}</small></td>
                                                <td>{{ trim(($order->customer->first_name ?? '').' '.($order->customer->last_name ?? '')) ?: translate('not_available') }}</td>
                                                <td>{{ $order->driver ? trim(($order->driver->first_name ?? '').' '.($order->driver->last_name ?? '')) : translate('no_driver_assigned') }}</td>
                                                <td>{{ $order->items->count() }}</td>
                                                <td>{{ getCurrencyFormat($order->total_amount ?? 0) }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $order->payment_status === 'paid' ? 'success' : 'secondary' }}">
                                                        {{ translate($order->payment_status ?? 'unpaid') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-{{ $statusColors[$order->status] ?? 'secondary' }}">
                                                        {{ translate('order_status_'.$order->status) }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{route('admin.mart.orders.show', $order->id)}}" class="btn btn-outline-info btn-sm">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted py-4">{{translate('no_orders_found')}}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{$orders->links()}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
