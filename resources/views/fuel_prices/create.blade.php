@extends('layouts.main')

@section('title')
    {{ __('Add Fuel Prices') }}
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
        <form action="{{ route('fuel-prices.store') }}" method="POST" class="create-form"
              data-parsley-validate data-success-function="fuelPriceCreated">
            @csrf
            <div class="card">
                <div class="card-header"><h5 class="mb-0">{{ __('New Price Snapshot') }}</h5></div>
                <div class="card-body mt-3">
                    @include('fuel_prices.partials.form', ['fuelPrice' => $fuelPrice])
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">{{ __('Save Fuel Prices') }}</button>
            </div>
        </form>
    </section>
@endsection

@section('js')
    <script>
        function fuelPriceCreated() {
            window.location.href = @json(route('fuel-prices.index'));
        }
    </script>
@endsection
