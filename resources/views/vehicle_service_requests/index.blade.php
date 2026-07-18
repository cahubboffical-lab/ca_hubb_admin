@extends('layouts.main')

@section('title')
    {{ __($config['label'].' Requests') }}
@endsection

@section('css')
    <style>
        @include('shared._request-table-toolbar-styles')

        #vehicle-service-request-table td:last-child,
        #vehicle-service-request-table th:last-child {
            min-width: 485px;
            text-align: center;
        }

        .vehicle-service-request-actions {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            white-space: nowrap;
        }

        .vehicle-service-request-actions .btn {
            border-radius: 7px;
            font-weight: 600;
        }
    </style>
@endsection

@section('content')
    <div class="content-wrapper">
        <div class="page-header">
            <h3 class="page-title">{{ __($config['label'].' Requests') }}</h3>
        </div>

        <div class="card">
            <div class="card-body">
                <ul class="nav nav-pills request-status-toolbar" id="vehicle-request-status-tabs" role="tablist">
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
                    <table id="vehicle-service-request-table" class="table table-striped" data-toggle="table"
                           data-url="{{ route('vehicle-service-requests.table', ['section' => $section]) }}"
                           data-query-params="vehicleServiceRequestQueryParams"
                           data-pagination="true" data-search="true" data-side-pagination="server"
                           data-show-columns="true" data-show-refresh="true" data-sort-name="id"
                           data-sort-order="DESC">
                        <thead>
                        <tr>
                            <th data-field="id" data-sortable="true">{{ __('ID') }}</th>
                            <th data-field="full_name" data-sortable="true">{{ __('Customer') }}</th>
                            <th data-field="phone_number" data-sortable="true">{{ __('Phone') }}</th>
                            <th data-field="is_filer">{{ __('Filer') }}</th>
                            <th data-field="car">{{ __('Car') }}</th>
                            <th data-field="model_year" data-sortable="true">{{ __('Year') }}</th>
                            <th data-field="car_variant">{{ __('Variant') }}</th>
                            <th data-field="registration_place" data-sortable="true">{{ __('Registration Place') }}</th>
                            <th data-field="created_at" data-sortable="true">{{ __('Created At') }}</th>
                            <th data-field="completed_at" data-sortable="true">{{ __('Completed At') }}</th>
                            <th data-field="operate">{{ __('Actions') }}</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('vehicle_service_requests.partials.detail-modal')
@endsection

@section('js')
    @include('vehicle_service_requests.partials.workflow-script')
@endsection
