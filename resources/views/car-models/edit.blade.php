@extends('layouts.main')

@section('title', __('Edit Car Model'))

@section('page-title')
    <div class="page-title"><h4>@yield('title')</h4></div>
@endsection

@section('content')
    <section class="section">
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('car-models.index') }}">< {{ __('Back to Car Models') }}</a>
        </div>
        <form action="{{ route('car-models.update', $carModel) }}" method="POST" data-parsley-validate>
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">{{ __('Edit Car Model') }}</div>
                <div class="card-body mt-3">
                    @include('car-models.partials.form', ['carModel' => $carModel])
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">{{ __('Save and Back') }}</button>
            </div>
        </form>
    </section>
@endsection
