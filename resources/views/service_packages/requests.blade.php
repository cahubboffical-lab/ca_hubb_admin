@extends('layouts.main')

@section('title')
    {{ __($config['label'] . ' Requests') }}
@endsection

@section('css')
    <style>
        .service-request-actions .dropdown-toggle {
            min-width: 105px;
            border-radius: 8px;
            font-weight: 600;
        }

        .service-request-actions .dropdown-menu {
            min-width: 210px;
            padding: 0.5rem;
            border: 0;
            border-radius: 10px;
        }

        .service-request-actions .dropdown-item {
            padding: 0.6rem 0.75rem;
            border-radius: 7px;
        }

        .service-request-actions .dropdown-item:hover {
            background: #f3f5f9;
        }

        #service-request-table td:last-child,
        #service-request-table th:last-child {
            min-width: 135px;
            text-align: center;
        }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __($config['label'] . ' Requests') }}</h3>
        </div>

        <div class="row grid-margin">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-pills mb-4" id="request-status-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" type="button" data-status="pending">{{ __('Pending') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" type="button" data-status="in_progress">{{ __('In Process') }}</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" type="button" data-status="completed">{{ __('Completed') }}</button>
                            </li>
                        </ul>

                        <div class="table-responsive">
                            <table id="service-request-table" class="table table-striped" data-toggle="table"
                                   data-url="{{ route('service-requests.table', ['section' => request()->route('section')]) }}"
                                   data-query-params="serviceRequestQueryParams"
                                   data-pagination="true" data-search="true" data-side-pagination="server"
                                   data-show-columns="true" data-show-refresh="true" data-sort-name="id"
                                   data-sort-order="DESC">
                                <thead>
                                <tr>
                                    <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                                    <th data-field="full_name" data-sortable="true">{{ __('Customer') }}</th>
                                    <th data-field="phone_number" data-sortable="true">{{ __('Phone') }}</th>
                                    <th data-field="package_name">{{ __('Package') }}</th>
                                    <th data-field="city_name">{{ __('City') }}</th>
                                    <th data-field="car">{{ __('Car') }}</th>
                                    <th data-field="model_year" data-sortable="true">{{ __('Year') }}</th>
                                    <th data-field="visit_date" data-sortable="true">{{ __('Visit Date') }}</th>
                                    <th data-field="visit_time">{{ __('Visit Time') }}</th>
                                    <th data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                                    <th data-field="operate">{{ __('Action') }}</th>
                                </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('service_packages.partials.request-detail-modal')
@endsection

@section('js')
    @include('service_packages.partials.request-workflow-script')
@endsection
