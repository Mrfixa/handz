@extends('adminmodule::layouts.master')

@section('title', translate('promo_codes'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-30 mb-3 gap-3">
                        <h2 class="fs-22 text-capitalize">{{translate('promo_codes')}}</h2>
                        @can('vito_mart_add')
                            <a href="{{route('admin.mart.promo.create')}}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> {{translate('add_promo_code')}}
                            </a>
                        @endcan
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{url()->current()}}" class="search-form search-form_style-two">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon"><i class="bi bi-search"></i></span>
                                        <input type="search" name="search" value="{{$search ?? ''}}" class="theme-input-style search-form__input" placeholder="{{translate('search_by_code')}}">
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{translate('search')}}</button>
                                </form>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-borderless align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{translate('sl')}}</th>
                                            <th>{{translate('code')}}</th>
                                            <th>{{translate('discount')}}</th>
                                            <th>{{translate('min_order')}}</th>
                                            <th>{{translate('usage')}}</th>
                                            <th>{{translate('expires_at')}}</th>
                                            <th>{{translate('status')}}</th>
                                            <th class="text-center">{{translate('actions')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($promos as $key => $promo)
                                            <tr>
                                                <td>{{$promos->firstItem() + $key}}</td>
                                                <td><span class="badge bg-light text-dark fs-12">{{$promo->code}}</span></td>
                                                <td>
                                                    @if($promo->discount_type === 'percent')
                                                        {{ rtrim(rtrim(number_format($promo->discount_value, 2), '0'), '.') }}%
                                                    @else
                                                        {{ getCurrencyFormat($promo->discount_value) }}
                                                    @endif
                                                    @if($promo->max_discount)
                                                        <small class="text-muted d-block">{{translate('max')}}: {{ getCurrencyFormat($promo->max_discount) }}</small>
                                                    @endif
                                                </td>
                                                <td>{{ getCurrencyFormat($promo->min_order_amount ?? 0) }}</td>
                                                <td>
                                                    {{ $promo->used_count }}{{ $promo->usage_limit ? ' / '.$promo->usage_limit : '' }}
                                                </td>
                                                <td>{{ $promo->expires_at ? date('d M Y', strtotime($promo->expires_at)) : translate('never') }}</td>
                                                <td>
                                                    <span class="badge bg-{{$promo->is_active ? 'success' : 'danger'}}">
                                                        {{$promo->is_active ? translate('active') : translate('inactive')}}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        @can('vito_mart_edit')
                                                            <a href="{{route('admin.mart.promo.edit', $promo->id)}}" class="btn btn-outline-info btn-sm"><i class="bi bi-pencil"></i></a>
                                                            <form action="{{route('admin.mart.promo.toggle-status', $promo->id)}}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-toggle-{{$promo->is_active ? 'on' : 'off'}}"></i></button>
                                                            </form>
                                                        @endcan
                                                        @can('vito_mart_delete')
                                                            <form action="{{route('admin.mart.promo.destroy', $promo->id)}}" method="POST" onsubmit="return confirm('{{translate('are_you_sure')}}')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="text-center text-muted py-4">{{translate('no_promo_codes_found')}}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{$promos->links()}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
