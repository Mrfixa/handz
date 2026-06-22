@extends('adminmodule::layouts.master')

@section('title', translate('mart_categories'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-30 mb-3 gap-3">
                        <h2 class="fs-22 text-capitalize">{{translate('mart_categories')}}</h2>
                        @can('vito_mart_add')
                            <a href="{{route('admin.mart.categories.create')}}" class="btn btn-primary">
                                <i class="bi bi-plus-circle"></i> {{translate('add_category')}}
                            </a>
                        @endcan
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <div class="table-top d-flex flex-wrap gap-10 justify-content-between">
                                <form action="{{url()->current()}}" class="search-form search-form_style-two">
                                    <div class="input-group search-form__input_group">
                                        <span class="search-form__icon"><i class="bi bi-search"></i></span>
                                        <input type="search" name="search" value="{{$search ?? ''}}" class="theme-input-style search-form__input" placeholder="{{translate('search_by_name')}}">
                                    </div>
                                    <button type="submit" class="btn btn-primary">{{translate('search')}}</button>
                                </form>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-borderless align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>{{translate('image')}}</th>
                                            <th>{{translate('sl')}}</th>
                                            <th>{{translate('name')}}</th>
                                            <th>{{translate('products')}}</th>
                                            <th>{{translate('sort_order')}}</th>
                                            <th>{{translate('status')}}</th>
                                            <th class="text-center">{{translate('actions')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($categories as $key => $category)
                                            <tr>
                                                <td>
                                                    @if($category->image)
                                                        <img src="{{ asset('storage/'.$category->image) }}" style="width:42px;height:42px;object-fit:cover;border-radius:6px;border:1px solid #dee2e6">
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td>{{$categories->firstItem() + $key}}</td>
                                                <td>{{$category->name}}</td>
                                                <td>{{$category->products_count}}</td>
                                                <td>{{$category->sort_order}}</td>
                                                <td>
                                                    <span class="badge bg-{{$category->is_active ? 'success' : 'danger'}}">
                                                        {{$category->is_active ? translate('active') : translate('inactive')}}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-2">
                                                        @can('vito_mart_edit')
                                                            <a href="{{route('admin.mart.categories.edit', $category->id)}}" class="btn btn-outline-info btn-sm"><i class="bi bi-pencil"></i></a>
                                                            <form action="{{route('admin.mart.categories.toggle-status', $category->id)}}" method="POST">
                                                                @csrf
                                                                <button type="submit" class="btn btn-outline-warning btn-sm"><i class="bi bi-toggle-{{$category->is_active ? 'on' : 'off'}}"></i></button>
                                                            </form>
                                                        @endcan
                                                        @can('vito_mart_delete')
                                                            <form action="{{route('admin.mart.categories.destroy', $category->id)}}" method="POST" onsubmit="return confirm('{{translate('are_you_sure')}}')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                                            </form>
                                                        @endcan
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted py-4">{{translate('no_categories_found')}}</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            {{$categories->links()}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
