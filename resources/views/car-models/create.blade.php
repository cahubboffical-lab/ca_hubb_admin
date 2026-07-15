@extends('layouts.main')

@section('title', __('Create Car Model'))

@section('page-title')
    <div class="page-title"><h4>@yield('title')</h4></div>
@endsection

@section('content')
    <section class="section">
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('car-models.index') }}">< {{ __('Back to Car Models') }}</a>
        </div>
        <form action="{{ route('car-models.store') }}" class="form-redirection" method="POST" data-parsley-validate>
            @csrf
            <div class="card">
                <div class="card-header">{{ __('Add Car Model') }}</div>
                <div class="card-body mt-3">
                    @include('car-models.partials.form', ['carModel' => null])
                </div>
            </div>
            <div class="text-end">
                <button type="submit" class="btn btn-primary">{{ __('Save and Back') }}</button>
            </div>
        </form>
    </section>
@endsection
