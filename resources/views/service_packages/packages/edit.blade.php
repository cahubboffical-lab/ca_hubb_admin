@extends('layouts.main')

@section('title')
    {{ __('Edit ' . $config['label'] . ' Package') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __('Edit ' . $config['label'] . ' Package') }}</h3>
        </div>

        <div class="row grid-margin">
            <div class="col-12">
                <div class="mb-2">
                    <a class="btn btn-primary" href="{{ route('service-packages.packages.index', ['section' => request()->route('section')]) }}">{{ __('Back') }}</a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('service-packages.packages.update', ['section' => request()->route('section'), 'servicePackage' => $servicePackage->id]) }}" method="POST" enctype="multipart/form-data" class="create-form" data-parsley-validate>
                            @csrf
                            @method('PUT')
                            @include('service_packages.packages._form', ['servicePackage' => $servicePackage, 'config' => $config])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @include('service_packages.packages._form-script')
@endsection
