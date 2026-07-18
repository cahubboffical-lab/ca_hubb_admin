@extends('layouts.main')

@section('title')
    {{ __('Edit Fuel Prices') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row align-items-center">
            <div class="col-12 col-md-6"><h4 class="mb-0">@yield('title')</h4></div>
            <div class="col-12 col-md-6 d-flex justify-content-end">
                <a class="btn btn-outline-primary" href="{{ route('fuel-prices.index') }}">{{ __('Back to Fuel Prices') }}</a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <form action="{{ route('fuel-prices.update', $fuelPrice) }}" method="POST" class="form-redirection" data-parsley-validate>
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('Price Snapshot #:id', ['id' => $fuelPrice->id]) }}</h5></div>
                <div class="card-body mt-3">
                    @include('fuel_prices.partials.form', ['fuelPrice' => $fuelPrice])
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">{{ __('Update Fuel Prices') }}</button>
            </div>
        </form>
    </section>
@endsection
