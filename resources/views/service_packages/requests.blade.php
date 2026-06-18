@extends('layouts.main')

@section('title')
    {{ __($config['label'] . ' Requests') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __($config['label'] . ' Requests') }}</h3>
        </div>

        <div class="row grid-margin">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h5 class="mb-2">{{ __('Requests page coming soon') }}</h5>
                        <p class="text-muted mb-0">{{ __('This section is ready for future request workflows.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
