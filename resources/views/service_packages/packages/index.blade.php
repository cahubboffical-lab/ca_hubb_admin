@extends('layouts.main')

@section('title')
    {{ __($config['label'] . ' Packages') }}
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __($config['label'] . ' Packages') }}</h3>
        </div>

        <div class="row grid-margin">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3 d-flex justify-content-end">
                            @can($config['package_permission_prefix'] . '-create')
                                <a href="{{ route('service-packages.packages.create', ['section' => request()->route('section')]) }}" class="btn btn-primary">
                                    {{ __('Add New') }}
                                </a>
                            @endcan
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped" data-toggle="table"
                                   data-url="{{ route('service-packages.packages.show', ['section' => request()->route('section')]) }}"
                                   data-pagination="true" data-search="true" data-side-pagination="server"
                                   data-show-columns="true" data-show-refresh="true" data-sort-name="id"
                                   data-sort-order="DESC">
                                <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                    <th data-field="name" data-sortable="true">{{ __('Name') }}</th>
                                    <th data-field="features_display" data-sortable="false">{{ __('Features') }}</th>
                                    <th data-field="icon_display" data-sortable="false">{{ __('Icon') }}</th>
                                    <th data-field="price" data-sortable="true">{{ __('Price') }}</th>
                                    <th data-field="type_label" data-sortable="true">{{ __('Type') }}</th>
                                    <th data-field="created_at_formatted" data-sortable="true">{{ __('Created At') }}</th>
                                    <th data-field="updated_at_formatted" data-sortable="true">{{ __('Updated At') }}</th>
                                    <th data-field="created_by_name" data-sortable="true">{{ __('Created By') }}</th>
                                    <th data-field="updated_by_name" data-sortable="true">{{ __('Updated By') }}</th>
                                    <th data-field="operate" data-sortable="false">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
