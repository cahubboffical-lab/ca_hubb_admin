@extends('layouts.main')

@section('title') {{ __('Edit :section', ['section' => __($config['label'])]) }} @endsection

@section('page-title')
    <div class="page-title"><div class="row align-items-center">
        <div class="col-12 col-md-6"><h4 class="mb-0">@yield('title')</h4></div>
        <div class="col-12 col-md-6 d-flex justify-content-end">
            <a class="btn btn-outline-primary" href="{{ route('startup-ads.index', compact('section')) }}">{{ __('Back') }}</a>
        </div>
    </div></div>
@endsection

@section('content')
    <section class="section">
        <form action="{{ route('startup-ads.update', ['section' => $section, 'startupAdId' => $startupAd->id]) }}"
              method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card"><div class="card-body mt-3">
                @include('startup_ads.partials.form', ['startupAd' => $startupAd])
            </div></div>
            <div class="text-end"><button type="submit" class="btn btn-primary">{{ __('Update Ad') }}</button></div>
        </form>
    </section>
@endsection
