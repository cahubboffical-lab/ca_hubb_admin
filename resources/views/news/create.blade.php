@extends('layouts.main')
@section('title')
    {{ __('Create News') }}
@endsection

@section('page-title')
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h4>@yield('title')</h4>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="buttons">
            <a class="btn btn-primary" href="{{ route('news.index') }}">< {{ __('Back to News') }}</a>
        </div>
        <div class="row">
            <form action="{{ route('news.store') }}" class="form-redirection" data-parsley-validate method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="card">
                    <div class="card-header">{{ __('Add News') }}</div>
                    <div class="card-body mt-3">
                        @include('news.partials.form', ['news' => null, 'cities' => $cities])
                    </div>
                </div>
                <div class="col-md-12 text-end">
                    <input type="submit" class="btn btn-primary" value="{{ __('Save and Back') }}">
                </div>
            </form>
        </div>
    </section>
@endsection

@section('script')
    @include('news.partials.editor-script')
@endsection
