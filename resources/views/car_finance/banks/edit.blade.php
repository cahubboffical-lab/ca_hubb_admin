@extends('layouts.main')

@section('title') {{ __('Edit Finance Bank') }} @endsection

@section('page-title')
    <div class="page-title d-flex justify-content-between align-items-center">
        <h4 class="mb-0">@yield('title')</h4>
        <a class="btn btn-outline-primary" href="{{ route('car-finance-banks.index') }}">{{ __('Back to Banks') }}</a>
    </div>
@endsection

@section('content')
    <section class="section">
        <form action="{{ route('car-finance-banks.update', $carFinanceBank) }}" method="POST" class="form-redirection" data-parsley-validate>
            @csrf
            @method('PUT')
            <div class="card"><div class="card-body mt-3">@include('car_finance.banks.partials.form', ['carFinanceBank' => $carFinanceBank])</div></div>
            <div class="text-end"><button class="btn btn-primary" type="submit">{{ __('Update Bank') }}</button></div>
        </form>
    </section>
@endsection
