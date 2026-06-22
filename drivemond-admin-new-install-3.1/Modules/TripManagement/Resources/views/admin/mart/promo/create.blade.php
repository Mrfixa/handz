@extends('adminmodule::layouts.master')

@section('title', translate('add_promo_code'))

@section('content')
    <div class="main-content">
        <div class="container-fluid">
            <div class="row g-4">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-30 mb-3 gap-3">
                        <h2 class="fs-22 text-capitalize">{{translate('add_promo_code')}}</h2>
                        <a href="{{route('admin.mart.promo.index')}}" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> {{translate('back')}}</a>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <form action="{{route('admin.mart.promo.store')}}" method="POST">
                                @csrf
                                @include('tripmanagement::admin.mart.promo._form')
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
