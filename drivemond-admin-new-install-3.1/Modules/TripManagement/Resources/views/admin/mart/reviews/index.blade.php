@extends('adminmodule::layouts.master')

@section('title', translate('mart_reviews'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-30 mb-3 gap-3">
                        <h2 class="fs-22 text-capitalize">{{translate('mart_reviews')}}</h2>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted">{{translate('average_rating')}}:</span>
                            <span class="badge bg-warning text-dark fs-14"><i class="bi bi-star-fill"></i> {{ $averageRating ?: '0.0' }}</span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <form action="{{url()->current()}}" class="d-flex flex-wrap gap-2 mb-3">
                                <div class="input-group search-form__input_group" style="max-width:320px">
                                    <span class="search-form__icon"><i class="bi bi-search"></i></span>
                                    <input type="search" name="search" value="{{$search ?? ''}}" class="theme-input-style search-form__input" placeholder="{{translate('search_by_order_or_comment')}}">
                                </div>
                                <select name="rating" class="form-control" style="max-width:160px" onchange="this.form.submit()">
                                    <option value="">{{translate('all_ratings')}}</option>
                                    @for($i = 5; $i >= 1; $i--)
                                        <option value="{{$i}}" @selected(($rating ?? '') == $i)>{{$i}} ★</option>
                                    @endfor
                                </select>
                                <button type="submit" class="btn btn-primary">{{translate('search')}}</button>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-borderless align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{translate('sl')}}</th>
                                            <th>{{translate('order_id')}}</th>
                                            <th>{{translate('customer')}}</th>
                                            <th>{{translate('driver')}}</th>
                                            <th>{{translate('rating')}}</th>
                                            <th>{{translate('comment')}}</th>
                                            <th>{{translate('date')}}</th>
                                            @can('vito_mart_delete')<th class="text-center">{{translate('action')}}</th>@endcan
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reviews as $key => $review)
                                            <tr>
                                                <td>{{$reviews->firstItem() + $key}}</td>
                                                <td>
                                                    @if($review->order)
                                                        <a href="{{route('admin.mart.orders.show', $review->order->id)}}" class="text-primary">#{{$review->order->ref_id}}</a>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>{{ trim(($review->customer->first_name ?? '').' '.($review->customer->last_name ?? '')) ?: translate('not_available') }}</td>
                                                <td>{{ $review->driver ? trim(($review->driver->first_name ?? '').' '.($review->driver->last_name ?? '')) : translate('not_available') }}</td>
                                                <td class="text-warning text-nowrap">
                                                    @for($i = 1; $i <= 5; $i++)
                                                        <i class="bi bi-star{{ $i <= $review->rating ? '-fill' : '' }}"></i>
                                                    @endfor
                                                </td>
                                                <td>{{ $review->comment ?: '—' }}</td>
                                                <td>{{ date('d M Y', strtotime($review->created_at)) }}</td>
                                                @can('vito_mart_delete')
                                                    <td class="text-center">
                                                        <form action="{{route('admin.mart.reviews.destroy', $review->id)}}" method="POST" onsubmit="return confirm('{{translate('are_you_sure')}}')">
                                                            @csrf @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                                        </form>
                                                    </td>
                                                @endcan
                                            </tr>
                                        @empty
                                            <tr><td colspan="8" class="text-center text-muted py-4">{{translate('no_reviews_found')}}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{$reviews->links()}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
